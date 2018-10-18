<?php
namespace core;
/**
/xtw
2018
视图基础类
集成think,smarty两个模板引擎
*/

class View
{   
    // 视图实例
    protected static $instance;
    // 模板引擎实例
    public $engine;
    // 模板变量
    protected $data = [];
    // 用于静态赋值的模板变量
    protected static $var = [];
    // 视图输出替换
    protected $replace = [];
    
    function __construct($engine='',$replace=[])
    {
        // 初始化模板引擎
        $this->engine($engine);
        // 基础替换字符串
        $infos=Module::getname();
        $baseReplace = [
            '__ROOT__'   => '/',
            '__URL__'    =>  '/' . $infos['module'] . '/'.$infos['controller'],
            '__STATIC__' =>  '/static',
            '__CSS__'    =>  '/static/css',
            '__JS__'     =>  '/static/js',
        ];
        $this->replace = array_merge($baseReplace, (array) $replace);
    }
    public static function init($engine,$replace)
    {
          if(is_null(self::$instance))
              self::$instance =new self($engine,$replace);
          return self::$instance;
    }
    
    public static function share($name, $value = '')
    {
        if (is_array($name)) {
            self::$var = array_merge(self::$var, $name);
        } else {
            self::$var[$name] = $value;
        }
    }
    
    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }
        return $this;
    }
    public function replace($content, $replace = '')
    {
        if (is_array($content)) {
            $this->replace = array_merge($this->replace, $content);
        } else {
            $this->replace[$content] = $replace;
        }
        return $this;
    }
  /**
   * 模板引擎选择和初始配置
   */  
    public function engine($option=[])
    {
        if(is_string($option)&&!empty($type)){
            $type=$option;
            $configs=[];
        }else{
            $type=!empty($option['type'])?$option['type']:'think';
            $configs=isset($option['config'])?$option['config']:[];
        }
       $class= is_file(CORE_PATH.DS.'view'.DS.$type.'.php')?'\\core\\view\\'.ucfirst($type):'\\core\\view\\'.$type.'\\'.ucfirst($type);
  
     //  require ROOT_PATH.DS.'extend'.DS.'Smarty.class.php';
       $this->engine=new $class($configs);
       return $this;
    }
    
    public function display($tpl='',$var=[],$replace=[],$config=[])
    {
        $vars=array_merge(self::$var,$var,$this->data);
        $replace=array_merge($this->replace,$replace);
        
        ob_start();
        ob_implicit_flush(0);
        
        try {
            $method ='fetch';
            // 允许用户自定义模板的字符串替换
            $replace = array_merge($this->replace, $replace, (array) $this->engine->config('tpl_replace_string'));
            $this->engine->config('tpl_replace_string', $replace);
            $this->engine->$method($tpl, $vars, $config);
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }       
        $content = ob_get_clean();
        return $content;
    }
    
}