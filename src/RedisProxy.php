<?php
namespace Haozu\DelayQueue;

/**
 * Redis
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-18 14:00:12
 * @version 1.0
 */
class RedisProxy
{
	/**
	 * Redis namespace
	 * @var string
	 */
	private static $defaultNamespace = 'delayqueue:';

	/**
	 * Redis client
	 * @param object $client 
	 */
    public function __construct($client)
	{
		$this->driver = $client;
	}

	/**
	 * Set Redis namespace (prefix) default: delayqueue
	 * @param string $namespace
	 */
	public static function prefix($namespace)
	{
	    if (substr($namespace, -1) !== ':' && $namespace != '') {
	        $namespace .= ':';
	    }
	    self::$defaultNamespace = $namespace;
	}

	public static function getPrefix()
	{
	    return self::$defaultNamespace;
	}

	/**
	 * Magic method to handle all function requests and prefix key based
	 *
	 * @param  string $name The name of the method called.
	 * @param  array  $args Array of supplied arguments to the method.
	 * @return mixed  Result 
	 */
	public function __call($name, $args)
	{
		if (is_array($args[0])) {
			foreach ($args[0] as $i => $v) {
				$args[0][$i] = self::$defaultNamespace . $v;
			}
		}
		else {
			$args[0] = self::$defaultNamespace . $args[0];
		}
		return call_user_func_array([$this->driver,$name],$args);
		
	}

	

}
