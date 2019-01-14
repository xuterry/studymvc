<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Freight extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    public function index(Request $request)
    {       
        $name = addslashes(trim($request->param('name'))); // 标题     
        // 导出
        $pagesize = $request -> param('pagesize');
        $pagesize = $pagesize ? $pagesize:'10';
        // 每页显示多少条数据
        $condition = ' 1=1 ';
        if($name != ''){
            $condition .= " and name like '%$name%' ";
        }
        $r=$this->getModel('Freight')->where($condition)
        ->order(['add_time'=>'desc'])
        ->paginator($pagesize,$this->getUrlConfig($request->url));
        if($r){
            $list = $r;
        }else{
            $list = [];
        }
        $pages_show=$r->render();
        $this->assign("name", $name);
        $this->assign("list", $list);
        $this -> assign('pages_show', $pages_show);
        $this -> assign('pagesize', $pagesize);
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
        
    }
    public function add(Request $request)
    {
       $request->method()=='post'&&$this->do_add($request);

		return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
	}

	
    private function do_add($request)
    {
		
		
        $admin_id = Session::get('admin_id');

        // 接收数据
        $name = addslashes(trim($request->param('name'))); // 规则名称
        $type = addslashes(trim($request->param('type'))); // 类型
        $hidden_freight = $request->param('hidden_freight'); // 运费信息

        if($hidden_freight){
            $freight_list = json_decode($hidden_freight,true);
            $freight = serialize($freight_list);
        }else{
            $freight = '';
        }

		if($name == ''){
            $this->error('规则名称不能为空！',$this->module_url."/freight/add");
            
        }else{
            $r=$this->getModel('Freight')->fetchAll();
            if($r){
                foreach ($r as $k => $v){
                    if($name == $v->name){
                        $this->error('规则名称'.$name.'存在，请选用其他名称！',$this->module_url."/freight/add");
                        
                    }
                }
            }
        }

        // 添加规则
        $rr=$this->getModel('Freight')->insert(['name'=>$name,'type'=>$type,'freight'=>$freight,'is_default'=>0,'add_time'=>nowDate()]);
        if($rr > 0){
            $this->recordAdmin($admin_id,' 添加规则 '.$name,1);

            $this->success('规则添加成功！',$this->module_url."/freight");
            
        }else{
            $this->recordAdmin($admin_id,' 添加规则失败',1);

            $this->error('未知原因，规则添加失败！',$this->module_url."/freight");
            
        }
	    return;
	}

	
    public function del(Request $request)
    {
        
        
        $admin_id = Session::get('admin_id');

        // 接收信息
        $id = $request->param('id'); // 产品id

        $id = rtrim($id, ','); // 去掉最后一个逗号
        $id = explode(',',$id); // 变成数组

        foreach ($id as $k => $v){
            $r=$this->getModel('ProductList')->where(['freight'=>['=',$v]])->fetchAll('id');
            if($r){
                $update_rs=$this->getModel('ProductList')->saveAll(['freight'=>0],['id'=>['=',$r[0]->id]]);
            }
            // 根据产品id，删除产品信息
            $delete_rs=$this->getModel('Freight')->delete($v,'id');

            $this->recordAdmin($admin_id,' 删除规则id为 '.$v.' 的信息',3);
        }

        $res = array('status'=>1,'info'=>'成功！');
        echo json_encode($res);
        return;
    }

    
    public function is_default(Request $request)
    {
        
        
        $admin_id = Session::get('admin_id');

        // 接收信息
        $id = $request->param('id'); // 产品id

        $r=$this->getModel('Freight')->fetchAll('id,is_default');
        if($r){
            $y_id = 0;
            foreach ($r as $k => $v){
                $is_default = $v->is_default;

                if($is_default == 1){
                    $y_id = $v->id;
                }
            }
            if($y_id != 0){
                if($y_id == $id){
                    $update_rs=$this->getModel('Freight')->saveAll(['is_default'=>0],['id'=>['=',$id]]);
                }else{
                    $update_rs=$this->getModel('Freight')->saveAll(['is_default'=>0],['1'=>['=','1']]);

                    $update_rs=$this->getModel('Freight')->saveAll(['is_default'=>1],['id'=>['=',$id]]);
                }
            }else{
                $update_rs=$this->getModel('Freight')->saveAll(['is_default'=>1],['id'=>['=',$id]]);
            }
            $this->recordAdmin($admin_id,' 修改规则id为 '.$id.' 的状态 ',2);

        }
        $res = array('status' => '1','info'=>'成功！');
        echo json_encode($res);
        return;
    }

    
    public function modify(Request $request)
    {
       $request->method()=='post'&&$this->do_modify($request);      

        // 接收信息
        $id = intval($request->param("id")); // 产品id
        $r=$this->getModel('Freight')->get($id,'id');
        if($r){
            $name = $r[0]->name; // 规则名称
            $type = $r[0]->type; // 规则类型
            $freight = unserialize($r[0]->freight); // 属性
            $res = '';
            foreach ($freight as $k => $v){
                $k1 = $k + 1;
                $res .= "<tr class='tr_freight_num' id='tr_freight_$k1'>" .
                    "<td>".$v['one']."</td>" .
                    "<td>".$v['two']."</td>" .
                    "<td>".$v['three']."</td>" .
                    "<td>".$v['four']."</td>" .
                    "<td>".$v['name']."</td>" .
                    "<td><span class='btn btn-secondary radius' onclick='freight_del($k1)' >删除</span></td>" .
                    "</tr>";
            }
            $freight = json_encode($freight);
        }
        $this->assign("id",$id);
        $this->assign("name",$name);
        $this->assign("type",$type);
        $this->assign("freight",$freight);
        $this->assign("list",$res);

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
	}

	
    private function do_modify($request)
    {
		
		
        $admin_id = Session::get('admin_id');

        // 接收数据
        $id = addslashes(trim($request->param('id'))); // 规则id
        $name = addslashes(trim($request->param('name'))); // 规则名称
        $type = addslashes(trim($request->param('type'))); // 类型
        $hidden_freight = $request->param('hidden_freight'); // 运费信息
        if($hidden_freight){
            $freight_list = json_decode($hidden_freight,true);
            $freight = serialize($freight_list);
        }else{
            $freight = '';
        }
        if($name == ''){
            $this->error('规则名称不能为空！',$this->module_url."/freight/add");
            
        }else{
            $r=$this->getModel('Freight')->where('id','<>',$id)->fetchAll();
            if($r){
                foreach ($r as $k => $v){
                    if($name == $v->name){
                        $this->error('规则名称 {$name} 已经存在，请选用其他名称！',$this->module_url."/freight/add");
                        
                    }
                }
            }
        }

        $rr=$this->getModel('Freight')->saveAll(['name'=>$name,'type'=>$type,'freight'=>$freight],['id'=>['=',$id]]);
        if($rr > 0){
            $this->recordAdmin($admin_id,' 修改规则id为 '.$id.' 的信息 ',2);

            $this->success('规则修改成功！',$this->module_url."/freight");
        }else{
            $this->recordAdmin($admin_id,' 修改规则id为 '.$id.' 失败 ',2);

            $this->error('未知原因，规则修改失败！',$this->module_url."/freight");
            
        }
		exit();
	}

	
    public function province(Request $request)
    {
       $request->method()=='post'&&$this->do_province($request);       

        $data = $request->param('data');
        $r=$this->getModel('AdminCgGroup')->where(['G_ParentID'=>['=','0']])->fetchAll('GroupID,G_CName');
        foreach ($r as $k => $v){
            $res[$v->GroupID] = $v->G_CName; // 省名称数组
        }
        if($data){ // 有运费信息
            $arr = json_decode($data,true);
            foreach ($arr as $k => $v){
                $arr1 = $v['name']; // 获取该运费下的省信息
                $arr2[] = explode(',',$arr1);  // 转数组
            }

            foreach($arr2 as $k1=>$v1) {
                foreach ($v1 as $key => $val) {
                    $arr3[] = $val; // 二维数组转以为数组
                }
            }

            foreach ($arr3 as $v2) {
                if (in_array($v2, $res)) { // 判断字符串是否存在数组里
                    foreach ($res as $k3 => $v3){ // 存在，循环数组
                        if($v3 == $v2){ // 当数组中的值与字符串相等，删除这个元素
                            unset($res[$k3]);
                        }
                    }
                }
            }
            if($res != []){
                foreach ($res as $k4 => $v4){
                    $list[] = array('GroupID'=>$k4,'G_CName'=>$v4);
                }
            }else{
                $list = array();
                $res = array('status' => '0','info'=>'已经包含所有省份！');
                echo json_encode($res);
                return;
            }
        }else{
            $list = $r;
        }
        $res = array('status' => '1','list'=>$list,'info'=>'成功！');
        echo json_encode($res);

        return;
	}

	
    private function do_province($request)
    {
        $check_val = $request->param('check_val'); // 属性
        $G_CName = '';
        if(empty($check_val))
            exitJson(['status'=>0]);
        foreach ($check_val as $k => $v){
            $r=$this->getModel('AdminCgGroup')->where(['GroupID'=>['=',$v]])->fetchAll('G_CName');
            if($r){
                $G_CName .= $r[0]->G_CName . ',';
            }
        }
        $name = rtrim($G_CName, ',');
        $res = array('status' => '1','name'=>$name,'info'=>'成功！');
        echo json_encode($res);

	    exit();
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