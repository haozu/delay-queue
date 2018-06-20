<?php

namespace Haozu\DelayQueue;

use Psr\Container\ContainerInterface;
use Haozu\DelayQueue\Exception\ServiceNotFoundException;

/**
 * 服务容器
 */
class Container implements  ContainerInterface, \ArrayAccess
{
    /**
     * @var array 服务定义
     */
    protected  $definitions = [];

    /**
     * @var array 已实例化的服务
     */
    protected  $instances = [];

    /**
     * @var Container 服务
     */
    private  static $container;


    /**
     * @return Haozu\DelayQueue\Container\Container
     */
    public static function instance(){
        if( 
                isset(self::$container)
            &&  self::$container instanceof Container
        ){
            return self::$container;
        }
        return self::$container = new Container();
    }

    /**
     * 添加服务
     *
     * @param string  $id       服务唯一标识
     * @param Closure $callback
     */
    public  function set($id, \Closure $callback)
    {
        if ($id) {
            $this->definitions[$id] = $callback;
        }
    }

    /**
     * 查找服务是否存在
     *
     * @param  string $id 服务唯一标识
     * @return bool
     */
    public function has($id)
    {
        return isset($this->definitions[$id]);
    }

    /**
     * 获取服务
     *
     * @param  string $id 服务唯一标识
     * @return mixed
     * @throws ServiceNotFoundException
     */
    public  function get($id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->definitions[$id])) {
            $message = sprintf('service [%s] not exists', $id);
            throw new ServiceNotFoundException($message);
        }

        /** @var Closure $callback */
        $callback = $this->definitions[$id];
        $callback = $callback->bindTo($this);

        $this->instances[$id] = $callback();

        return $this->instances[$id];
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->definitions[$offset]);
        unset($this->instances[$offset]);
    }
}