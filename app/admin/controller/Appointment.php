<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Appointment extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    public function Index(Request $request)
    {
        
        
        $name = addslashes(trim($request->param('name'))); // 姓名
        $startdate = $request->param('startdate'); // 开始时间
        $enddate = $request->param('enddate'); // 结束日期
        
        $condition = ' status != 3 ';
        if($name != ''){ 
            $condition .= " and name = '$name' ";
        }
        if($startdate != ''){ // 查询开始日期不为空
            $condition .= " and add_date >= '$startdate 00:00:00' ";
        }
        if($enddate != ''){ // 查询结束日期不为空
            $condition .= " and add_date <= '$enddate 23:59:59' ";
        }
        
        $sql = "select * from lkt_experience where $condition";
        $r = $db->select($sql);

        $this->assign("startdate",$startdate);
        $this->assign("enddate",$enddate);
        $this->assign("name",$name);
        $this->assign("list",$r);

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function cancel(Request $request)
    {
        
        
        $id = $request->param('id');
        
        $sql = "update lkt_experience set status = 2 where id = '$id' ";
        $r = $db->update($sql);
        if($r == 1){
            $this->error('取消预约！',$this->module_url."/appointment");
            return;
        }else{
            $this->error('取消预约失败！',$this->module_url."/appointment");
            return;
        }

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function make(Request $request)
    {
        
        
        $id = $request->param('id');
        
        $sql = "update lkt_experience set status = 1 where id = '$id' ";
        $r = $db->update($sql);
        if($r == 1){
            $this->error('确定要预约！',$this->module_url."/appointment");
            return;
        }else{
            $this->error('预约失败！',$this->module_url."/appointment");
            return;
        }

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function see(Request $request)
    {
        
        
        $id = $request->param('id'); 
        
        $r=$this->getModel('Experience')->get($id,'id');
        $name = $r[0]->name;
        
        $this->assign("name",$name);
        $this->assign("list",$r[0]);

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