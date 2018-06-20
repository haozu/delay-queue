<?php

namespace Haozu\DelayQueue\Process;

use Haozu\DelayQueue\Container;
use Haozu\DelayQueue\Exception\SubClassException;
use Haozu\DelayQueue\Exception\ClassNotFoundException;

/**
 * pcntl拓展在实现signal上使用了“延后执行”的机制；
 * 因此使用该功能时，必须先使用语句declare(ticks=1)，否则注册的singal-handel就不会执行了
 * declare的效率是极低的，比较好的做法是去掉ticks，转而使用pcntl_signal_dispatch，在代码循环中自行处理信号. 
 * 参考:http://rango.swoole.com/archives/364
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 14:23:14
 * @version 1.0
 */
class Worker
{

	/**
     * @var array 轮询队列
     */
    protected $topics;

    /**
     * @var bool 是否在下次循环中退出
     */
    protected $shutdown = false;

    /**
     * 子进程id
     */
    private $child = null;

    /**
     * 设置topics
     * @param array $topics job任务集合
     */
    public function setTopics(array $topics)
    {
        $this->topics = $topics;
    }

    /**
     * 设置空数据时等待时长
     * @param integer $interval 等待时长
     */
    public function setInterval($interval)
    {
        $this->interval = abs(intval($interval));
    }

    /**
     * 设置是否堵塞
     * @param boolean $blocking 
     */
    public function setBlocking($blocking)
    {   $this->registerSignalHandlers();
        $this->blocking = (bool)$blocking;
    }

    /**
     * 运行worker
     */
    public function run()
    {
        while(true) {
            //每次处理完任务后触发下信号处理
            pcntl_signal_dispatch();
            if ($this->shutdown) {
                break;
            }
            $data = null;
            try {
            	if ($this->blocking) {
            		$data = Container::instance()->delayqueue->bpop($this->topics,$this->interval);
                    Container::instance()->logger->info(sprintf('blocking with timeout of %d',$this->interval));
            	} else {
            		$data = Container::instance()->delayqueue->pop($this->topics);
            		sleep($this->interval);
                    Container::instance()->logger->info(sprintf('sleeping with timeout of %d',$this->interval));
            	}
                
            } catch (\Exception $exception) {
                Container::instance()->logger->warning(sprintf('polling queue exception: %s', $exception->getMessage()));
                continue;
            }
            if (!$data) {
                // 空轮询
                continue;
            }

            $this->perform($data);
        }
    }

    /**
     * 子进程运行job任务
     */
    protected function perform(array $data)
    {
        $this->child = pcntl_fork();
        if ($this->child< 0) {
            Container::instance()->logger->emergency('Unable to fork child worker', ['job' => $data]);
            return;
        }
        // 子进程
        if ($this->child === 0) {
            $this->validateClassName($data['class']);
            $class = new $data['class']();
            $class->setId($data['id']);
            $class->setArgs($data['args']);
            Container::instance()->logger->info('Start processing Job', ['data' => $data]);
            $class->run();
            Container::instance()->logger->info('Job finished', ['data' => $data]);
            exit(0);
        }
        // 父进程
        $status = null;
        pcntl_wait($status);
        $exitStatus = pcntl_wexitstatus($status);
        if ($exitStatus !== 0) {
            //执行失败
            Container::instance()->logger->warning('Job exited with exit code ' . $exitStatus);
            //失败时删除job任务避免重复的投递到bucket中 一直触发执行
            Container::instance()->delayqueue->remove($data['id']);
        }
        $this->child = null;
    }

    /**
     * 验证子类
     * @param  string $className job任务
     * @return       
     */
    public function validateClassName($className) {
        if (!class_exists($className)) {
            throw new ClassNotFoundException(sprintf('can not find class [%s]', $className));
        }
        if (!is_subClass_of($className,JobHandler::class)) {
            throw new SubClassException(sprintf('[%s] is not subclass of [%s]', $className, JobHandler::class));
        }
    }

    /**
     * 注册信号处理
     */
    protected function registerSignalHandlers()
    {
        if(!function_exists('pcntl_signal')) {
            return;
        }
        //kill 进程id触发
        pcntl_signal(SIGTERM, [$this, 'shutdown']);
        //ctrl+c 触发
        pcntl_signal(SIGINT,  [$this, 'shutdown']);
        //ctrl+\ 触发
        pcntl_signal(SIGQUIT, [$this, 'shutdown']);
        Container::instance()->logger->debug('Registered signals');
    }
  
    /**
     * 退出worker 如果有子进程正在执行会等在子进程结束后退出
     */
    public function shutdown()
    {
        Container::instance()->logger->notice('Shutting down');
        $this->shutdown = true;
    }

}