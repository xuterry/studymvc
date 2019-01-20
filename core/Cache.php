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
     * 自增
     * @param string $name
     * @param number $value
     */
    public static function inc($name,$value=1,$expire=0)
    {
        self::$handler=self::init();
        if(self::$handler->has($name)){
            $getvalue=self::$handler->get($name);
            if(!is_numeric($getvalue))
                throw new \Exception('cache '.$name.' is not numeric');  
            $getvalue+=$value;
            self::set($name,$getvalue);   
            return $getvalue;
        }else{
            self::set($name,$value,$expire);
            return $value;
        }
        return false;
    }
    /**
     * 自减
     * @param string $name
     * @param number $value
     */
    public static function dec($name,$value=1,$expire=0,$zero=false)
    {
        self::$handler=self::init();
        if(self::$handler->has($name)){
            $getvalue=self::$handler->get($name);
            if(!is_numeric($getvalue))
                throw new \Exception('cache '.$name.' is not numeric');
                $getvalue-=$value;
                $zero&&$getvalue<0&&$getvalue=0;
                self::set($name,$getvalue);
                return $getvalue;
        }else{
            $getvalue=-$value;
            $zero&&$getvalue<0&&$getvalue=0;
            self::set($name,$getvalue,$expire);
            return $getvalue;
        }
        return false;
    }
    function __call($func,$args)
    {
        if(is_callable([self::init(),$func])){
            return call_user_func_array([self::init(),$func], $args);
        }else 
            throw new \Exception(__CLASS__."::".$func.' not exists');
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