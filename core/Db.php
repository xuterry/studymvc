<?php
namespace core;
use core\db\Connection;
/**
 * /xtw 2018 数据库类 暂时支持mysql
 */
class Db
{
    /**
     * @var Connection[] 数据库连接实例
     */
    private static $instance = [];
 // 实例
    
    /**
     * 获取数据库连接
     */
    public static function connect($config = [], $name = false)
    {
        $config = empty($config) ? Config::get('database') : $config;
        if ($name === false) {
            $name = md5(serialize($config));
        }
        if (! isset(self::$instance[$name]) || $name === true) {
            $type = empty($config['type']) ? 'mysql' : $config['type'];
            $connect = '\\core\\db\\' . strtolower($type) . '\\Connect';
            if ($name === true)
                $name = md5(serialize($config));
            self::$instance[$name] = new $connect($config);
        }
        return self::$instance[$name];
    }

    public static function clear()
    {
        self::$instance = [];
    }
}