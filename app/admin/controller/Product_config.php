<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Product_config extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    private function do_Index($request)
    {
        
        
        $admin_id = Session::get('admin_id');

        $config = serialize(($request->param('config')));

        $rr=$this->getModel('ProductConfig')->where(['id'=>['=','1']])->fetchAll('*');
        if($rr){
            $r=$this->getModel('ProductConfig')->save(['config'=>$config,'add_date'=>nowDate()],1,'id');
            if($r > 0){
                $this->recordAdmin($admin_id,' 修改商品参数信息 ',2);

                $this->success('修改信息成功！',$this->module_url."/product_config");
            }else{
                $this->recordAdmin($admin_id,' 修改商品参数信息失败 ',2);

                $this->error('未知原因，修改参数失败！','');
                
            }
        }else{
            $r=$this->getModel('ProductConfig')->insert(['config'=>$config,'add_date'=>nowDate()]);
            if($r > 0){
                $this->recordAdmin($admin_id,' 添加商品参数信息 ',1);

                $this->success('添加信息成功！',$this->module_url."/product_config");
            }else{
                $this->recordAdmin($admin_id,' 添加商品参数信息失败 ',1);

                $this->error('未知原因，添加参数失败！','');
                
            }
        }
        return;
    }

    
    public function Index(Request $request)
    {
        $request->method()=='post'&&$this->do_index($request);
        $r=$this->getModel('ProductConfig')->get('1','id');

        if($r){
            $config = unserialize($r[0]->config);
        }
        $config = (object)$config;

        $this->assign('config', isset($config) ? $config : '');
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    function changePassword(Request $request)
    {
        $this->redirect($this->module_url.'/index/changePassword');
    }
    function maskContent(Request $request)
    {
       $this->redirect($this->module_url.'/index/maskContent');
    }
}