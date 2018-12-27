<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Brand_class extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    public function Index(Request $request)
    {
           
        $uploadImg = Session::get('uploadImg');

        $pageto = $request -> param('pageto');
        // 导出
        $pagesize = $request -> param('pagesize');
        $pagesize = $pagesize ? $pagesize:10;
        // 每页显示多少条数据
        $page = $request -> param('page');

        // 页码

        // 查询新闻分类表，根据sort顺序排列
        $r=$this->getModel('BrandClass')->where(['recycle'=>['=','0']])->order(['brand_time'=>'desc'])->paginator($pagesize);

        $this->assign("uploadImg",$uploadImg);
        $this->assign("list",$r);
        $this->assign('pages_show', $r->render());

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
		
        
        $admin_id = Session::get('admin_id');

        // 获取分类名称和排序号
        $brand_name = addslashes(trim($request->param('pname'))); // 品牌名称
        $brand_y_pname = addslashes(trim($request->param('y_pname'))); // 品牌名称
        $image = addslashes(trim($request->param('image'))); // 品牌图片
        $producer = addslashes(trim($request->param('producer'))); // 产地
        $sort = addslashes(trim($request->param('sort'))); // 排序
        $remarks = addslashes(trim($request->param('remarks'))); // 备注
        if($image){
            $image = preg_replace('/.*\//','',$image);
        }
        if($brand_name == ''){
            $this->error('中文名称不能为空！','');
            
        }
		//检查分类名称是否重复
        $r=$this->getModel('BrandClass')->where(['brand_name'=>['=',$brand_name]])->fetchAll('*');
		// 如果有数据 并且 数据条数大于0
        if ($r && count($r) > 0) {
            $this->error('商品品牌中文名称{$brand_name} 已经存在，请选用其他名称！','');
            
        }
		//添加分类
		$r=$this->getModel('BrandClass')->insert(['brand_name'=>$brand_name,'brand_y_name'=>$brand_y_pname,'brand_pic'=>$image,'producer'=>$producer,'remarks'=>$remarks,'brand_time'=>nowDate(),'sort'=>$sort]);
		if($r ==false) {
            $this->recordAdmin($admin_id,'添加商品品牌'.$brand_name.'失败',1);

            $this->error('未知原因，添加产品品牌失败！','');
			
		} else {
            $this->recordAdmin($admin_id,'添加商品品牌'.$brand_name,1);

            $this->success('添加产品品牌成功！',$this->module_url."/brand_class");
			
		}
		
		return;
	}

	
    public function del(Request $request)
    {
        
        
        $admin_id = Session::get('admin_id');

        $brand_id = intval($request->param('cid')); // 品牌id
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        $r=$this->getModel('BrandClass')->where(['brand_id'=>['=',$brand_id]])->fetchAll('*');
        $brand_pic = $r[0]->brand_pic;
        //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$brand_pic));

        $r=$this->getModel('ProductList')->where(['brand_id'=>['=',$brand_id]])->fetchAll('id');
        if($r){
            $this->recordAdmin($admin_id,' 删除商品品牌id为 '.$brand_id.' 失败',3);
            echo 2;
            exit;
        }
        // 根据分类id,删除这条数据
        $res=$this->getModel('BrandClass')->saveAll(['recycle'=>1,'status'=>1],['brand_id'=>['=',$brand_id]]);

		if($res > 0){
            $this->recordAdmin($admin_id,' 删除商品品牌id为 '.$brand_id.' 的信息',3);

            echo 1;
            exit;
		}else{
            $this->recordAdmin($admin_id,' 删除商品品牌id为 '.$brand_id.' 失败',3);
            echo 0;
            exit;
		};
    }

    
    public function modify(Request $request)
    {
       $request->method()=='post'&&$this->do_modify($request);
        
        
        // 接收分类id
        $brand_id = intval($request->param("cid")); // 品牌id
        $uploadImg = Session::get('uploadImg');

        // 根据分类id,查询产品分类表
        $r=$this->getModel('BrandClass')->where(['brand_id'=>['=',$brand_id]])->fetchAll('*');
        if($r){
            $brand_name = $r[0]->brand_name; // 品牌名称
            $brand_y_name = $r[0]->brand_y_name; // 品牌名称
            $brand_pic = $r[0]->brand_pic; // 品牌图片
            $producer = $r[0]->producer; // 产地
            $remarks = $r[0]->remarks; // 备注
            $sort = $r[0]->sort; // 排序
        }
        $this->assign("brand_id",$brand_id);
        $this->assign("uploadImg",$uploadImg);
        $this->assign("brand_name",$brand_name);
        $this->assign("brand_y_name",$brand_y_name);
        $this->assign('brand_pic', $brand_pic);
        $this->assign('producer', $producer);
        $this->assign('remarks', $remarks);
        $this->assign('sort', $sort);
		return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
	}

	
    private function do_modify($request)
    {
		
		
        $admin_id = Session::get('admin_id');

        $brand_id = intval($request->param('cid')); // 品牌id
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        $brand_name = addslashes(trim($request->param('pname'))); // 品牌名称
        $brand_y_pname = addslashes(trim($request->param('y_pname'))); // 品牌名称
        $image = addslashes(trim($request->param('image'))); // 品牌新图片
        $oldpic = addslashes(trim($request->param('oldpic'))); // 品牌原图片
        $producer = addslashes(trim($request->param('producer'))); // 产地
        $sort = addslashes(trim($request->param('sort'))); // 排序
        $remarks = addslashes(trim($request->param('remarks'))); // 备注
        if($image){
            $image = preg_replace('/.*\//','',$image);
            if($image != $oldpic){
                //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$oldpic));
            }
        }else{
            $image = $oldpic;
        }
        if($brand_name == ''){
            $this->error('中文名称不能为空！','');
            
        }
        //检查分类名是否重复
        $r=$this->getModel('BrandClass')->where(['brand_name'=>['=',$brand_name],'brand_id'=>['<>',$brand_id]])->fetchAll('brand_name');
        if ($r) {
            $this->error('商品品牌中文名称{$brand_name} 已经存在，请选用其他名称修改！','');
            
        }
		//更新分类列表
		$r=$this->getModel('BrandClass')->saveAll(['brand_name'=>$brand_name,'brand_y_name'=>$brand_y_pname,'brand_pic'=>$image,'producer'=>$producer,'remarks'=>$remarks,'sort'=>$sort],['brand_id'=>['=',$brand_id]]);

		if($r ==false) {
            $this->recordAdmin($admin_id,' 修改商品品牌id为 '.$brand_id.' 失败',2);

            $this->error('未知原因，修改产品品牌失败！',$this->module_url."/brand_class");
			
		} else {
            $this->recordAdmin($admin_id,' 修改商品品牌id为 '.$brand_id.' 的信息',2);

            $this->success('修改产品品牌成功！',$this->module_url."/brand_class");
		}
		return;
	}

	
    public function status(Request $request)
    {
        
        
        $admin_id = Session::get('admin_id');

        $id = addslashes(trim($request->param('id')));

        $r=$this->getModel('BrandClass')->where(['brand_id'=>['=',$id]])->fetchAll('status');
        if($r){
            $status = $r[0]->status;
            if($status == 0){
                $r=$this->getModel('ProductList')->where(['brand_id'=>['=',$id]])->fetchAll('id');
                if($r){
                    $this->recordAdmin($admin_id,'禁用品牌id为'.$id .' 失败',5);
                    echo 2;
                    exit;
                }
                $update_rs=$this->getModel('BrandClass')->saveAll(['status'=>1],['brand_id'=>['=',$id]]);

                $this->recordAdmin($admin_id,'禁用品牌id为'.$id,5);

                echo 1;
                return;
            }else{
                $update_rs=$this->getModel('BrandClass')->saveAll(['status'=>0],['brand_id'=>['=',$id]]);

                $this->recordAdmin($admin_id,'启用品牌id为'.$id,5);
                echo 1;
                return;
            }
        }
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