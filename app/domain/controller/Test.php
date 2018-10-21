<?php
namespace app\domain\controller;
use core\Controller;
use core\Response;
use core\Request;
/**
/xtw
2018
*/
class Test extends Controller
{
     function index($a=''){
       // echo 'hello kitty'.$a;
       $req=new Request();
      // $req->abc();
         $zip=new \ZipExtension();
      //   $test=new Test2();
   //      $test->index('1');
        $rs= $zip->createFile('ddd','ssss.tst');
         writefile('','test.zip',$rs);
        // $view=new View(['type'=>'Think']);
         $this->assign('hello','hello world');
         $this->assign('test',[['v'=>'aaaaa'],['v'=>'ddd']]);
         $paginer=[1=>'aaa',2,3,4,5,6,7,8,9,10=>'sssss'];
         $re=new Response($paginer,'json');
       //  $re->send();
         return $re;
         //echo '中国';exit();
      // return $this->display("monitor");
        //echo $content;
    }
}