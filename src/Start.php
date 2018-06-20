<?php
namespace Haozu\DelayQueue;

use Haozu\DelayQueue\Packer\MsgPacker;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Haozu\DelayQueue\Process\Worker;
use Haozu\DelayQueue\Process\Timer;

/**
 * Start
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 15:08:34
 * @version 1.0
 */
class Start
{   

    /**
     * 版本
     */
    const VERSION = '1.0';

    /**
     * @var instance
     */
    private  static $instance;

    /**
     * 初始化标识
     */
    private static $initialize = false;

    /**
     * @return Haozu\DelayQueue\Start
     */
    public static function instance()
    {
        if( 
                isset(self::$instance)
            &&  self::$instance instanceof Start
        ){
            return self::$instance;
        }
        return self::$instance = new Start();
    }


    public function getMessage()
    {

        $title = 
<<<EOF
     _      _                                         
  __| | ___| | __ _ _   _  __ _ _   _  ___ _   _  ___ 
 / _` |/ _ \ |/ _` | | | |/ _` | | | |/ _ \ | | |/ _ \
| (_| |  __/ | (_| | |_| | (_| | |_| |  __/ |_| |  __/
 \__,_|\___|_|\__,_|\__, |\__, |\__,_|\___|\__,_|\___|
                    |___/    |_|                      

EOF;
        $message = 
<<<EOF
Usage: 

    -m                module模块名 timer
        --contrast    每次对比的元素数量 默认10
        --interval    无数据处理等待间隔 默认1
        --prefix      redis前缀 默认delayqueue:

    -m                module模块名 worker
        --topic       一组相同类型Job的集合（队列）多个用,隔开 (必须)
        --interval    无数据时等待时长 默认1
        --prefix      redis前缀 默认delayqueue:
        --blocking    非否阻塞(即时性要求高使用blpop否则lpop) 不需要值 

    example 
        -m=timer  --contrast=5  --interval=1   无数据等待1秒 
        -m=worker --topic=order --blocking     阻塞
        -m=worker --topic=order                不阻塞

EOF;
        return compact('title','message');

    }

    /**
     * 初始化
     * 
     * @param  object $callback 返回一个可操作的redis对象的回调
     * @return boolean 
     */
    public function initialize(\Closure $callback){
        if(self::$initialize!==false){
            return self::$initialize;
        }
        $container = Container::instance();
        //注册packer
        $container->set('msgpacker',function(){
            return new MsgPacker();
        });
        //注册jobPool
        $container->set('jobpool',function(){
            return new JobPool();
        });
        //注册Bucket
        $container->set('bucket',function(){
            return new Bucket();
        });
        //注册ReadyQueue
        $container->set('readyqueue',function(){
            return new ReadyQueue();
        });
        //注册DelayQueue
        $container->set('delayqueue',function(){
            return new DelayQueue();
        });
        //注册RedisProxy
        $container->set('redisproxy',$callback);
        //注册logger
        $container->set('logger', function () {
            $logger = new Logger('delay-queue');
            $logger->pushHandler(new StreamHandler('php://stdout',Logger::DEBUG,true,null,true));
            $logger->pushProcessor(new PsrLogMessageProcessor());
            return $logger;
        });   
        //初始化完成
        return self::$initialize = true;
    }

    public function run()
    {
        $data = $this->getMessage();
        //输出个头部信息
        echo $data['title'];
        //检查扩展和执行模式等
        $this->checkEnv();

        //获取传入参数
        $params = $this->getPramas();
        if( 
            !in_array($params['m'],['timer','worker','help'])
            || ($params['m']=='worker' && !isset($params['topic']))
            || $params['m'] == 'help'
        ){
            echo $data['message'].PHP_EOL;exit;
        }

        $container = Container::instance();
        if(isset($params['prefix'])){
            RedisProxy::prefix($params['prefix']);
        }
        $container['logger']->info('Prefix set to {prefix}',['prefix'=>RedisProxy::getPrefix()]);

        //执行相应模块
        switch ($params['m']) {
            case 'timer':
                $timer = new Timer();
                $timer->setContrast(intval($params['contrast']));
                $timer->setInterval($params['interval']);
                $container['logger']->notice('Starting Timer',['pid' => getmypid(),'contrast' => $params['contrast']]);
                $timer->run();
                break;
            case 'worker':
                $topics = explode(',', $params['topic']);
                $worker = new Worker();
                $worker->setTopics($topics);
                $worker->setInterval($params['interval']);
                $worker->setBlocking(isset($params['blocking']));
                $container['logger']->notice('Starting worker',['pid' => getmypid(),'topic' => $params['topic'],]);
                $worker->run();
                break;
        }
    }

    /**
     * 获取参数
     * @return array
     */
    public function getPramas()
    {
        $params = getopt('m:',['contrast:','interval:','prefix:','topic:','interval:','blocking']);
        $params = array_map('trim', $params);
        $default = [
            'm'        =>'help',
            'contrast' =>10,
            'interval' =>1,
        ];
        $params = array_merge($default,$params);
        return $params;
    }

    /**
     * 检查环境
     */
    public function checkEnv(){
        if (php_sapi_name() !== 'cli') {
            echo 'only run in cli'.PHP_EOL;
            exit(1);
        }
        if (!extension_loaded('pcntl')) {
            echo 'extension pcntl not loaded'.PHP_EOL;
            exit(1);
        }
        if (!extension_loaded('msgpack')) {
            echo 'extension msgpack not loaded'.PHP_EOL;
            exit(1);
        }
    }
    
}
