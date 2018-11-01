<?php
namespace app\domain\controller;
use core\Controller;

/**
/xtw
2018
*/
class Test2 extends Controller
{
    function index($a=''){
        echo 'hello kitty'.$a;
      //   $zip=new ZipExtension();
       // $rs= $zip->createFile('ddd','ssss.tst');
       //  writefile('','test.zip',$rs);
       return $this->fetch('test/monitor');
        ;
    }
}