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
            echo $e->getMessage().$e->getLine();
            var_dump($e);//调试
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
        $controller=$module.DS.'controller'.DS.$controller_name.'.php';
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
        $params='';
        if(!empty(self::$params)){
            foreach(self::$params as $k=>$v)
                $params.=$v.',';
            $params=substr($params,0,-1);
        }
        if(is_callable(array($classname,$method))){
            $app=new $classname;
            //输出
          //  var_dump($app->$method($params));
          Response::instance()->send(($app->$method($params)));
        //  call_user_func_array([$app,$method], self::$params);
        }
        else 
            throw new \Exception($classname.'::'.self::$method.' method not exists');
        unset($app,$params);
        }
    }
}