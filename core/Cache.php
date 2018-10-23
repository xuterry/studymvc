<?php
namespace core;
/**
缓存类,暂时支持file,memcached,radis三种方式
*/
class Cache
{
    //实例
    public static $instance;
    //驱动
    public static $handler;
    
    public static function init($options=[])
    {
        $options=Config::get('cache');
        if(is_null(self::$handler))
            self::$handler=self::connect($options);
        return self::$handler;
    }
    public static function connect($options,$name=false)
    {
        $type=empty($options['type'])?'File':$options['type'];
        $name=$name===false?md5(serialize($options)):$name;
        $classname='core\\cache\\'.ucfirst($type);
        if(!isset(self::$instance[$name])){
            self::$instance[$name]=new $classname($options);
        }
        return self::$instance[$name];
    }
    public static function clear($tag=null)
    {
        return self::init()->clear($tag);       
    }
    public static function del($name)
    {
        return self::init()->del($name);
    }
    public static function get($name,$default=false)
    {
        return self::init()->get($name,$default);
    }
    public static function set($name,$value,$exprie=0)
    {
        return self::init()->set($name,$value,$exprie);
    }
}