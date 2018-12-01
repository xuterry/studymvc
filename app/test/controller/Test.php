<?php
namespace app\test\controller;
use core\Controller;
use core\Response;
use core\Request;
use http\Exchange;
/**
/xtw
2018
*/
class Test extends Controller
{
     function index($a=''){
         return $this->fetch('');
         $symbol='btcusdt';
         $period='1day';
         $size=200;
         $ex=new Exchange(['type'=>'Binance']);
         $ex=new Exchange(['type'=>'Coineal']);
        //  $ex=new Exchange();
         dump($ex->get_symbol_open());exit(); 
         //dump($ex->place_order('188','0.00000662','TRXBTC','sell-limit'));exit();
        //   dump($ex->cancel_order('75633174','trxbtc'));exit();
            //dump($ex->get_order_state('75633174','TRXBTC'));exit();
        //  dump($ex->get_orders_matchresults($symbol));exit();
        //  dump($ex->get_market_depth($symbol));exit(); 
          dump($ex->get_market_detail($symbol));exit();
          dump($ex->get_detail_merged($symbol));
          dump($ex->get_market_trade($symbol));exit();
          dump($ex->get_history_trade($symbol,$size));exit();
          dump($ex->get_balance('198800'));exit();
         dump($ex->get_market_tickers());exit();
         dump($ex->get_history_kline($symbol,$period,$size));exit();
         dump($ex->get_common_symbols());exit();
       // echo 'hello kitty'.$a;
         $zip=new \ZipExtension();
         $req=new Request();
        var_dump( $req);exit();
      //   $req->abc();
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