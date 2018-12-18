<?php
/**
/xtw
2018
*/
//获取当前路径
$base=__DIR__;
define('PUBLIC_PATH',$base);
define('ROOT_PATH',str_replace('public','',PUBLIC_PATH));
define('OS',strtolower(PHP_OS));
OS=='winnt'?define('DS','\\'):define('DS','/');
define('RUNTIME_PATH',ROOT_PATH.'runtime');
define('TEMP_PATH',RUNTIME_PATH.DS.'temp');
define('CACHE_PATH',TEMP_PATH);
define('CORE_PATH',ROOT_PATH .'core');
define('APP_PATH',ROOT_PATH.'app');
define('LOG_PATH',ROOT_PATH.'runtime'.DS.'log');
date_default_timezone_set("ETC/GMT-8");
require CORE_PATH.DS.'Loader.php';
core\Loader::logStart();
//include(CORE_PATH.DS.'app.php');
core\Loader::excep();
core\Loader::auto();
core\App::run();
//dump(core\Loader::logGet(''));
