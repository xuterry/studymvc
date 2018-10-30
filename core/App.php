<?php
namespace  core;
/**
/xtw
2018
app加载运行类
*/
class App
{
    static private $module='';//获取模型
    static private $controller;//获取控制器
    static private $method;
    static private $params;
    //初始
   public static  function init()
    {
        //Loader::logFile(request()->ip().request()->url());
        $infos=Module::getname();
        self::$module=$infos['module'];
        self::$controller=$infos['controller'];
        self::$method=$infos['method'];
        self::$params=$infos['params'];
        self::load_controller();
        
    }
    //运行
    public static function run()
    {
        try{
               self::init();
               self::exec();
        }catch(\Exception $e){
            if(self::debug()){
                $errmsg= static::parseException($e);
                Loader::logFile($errmsg);
                echo $errmsg;
              // var_dump($e);//调试
           // print_r($e->xdebug_message);
            }
        }
    }
    //载入模块
    public static function load_module()
    {
        $module_name=self::$module;
        $module=APP_PATH.DS.$module_name;
        if(!is_dir($module))
            throw new \Exception($module_name.' module not exists');
            return $module;
    }
    /**
     * 载入控制器
     * @throws \Exception
     */
    public static function load_controller()
    {
        $module=self::load_module();
        $controller_name=self::$controller;
        $controller=$module.DS.'controller'.DS.ucfirst($controller_name).'.php';
        if(!is_file($controller))
            throw new \Exception($controller_name.' controller not exists');
    }
    /**
     * 判断方法是否存在
     * @param $class
     * @param  $method
     * @throws \Exception
     * @return number
     */
    public static function exists_method($class,$method)
    {
        if(!class_exists($class,1))
            throw new \Exception($class.' class not exists');
        if(method_exists($class, $method))
            return 1;
        else 
            throw new \Exception($class.'::'.$method.' method not exists');
    }
    /**
     * 判断是否开启调试
     * @return number
     */
    public static function debug()
    {
        if(Config::get('debug'))
        return 1;
    }
    /**
     * 执行app，返回结果，输出
     * @throws \Exception
     */
    public static function exec()
    {
        $method=self::$method;
        $classname='app\\'.self::$module.'\\'.'controller\\'.ucfirst(self::$controller);
        if(self::exists_method($classname, $method)){
        $params=Request::init();;
        if(!empty(self::$params)){
            foreach(self::$params as $k=>$v)
                $params[$k]=$v;
        }
        Loader::log('params', $params);
        //exit($params);
        if(is_callable(array($classname,$method))){
            $reflect=new \ReflectionMethod($classname,$method);
            $app=new $classname;
            $getparams=$reflect->getParameters();
            $args=[];
            foreach($getparams as $param){
                $getname=$param->getName();
                if(isset($params[$getname]))
                    $args[$getname]=$params[$getname];
                    elseif($param->isDefaultValueAvailable())
                    $args[$getname]=$param->getDefaultValue();
                    else{ 
                        if($param->getClass())
                             $args[$getname]=$params;
                    }
            }
           if(count($args)==0)
               Response::instance()->send($reflect->invoke($app));
           else{
             Response::instance()->send($reflect->invokeArgs($app, $args));
           }
        //  call_user_func_array([$app,$method], self::$params);
        }
        else 
            throw new \Exception($classname.'::'.self::$method.' method not exists');
        unset($app,$params);
        }
    }
   static function parseException($e)
   {
       $trace=$e->getTrace()[0];
       if(isset($trace['file']))
           $errmsg=$e->getMessage().' in '.$trace['file'].' line '.$trace['line'];
       elseif(isset($trace['args'])){
           $trace=$trace['args'];
           $errmsg=$e->getMessage().' in '.$trace[2].' line '.$trace[3];          
       }else
           $errmsg=$e->getMessage().' in '.$e->getFile().' line '.$e->getLine();
       return $errmsg;
   }
}