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
   public static function init()
   {
       self::$configs=include(APP_PATH.DS.'config.php');
   }
    public static function debug()
    {
        if(self::get('debug'))
            return 1;
    }
    public static function get($name)
    {
        self::init();
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