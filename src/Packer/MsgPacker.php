<?php

namespace Haozu\DelayQueue\Packer;

/**
 * the packer of MsgPack
 * 
 * @authors xiexinyang (xiexinyang@haozu.com)
 * @date    2018-05-17 15:08:34
 * @version 1.0
 */
class MsgPacker implements PackerInterface
{
    /**
     * pack data
     *
     * @param mixed $data
     *
     * @return string
     */
    public function pack($data)
    {   
        return msgpack_pack($data);
    }

    /**
     * unpack data
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function unpack($data)
    {
        return msgpack_unpack($data);
    }
}
