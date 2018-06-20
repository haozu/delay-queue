<?php

namespace Haozu\DelayQueue\Packer;

/**
 * the interface of packer
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 15:08:34
 * @version 1.0
 */
interface PackerInterface
{
    /**
     * pack data
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function pack($data);

    /**
     * unpack data
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function unpack($data);
}
