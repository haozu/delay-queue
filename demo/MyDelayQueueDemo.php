<?php
namespace Haozu\DelayQueue\Demo;

use Haozu\DelayQueue\DelayQueue;
use Haozu\DelayQueue\Start;
use Haozu\DelayQueue\RedisProxy;

/**
 * MyDelayQueue 在自己系统中可包装后的参考例子 可根据业务形态自行调整
 * 修改下面设定的redis前缀以及redis对象即可
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 15:08:34
 * @version 1.0
 */
class MyDelayQueueDemo
{	

	/**
     * 初始化标识
     */
    private static $initialize = false;

    /**
     * @var instance
     */
    private  static $instance;

    /**
     * @return Haozu\DelayQueue\Demo\MyDelayQueueDemo
     */
    public static function instance()
    {
        if( 
            isset(self::$instance) 
            && self::$instance instanceof MyDelayQueueDemo
        ){
            return self::$instance;
        }
        return self::$instance = new MyDelayQueueDemo();
    }

    /**
     * 初始化redis连接等 
     * 在php5中闭包设置调用使用static方法则会认为闭包是一个静态的方法,导致抛出异常
     * php7中本方法包装可以直接设定成静态方法,不需要实例化后调用
     */
	public function initialize()
    {
		if(self::$initialize!==false){
			return true;
		}
		Start::instance()->initialize(function(){
			$redis = \Gj_Lib_Cache_RedisClient::getObject();
			return new RedisProxy($redis);
		});
		//设定统一前缀 默认delayqueue:
		RedisProxy::prefix('delayqueue:');
		self::$initialize = true;
	}

	/**
     * 创建Job任务并将其保存到延迟队列中
     * 
     * @example MyDelayQueue::enqueue('order',Job\Order\UpdateMoney::class,20,10,['order_id'=>20847]);
     * 
     * @param  string  $topic   一组相同类型Job的集合（队列）。供消费者来订阅。
     * @param  string  $jobName job任务的类名，是延迟队列里的基本单元。与具体的Topic关联在一起。
     * @param  integer $delay   job任务延迟时间 传入相对于当前时间的延迟时间即可 例如延迟10分钟执行 传入 10*60
     * @param  integer $ttr     job任务超时时间,保证job至少被消费一次,如果时间内未删除Job方法,则会再次投入ready队列中 
     * @param  array   $args    执行Job任务时传递的可选参数。
     * @param  string  $jobId   任务id可传入或默认生成
     * @return string|boolean 
     */
	public static function enqueue($topic,$jobName,$delay,$ttr,$args = null,$jobId = null)
	{
		static::instance()->initialize();
		return DelayQueue::enqueue($topic,$jobName,$delay,$ttr,$args,$jobId);
	}

	/**
     * 删除job任务,业务上主动性的要删除某个删除任务使用
     * 
     * @param  string  $jobId  任务id
     * @return boolean        
     */
    public static function remove($jobId)
    {	
    	static::instance()->initialize();
        return DelayQueue::enqueue($jobId);
    }


    /**
     * 获取job信息,删除后返回空数组
     * 
     * @param  string  $jobId  任务id
     * @return array        
     */
    public static function getJob($jobId)
    {	
    	static::instance()->initialize();
        return DelayQueue::get($jobId);
    }


    /**
     * worker和timer触发器使用后台常驻触发job任何和执行job任务
     */
	public static function startDelayQueue(){
		static::instance()->initialize();
		Start::instance()->run();
	}


}

