<?php
namespace Haozu\DelayQueue\Tests;

use Haozu\DelayQueue\Demo\MyDelayQueueDemo;

/**
 * 测试基类
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-18 14:00:12
 * @version $Id$
 */

class TestCase extends \PHPUnit_Framework_TestCase 
{

    /**
     *
     * 备注 测试方法未实现时 标记该方法:
     *  void markTestSkipped()                   标记当前的测试被跳过，用“S”标记
     *  void markTestSkipped(string $message)    标记当前的测试被跳过，用“S”标记，并且输出一段示消息
     *  void markTestIncomplete                  标记当前的测试不完全，用“I”标记
     *  void markTestIncomplete(string $message) 标记当前的测试不完全，用“I”标记，并且输出一段提示消息
     *  自动生成测试phpunit-skelgen generate-test [--bootstrap="..."] class [class-source] [test-class] [test-source]
     */ 
    protected function setUp(){
        //初始化
        MyDelayQueueDemo::instance()->initialize();;

    }


    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

}
