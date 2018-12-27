<?php
namespace app\admin\controller;

use core\Request;
use core\Session;

class Notice extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        $r = $this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
                                       // 查询插件表
        $r = $this->getModel('SetNotice')->fetchAll();
        if (! empty($r)) {
            foreach ($r as $k => $v) {
                if ($v->img_url == '') {
                    $v->img_url = 'nopic.jpg';
                }
            }
        }
        $this->assign("uploadImg", $uploadImg);
        $this->assign("list", $r);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_add($request)
    {
        $admin_id = Session::get('admin_id');
        $notice = $request->param('notice'); // notice
        $image = addslashes($request->param('image')); // 活动图片
        $detail = $this->trimContent(addslashes(trim($request->param('detail'))),''); // 产品内容
        
        if ($image) {
            $image = preg_replace('/.*\//', '', $image);
        } else {
            $this->error('公告活动图片不能为空！', '');
        }
        
        if (empty($detail)) {
            $this->error('公告内容不能为空！', '');
        }
        
        if (empty($notice)) {
            $this->error('公告名称不能为空！', '');
        }
        $rr = $this->getModel('SetNotice')->insert([
                                                        'user' => $admin_id,'name' => $notice,'img_url' => $image,'detail' => $detail,'time' => nowDate()
        ]);
        if ($rr == false) {
            $this->error('未知原因，添加失败！', $this->module_url . "/notice");
        } else {
            $this->success('添加成功！', $this->module_url . "/notice");
        }
    }

    public function add(Request $request)
    {
       $request->method()=='post'&&$this->do_add($request);

        $r = $this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        
        $this->assign("uploadImg", $uploadImg);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_article($request)
    {
        print_r(12);
        die();
        $id = $request->param('id'); // notice
    }

    public function article(Request $request)
    {
       $request->method()=='post'&&$this->do_article($request);

        $id = $request->param('id');
        
        $r = $this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        
        $res_notice=$this->getModel('SetNotice')->where(['id'=>['=',$id]])->fetchAll('*');
        
        $this->assign("uploadImg", $uploadImg);
        $this->assign("res_notice", $res_notice);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function del(Request $request)
    {
        
        // 接收信息
        $id = intval($request->param('id')); // 插件id
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        
        $r = $this->getModel('SetNotice')->get($id, 'id');
        $image = $r[0]->img_url;
        // @unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$image));
        $res=$this->getModel('SetNotice')->delete($id,'id');
        
        if ($res > 0) {
            $this->success('删除成功！', $this->module_url . "/notice");
        } else {
            $this->error('删除失败！', $this->module_url . "/notice");
        }
        return;
    }

    public function modify(Request $request)
    {
       $request->method()=='post'&&$this->do_modify($request);

        $request->method() == 'post' && $this->do_modify($request);
        
        // 接收信息
        
        $id = intval($request->param("id")); // 活动id
        
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
                                                                     
        // 根据插件id，查询插件信息
        
        $res = $this->getModel('SetNotice')->get($id, 'id');
        
        if ($res) {
            
            $id = $res[0]->id;
            
            $image = $res[0]->img_url;
            
            $name = $res[0]->name;
            
            $detail = $res[0]->detail;
        }
        
        $this->assign("uploadImg", $uploadImg);
        
        $this->assign("id", $id);
        
        $this->assign("name", $name);
        
        $this->assign("image", $image);
        
        $this->assign("detail", $detail);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        $id = addslashes(trim($request->param('id'))); //
        
        $url = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        
        $name = addslashes(trim($request->param('name'))); // name
        
        $detail = $this->trimContent(addslashes(trim($request->param('detail'))),''); // 产品内容
        
        $oldpic = addslashes(trim($request->param('oldpic')));
        
        $image = addslashes(trim($request->param('image')));
        
        if ($image) {
            
            $image = preg_replace('/.*\//', '', $image);
            
            if ($image != $oldpic) {
                
                // @unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$oldpic));
            }
        } else {
            
            $image = $oldpic;
        }
        
        $admin_id = Session::get('admin_id');
        
        // 更新数据表
        
        $r=$this->getModel('SetNotice')->saveAll(['img_url'=>$image,'user'=>$admin_id,'detail'=>$detail,'time'=>nowDate()],['id'=>['=',$id]]);      
        if ($r == false) {           
            $this->error('未知原因，修改失败！', $this->module_url . "/notice");
        } else {
            
            $this->success('修改成功！', $this->module_url . "/notice");
        }
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