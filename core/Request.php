<?php
namespace core;

use ArrayAccess;
use IteratorAggregate;

/**
 * /xtw 2018 请求类,arrayAccess,可用数组方式提取参数
 */
class Request implements ArrayAccess, IteratorAggregate
{

    protected $data = [];

    function offsetSet($offset, $value)
    {
        is_null($offset) ? $this->data[] = $value : $this->data[$offset] = $value;
    }

    function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : '';
    }

    function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    function __get($offset)
    {
        return isset($this->data[$offset]) ? $this->filter($this->data[$offset]) : $this->getServer($offset);
    }

    function __set($offset, $value)
    {
        is_null($offset) ? $this->data[] = $value : $this->data[$offset] = $value;
    }

    function __unset($offset)
    {
        unset($this->data[$offset]);
    }

    function __isset($offset) 
    {
        return isset($this->data[$offset]);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this);
    }

    function __construct()
    {
      //  var_dump($_SERVER);
        $this->data['url'] = $_SERVER['REQUEST_URI'];
        // var_dump($this);exit();
        $this->data['ip']=$_SERVER['REMOTE_ADDR'];
        $this->data['serverip'] = $_SERVER['SERVER_ADDR'];
        $this->data['get'] = $_GET;
        $this->data['post'] = $_POST;
        !empty($_FILES)&&$this->data['file']=$_FILES;
        $this->data['domain'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        $this->urlPath = $this->urlPath();
        $this->setParmas();
    }
/**
 * 静态初始实例
 * @return \core\Request
 */
    static function init()
    {
        return new self();
    }
    //同上
    static function instance()
    {
        return new self();
    }
    /**
     * 从server变量获取属性
     */
    protected function getServer($name)
    {
        foreach($_SERVER as $k=>$v)
        {
           if(strpos($k,strtoupper($name))>0)
            return $v;
        }
        return '';
    }
    /**
     * 获取 get参数，过滤
     *
     * @param string $name
     * @return string
     */
    public function get($name = '')
    {
        $name = $this->parseName($name);
        if (! empty($this->data['get'][$name]))
            // return $this;
            return $this->filter($this->data['get'][$name]);
    }

    /**
     * 获取post参数，过滤
     * 
     * @param string $name            
     * @return string
     */
    public function post($name = '')
    {
        $name = $this->parseName($name);
        if (! empty($this->data['post'][$name]))
            return $this->filter($this->data['post'][$name]);
    }
   public function cookie($name='')
   {
       $name = $this->parseName($name);
      if(empty($name))
          return $_COOKIE;
       if (! empty($_COOKIE[$name]))
           return $this->filter($_COOKIE[$name]);
   }
    public function ip()
    {
        return $this->ip;
    }

    public function domain()
    {
        return $this->data['domain'];
    }

    public function url()
    {
        return substr($this->data['url'], 1);
    }

    public function urlPath()
    {
        $parse = parse_url($this->url());
        return ! empty($parse['path']) ? $parse['path'] : '';
    }

    /**
     * 设置参数进params
     */
    protected function setParmas()
    {
        $this->data['params'] = [];
        if (! empty($this->data['get'])) {
            foreach ($this->data['get'] as $k => $v)
                $this->data['params'][$k] = $v;
        }
        if (! empty($this->data['post'])) {
            foreach ($this->data['post'] as $k => $v)
                $this->data['params'][$k] = $v;
        }
    }

    public function params($name)
    {
        $name = $this->parseName($name);
        if (empty($name))
            return $this->data['params'];
        return isset($this->data['params'][$name]) ? $this->data['params'][$name] : '';
    }

    public function param($name)
    {
        $name = $this->parseName($name);
        if (empty($name))
            return $this->data['params'];
        return isset($this->data['params'][$name]) ? $this->data['params'][$name] : '';
    }

    /**
     * 判断是否存在相差参数
     * 
     * @param string $name            
     * @return number|boolean
     */
    function has($name, $method = '')
    {
        $name = $this->parseName($name);
        if (! empty($method))
            return isset($this->data[$method][$name]);
        else
            return isset($this->data[$name]) || isset($this->data['params'][$name]);
    }

    function __call($name, $param)
    {
       if(isset($this->data[$name])){
           $name2=current($param);
           if(isset($this->data[$name][$name2]))
              return   $this->data[$name][$name2];
           else 
             return null;
       }
       return null;
       // throw new \Exception(__CLASS__ . '::' . $name . ' not exsit');
    }

    /**
     * url 请求方法
     * 
     * @return string
     */
    protected function filter($var)
    {
        $pattern = '/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT LIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOT EXISTS|NOTEXISTS|EXISTS|NOT NULL|NOTNULL|NULL|BETWEEN TIME|NOT BETWEEN TIME|NOTBETWEEN TIME|NOTIN|NOT IN|IN)$/i';
        
        if (is_string($var))
            return preg_replace($pattern, '', $var);
        if (is_array($var)) {
            foreach ($var as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k1 => $v1)
                        $var[$k][$k1] = preg_replace($pattern, '', $v1);
                } else
                    $var[$k] = preg_replace($pattern, '', $v);
            }
            return $var;
        }
        return '';
    }

    protected function parseName($name)
    {
        if (strpos($name, "/a") !== false)
            $name = str_replace("/a", "", $name);
        return $name;
    }

    public function method()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        return strtolower($method);
    }
}