<?php

require_once  __DIR__."/../tests/autoload.php";

use Haozu\DelayQueue\Demo\MyDelayQueueDemo;

/**
 * 后台常驻worker和timer的demo 可根据业务形态自行调整
 * 包装好的直接调用 
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 15:08:34
 * @version 1.0
 */

MyDelayQueueDemo::startDelayQueue();