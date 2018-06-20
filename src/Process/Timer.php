<?php
namespace Haozu\DelayQueue\Process;

use Haozu\DelayQueue\Container;

/**
 * Timer
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 14:23:14
 * @version 1.0
 */
class Timer{


	/**
     * 每次对比的元素数量
     */
    protected $contrast;

    /**
     * 空数据时等待时长
     */
    protected $interval;


    /**
     * 设置每次对比的元素数量
     * @param integer $contrast 元素数量
     */
    public function setContrast($contrast)
    {
        $this->contrast = $contrast>0?$contrast:10;
    }

    /**
     * 设置空数据时等待时长
     * @param integer $interval 等待时长
     */
    public function setInterval($interval)
    {
        $this->interval = abs(floatval($interval));
    }


    public function run()
    {
        
        $sleep = $this->interval*1000000;
        while(true) 
        {
            Container::instance()->delayqueue->touchTimer($this->contrast);
            Container::instance()->logger->info('sleeping {interval}.',['interval'=>$this->interval]);
            usleep($sleep);
        }
    }


}