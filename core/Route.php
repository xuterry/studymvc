<?php
namespace core;
/**
/xtw
2018
处理路由规则
*/
class Route
{
    static $routes=[];
    static $pattern=[];
    static $rules=[];
    /**
     * 获取规则配置
     */
    public static function get()
    {
        $config=Config::get('route');
        self::$routes=$config;
       // return $config?$config:false;
    }
    /**
     * 获取参数设置
     */
    public static function set()
    {
        self::get();
        if(isset(self::$routes['__pattern__'])) {
            self::$pattern= self::$routes['__pattern__'];
            unset(self::$routes['__pattern__']);
        }
    }
    /**
     *  设置路由规则
     * @param string $name
     * @param mix $rule
     * @param string $method
     * @param string $preg
     */
    public static function set_rules($name,$rule,$method='',$preg='')
    {
       $param='';
       $is_or=strpos($name,'[')?1:0;
       $name=str_replace(["[","]",":"],[''],$name); 
       $name=strpos($name,'http')!==false?str_replace("//","://",$name):$name;      
        if(!empty($preg)){
            $param=array_keys($preg);
            foreach($preg as $k=>$v)
                $name=str_replace($k,$is_or?'('.$v.')*':'('.$v.')',$name);
        }
        else{
            if(!empty(self::$pattern)){
            foreach(self::$pattern as $key=>$val){
                if(strpos($name,$key)!==false){
                    $param=[$key];
                    $name=str_replace($key,$is_or?'('.$val.')*':'('.$val.')',$name);
                }
            }
            }
        }
        //var_dump($method);
        $name='^'.$name;
        if(is_array($method)&&isset($method['ext']))
            $name.="(.".$method['ext'].')$';
        else
             $name.=$is_or?'':'$';
        $method=empty($method)?'get':(is_array($method)?$method['method']:$method);
        self::$rules[$method][]=[$name,$rule,$param];
    }
    /**
     * 注册路由规则
     */
    public static function reg_route()
    {
        self::set();
        foreach(self::$routes as $name=>$route){
           if(is_numeric($name))
               $name=$route[0];
           if(empty($route))
               continue;
           if(is_string($name)&&strpos($name,'[')===0){
               $name=substr($name, 1, -1);
               if(is_array($route)){
                   foreach($route as $key=>$val){
                       self::set_rules($name.'/'.$key, $val[0],isset($val[1])?$val[1]:'',isset($val[2])?$val[2]:'');
                   }
               }
           }elseif(is_array($route)){
               self::set_rules($name, $route[0],isset($route[1])?$route[1]:'',isset($route[2])?$route[2]:'');
           }else{
               self::set_rules($name, $route);
           }
        }
    }
    /** 
     * 获取处理后的路由表
     * @return array
     */
    public static function  get_route()
    {
        self::reg_route();
        return self::$rules;
    }
}