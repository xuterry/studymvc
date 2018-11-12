<?php
/**
 * 验证码类
 *
 * @author xtw
 */
class Captcha
{
    protected $handler = null;

    function __construct($options = [])
    {
        if (is_string($options))
            $type = $options;
        if(empty($type)){
           $type=!empty($options['type'])?$options['type']:'picture';
        }
        
        $class = 'captcha\\' . ucfirst($type);
        
        $this->handler = new $class((array)$options);
    }

    /**
     * 静态实例
     * 
     * @return Captcha
     */
    public static function instance($options = [])
    {
        return new self($options);
    }
   public function __set($name,$value)
   {
       $this->handler->$name=$value;
   }
   function __get($name)
   {
       return $this->handler->$name;
   }
    public function __call($fun, $args)
    {
        return call_user_func_array([
                                        $this->handler,$fun
        ], $args);
    }

    function __destruct()
    {
        unset($this->handler);
    }
}