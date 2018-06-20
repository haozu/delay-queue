<?php
namespace Haozu\DelayQueue;

/**
 * ReadyQueue
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 13:06:19
 * @version 1.0
 */
class ReadyQueue
{

	
    /**
     * 添加JobId到队列中
     * 
     * @param  string $queueName 队列名称即主题topic名称
     * @param  string $jobId     任务id
     * @return boolean            
     */
    public function pushReadyQueue($queueName,$jobId)
    {   
        return Container::instance()->redisproxy->rpush($queueName,$jobId);
    }

    /**
     * 从队列中获取JobId 即时性要求不高的
     *
     * @param  array   $queueNames  多个队列名称即多个主题topic名称
     * @return array
     */
    public function popReadyQueue(array $queueNames)
    {   
        foreach($queueNames as $queueName){
            $job = Container::instance()->redisproxy->lpop($queueName);
            if(!empty($job)){
               return $job; 
            }
        }
        return [];
    }
  
    /**
     * 从队列中阻塞获取JobId 即时性要求高的时候使用
     *
     * @param  array   $queueNames  多个队列名称即多个主题topic名称
     * @param  integer $timeout     超时时间
     * @return array
     */
    public function bpopReadyQueue(array $queueNames,$timeout)
    {
        return Container::instance()->redisproxy->blpop($queueNames,$timeout);
    }

   

    

}