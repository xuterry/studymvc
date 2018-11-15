<?php
namespace app\domain\controller;
use core\Controller;
use core\Response;
use core\Request;
use core\Cookie;
use core\Cache;
use core\Session;
use core\Db;
use core\Paginator;
use core\Collection;
use core\Model;
/**
/xtw
2018
*/
class Test extends Controller
{
    function __construct($s=1)
    {
        echo '__construct<br>';
    }
     function index($n=''){
       // echo 'hello kitty'.$a;
      // $req=new Request();
      // $req->abc();
      //cookie
     //var_dump(phpinfo());exit();
      echo $n;
     $a=true;
     echo $a?:3;
     $config=\core\Config::get('db_connect',APP_PATH.DS.'huobi'.DS.'config.php');
   //  dump($config);exit();
    // $conn=Db::connect($config);
       $count=['a'=>'a','dd','a'=>['a','b']];
      echo sizeof($count);
      //var_dump(count($conn));exit();
      $db=new Db();
   $config['table']='btcusdt';
   dump($config);
     $user =new Model($config);
     $list=$user->paginator(20);
     //dump($list);
     foreach($list as $user){
         echo $user['id'].' '.$user['dbkey'].' '.date('Y-m-d H:i:s',substr($user['dbtime'],0,10)).'<br>';
     }
     $this->assign('list',$list);return $this->display("{\$list->render()}");exit();
     $rs=$user->fetchAll();
 //    $rs=$conn->query(' SELECT * FROM `think_data` WHERE `id` > 1 limit 30');
    //$rs=Collection::make($rs);
   // $page=new Paginator($rs,5,1,count($rs));
  //  dump($page);exit('d');
    //  $conn->free();
 //  $conn->abc();
      $getfiled=$conn->getFields('think_data');
      //var_dump($getfiled);
   //  $conn->free();
      $rs2=$conn->name('data')->where('id','>','12')->select();  
     // var_dump($rs2,$conn->getLastSql());
    // $conn->close();
    //  exit();
      Session::init(['type'=>'redis']);
      Session::set('abac',['sss']);
      $_SESSION['AAA']='AAA';
      var_dump(Session::get('abac'));
      var_dump($_SESSION);
      Session::clear();
    //  exit();
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
      //  Cache::$handler=null;
      //  Cache::init();
       // exit();
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
         var_dump($req,Request::init()->params(''));echo $req->urlPath.' '.$req['urlPath'];exit();
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