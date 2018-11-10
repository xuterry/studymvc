<?php
namespace app\domain\controller;
use core\Controller;
use Captcha;
use core\Model;
use core\Config;
/**
/xtw
2018
*/
class Test2 extends Controller
{
    function index(){
        dump(Config::get('database'));
      //   $zip=new ZipExtension();
      $user=new Model('data');
      dump($user->delete(2000),$user->fetchAll(),$user->get(10));
      
    }
    function image()
    {
        $captcha=new Captcha(['height'=>40,'width'=>'150','length'=>5,'zh'=>1]);
      return $captcha->create();
    }
}