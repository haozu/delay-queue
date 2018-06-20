<?php
namespace Haozu\DelayQueue\Process;

use Haozu\DelayQueue\Container;

/**
 * Job处理抽象类
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 14:23:14
 * @version 1.0
 */
abstract class JobHandler
{

    /**
     * @var string Job唯一标识
     */
    protected $id;

    /**
     * @var mixed
     */
    protected $args;

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param mixed $args
     */
    public function setArgs($args)
    {
        $this->args = $args;
    }

    public function run()
    {
        $this->setUp();

        try {
            $this->perform();
            Container::instance()->delayqueue->remove($this->id);
        } catch (\Exception $exception) {
            Container::instance()->logger->warning(sprintf('Job execution failed %s', $exception->getMessage()));
            //失败时删除job任务避免重复的投递到bucket中,一直触发执行报错的job任务,如果需要执行重载次方法删除下面一行代码即可
            Container::instance()->delayqueue->remove($this->id);
        }

        $this->tearDown();
    }

    protected function setUp() { }

    protected function tearDown() { }

    abstract protected function perform();
}