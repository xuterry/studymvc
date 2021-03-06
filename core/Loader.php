<?php
namespace core;
/**
 * /xtw 2018
 * 自动加载类
 */
class Loader
{
    private static $loginfo=[];
    private static $extend_path = ROOT_PATH . 'extend';

    public static function auto()
    {
        spl_autoload_register('self::loadclass', true, true);
        self::auto_extend();
        self::loadComposer();
    }

    public static function request()
    {
        return Request::instance();
    }

/**
 * 类不存在时自动加载
 * @param string $name
 * @return boolean
 */
    static function loadclass($name)
    {
        // echo $name.'<br>';
       // if(strpos($name,"_")!==false)
       //     list($path,$name)=explode("_",$name);
        $name = str_replace("\\", DS, $name);
        $parsename=self::parseName($name);
        $class_file = ROOT_PATH . $name . '.php';
        if (is_file($class_file)){
            require_once $class_file;
            return true;
        }
        else {
            if (is_file(self::$extend_path . DS . $name . '.php')){
                require_once self::$extend_path . DS . $name . '.php';
                return true;
            }elseif(is_file(self::$extend_path.DS.$parsename.'.php')){
                require_once self::$extend_path . DS . $parsename . '.php';
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
/**
 * 异常处理
 */
    public static function excep()
    {
        if(!\core\App::debug()){
            error_reporting(0);         
            return;
        }
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
/**
 * 获取模块
 * @return boolean|\core\unknown|array|\core\string[]|\core\unknown[]
 */
    public static function get_module()
    {
        $module = Module::getname();
        return $module;
    }

    public static function apperror($errno, $errstr, $errfile = '', $errline = 0)
    {
        $exception = new \Exception($errstr);
        if (error_reporting() && $errno) {
            throw $exception;
        }
    }
 /**
  * 处理异常
  * @param  $e
  * @throws $e
  */
    public static function appexception($e)
    {
        $trace=$e->getTrace();
         throw $e;
    }
  /**
   * 获取记录致使异常
   */
    public static function appshutdown()
    {
        if (! is_null($geterr=error_get_last())){
          //  $exe
            static::logFile(implode(' ',$geterr));
           var_dump($geterr);
           var_dump(debug_backtrace());
        }
    }

    protected static function isfatal($type)
    {
        return in_array($type, [
                                    E_ERROR,E_CORE_ERROR,E_COMPILE_ERROR,E_PARSE
        ]);
    }
         
   public static function loadComposer()
   {
       $vendor = ROOT_PATH   . 'vendor'.DS.'autoload.php';
       if(is_file($vendor))
        require_once $vendor;
       
   }
    // 加载扩展类的函数
    public static function auto_extend()
    {
        $app_function = ROOT_PATH . 'app' . DS . 'common.php';
        $core_function=CORE_PATH.DS.'Functions.php';
        require_once $app_function;
        require_once $core_function;
    }
/**
 * 加载目录下的文件
 * @param unknown $path
 */
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
    /**
     * 开始记录运行信息，设置全局变量self::$loginfo
     */
    public static function logStart()
    {
        if(!\core\App::debug())
            return;
        self::$loginfo['start_time']=microtime(1);
        self::$loginfo['start_mem_peak']=memory_get_peak_usage();
        self::$loginfo['start_mem']=memory_get_usage();
    }
    /**
     * 记录运行信息
     */
    public static function log($name,$var)
    {
        self::$loginfo[$name]=$var;
    }
    /**
     * 获取运行记录
     * @param string $name
     * @return NULL|unknown|mixed
     */
    public static function logGet($name='')
    {
        self::logTrace();
       if(!empty($name)&&!isset(self::$loginfo[$name]))
           return null;
        return !empty($name)?self::$loginfo[$name]:self::$loginfo;
    }
   /**
    * 记录运行信息
    */
    public static function logTrace()
    {
        self::$loginfo['trace']['runtime']=microtime(1)-self::$loginfo['start_time'];
        self::$loginfo['trace']['mem']=get_round((memory_get_usage()-self::$loginfo['start_mem'])/1024);
        self::$loginfo['trace']['mem_peak']=get_round((memory_get_peak_usage()-self::$loginfo['start_mem_peak'])/1024);
        self::$loginfo['trace']['includefile']=get_included_files();
    }
    public static function parseName($name, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
                
                return $ucfirst ? ucfirst($name) : lcfirst($name);
        }
        
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
    /**
     * 异常写入日志文件
     * @param string $str
     */
    public static function logFile($str='')
    {
        $timezone=date_default_timezone_get();
      //  date_default_timezone_set("Etc/GMT-8");
        $filename=LOG_PATH.DS.date("Ym").DS.date('d').'.log';
        self::checkPath($filename);
        $fh=fopen($filename,'a');
        if($fh){
            fwrite($fh,date('Y-m-d H:i:s').' '.$str."\n");
            fclose($fh);
        }
        date_default_timezone_set($timezone);
    }
    /**
     * 检查路径，不存在尝试创建
     * @param string $filename
     * @return boolean
     */
    public static function checkPath($filename='')
    {
           $paths=explode(DS,pathinfo($filename,PATHINFO_DIRNAME)); 
            $Path = '';
            if (sizeof($paths) > 0) {
                foreach ($paths as $v) {
                    $Path .= $v . DS;
                    if (! is_dir($Path))
                        mkdir($Path);
                }
            }
            return true;
    }
}