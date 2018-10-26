<?php
namespace core;
/**
缓存类,暂时支持file,sqlite3,memcache,redis四种方式
*/
class Cache
{
    //实例
    public static $instance;
    //驱动
    public static $handler;
    
    /**
     * 初始化配置
     * @param array $options
     * @return handler
     */
    public static function init($options=[])
    {
        $options=array_merge(Config::get('cache'),$options);
        if(is_null(self::$handler))
            self::$handler=self::connect($options);
        return self::$handler;
    }
    /**
     * 连接
     * @param  $options
     * @param string $name
     * @return mx
     */
    public static function connect($options,$name=false)
    {
        $type=empty($options['type'])?'File':$options['type'];
        $name=$name===false?md5(serialize($options)):$name;
        $classname='core\\cache\\'.ucfirst($type);
        if(!isset(self::$instance[$name])){
            self::$instance[$name]=new $classname($options);
        }
        //echo $name;var_dump(self::$instance);
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
    /**
     * 设置标签
     */
    public static function tag($name,$keys=null,$overwrite=false)
    {
        return self::init()->tag($name,$keys,$overwrite);
    }
    /**
     * 清空实例
     */
    public static function clearHandler()
    {
        self::$handler=[];
    }
}