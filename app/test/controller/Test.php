<?php
namespace app\test\controller;
use core\View;
use core\Controller;
/**
/xtw
2018
*/
class Test extends Controller
{
     function index($a=''){
       // echo 'hello kitty'.$a;
         $zip=new \ZipExtension();
      //   $test=new Test2();
   //      $test->index('1');
        $rs= $zip->createFile('ddd','ssss.tst');
         writefile('','test.zip',$rs);
         $view=new View(['type'=>'Think']);
         $view->assign('hello','hello world');
         $view->assign('test',[['v'=>'aaaaa'],['v'=>'ddd']]);
         $paginer=[1,2,3,4,5,6,7,8,9,10];
         $content=$view->display("monitor");
        echo $content;
    }
}