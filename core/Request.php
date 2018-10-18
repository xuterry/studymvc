<?php
namespace core;
use core\Route;
/**
/xtw
2018
*/
class Request
{
   protected $data=[];
    function __construct(){
        //var_dump($_SERVER);
        $this->data['url']=$_SERVER['REQUEST_URI'];
        $this->data['ip']=$_SERVER['SERVER_ADDR'];
        $this->data['get']=$_GET;
        $this->data['post']=$_POST;
    }
    public   function get($name)
    {
        if(!empty($this->data['get'][$name]))
        return $this->data['get'][$name];
    }
    public   function post($name)
    {
        if(!empty($this->data['get'][$name]))
            return $this->data['get'][$name];
    }
    public   function url()
    {
        return substr($this->data['url'],1);
    }
    public function method()
    {
        $method=$_SERVER['REQUEST_METHOD'];
        return strtolower($method);
    }
}