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
       $a=1;
       var_export($a,1);
        }catch(\Exception $e){
            throw $e;
        }
      // pcntl_fork();
    }
}