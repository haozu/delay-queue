<?php
namespace Haozu\DelayQueue;

/**
 * Job
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 14:23:14
 * @version 1.0
 */
class JobPool 
{   


    /**
     * 获取job元数据
     * 
     * @param  string $jobId job id
     * @return mixed        
     */
    public function getJob($jobId)
    {   
        $container = Container::instance();
        $data = $container['redisproxy']->get($jobId);
        if(empty($data)){
            return [];
        }
        return $container['msgpacker']->unpack($data);
    }


    /**
     * 放入job元数据
     * 
     * @param  Haozu\DelayQueue\Job  $job 
     * @return boolean
     */
    public function putJob(Job $job)
    {   
        $container = Container::instance();
        $data = $container['msgpacker']->pack($job->getAttribute());
        return $container['redisproxy']->set($job['id'],$data);
        
    }

    /**
     * 删除job元数据
     * 
     * @param  string $jobId job id
     * @return mixed        
     */
    public function removeJob($jobId)
    {
        return Container::instance()->redisproxy->del($jobId);
    }


}
