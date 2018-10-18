<?php
/**
/xtw
2018
*/
//获取当前路径
$m_time=microtime(1);
$base=__DIR__;
define('PUBLIC_PATH',$base);
define('ROOT_PATH',str_replace('public','',PUBLIC_PATH));
define('OS',strtolower(PHP_OS));
OS=='winnt'?define('DS','\\'):define('DS','/');
define('RUNTIME_PATH',ROOT_PATH.'runtime');
define('TEMP_PATH',RUNTIME_PATH.DS.'temp');
define('CORE_PATH',ROOT_PATH .'core');
define('APP_PATH',ROOT_PATH.'app');
require CORE_PATH.DS.'base.php';
require CORE_PATH.DS.'loader.php';
//include(CORE_PATH.DS.'app.php');
//core\Loader::excep();
core\Loader::auto();
core\App::run();
echo 'runtime '.(microtime(1)-$m_time);