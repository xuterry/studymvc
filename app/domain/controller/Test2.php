<?php
namespace app\domain\controller;
use core\Controller;
use Captcha;
use core\Model;
use core\Config;
use core\Session;
/**
/xtw
2018
*/
class Test2 extends Controller
{
    function index(){
       
        $code=Session::get('captcha_code');
        echo $code;
      // echo md5('炎止吕Mt');
        echo Captcha::instance()->check($code);exit();
        dump(Config::get('database'));
      //   $zip=new ZipExtension();
      $user=new Model('data');
      dump($user->delete(2000),$user->fetchAll(),$user->get(10));
    }
    function image()
    {
        //echo strtolower('在SSdd');exit();
        $captcha=new Captcha(['height'=>80,'width'=>300,'length'=>5,'zh'=>1,'mix'=>1,'bg'=>1]);
        $captcha->font_size=40;
      return $captcha->create();
    }
    function show()
    {
       return  Captcha::instance(['type'=>'drag'])->create();
    }
    function drag()
    {
        return $this->fetch('test/drag');
    }
    function check()
    {
        $value=input('get.value');
        if(Captcha::instance('drag')->check($value))
            echo 'ok';
        else 
            echo 'error';
    }
}