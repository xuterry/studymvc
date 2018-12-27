<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Keyword extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    private function do_Add($request)
    {
        
        
        $kw = addslashes(trim($request -> param('keyword')));
        
        $countsql = 'select count(keyword) from lkt_hotkeywords';
        $count = $db -> selectarray($countsql);
        list($count) = $count;
        $count = intval($count['count(keyword)']);
        if($count >= 6){
           $this->error('添加失败,最多只能添加六个关键词！',$this->module_url."/keyword");die;
        }
        if($kw !== ''){
           $res=$this->getModel('Hotkeywords')->insert(['keyword'=>$kw]);

           if($res > 0){
             $this->success('添加成功！',$this->module_url."/keyword");
             }
           }else{
              $this->error('关键词不能为空！','');
           }
        
        return;                                                                                                                                                                                                                                
    }

    
    public function Add(Request $request)
    {
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function Del(Request $request)
    {
        
        
        $id = intval(trim($request -> param('id')));

        $sql = 'delete from lkt_hotkeywords where id='.$id;
        
        $res = $db -> delete($sql);
        if($res > 0){
          $this->success('删除成功！',$this->module_url."/keyword");
        }
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function Index(Request $request)
    {
        
        
        $res=$this->getModel('Hotkeywords')->fetchAll();
        
        $this->assign("res",$res);
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }
   
  
    private function do_Modify($request)
    {
        
        
        $id = intval(trim($request -> param('id')));
        $name = addslashes(trim($request -> param('name')));
        
        if($name !== ''){
          $res=$this->getModel('Hotkeywords')->saveAll(['keyword'=>$name],['id'=>['=',$id]]);
          if($res > 0){
             $this->success('修改成功！',$this->module_url."/keyword");
             }
           }else{
              $this->error('关键词不能为空！','');
           }

        return;
    }

    
    public function Modify(Request $request)
    {
        
        
        $id = trim($request -> param('id'));

        $sel = $db -> selectarray('select id,keyword from lkt_hotkeywords where id='.$id);
        if(!empty($sel)){
           $id = $sel['0']['id'];
           $sel = $sel['0']['keyword'];
          }
        $this->assign("id",$id);
        $this->assign("sel",$sel);
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