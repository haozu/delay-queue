<?php
namespace Haozu\DelayQueue;

use Haozu\DelayQueue\Job;
use Haozu\DelayQueue\Exception\InvalidJobException;

/**
 * DelayQueue
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 15:08:34
 * @version 1.0
 */
class DelayQueue
{   

    

    /**
     * 创建Job任务并将其保存到延迟队列中
     * 
     * @example DelayQueue::enqueue('order',Job\Order\UpdateMoney::class,20,10,['order_id'=>20847]);
     * 
     * @param  string  $topic   一组相同类型Job的集合（队列）。供消费者来订阅。
     * @param  string  $jobName job任务的类名，是延迟队列里的基本单元。与具体的Topic关联在一起。
     * @param  integer $delay   job任务延迟时间 传入相对于当前时间的延迟时间即可 例如延迟10分钟执行 传入 10*60
     * @param  integer $ttr     job任务超时时间,保证job至少被消费一次,如果时间内未删除Job方法,则会再次投入ready队列中 
     * @param  array   $args    执行Job任务时传递的可选参数。
     * @param  string  $jobId   任务id可传入或默认生成
     * @return string|boolean 
     * 
     */
    public static function enqueue($topic,$jobName,$delay,$ttr,$args = null,$jobId = null)
    {
        if(empty($topic)||empty($jobName)){
            return false;
        }
        $job = new Job();
        $job['id']    = is_null($jobId)?md5(uniqid(microtime(true),true)):$jobId;
        $job['class'] = $jobName;
        $job['topic'] = $topic;
        $job['args']  = $args;
        $job['delay'] = time()+intval($delay);
        $job['ttr']   = intval($ttr);
        if(!static::push($job)){
            return false;
        }
        return $job['id'];
    }

    /**
     * 将job推入到延迟队列
     * 
     * @param  Haozu\DelayQueue\Job  $job 
     * @return boolean
     */
    private static function push(Job $job)
    {
        if(
               empty($job['id'])
            || empty($job['topic'])
            || empty($job['class'])
            || $job['delay'] < 0
            || $job['ttr']   < 0
        ){
            throw new InvalidJobException("job attribute cannot be empty.");
        }
        $container = Container::instance();
        $result = $container['jobpool']->putJob($job);
        if(!$result){
            return false;
        }
        $result = $container['bucket']->pushBucket($job['id'],$job['delay']);
        //Bucket添加失败 删除元数据
        if(!$result){
            $container['jobpool']->removeJob($job['id']);
            return false;
        }
        return $job['id'];
    }

    /**
     * 删除job任务,元数据和bucket等信息都会删除 
     * 在job任务处理结束后调用,不删除在达到超时时间后，会再次投递到可消费队列中,等待再次消费
     * 
     * @param  string  $jobId  任务id
     * @return boolean        
     */
    public static function remove($jobId)
    {
        $container = Container::instance();
        $container['bucket']->removeBucket($jobId);
        return $container['jobpool']->removeJob($jobId);
    }

    /**
     * 获取job任务信息
     * 
     * @param  string  $jobId  任务id
     * @return array        
     */
    public static function get($jobId)
    {
        return Container::instance()->jobpool->getJob($jobId);
    }

    /**
     * 立即弹出
     * 
     * @param  array   $topics  一组相同类型Job的集合（队列）。
     * @return array          
     */
    public function pop(array $topics)
    {
        $container = Container::instance();
        $readyJob = $container['readyqueue']->popReadyQueue($topics);
        if(empty($readyJob)){
            return [];
        }
        $jobInfo = static::get($readyJob);
        if(empty($jobInfo)){
            return [];
        }
        $container['bucket']->pushBucket($jobInfo['id'],time()+$jobInfo['ttr']);
        return $jobInfo;
    }

    /**
     * 阻塞等待弹出
     * 
     * @param  array   $topics  一组相同类型Job的集合（队列）。
     * @param  integer $timeout 阻塞等待超时时间
     * @return array          
     */
    public function bpop(array $topics,$timeout)
    {
        $container = Container::instance();
        $readyJob = $container['readyqueue']->bpopReadyQueue($topics,$timeout);
        if(empty($readyJob)||count($readyJob)!=2){
            return [];
        }
        $jobInfo = static::get($readyJob[1]);
        if(empty($jobInfo)){
            return [];
        }
        $container['bucket']->pushBucket($jobInfo['id'],time()+$jobInfo['ttr']);
        return $jobInfo;
    }

    /**
     * Timer触发器 扫描bucket, 将符合执行时间的任务放到readyqueue中
     * @param  integer $index 索引位置
     */
    public function touchTimer($index)
    {
        $container  = Container::instance();
        while (true) {
            $bucketJobs = $container['bucket']->getJobsMinDelayTime($index);
            // 集合为空
            if(empty($bucketJobs)){
                return false;
            }
            $isBreak = false;
            foreach ($bucketJobs as $jobId => $time) {
                if($time>time()){
                    $isBreak = true;
                    break;
                }
                $jobInfo = $container['jobpool']->getJob($jobId);
                // job元信息不存在, 从bucket中删除
                if(empty($jobInfo)){
                    $container['bucket']->removeBucket($jobId);
                    continue;
                }
                // 元信息中delay是否小于等于当前时间
                if($jobInfo['delay']>time()){
                    $container['bucket']->removeBucket($jobInfo['id']);
                    $container['bucket']->pushBucket($jobInfo['id'],$jobInfo['delay']);
                    continue;
                }
                $container['logger']->info('Found job {id} on Bucket.',['id'=>$jobId,'time'=>$time]);
                $container['readyqueue']->pushReadyQueue($jobInfo['topic'],$jobInfo['id']);
                $container['logger']->notice('Push job {id} to {topic}',$jobInfo);
                $container['bucket']->removeBucket($jobInfo['id']);
            }
            if($isBreak){
                return false;
            }
        }
    }
 

}
