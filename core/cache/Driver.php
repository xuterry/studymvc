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
    abstract public  function clear($tag='');
    abstract public function del($name='');
    abstract public function get($name='',$default=false);
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
           $values=$overwrite?$keys:array_merge($this->getTag($key),$keys);
           $this->set($key,$values,0);
       }
       return $this;
       
   }
   public function getTag($name)
   {
        $tags=$this->get(md5('tag_'.$name));
        return $tags?array_filter(explode(",",$tags)):[];
   }
   public function setTag($name)
   {
       $key=md5('tag_'.$this->tag);
       if($this->has($key)){
           $values=explode(",",$this->get($key));
           $values[]=$name;
           $this->set($key,implode(",",array_unique($values)),0);
       }else 
           $this->set($key,$name,0);
   }
   public function getPreKey($name)
   {
       return $this->option['prefix'].$name;
   }
    
}