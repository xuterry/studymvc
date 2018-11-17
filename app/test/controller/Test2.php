<?php
namespace app\test\controller;
use http\Curl;
/**
/xtw
2018
*/
class Test2
{
    function index(){
        dump(Curl::get('http://t.cctvlian.cn/api/detail?exchange=huobi'));exit();
        try{
       dump(posix_getcwd(),posix_getpid());
       $a="dd://ddd";
      dump( var_export($a,1));
    echo 41|8;
      echo 2^2;
        }catch(\Exception $e){
            throw $e;
        }
      // pcntl_fork();
    }
}