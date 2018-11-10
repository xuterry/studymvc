<?php
namespace app\test\controller;
/**
/xtw
2018
*/
class Test2
{
    function index(){
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