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
    protected $is_init=0;
    protected $config=[];
    function __construct()
    {
    }
    protected function initView()
    {
        $this->is_init||($this->view=new View(array_merge(Config::get('template'),$this->config),Config::get('view_replace_str')))&&$this->is_init=1;
    }
    protected function assign($name,$value)
    {
        $this->initView();
        return $this->view->assign($name,$value);
    }
    protected function display($content='',$var=[],$rep=[],$conf=[])
    {
        $this->initView();
        return $this->view->display($content,$var,$rep,$conf);
    }
    protected function fetch($tpl='',$var=[],$rep=[],$conf=[])
    {       
        $this->initView();   
        return $this->view->fetch($tpl,$var,$rep,$conf);
    }
    /**
     * 配置view config
     * @param string $name
     * @param string $value
     * @return 
     */
    protected function config($name,$value=null)
    {
        $this->init();
        return $this->view->config($name,$value);
    }
    protected function success($msg = '', $url = null, $data = '', $wait = 5, array $header = [])
    {
        Response::instance($msg.$this->tpl($url,$wait),'text',200)->send();
        exit();
    }
    protected function error($msg = '', $url = null, $data = '', $wait = 5, array $header = [])
    {
         Response::instance($msg.$this->tpl($url,$wait),'text',404)->send();
        exit();
    }
    protected function tpl($url,$wait)
    {
        $str="<p class='jump'>
        页面自动 <a id='href' href='".$url."'>跳转</a> 等待时间： <b id='wait'>".$wait."</b>
        </p>
        <script type='text/javascript'>
        (function(){
            var wait = document.getElementById('wait'),
                href = document.getElementById('href').href;
            var interval = setInterval(function(){
                var time = --wait.innerHTML;
                if(time <= 0) {
                    location.href = href;
                    clearInterval(interval);
                };
            }, 1000);
        })();
    </script>";
        return $str;
    }
    /**
     * 验证器
     * @param array $data
     * @param array $rule
     */
    public function validate($data=[],$rule=[])
    {
        return (new Validate($data,$rule))->check();
    }
    protected function redirect($url)
    {
        !empty($url)&&header("Location:".$url);
        exit;
    }
}