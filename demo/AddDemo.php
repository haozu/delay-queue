<?php

use Haozu\DelayQueue\Demo\MyDelayQueueDemo;
use Haozu\DelayQueue\Demo\Job\JobDemo;

require_once  __DIR__."/../tests/autoload.php";

/**
 * 新增延迟任务demo 可根据业务形态自行调整
 * 包装好的直接调用 
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 15:08:34
 * @version 1.0
 */


MyDelayQueueDemo::enqueue('order',JobDemo::class,3,10,['id'=>8008]);
echo date('Y-m-d H:i:s',time()+3).' 应该触发job任务'.PHP_EOL;