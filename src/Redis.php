<?php

namespace jinyicheng\redis;

use BadFunctionCallException;
use Redis as OriginalRedis;


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
    private function __construct($db_number=1)
    {
        if (!extension_loaded('redis')) {
            throw new BadFunctionCallException('not support: redis');      //判断是否有扩展
        }

        $db_number = (int)$db_number;
        switch (true) {
            case class_exists(\think\Config::class):
                $options = \think\Config::get('cache.redis');
                break;
            case class_exists(\think\Facade\Config::class):
                $options = \think\Facade\Config::get('cache.redis');
                break;
            case class_exists(\Illuminate\Support\Facades\Config::class):
                $options = \Illuminate\Support\Facades\Config::get('cache.redis');
                break;
            default:
                throw new BadFunctionCallException('not support: config');      //判断是否有扩展
        }
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
     * @return OriginalRedis
     */
    public static function db($db_number)
    {
        if (!isset(self::$instance[(int)$db_number])) {
            self::$instance[(int)$db_number] = new self($db_number);
        }
        return self::$instance[(int)$db_number]->redis;
    }

    /**
     * 关闭单例时做清理工作
     */
    public function __destruct()
    {
        $key = $this->hash;
        self::$instance[$key]->redis->close();
        self::$instance[$key] = null;
        unset(self::$instance[$key]);
    }
}