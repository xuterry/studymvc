<?php
namespace core;
/**
/xtw
2018
*/
/**
 * 配置文件类
 * @author xtw
 *
 */
class Config
{
   private static $configs;
   private static $base_file=APP_PATH.DS.'config.php';
   private static $instance;
   public static function init($file)
   {
       $name=md5($file);
       if(!isset(self::$instance[$name])){
       self::$configs=include($file);
       self::$instance[$name]=1;
       //echo $file;
       }
   }
    public static function debug()
    {
        if(self::get('debug'))
            return 1;
    }
    public static function get($name,$file='')
    {
        $file=empty($file)?self::$base_file:$file;
        self::init($file);
        if(strpos($name,".")>0)
            list($name,$type)=explode(".",$name);
        if(array_key_exists($name, self::$configs))
        return isset($type)?self::$configs[$name][$type]:self::$configs[$name];
    }
    public static function set($name,$value)
    {
        self::$configs[$name]=$value;
    }
}