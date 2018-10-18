<?php
namespace core;

/**
 * /xtw 2018
 */
class Loader
{

    private static $extend_path = ROOT_PATH . 'extend';

    public static function auto()
    {
        spl_autoload_register('self::loadclass', true, true);
        self::auto_extend();
    }

    public static function request()
    {
        $request = Request();
    }

    public function reg($name)
    {
        return spl_autoload_register($name);
    }

    static function loadclass($name)
    {
        // echo $name.'<br>';
       // if(strpos($name,"_")!==false)
       //     list($path,$name)=explode("_",$name);
        $name = str_replace("\\", DS, $name);
        $class_file = ROOT_PATH . $name . '.php';
        if (is_file($class_file)){
            require_once $class_file;
            return true;
        }
        else {
            if (is_file(self::$extend_path . DS . $name . '.php')){
                require_once self::$extend_path . DS . $name . '.php';
                return true;
            }
            else {
                if (is_file(ROOT_PATH . $name . '.class.php')){
                    require_once ROOT_PATH . $name . '.class.php';
                    return true;
                }
                else{   
                    
                    if (is_file(self::$extend_path . DS . $name . '.class.php')){ 
                        require_once self::$extend_path . DS . $name . '.class.php';
                        return true;
                    }
                }
                 //   throw new \Exception($name . ' class not exists');
            }
        }
        return false;
    }

    public static function excep()
    {
        error_reporting(E_ALL);
        set_error_handler([
                                __CLASS__,'apperror'
        ]);
        set_exception_handler([
                                    __CLASS__,'appexception'
        ]);
        register_shutdown_function([
                                        __CLASS__,'appshutdown'
        ]);
    }

    public static function get_module()
    {
        $module = Module::getname();
        return $module;
    }

    public static function apperror($errno, $errstr, $errfile = '', $errline = 0)
    {
        $exception = new \Exception($errstr);
        // var_dump($exception);
        if (error_reporting() && $errno) {
            throw $exception;
        }
        // echo 'type1';
        // var_dump($errstr);
    }

    public static function appexception($e)
    {
        echo 'type2';
        var_dump($e);
        // return $e;
    }

    public static function appshutdown()
    {
        if (! is_null(error_get_last()))
            var_dump(error_get_last());
    }

    protected static function isfatal($type)
    {
        return in_array($type, [
                                    E_ERROR,E_CORE_ERROR,E_COMPILE_ERROR,E_PARSE
        ]);
    }

    // 加载扩展类的函数
    public static function auto_extend()
    {
        $app_function = ROOT_PATH . 'app' . DS . 'common.php';
        require_once $app_function;
    }

    public static function include_path($path)
    {
        is_dir($path) ? $getdir = scandir($path) : $getdir = [];
        if (empty($getdir))
            return;
        foreach ($getdir as $file) {
            if ($file == '.' || $file == '..' || strpos($file, '.php') === false)
                continue;
            require_once $path . $file;
        }
    }
}