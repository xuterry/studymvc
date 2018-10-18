<?php
namespace core;
/**
/xtw
2018
控制器基础类
*/
class Controller
{
    //视图
    protected $view;
    function __construct()
    {
        $this->view=new View(Config::get('template'),Config::get('view_replace_str'));
        //var_dump($this);
      // exit();
    }
    protected function assign($name,$value)
    {
        return $this->view->assign($name,$value);
    }
    protected function display($tpl='',$var=[],$rep=[],$conf=[])
    {
        return $this->view->display($tpl,$var,$rep,$conf);
    }
}