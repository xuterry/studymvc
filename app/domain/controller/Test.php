<?php
namespace app\domain\controller;
use core\Controller;
use core\Response;
use core\Request;
use core\Cookie;
use core\Cache;
/**
/xtw
2018
*/
class Test extends Controller
{
     function index($a=''){
       // echo 'hello kitty'.$a;
      // $req=new Request();
      // $req->abc();
      //cookie
    // var_dump(phpinfo());exit();
      Cookie::set('abc',['aaaaaa','sssss','中国']);
      Cookie::set('ddddd','dddd');
      Cookie::clear();
      Cookie::delete('abc');
      var_dump(Cookie::get(''));
         Cache::init(['data_compress'=>1,'type'=>'redis']);
         Cache::tag('cba',['abc']);
         Cache::set('abc',['sssssssssssss']);
         Cache::set('abcds','ddddd');
         //Cache::del('abc');
        var_dump( Cache::get('abc'));
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
         $req=new Request();
         foreach($req as $v)
             echo $v;
      //  $req['url']='aaa'
      //   !0&&var_dump($req['ip']);exit();
         \core\Loader::log('re',$re);
         \core\Loader::log('re',$req);
       //  $re->send();
      //   return $re;
         //echo '中国';exit();
        $this->config(['cache_id'=>'abcd','display_cache'=>1]);
       // Cache::clear();
       return $this->display("monitor");
        //echo $content;
    }
}