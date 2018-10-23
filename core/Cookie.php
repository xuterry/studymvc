<?php
namespace core;
/**
cookie类
*/
class Cookie
{
    protected static $config=  [
        'prefix'    => '', // cookie 名称前缀
        'expire'    => 0, // cookie 保存时间
        'path'      => '/', // cookie 保存路径
        'domain'    => '', // cookie 有效域名
        'secure'    => false, //  cookie 启用安全传输
        'httponly'  => false, // httponly 设置
        'setcookie' => true, // 是否使用 setcookie
    ];
    protected static $is_init=0;
    /**
     * 初始化cookie
     * @param array $option
     */
    public static function init($option=[])
    {
        $config=empty($option)?Config::get('cookie'):[];
        self::$config=array_merge(self::$config,array_change_key_case($config));
        !empty(self::$config['httponly'])?ini_set("session.cookie_httponly",1):'';
        self::$is_init=1;
    }
    /**
     * 设置cookie
     * @param string $name
     * @param mix $value
     * @param array $option
     */
    public static function set($name,$value,$option=[])
    {
        //检查初始
        self::$is_init||self::init();
        if (!is_null($option)) {
            if (is_numeric($option)) {
                $option = ['expire' => $option];
            } elseif (is_string($option)) {
                parse_str($option, $option);
            }
        }
        self::$config=array_merge(self::$config,$option);
        $name=self::$config['prefix'].$name;
        if(is_array($value)){
            array_walk_recursive($value,'self::format','encode');
            $value='array:'.json_encode($value);
        }
        $expire = !empty(self::$config['expire']) ? $_SERVER['REQUEST_TIME'] + intval(self::$config['expire']) :  0;
        if (self::$config['setcookie']) {
            setcookie(
                $name, $value, $expire, self::$config['path'], self::$config['domain'],
                self::$config['secure'], self::$config['httponly']
                );
        }
        $_COOKIE[$name] = $value;
    }
/**
 * 获取cookie
 * @param string $name
 * @param string $prefix
 * @return NULL|mix
 */
    public static function get($name,$prefix=null)
    {
        self::$is_init||self::init();
        $prefix=!is_null($prefix)?$prefix:self::$config['prefix'];
        $key=$prefix.$name;
        if(empty($name)){
            if($prefix){
                $values=[];
                foreach($_COOKIE as $k=>$v){
                    if(strpos($k,$prefix)===0)
                    $values[$k]=$v;
                }
            }else 
                $values=$_COOKIE;
            foreach($values as $k=>$v){
                if(strpos($v,'array:')===0){
                    $v = json_decode(substr($v, 6), true);
                    array_walk_recursive($v, 'self::format', 'decode');
                    $values[$k]=$v;
                }
            }
        }elseif(isset($_COOKIE[$key])){
            $values=$_COOKIE[$key];
            if(strpos($values,'array:')===0){
                $values = json_decode(substr($values, 6), true);
                array_walk_recursive($values, 'self::format', 'decode');
            }
        }else 
            $values=null;
         return $values;               
    }

    /**
     * 根据名称删除对应的cookie
     * @param string $name
     * @param string $prefix
     * @return NULL|mix
     */
    public static function delete($name, $prefix = null)
    {
        self::$is_init||self::init();
        $config = self::$config;
        $prefix = !is_null($prefix) ? $prefix : $config['prefix'];
        $name   = $prefix . $name;
        
        if ($config['setcookie']) {
            setcookie(
                $name, '', $_SERVER['REQUEST_TIME'] - 3600, $config['path'],
                $config['domain'], $config['secure'], $config['httponly']
                );
        }       
        unset($_COOKIE[$name]);
    }
    /**
     * 删除指定前缀的cookie
     * @param string $name
     * @param string $prefix
     */
    public static function clear($prefix = null)
    {
        if (empty($_COOKIE)) {
            return;
        }
        self::$is_init||self::init();
        
        $config = self::$config;
        $prefix = !is_null($prefix) ? $prefix : $config['prefix'];
        
        if ($prefix) {
            foreach ($_COOKIE as $key => $val) {
                if (0 === strpos($key, $prefix)) {
                    if ($config['setcookie']) {
                        setcookie(
                            $key, '', $_SERVER['REQUEST_TIME'] - 3600, $config['path'],
                            $config['domain'], $config['secure'], $config['httponly']
                            );
                    }                  
                    unset($_COOKIE[$key]);
                }
            }
        }
    }
    /**
     * 处理数组数据
     * @param array $val
     * @param string $key
     * @param string $type
     */
    public static function format($val,$key,$type='encode')
    {
        if (!empty($val) && true !== $val) {
            $val = 'decode' == $type ? urldecode($val) : urlencode($val);
        }
    }
}