<?php

namespace Haozu\DelayQueue\Exception;

use Exception;
use \Psr\Container\NotFoundExceptionInterface;

class ServiceNotFoundException extends Exception implements NotFoundExceptionInterface
{

}