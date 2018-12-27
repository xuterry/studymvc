<?php
namespace app\admin\controller;

use core\Request;
class Banner extends Index
{
    protected $banner;
    function __construct()
    {
        parent::__construct();
        $this->banner=$this->getModel('banner'); 
    }

    public function Index(Request $request)
    {
        $pageto = $request->param('pageto');
        // 导出
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : '10';
        // 每页显示多少条数据
        $page = $request->param('page');
        
        // 页码
        if ($page) {
            $start = ($page - 1) * $pagesize;
        } else {
            $start = 0;
        }
        $r = $this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置       
        // 查询轮播图表，根据sort顺序排列
        $r = $this->banner->order('sort')->paginator($pagesize);
        foreach ($r as $k => $v) {
            $v->image = $uploadImg . $v->image;
        }
            
        $this->assign("list", $r);
        $this->assign('pages_show', $r->render());
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function add(Request $request)
    {
        $request->method()=='post'&&$this->do_add($request);
        $r = $this->getConfig();
        
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        
        $products = $this->getModel('productList')->fetchOrder("sort,id","id,product_title,sort,add_date");
        
        $this->assign('products', $products);
        
        $this->assign("uploadImg", $uploadImg);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_add($request)
    {
        
        // 接收数据
        $image = addslashes($request->param('image')); // 轮播图
        
        $url = addslashes(trim($request->param('url'))); // 链接
        
        $sort = floatval(trim($request->param('sort'))); // 排序
        
        if ($image) {
            
            $image = preg_replace('/.*\//', '', $image);
        } else {
            
            $this->error('轮播图不能为空！','');
        }
        
        // 添加轮播图
        $data = $this->parseSql("(image,url,sort,add_date) values('$image','$url','$sort',".nowDate()."'",'insert');
        $r = $this->banner->insert($data);
        if ($r == false) {
            $this->error('未知原因，添加失败！','');
        } else {
            
            $this->success('添加成功！',$this->module_url."/banner");
        }
        
        exit;
    }

    public function del(Request $request)
    {
        
        // 接收信息
        $id = intval($request->param('id')); // 轮播图id
        
        $yimage = addslashes(trim($request->param('yimage'))); // 原图片路径带名称
        
        $uploadImg = substr($yimage, 0, strripos($yimage, '/')); // 图片路径
        
        if(empty($uploadImg)){
            $uploadImg=$this->getConfig()[0]->uploadImg;
        }
        
        $r = $this->banner->get($id,'id');
        
        $image = $r[0]->image;     
        
        // 根据轮播图id，删除轮播图信息
        
        
        $res = $this->banner->delete($id);
        if($res==false)
            exit('-1');
        else{
            @unlink(check_file(PUBLIC_PATH.DS.$uploadImg . $image));
            exit('1');
        }
    }

    public function modify(Request $request)
    {
        $request->method()=='post'&&$this->do_modify($request); 
        // 接收信息
        $id = intval($request->param("id")); // 轮播图id
        empty($id)&&exit('id null');
        $yimage = addslashes(trim($request->param('yimage'))); // 原图片路径带名称
        
        $uploadImg = substr($yimage, 0, strripos($yimage, '/')) . '/'; // 图片路径
                                                                     
        // 根据轮播图id，查询轮播图信息
                
        $r = $this->banner->get($id,'id');
        $url='';
        if ($r) {
            
            $image = $r[0]->image; // 轮播图
            
            $url = $r[0]->url; // 链接
            
            $sort = $r[0]->sort; // 排序
        }
        
        if ($url == '') {
            
            $url = '#';
        }
               
        $products = $this->getModel('productList')->fetchOrder("sort,id","id,product_title,sort,add_date");
        
        $this->assign('products', $products);
        
        $this->assign("uploadImg", $uploadImg);
        
        $this->assign("image", $image);
        
        $this->assign('id', $id);
        
        $this->assign('url', $url);
        
        $this->assign('sort', $sort);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        // 接收信息
        $id = intval($request->param('id'));
        
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        
        $image = addslashes(trim($request->param('image'))); // 轮播图
        
        $oldpic = addslashes(trim($request->param('oldpic'))); // 原轮播图
        
        $url = addslashes(trim($request->param('url'))); // 链接
        
        $sort = floatval(trim($request->param('sort'))); // 排序
        
        if ($image) {
            
            $image = preg_replace('/.*\//', '', $image);
            
            if ($image != $oldpic) {
                
                @unlink($uploadImg . $oldpic);
            }
        } else {
            
            $image = $oldpic;
        }
        
        // 更新数据表
        
        $data=  $this->parseSql("image = '$image',url = '$url', sort = '$sort'");
        
        $r = $this->banner->save($data,$id);
        
        if ($r == false) {
            
            $this->error('未知原因，修改失败！',$this->module_url."/banner");
        } else {
            
            $this->success('修改成功！',$this->module_url."/banner");
        }
        
        exit;
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