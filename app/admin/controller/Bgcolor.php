<?php
namespace app\admin\controller;

use core\Request;

class Bgcolor extends Index
{
   private $color;
    function __construct()
    {
        parent::__construct();
        $this->color=$this->getModel('BackgroundColor');
        
    }

    public function Index(Request $request)
    {
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : 10;
        // 每页显示多少条数据
        $page = $request->param('page');
        
        // 页码
        if ($page) {
            $start = ($page - 1) * $pagesize;
        } else {
            $start = 0;
        }
        $r = $this->getModel('BackgroundColor')
            ->order("sort")
            ->limit($start, $pagesize)
            ->paginator($pagesize);
        $url = $this->module_url . "/product/Index/pagesize=" . urlencode($pagesize);
        $this->assign("list", $r);
        $this->assign('pages_show', $r->render());
        $this->assign('pagesize', $pagesize);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function add(Request $request)
    {
        $request->method() == 'post' && $this->do_add($request);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_add($request)
    {
        
        // 接收数据
        $color_name = addslashes(trim($request->param('color_name')));
        
        $color = addslashes(trim($request->param('color')));
        
        $sort = floatval(trim($request->param('sort')));
        
        $data = $this->parseSql("(color_name,color,sort) values('$color_name','$color','$sort')",'insert');
        
        $r = $this->color->insert($data);
        
        if ($r == false) {
            
            $this->error('未知原因，添加失败！', '');
        } else {
            
            $this->success('添加成功！', $this->module_url . "/bgcolor");
        }
        
        return;
    }

    public function del(Request $request)
    {
        
        // 接收信息
        $id = intval($request->param('id')); // id
                                             // 根据id删除信息
        $res = $this->color->delete($id,'id');
        if($res==false)
            exit('-1');
        exit('1');
        $this->success('删除成功！', $this->module_url . "/bgcolor");
        return;
    }

    public function enable(Request $request)
    {
        
        // 接收信息
        $id = intval($request->param('id')); // id
        $rr = $this->color->saveAll(['status'=>0],"status = 1");
        $r = $this->color->save(['status'=>1],$id,'id'); 
        if ($r) {
            exit('1');
            $this->success('启用成功！', $this->module_url . "/bgcolor");
            return;
        } else {
            exit('-1');
            $this->error('启用失败！', $this->module_url . "/bgcolor");
            return;
        }
    }

    public function modify(Request $request)
    {
        $request->method() == 'post' && $this->do_modify($request);
        
        // 接收信息
        
        $id = intval($request->param("id")); // id
                                             
        // 根据id查询
        
        $r = $this->getModel('BackgroundColor')->get($id, 'id ');
        
        if ($r) {
            
            $color_name = $r[0]->color_name;
            
            $color = $r[0]->color;
            
            $sort = $r[0]->sort;
        }
        
        $this->assign('id', $id);
        
        $this->assign('color_name', isset($color_name) ? $color_name : '');
        
        $this->assign('color', isset($color) ? $color : '');
        
        $this->assign('sort', isset($sort) ? $sort : '');
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        
        // 接收数据
        $id = intval($request->param('id'));
        
        $color_name = addslashes(trim($request->param('color_name')));
        
        $color = addslashes(trim($request->param('color')));
        
        $sort = floatval(trim($request->param('sort')));
        
        // 更新数据表
        
        $data = $this->parseSql("color_name = '$color_name',color = '$color', sort = '$sort'");
        
        $r=$this->color->save($data,$id,'id');        
        if ($r == false) {
            
            $this->error('未知原因，修改失败！', $this->module_url . "/bgcolor");
        } else {
            
            $this->success('修改成功！', $this->module_url . "/bgcolor");
        }
        
        return;
    }

    function changePassword(Request $request)
    {
        $this->redirect($this->module_url . '/index/changePassword');
    }

    function maskContent(Request $request)
    {
        $this->redirect($this->module_url . '/index/maskContent');
    }
}