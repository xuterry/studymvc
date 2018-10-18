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
    protected $head;
    function __construct($data='')
    {
       $this->data=$data;
        if(is_array($this->data))
            print_r($this->data);
       else
            echo $this->data;   
    }
    public static function output($content='')
    {
        return(new self($content));       
    }
}