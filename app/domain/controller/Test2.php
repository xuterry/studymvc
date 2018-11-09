<?php
namespace app\domain\controller;
use core\Controller;
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
      dump($user->delete(2000),$user->fetchAll('id'));
      
    }
}