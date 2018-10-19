<?php
namespace app\test\controller;
use core\Controller;
use core\Response;
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
        // $view=new View(['type'=>'Think']);
         $this->assign('hello','hello world');
         $this->assign('test',[['v'=>'aaaaa'],['v'=>'ddd']]);
         $paginer=[1,2,3,4,5,6,7,8,9,10];
         $re=new Response($paginer,'json');
         return Response::instance($paginer,'json');
       //  $re->send();
         return $re;
         //echo '中国';exit();
      // return $this->display("monitor");
        //echo $content;
    }
}