<?php

namespace Haozu\DelayQueue;

/**
 * Bucket
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 15:08:34
 * @version 1.0
 */
class Bucket
{

	
    /**
     * 添加JobId到bucket中
     * 
     * @param  string  $jobId  任务id
     * @param  integer $delay  触发时间
     * @return boolean        
     */
    public function pushBucket($jobId,$delay)
    {	
    	$bucketName = $this->generateBucketName();
    	return Container::instance()->redisproxy->zadd($bucketName,intval($delay),$jobId);
    }

    /**
     * 从bucket中获取延迟时间最小的一批Job任务
     * 
     * @param  integer $index 索引位置
     * @return array
     */
	public function getJobsMinDelayTime($index){
		$bucketName = $this->generateBucketName();
		return Container::instance()->redisproxy->zrange($bucketName,0,$index-1,WITHSCORES);
	}
	
	/**
     * 从bucket中删除JobId
     * 
     * @param  string  $jobId  任务id
     * @return boolean        
     */
	public function  removeBucket($jobId) {
		$bucketName = $this->generateBucketName();
		return Container::instance()->redisproxy->zrem($bucketName,$jobId);
	}

	/**
	 * 获取bucket 
	 * @return string
	 */
	public function generateBucketName(){
        return 'bucket';
    }
    
}

