<?php
namespace core\cache;
use core\cache\Driver;
/**
redis缓存
*/
class Redis extends Driver
{
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => '',
    ];
    function __construct($options=[])
    {
        if(!extension_loaded('redis'))
            throw new \Exception('redis can not load');
        $this->options=array_merge($this->options,$options);
        $this->handler=new \Redis();
        $this->options['persistent']?
            $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select'])
            :$this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
        if(!empty($this->options['password']))
            $this->handler->auth($this->options['password']);
        if($this->options['select'])
            $this->handler->select($this->options['select']);
    }
    /**
     * 清除缓存
     * @param string $tag
     */
     public  function clear($tag='')
     {
         if ($tag) {
             $keys = $this->getPreKey($tag);
             foreach ($keys as $key) {
                 $this->handler->delete($key);
             }
             $this->del('tag_' . md5($tag));
             return true;
         }
         return $this->handler->flushDB();
     }
    
    /**
     * 根据name删除缓存
     * @param string $name
     */
     public function del($name='')
     {
         return $this->handler->delete($this->getPreKey($name));
     }
    
    /**
     * 获取缓存
     * @param string $name
     * @param string $default
     */
    public function get($name='',$default=false)
    {
        $value=$this->handler->get($this->getPreKey($name));
        return (is_null($value)||$value===false)?$default:unserialize($value);
    }
    
    /**
     * 判断是否存在缓存
     */
    public function has($name='')
    {
        return $this->get($name)?true:false;
    }
    /**
     * 设置缓存
     * @param ring $name
     * @param mix $value
     * @param number $expire
     */
     public function set($name,$value,$expire=null)
     {
         $expire=is_null($expire)?$this->options['expire']:$expire;
         $expire=$expire instanceof \DateTime?($expire->getTimestamp()-time()):$expire;
         if($this->tag&&!$this->has($name))
             $tag=1;
         $key=$this->getPreKey($name);
         $value=serialize($value);
         $rs=$expire?$this->handler->setex($key,$expire,$value):$this->handler->set($key,$value);
         isset($tag)&&$this->setTag($key);
         return $rs;
     }
    
}