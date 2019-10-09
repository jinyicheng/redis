<?php

namespace Redis;

use BadFunctionCallException;
use Redis as OriginalRedis;
use think\Config;

class Redis
{
    /**
     * 类单例数组
     *
     * @var array
     */
    private static $instance = [];
    /**
     * redis连接句柄
     *
     * @var object
     */
    private $redis;
    /**
     * hash的key
     *
     * @var int
     */
    private $hash;

    /**
     * 私有化构造函数,防止类外实例化
     *
     * @param int $db_number
     */
    private function __construct($db_number)
    {
        if (!extension_loaded('redis')) {
            throw new BadFunctionCallException('not support: redis');      //判断是否有扩展
        }

        $db_number = (int)$db_number;
        $options = Config::get('cache.redis');
        $this->hash = $db_number;
        $this->redis = new OriginalRedis();
        $func = $options['persistent'] ? 'pconnect' : 'connect';     //长链接
        $this->redis->$func($options['host'], $options['port'], $options['timeout']);
        $this->redis->auth($options['password']);
        $this->redis->select($db_number);
    }

    private function __clone()
    {
    }

    /**
     * 获取类单例
     *
     * @param int $db_number
     * @return object
     */
    public static function db($db_number)
    {
        $hash = (int)$db_number;
        if (!isset(self::$instance[$hash])) {
            self::$instance[$hash] = new self($db_number);
        }
        return self::$instance[$hash]->redis;
    }

    /**
     * 关闭单例时做清理工作
     */
    public function __destruct()
    {
        $key = $this->hash;
        self::$instance[$key]->redis->close();
        self::$instance[$key] = null;
    }
}