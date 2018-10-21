<?php
namespace core;
/**
/xtw
2018
请求类
*/
class Request
{
   protected $data=[];
    function __construct(){
      //  var_dump($_SERVER);
        $this->data['url']=$_SERVER['REQUEST_URI'];
        $this->data['ip']=$_SERVER['SERVER_ADDR'];
        $this->data['get']=$_GET;
        $this->data['post']=$_POST;
        $this->data['domain']=$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
        $this->setParmas();
    }
    public   function get($name)
    {
        if(!empty($this->data['get'][$name]))
           // return $this;
        return $this->data['get'][$name];
    }
    public   function post($name)
    {
        if(!empty($this->data['get'][$name]))
           // return $this;
            return $this->data['get'][$name];
    }
    public  function domain()
    {
        return $this->data['domain'];
    }
    public   function url()
    {
        return substr($this->data['url'],1);
    }
    protected function setParmas()
    {
        if(!empty($this->data['get'])){
            foreach($this->data['get'] as $k=>$v)
                $this->data['params'][$k]=$v;
        }
        if(!empty($this->data['post'])){
            foreach($this->data['post'] as $k=>$v)
                $this->data['params'][$k]=$v;              
        }
    }
    function has($name)
    {
        if(isset($this->data[$name]))
            return 1;        
        if(isset($this->data['params'][$name]))
            return 1;
        return false;
    }
    function __call($name,$param)
    {
        throw new \Exception(__CLASS__.'::'.$name.' not exsit');
    }
    /**
     * url 请求方法
     * @return string
     */
    public function method()
    {
        $method=$_SERVER['REQUEST_METHOD'];
        return strtolower($method);
    }
}