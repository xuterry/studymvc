<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Product_class extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    public function Index(Request $request)
    {
        $cid = $request->param("cid"); // 分类id     
        if($request->method()=='post'){
            $sid=intval($request->post('sid'));
            if(empty($cid))
            exit('-1');
            if($this->getModel('ProductClass')->save(['sid'=>'0','level'=>0],$cid,'cid')){
                exit('1');
            }
            exit('-1');
        }
        $uploadImg = Session::get('uploadImg');

        $array = ['顶级','一级','二级','三级','四级','五级'];
        $pagesize = $request -> param('pagesize');
        $pagesize = $pagesize ? $pagesize:'10';
        $page = $request->param('page'); // 页码
        if($page){
            $start = ($page-1)*$pagesize;
        }else{
            $start = 0;
        }

        $con = '';
        foreach ($_GET as $key => $value001) {
            $con .= "&$key=$value001";
        }
        
        if($cid){ // 上级id
            // 根据分类id,查询所有下级
            $rr=$this->getModel('ProductClass ')->where(['recycle'=>['=','0'],'sid'=>['=',$cid]])->fetchOrder(['sort'=>'desc'],'*',"$start,$pagesize");
            if($rr){
                // 有数据
                $level = $rr[0]->level;
                // 循环查询该分类是否有商品
                foreach ($rr as $k => $v){
                    $product_class = '-' . $v->cid . '-';
                    $rr1=$this->getModel('ProductList ')->where(['product_class'=>['like','%$product_class%']])->fetchOrder(['sort'=>'desc'],'id');
                    if($rr1){
                        $v->status = 1; // 有商品，隐藏删除按钮
                    }else{
                        $v->status = 0; // 没商品，显示删除按钮
                    }
                }
            }else{ // 没数据，查询当前分类级别
                $rrr=$this->getModel('ProductClass ')->where(['recycle'=>['=','0'],'cid'=>['=',$cid]])->fetchOrder(['sort'=>'desc'],'level',"$start,$pagesize");
                $level = $rrr[0]->level+1;
            }
            $sid_1 = $cid;
        }else{
            // 查询分类表，根据sort顺序排列
            $rr=$this->getModel('ProductClass')->where(['recycle'=>['=','0'],'sid'=>['=','0']])->fetchOrder(['sort'=>'desc'],'*',"$start,$pagesize");
            $level = 0;
            foreach ($rr as $k => $v){
                $product_class = '-' . $v->cid . '-';
                $rr1=$this->getModel('ProductList ')->where(['product_class'=>['like','%$product_class%']])->fetchOrder(['sort'=>'desc'],'id',1);
                if($rr1){
                    $v->status = 1;
                }else{
                    $v->status = 0;
                }
            }
        }
        $sid = $cid ? $cid:0;
        //$total = $db->selectrow("select * from lkt_product_class where recycle = 0 and sid = '$sid'");

        $url = $this->module_url."/product_class/pagesize=".urlencode($pagesize).'&cid='.urlencode($cid).'&con='.urlencode($con);
        $level= $level ? $level:0;
        $newlerevl = $array[$level];

        $this->assign("level_xs",$newlerevl);
        $this->assign("level",$level);
        $this->assign("list",$rr);
        $this->assign("cid",$cid);
        
        $this->assign("pages_show",$this->getModel('ProductClass')->where(["recycle"=>['=','0'],'sid'=>['=',$sid]])->paginator($pagesize)->render());
        $this->assign("uploadImg",$uploadImg);
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function add(Request $request)
    {
       $request->method()=='post'&&$this->do_add($request);
        // 接收分类id
        $cid = intval($request->param("cid")); // 分类id
        $uploadImg = Session::get('uploadImg'); // 图片上传位置
        $level = 0;

        // 根据分类id,查询产品分类表
        $r=$this->getModel('ProductClass')->where(['recycle'=>['=','0'],'cid'=>['=',$cid]])->fetchAll('*');

        if($r){
            $sid = $r[0]->sid; // 上级id
            $level = $r[0]->level+1;
        }

        $str_option = [];
        if($level >= 1){
            $cid_r = $sid;
            $str_option = $this->str_option($level,$cid,$str_option,$level);
        }else{
            $resc=$this->getModel('ProductClass')->where(['recycle'=>['=','0'],'sid'=>['=','0']])->fetchAll('*');
            $str_option[$cid] = $resc;
        }
        $this->assign('cid', $cid);
        $json = json_encode($str_option);
        $this->assign("str_option",$json);
        $this->assign('cid_r', $cid);
        $this->assign('level', $level);
        $this->assign('uploadImg', $uploadImg);
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
	}

	
    private function do_add($request)
    {
		
        
        // 获取分类名称和排序号
        $pname = addslashes(trim($request->param('pname'))); // 分类名称
        $sid = intval($request->param('val')); // 产品类别
        $level = $request->param('select_c'); // 级别
        $image = addslashes(trim($request->param('image'))); // 图片
		$sort = floatval(trim($request->param('sort'))); // 排序
        $bg = addslashes(trim($request->param('bg'))); // 展示图片
        if($level!=0&&$sid==0)
            $this->error('请选择上级',$this->module_url."/product_class/add");
        if($image){
            $image = preg_replace('/.*\//','',$image);
        }
        if($bg){
            $bg = preg_replace('/.*\//','',$bg);
        }
        if($pname == ''){
            $this->error('产品分类名称不能为空！','');            
        }
        if(empty($bg)||empty($image))
            $this->error('图片不能为空',$this->module_url."/product_class/add");
        //检查分类名称是否重复
        $r=$this->getModel('ProductClass')->where(['recycle'=>['=','0'],'pname'=>['=',$pname]])->fetchAll('*');
        // 如果有数据 并且 数据条数大于0
        if ($r && count($r) > 0) {
            $this->error('产品分类名称".$pname."已经存在，请选用其他名称！','');
            
        }
		//添加分类
		$r=$this->getModel('ProductClass')->insert(['pname'=>$pname,'sid'=>$sid,'img'=>$image,'bg'=>$bg,'level'=>$level,'sort'=>$sort,'add_date'=>nowDate()]);

		if($r ==false) {
			$this->error('未知原因，添加产品分类失败！','');
			
		} else {
			$this->success('添加产品分类成功！',$this->module_url."/product_class");
			
		}
		exit();
	}

	
    public function ajax(Request $request)
    {
				
        $level = $request->param("level"); // 分类级别
        $cid = $request->param("v"); // 分类id

		// 根据上级id为0,查询产品分类id、上级id、分类名称
        $r=$this->getModel('ProductClass ')->where(['recycle'=>['=','0'],'sid'=>['=',$cid]])->fetchOrder(['sort'=>'desc'],'cid,sid,pname');
        $asd = '';
        foreach($r as $k=>$v){
            $cid_1 = $v->cid; // 分类id
            $sid = $v->sid; // 上级id
            $pname_1 = $v->pname; // 分类名称
            $asd .=  "<option  value='$cid_1'>$pname_1</option>";
        }
        echo json_encode($asd);
		exit();
	}

	
    public function del(Request $request)
    {
       $request->method()=='post'&&$this->do_del($request);

    }

    
    private function do_del($request)
    {
        
        
        $admin_id = Session::get('admin_id');

        // 获取分类id
        $cid = intval($request->param('cid'));
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片路径
        empty($uploadImg)&&$uploadImg=Session::get('uploadImg');
        // 根据分类id,查询产品分类表
        $r=$this->getModel('ProductClass')->get($cid,'cid ');
        $level = $r[0]->level;
        $cid_r = $r[0]->cid;
        $str_option = [];
        $num = 0;
        $str_option[$num] = $cid;
        if($level >= 0){
            $str_option = $this->str_option($level,$cid,$str_option,$num);
        }
        foreach ($str_option as $k => $v){
            $rr=$this->getModel('ProductClass')->get($v,'cid ');
            $img = $rr[0]->img;
            $res=$this->getModel('ProductClass')->saveAll(['recycle'=>1],['cid'=>['=',$v]]);
            if($res > 0){
                //@unlink (check_file(PUBLIC_PATH.DS.$uploadImg.DS.$img));
                $this->recordAdmin($admin_id,' 删除商品分类id为 '.$v.' 的信息',3);
            }else{
                $this->recordAdmin($admin_id,' 删除商品分类id为 '.$v.' 失败',3);
            }
        }
        echo 1;
        return;
    }

    
    public function modify(Request $request)
    {
       $request->method()=='post'&&$this->do_modify($request);                
        // 接收分类id
        $cid = intval($request->param("cid")); // 分类id
        $uploadImg = Session::get('uploadImg'); // 图片上传位置

        // 根据分类id,查询产品分类表
        $r=$this->getModel('ProductClass')->where(['recycle'=>['=','0'],'cid'=>['=',$cid]])->fetchAll('*');

        if($r){
            $pname = $r[0]->pname; // 分类名称
            $sid = $r[0]->sid; // 上级id
            $img = $r[0]->img; // 分类图片
            $bg = $r[0]->bg; // 分类图片
            $sort = $r[0]->sort; // 分类排序
            $level = $r[0]->level;
        }

        $str_option = [];
        if($level >= 1){
            $cid_r = $sid;
            $str_option = $this->str_option($level,$sid,$str_option,$level);
        }else{
            $resc=$this->getModel('ProductClass')->where(['recycle'=>['=','0'],'sid'=>['=','0']])->fetchAll('*');
            $str_option[0] = $resc;
        }

        $array = ['顶级','一级','二级','三级','四级','五级'];

        $this->assign('cid', $sid ? $sid:0);
        $this->assign("level",$level);
        $json = json_encode($str_option);
        $this->assign("str_option",$json);
        $this->assign("bg",$bg);
        $this->assign('cid_r', $cid);
        $this->assign('uploadImg', $uploadImg);
        $this->assign('pname', isset($pname) ? $pname : '');
        // $this->assign('rname', isset($rname) ? $rname : '');
        $this->assign('img', isset($img) ? $img : '');
        $this->assign('sort', isset($sort) ? $sort : '');
		return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
	}

	
    private function do_modify($request)
    {
		$cid = intval($request->param('cid')); // 分类id
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        $pname = addslashes(trim($request->param('pname'))); // 分类名称
        $sid = $request->param('val'); // 产品类别

        $level = $request->param('select_c'); // 级别
        $level_old = $request->param('level'); // 原来等级
        
        
        $image = addslashes(trim($request->param('image'))); // 分类新图片
        $oldpic = addslashes(trim($request->param('oldpic'))); // 分类原图片
        $sort = floatval(trim($request->param('sort'))); // 排序
        // var_dump($cid,$sid,$_POST);exit;
        if($cid == $sid){
            $this->error('产品分类不能选择自己！',$this->module_url.'/product_class/modify?cid='.$cid);
        }
        if($level>0&&$sid==0)
            $this->error('sid不能为空！',$this->module_url.'/product_class/modify?cid='.$cid);            
        $bg = addslashes(trim($request->param('bg'))); // 展示图片
        $oldpicbg = addslashes(trim($request->param('oldpicbg'))); // 分类原图片
        if($image){
            $image = preg_replace('/.*\//','',$image);
            if($image != $oldpic){
                //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$oldpic));
            }
        }else{
            $image = $oldpic;
        }
        if($bg){
            $bg = preg_replace('/.*\//','',$bg);
            if($bg != $oldpicbg){
                //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$oldpicbg));
            }
        }else{
            $bg = $oldpicbg;
        }

        if($pname == ''){
            $this->error('产品分类名称不能为空！',$this->module_url.'/product_class/modify?cid='.$cid);
            
        }
        //检查分类名是否重复
        $r=$this->getModel('ProductClass')->where(['recycle'=>['=','0'],'pname'=>['=',$pname],'cid'=>['<>',$cid]])->fetchAll('cid');
        if ($r) {
            $this->error('产品分类 {$pname} 已经存在，请选用其他名称修改！',$this->module_url.'/product_class/modify?cid='.$cid);
        }

		/**
		 * 检查下级分类
		 */
		if($level>$level_old){
		    $get_children=$this->getModel('ProductClass')->getCount(['sid'=>['=',$cid],'level'=>['<=',$level]]);
		    if($get_children)
		        $this->error('下级分类存在同一级别或者小于新级别,选择小于'.$level.'的级别',$this->module_url.'/product_class/modify?cid='.$cid);
		}
        
		$level==0&&$sid=0;
	//	dump($this->getModel('ProductClass')->query("update lkt_product_class set pname='adfdd' where cid=3"));exit();
		$r=$this->getModel('ProductClass')->saveAll(['pname'=>$pname,'img'=>$image,'sid'=>$sid,'level'=>$level,'sort'=>$sort,'bg'=>$bg],['cid'=>['=',$cid]]);
		if($r ==false) {
    		$this->error('未知原因，修改产品分类失败！',$this->module_url."/product_class");
			
		} else {
			$this->success('修改产品分类成功！',$this->module_url."/product_class");
		}
		return;
	}

	

    private function str_option($level,$sid,$str_option,$num)
    {

        if($num > 0){
            $res=$this->getModel('ProductClass')->where(['recycle'=>['=','0'],'cid'=>['=',$sid]])->fetchAll('*');
            if($res){
                $sidc = $res[0]->sid; // 上级id
                $resc=$this->getModel('ProductClass')->where(['recycle'=>['=','0'],'sid'=>['=',$sidc]])->fetchAll('*');
                $str_option[$res[0]->cid] = $resc;
                $cnum = $num-1;
                return $this->str_option($level,$sidc,$str_option,$cnum);
            }else{
               return $str_option; 
            }
        }else{
               return $str_option;
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