<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Guide extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    public function Index(Request $request)
    {
        

        
        $r=$this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        // 查询轮播图表，根据sort顺序排列
        $sql = "select * from lkt_guide order by sort";
        $r = $db->select($sql);
        foreach ($r as $k => $v) {
            $v->image = $uploadImg . $v->image;
        }
        $this->assign("list",$r);

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function add(Request $request)
    {
       $request->method()=='post'&&$this->do_add($request);
        

        

        $r=$this->getConfig();

        $uploadImg = $r[0]->uploadImg; // 图片上传位置

        

        $this->assign("uploadImg",$uploadImg);

		return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);

	}

	
    private function do_add($request)
    {

		

		

        // 接收数据 

        $image= addslashes($request->param('image')); // 轮播图

        $type = addslashes(trim($request->param('type'))); // 类型

        $sort = floatval(trim($request->param('sort'))); // 排序

        if($image){

            $image = preg_replace('/.*\//','',$image);

        }else{

            $this->error('引导图不能为空！','');

            

        }

        // 添加轮播图

        $sql = "insert into lkt_guide(image,type,sort,add_date) " .

            "values('$image','$type','$sort',CURRENT_TIMESTAMP)";

        $r = $db->insert($sql);

        if($r ==false){

            $this->error('未知原因，添加失败！','');

            

        }else{

            $this->success('添加成功！',$this->module_url."/guide");

            

        }

	    return;

	}

	
    public function del(Request $request)
    {
        
        
        // 接收信息
        $id = intval($request->param('id')); // 轮播图id
        $yimage = addslashes(trim($request->param('yimage'))); // 原图片路径带名称
        $uploadImg = substr($yimage,0,strripos($yimage, '/')) . '/'; // 图片路径
        $r=$this->getModel('Banner')->get($id,'id');
        $image = $r[0]->image;
        //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$image));
        // 根据轮播图id，删除轮播图信息
        $sql = "delete from lkt_banner where id = '$id'";
        $db->delete($sql);
        $this->success('删除成功！',$this->module_url."/banner");
        return;
    }

    
    public function modify(Request $request)
    {
       $request->method()=='post'&&$this->do_modify($request);
        

        

        // 接收信息

        $id = intval($request->param("id")); // 轮播图id

        $yimage = addslashes(trim($request->param('yimage'))); // 原图片路径带名称

        $uploadImg = substr($yimage,0,strripos($yimage, '/')) . '/'; // 图片路径

        // 根据轮播图id，查询轮播图信息

        $r=$this->getModel('Guide')->get($id,'id');

        if($r){

            $image = $r[0]->image; // 轮播图

            $type = $r[0]->type ; // 链接

            $sort = $r[0]->sort; // 排序

        }

        

        $this->assign("uploadImg",$uploadImg);

        $this->assign("image",$image);

        $this->assign('id', $id);

        $this->assign('type', $type);

        $this->assign('sort', $sort);

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);

	}

	
    private function do_modify($request)
    {

		

		

        // 接收信息

		$id = intval($request->param('id'));

        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置

        $image = addslashes(trim($request->param('image'))); // 轮播图

        $oldpic = addslashes(trim($request->param('oldpic'))); // 原轮播图

        $type = addslashes(trim($request->param('type'))); // 类型

        $sort = floatval(trim($request->param('sort'))); // 排序

        if($image){

            $image = preg_replace('/.*\//','',$image);

            if($image != $oldpic){

                //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$oldpic));

            }

        }else{

            $image = $oldpic;

        }

		//更新数据表

		$sql = "update lkt_guide " .

			"set image = '$image',type = '$type', sort = '$sort' "

			."where id = '$id'";

		$r = $db->update($sql);

		if($r ==false) {

		$this->error('未知原因，修改失败！',$this->module_url."/guide");

			

		}else {

			$this->success('修改成功！',$this->module_url."/guide");

		}

		return;

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