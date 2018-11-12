<?php
namespace core;
/**
session类
*/
class Session
{
    protected static $prefix;
    protected static $is_init=null;
    /**
     * 初始
     * @param array $options
     * @throws \Exception
     * @return boolean
     */
    public static function init($options=[])
    {
        $options=array_merge(Config::get('session'),$options);
        self::$prefix=is_null(self::$prefix)?$options['prefix']:self::$prefix;
        if(self::$is_init||session_status()==PHP_SESSION_ACTIVE)
            return true;
        if(self::$is_init===false&&session_status()!=PHP_SESSION_ACTIVE){
            session_start();
            self::$is_init=1;
            return true;
        }
        $start=0;
        if(isset($options['use_trans_sid']))
            ini_set('session.use_trans_sid',$options['use_trans_sid']?1:0);
        
        if(!empty($options['auto_start'])&&session_status()!=PHP_SESSION_ACTIVE){
            ini_set('session.auto_start',0);
            $start=1;
        }
        
        if(isset($options['session_id'])&&isset($_REQUEST[$options['session_id']])){
            session_id($options['session_id']);
        }elseif(!empty($options['id'])){
            session_id($options['id']);
        }
        
        if(isset($options['name']))
            session_name($options['name']);
        
        if(isset($options['domain']))
            ini_set('session.cookie_domain',$options['domain']);
        
       if(isset($options['expire'])){
           ini_set('session.gc_maxlifetime',$options['expire']);
           ini_set('session.cookie_lifetime',$options['expire']);
       }
       
       if(isset($options['secure']))
           ini_set('session.cookie_secure',$options['secure']);
       
       if(isset($options['use_cookies']))
           ini_set('session.use_cookies',$options['use_cookies']);
       
       !isset($options['httponly'])?:ini_set('session.cookie_httponly',$options['httponly']);
       !isset($options['cache_expire'])?:session_cache_expire($options['cache_expire']);
       !isset($options['cahce_limiter'])?:session_cache_limiter($options['cache_limiter']);
       
       if(!empty($options['type'])){
           $class='\\core\\session\\'.ucfirst($options['type']);
           if(!session_set_save_handler(new $class($options)))
               throw new \Exception($class.' session handler err');
       }
       if($start){
           session_start();
          // session_commit();
           self::$is_init=1;
       }else 
           self::$is_init=false;    
    }
    /**
     * 清除session
     * @param unknown $prefix
     */
    public static function clear($prefix=null)
    {
        self::init();
        $prefix=is_null($prefix)?self::$prefix:$prefix;
        if(is_null($prefix)){
            unset($_SESSION);
        }else 
            unset($_SESSION[$prefix]);
    }
    /**
     * 删除
     */
    public static function delete($name,$prefix=null)
    {
        self::init();
        $prefix=is_null($prefix)?self::$prefix:$prefix;
        if(empty($name)&&empty($prefix))
            return false;
        if(empty($name)&&!empty($prefix)){
            unset($_SESSION[$prefix]);
            return true;
        }else{
            if(is_array($name)){
                foreach($name as $v)
                 static::delete($v,$prefix); 
            }
            if(empty($prefix)){
                if(strpos($name,'.')!==false){
                    list($name1,$name2)=explode('.',$name);
                    unset($_SESSION[$name1][$name2]);
                      return true;
                }else{
                    unset($_SESSION[$name]);
                    return true;
                }
            }else{
                if(strpos($name,'.')!==false){
                    list($name1,$name2)=explode('.',$name);
                    unset($_SESSION[$prefix][$name1][$name2]);
                    return true;
                }else{
                    unset($_SESSION[$prefix][$name]);
                    return true;
                }
            }
                         
        }
        return false;
    }
    /**
     * 设置session
     * @param string $name
     * @param string $value
     * @param string $prefix
     */
    public  static function set($name,$value='',$prefix=null)
    {
        self::init();
        $prefix=is_null($prefix)?self::$prefix:$prefix;
        if(strpos($name,'.')!==false){
            list($name1,$name2)=explode(".",$name);
            if($prefix)
                $_SESSION[$prefix][$name1][$name2]=$value;
            else 
                $_SESSION[$name1][$name2]=$value;
        }elseif($prefix)
             $_SESSION[$prefix][$name]=$value;
        else 
            $_SESSION[$name]=$value;
    }
    /**
     * 获取session
     * @param string $name
     * @param string $prefix
     * @return mix
     */
    public static function get($name='',$prefix=null)
    {
        self::init();
        $prefix=is_null($prefix)?self::$prefix:$prefix;
        //var_dump($prefix);
        if(empty($name)&&empty($prefix))
            return $_SESSION;
        if(empty($name))
            return isset($_SESSION[$prefix])?$_SESSION[$prefix]:null;
        if(empty($prefix)&&!empty($name)){
        if(strpos(".",$name)!==false){
            list($name1,$name2)=explode(".",$name);
            return isset($_SESSION[$name1][$name2])?$_SESSION[$name1][$name2]:null;
            ;
        }else 
            return isset($_SESSION[$name])?$_SESSION[$name]:null;
        }else{
                if(strpos(".",$name)!==false){
                    list($name1,$name2)=explode(".",$name);
                    return isset($_SESSION[$prefix][$name1][$name2])?$_SESSION[$prefix][$name1][$name2]:null;
                    ;
                }else
                    return isset($_SESSION[$prefix][$name])?$_SESSION[$prefix][$name]:null;
        }
        return null;
    }
    /**
     * 设置前缀
     * @param string $prefiex
     */
   public static function setPrefix($prefiex='')
   {
       self::$prefix=!empty($prefix)?$prefiex:self::$prefix;
   }
   
   public static function has($name, $prefix = null)
   {
       self::init();
       $prefix = !is_null($prefix) ? $prefix : self::$prefix;
       if (strpos($name, '.')) {
           // 支持数组
           list($name1, $name2) = explode('.', $name);
           return $prefix ? isset($_SESSION[$prefix][$name1][$name2]) : isset($_SESSION[$name1][$name2]);
       } else {
           return $prefix ? isset($_SESSION[$prefix][$name]) : isset($_SESSION[$name]);
       }
   }
   
}