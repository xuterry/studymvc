<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Subtraction extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    private function do_Index($request)
    {
        
        

        $status = $request->param('status'); // 包邮状态
        $man_money = $request->param('man_money'); // 单笔满多少包邮
        $region = $request->param('region'); // 不参与包邮地区
        $man = $request->param('man'); // 满多少
        $jian = $request->param('jian'); // 减多少
        $list = array();

        foreach ($man as $k => $v){
            if($man[$k] != '' && $jian[$k] != ''){
                if($man[$k] != 0 && $man[$k] > $jian[$k] && $man[$k] > 0 && $jian[$k] >= 0){
                    $list[][$man[$k]] = $jian[$k];
                }
            }
        }
        $subtraction = serialize($list);

        $r=$this->getModel('Subtraction')->where(['id'=>['=','1']])->fetchAll('id');
        if($r){
            $rr=$this->getModel('Subtraction')->saveAll(['status'=>$status,'man_money'=>$man_money,'region'=>$region,'subtraction'=>$subtraction],['id'=>['=','1']]);
        }else{
            $rr=$this->getModel('Subtraction')->insert(['status'=>$status,'man_money'=>$man_money,'region'=>$region,'subtraction'=>$subtraction,'add_date'=>nowDate()]);
        }
        if($r ==false) {
            $this->error('未知原因，修改失败！',$this->module_url."/subtraction");
            
        } else {
            $this->success('修改成功！',$this->module_url."/subtraction");
        }
    }

    
    public function Index(Request $request)
    {
        
        

        $r=$this->getModel('Subtraction')->get('1','id');
        if($r){
            $status = $r[0]->status; // 包邮状态
            $man_money = $r[0]->man_money; // 单笔满多少包邮
            $region = $r[0]->region; // 不参与包邮地区
            $subtraction = unserialize ($r[0]->subtraction); // 满减
            $num  = count($subtraction);
        }

        $this->assign('status', isset($status) ? $status : '');
        $this->assign('man_money', isset($man_money) ? $man_money : '100');
        $this->assign("region", isset($region) ? $region : '');
        $this->assign('subtraction', isset($subtraction) ? $subtraction : '');
        $this->assign('num', isset($num) ? $num : '1');

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function province(Request $request)
    {
       $request->method()=='post'&&$this->do_province($request);

        
        

        $sql = "select GroupID,G_CName from lkt_admin_cg_group where G_ParentID = 0";
        $r = $db->select($sql);

        $list = $r;

        $res = array('status' => '1','list'=>$list,'info'=>'成功！');
        echo json_encode($res);

        return;
	}

	
    private function do_province($request)
    {
		
		

        $check_val = $request->param('check_val'); // 属性
        $G_CName = '';
        foreach ($check_val as $k => $v){
            $sql = "select G_CName from lkt_admin_cg_group where GroupID = '$v'";
            $r = $db->select($sql);
            if($r){
                $G_CName .= $r[0]->G_CName . ',';
            }
        }
        $name = rtrim($G_CName, ',');
        $res = array('status' => '1','name'=>$name,'info'=>'成功！');
        echo json_encode($res);

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