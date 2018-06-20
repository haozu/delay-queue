<?php
namespace Haozu\DelayQueue\Demo\Job;

use Haozu\DelayQueue\Process\JobHandler;

/**
 * JobDemo
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 14:23:14
 * @version 1.0
 */
class JobDemo extends JobHandler
{
	
	public function setUp(){
		echo '开始初始化'.PHP_EOL;

	}

	public function tearDown(){
		echo '执行结束'.PHP_EOL;
	}

	public function perform(){
	
		echo '执行中'.PHP_EOL;
		sleep(1);
		throw new \Exception("Error Processing Request", 2);
		file_put_contents('./log', time());
		var_export($this->args);
		var_export($this->id);
		
	}
}