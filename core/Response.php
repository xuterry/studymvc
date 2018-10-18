<?php
/**
/xtw
2018
输出类
*/
namespace core;
class Response
{
    protected $data;
    protected $head=[];
    protected $code=200;
    protected $charset='utf-8';
    protected $type='text';
    function __construct($data='',$type='',$code=0,$head=[])
    {
       $this->data=$data;
        if(is_array($this->data))
            print_r($this->data);
       else
            echo $this->data;   
    }
    public static function output($content='',$type='',$code=0,$head=[])
    {
        return(new self($content));       
    }
    function __destruct()
    {
        unset($this->data);
    }
    function send()
    {
        
    }
}