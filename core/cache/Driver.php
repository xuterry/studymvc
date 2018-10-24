<?php
namespace core\cache;
/**
驱动基类
*/
abstract class Driver
{
    protected $handler;
    protected  $options;
    protected $tag;
    
    /**
     * 清除缓存
     * @param string $tag
     */
    abstract public  function clear($tag='');
    
    /**
     * 根据name删除缓存
     * @param string $name
     */
    abstract public function del($name='');
    
    /**
     * 获取缓存
     * @param string $name
     * @param string $default
     */
    abstract public function get($name='',$default=false);
    
    /**
     * 判断是否存在缓存
     */
    abstract public function has($name='');
    /**
     * 设置缓存
     * @param ring $name
     * @param mix $value
     * @param number $expire
     */
    abstract public function set($name,$value,$expire=null);
    
    public function pull($name)
    {
        $result=$this->get($name);
        if($result){
            $this->del($name);
            return $result;
        }
        return false;
    }
   public function tag($name,$keys=null,$overwrite=false)
   {
       if(is_null($name))
           return $this;
       if(is_null($keys)){
           $this->tag=$name;
       }else{
           $key='tag_'.md5($name);
           $keys=is_string($keys)?explode(",",$keys):$keys;
           $keys=array_map([$this,'getPreKey'],$keys);
           $values=$overwrite?$keys:array_unique(array_merge($this->getTag($name),$keys));
          // var_dump($values);exit($key);
           $this->set($key,implode(',',$values),0);
       }
       return $this;
       
   }
   public function getTag($name)
   {
        $tags=$this->get('tag_'.md5($name));
        return $tags?array_filter(explode(",",$tags)):[];
   }
   public function setTag($name)
   {
       $key='tag_'.md5($this->tag);
       if($this->has($key)){
           $values=explode(",",$this->get($key));
           $values[]=$name;
           $this->set($key,implode(",",array_unique($values)),0);
       }else 
           $this->set($key,$name,0);
   }
   public function getPreKey($name)
   {
       return $this->options['prefix'].$name;
   }
    
}