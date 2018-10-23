<?php
namespace core;
use ArrayAccess;
/**
/xtw
2018
请求类,arrayAccess,可用数组方式提取参数
*/
class Request implements ArrayAccess
{
   protected $data=[];
    function offsetSet($offset, $value)
   {
       is_null($offset)?$this->data[]=$value:$this->data[$offset]=$value;
   }
    function offsetExists($offset)
   {
       return isset($this->data[$offset]);
   }
   function offsetGet($offset)
   {
       return $this->data[$offset];
   }
   function offsetUnset($offset)
   {
       unset($this->data[$offset]);
   }
   function __get($offset)
   {
       return $this->filter($this->data[$offset]);
   }
   function __set($offset,$value)
   {
       is_null($offset)?$this->data[]=$value:$this->data[$offset]=$value;
   }
   function __unset($offset)
   {
       unset($this->data[$offset]);
   }
   function __isset($offset)
   {
       return isset($this->data[$offset]);
   }
    function __construct(){
      //  var_dump($_SERVER);
       $this->data['url']=$_SERVER['REQUEST_URI'];
        //var_dump($this);exit();
        $this->data['ip']=$_SERVER['SERVER_ADDR'];
        $this->data['get']=$_GET;
        $this->data['post']=$_POST;
        $this->data['domain']=$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
        $this->setParmas();
    }
    /**
     * 获取 get参数，过滤
     * @param string $name
     * @return string
     */
    public   function get($name='')
    {
        if(!empty($this->data['get'][$name]))
          // return $this;
        return $this->filter($this->data['get'][$name]);
    }
    /**
     * 获取post参数，过滤
     * @param string $name
     * @return string
     */
    public   function post($name='')
    {
        if(!empty($this->data['post'][$name]))
            return $this->filter($this->data['get'][$name]);
    }
    public  function domain()
    {
        return $this->data['domain'];
    }

    public   function url()
    {
        return substr($this->data['url'],1);
    }
    /**
     * 设置参数进params
     */
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
    /**
     * 判断是否存在相差参数
     * @param string $name
     * @return number|boolean
     */
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
    protected function   filter($var)
    {
        $pattern='/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT LIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOT EXISTS|NOTEXISTS|EXISTS|NOT NULL|NOTNULL|NULL|BETWEEN TIME|NOT BETWEEN TIME|NOTBETWEEN TIME|NOTIN|NOT IN|IN)$/i';
        
        if(is_string($var))
        return preg_replace($pattern,'',$var);
        if(is_array($var)){
            foreach($var as $k=>$v){
                $var[$k]=preg_replace($pattern,'', $var);
            }
            return $var;
        }
        return '';
    }
    public function method()
    {
        $method=$_SERVER['REQUEST_METHOD'];
        return strtolower($method);
    }
}