<?php
namespace core\cache;
use core\cache\Driver;
/**
memcached缓存
*/
class Memcache extends Driver
{
    protected $options = [
        'host'     => '127.0.0.1',
        'port'     => 11211,
        'expire'   => 0,
        'timeout'  => 0, // 超时时间（单位：毫秒）
        'prefix'   => '',
        'persistent' => true,
    ];
    function __construct($options=[])
    {
        $this->options=array_merge($this->options,$options);
        if(!extension_loaded('memcache'))
            throw new \Exception('cat not load memcache');
        $this->handler=new \Memcache();
        $this->options['timeout']?
        $this->handler->addserver($this->options['host'],$this->options['port'],$this->options['persistent'],1,$this->options['timeout'])
        : $this->handler->addserver($this->options['host'],$this->options['port'],$this->options['persistent'],1);
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
         return $this->handler->flush();
     }
    
    /**
     * 根据name删除缓存
     * @param string $name
     */
    public function del($name='',$ttl=false)
    {
        $key=$this->getPreKey($name);
        $ttl===false?$this->handler->delete($key):$this->handler->delete($key,$ttl);
    }
    
    /**
     * 获取缓存
     * @param string $name
     * @param string $default
     */
   public function get($name='',$default=false)
   {
       $rs=$this->handler->get($this->getPreKey($name));
       return $rs?$rs:$default;
   }
    
    /**
     * 判断是否存在缓存
     */
   public function has($name='')
   {
       $key=$this->getPreKey($name);
       return $this->get($key)?true:false;
   }
    /**
     * 设置缓存
     * @param ring $name
     * @param mix $value
     * @param number $expire
     */
   public function set($name,$value,$expire=null)
   {
       $expire = is_null($expire) ? $this->options['expire'] : $expire;
       if ($expire instanceof \DateTime) {
           $expire = $expire->getTimestamp() - time();
       }
       if ($this->tag && ! $this->has($name))
           $tag = 1;
       $key=$this->getPreKey($name);
       if($this->handler->set($key,$value,0,$expire)){
           isset($tag)&&$this->setTag($key);
           return true;
       }
       return false;
   }
    
}