<?php
namespace app\api\controller;
use core\Request;

class Address extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function index (Request $request)
    {
                  // 获取信息
        $openid = $request['openid']; // 微信id
                                    // 根据微信id,查询用户id
        $user_id = $this->getUserId($openid);
        // 根据用户id,查询地址表
        $r=$this->getModel('UserAddress')->where(['uid'=>['=',$user_id]])->fetchAll('*');
        
        echo json_encode(array(
                                'adds' => $r
        ));
        exit();
        return;
    }

    public function del_select (Request $request)
    {
        
        
        $openid = trim($request->param('openid')); // 微信id
        $arr = trim($request->param('id_arr')); // 微信id
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
        if ($r) {
            $user_id = $r[0]->user_id;
            
            if (! empty($arr)) {
                $arrid = explode(',', $arr);
                // print_r($arrid);die;
                foreach ($arrid as $key => $value) {
                    if ($value != '') {
                        $r=$this->getModel('UserAddress')->delete($value,'id');
                    }
                }
                echo json_encode(array(
                                        'status' => 1,'succ' => '删除成功!'
                ));
                exit();
            }
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '删除失败!'
            ));
            exit();
        }
    }

    public function set_default (Request $request)
    {
          
        // 获取信息
        $openid = $request['openid']; // 微信id
        $addr_id = $request['addr_id']; // 地址id
                                      // 根据微信id,查询用户id
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
        if ($r) {
            $user_id = $r[0]->user_id;
            $r=$this->getModel('UserAddress')->saveAll(['is_default'=>0],['uid'=>['=',$user_id]]);
            $rr=$this->getModel('UserAddress')->saveAll(['is_default'=>1],['uid'=>['=',$user_id],'id'=>['=',$addr_id]]);
        } else {
            $rr = 0;
        }
        
        if ($rr > 0) {
            echo json_encode(array(
                                    'status' => 1,'err' => '操作成功!'
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '设置失败'
            ));
            exit();
        }
        return;
    }

    public function del_adds (Request $request)
    {
        
        
        // 获取信息
        $openid = $request['openid']; // 微信id
        $id_arr = $request['id_arr']; // 地址id
                                    // 根据微信id,查询用户id
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
        if ($r) {
            $user_id = $r[0]->user_id;
            // 根据用户id,查询地址表
            $r=$this->getModel('UserAddress')->delete($id_arr,'id');
            if ($r > 0) {
                echo json_encode(array(
                                        'status' => 1
                ));
                exit();
            } else {
                echo json_encode(array(
                                        'status' => 0,'err' => '删除失败'
                ));
                exit();
            }
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '删除失败'
            ));
            exit();
        }
        
        return;
    }

    public function up_adds (Request $request)
    {
        
        
        // 获取信息
        $openid = $request['openid']; // 微信id
        $id_arr = $request['id_arr']; // 地址id
        
        $user_name = $request['user_name']; // 联系人
        $mobile = $request['mobile']; // 联系电话
        $province = $request['province']; // 省
        $city = $request['city']; // 市
        $county = $request['county']; // 县
        $address = $request['address']; // 详细地址
        
        $r=$this->getModel('UserAddress')->where(['id'=>['=',$id_arr]])->fetchAll('*');
        $code = 0; //
        $uid = $r[0]->uid; // 用户ID
        $is_default = $r[0]->is_default; // 是否默认地址
                                         
        // 查询省的编号
        $r01=$this->getModel('AdminCgGroup')->where(['G_CName'=>['=',$province]])->fetchAll('GroupID');
        $sheng = $r01[0]->GroupID;
        // 查询市的编号
        $r02=$this->getModel('AdminCgGroup')->where(['G_CName'=>['=',$city]])->fetchAll('GroupID');
        $shi = $r02[0]->GroupID;
        // 查询县的编号
        $r03=$this->getModel('AdminCgGroup')->where(['G_CName'=>['=',$county]])->fetchAll('GroupID');
        $xian = $r03[0]->GroupID;
        $address_xq = $province . $city . $county . $address; // 带省市县的详细地址
        if (preg_match("/^1[34578]\d{9}$/", $mobile)) {
            $r04=$this->getModel('UserAddress')->saveAll(['name'=>$user_name,'tel'=>$mobile,'sheng'=>$sheng,'city'=>$shi,'quyu'=>$xian,'address'=>$address,'address_xq'=>$address_xq,'code'=>$code,'uid'=>$uid,'is_default'=>$is_default],['id'=>['=',$id_arr]]);
            if ($r04 == 1) {
                echo json_encode(array(
                                        'status' => 1,'info' => '修改成功！'
                ));
            } else {
                echo json_encode(array(
                                        'status' => 0,'info' => '修改失败！'
                ));
            }
        } else {
            echo json_encode(array(
                                    'status' => 0,'info' => '手机号码有误！'
            ));
        }
        exit();
        return;
    }

    public function up_addsindex (Request $request)
    {
        
        
        // 获取信息
        $openid = $request['openid']; // 微信id
        $id_arr = $request['id_arr']; // 地址id
        $r=$this->getModel('UserAddress')->where(['id'=>['=',$id_arr]])->fetchAll('*');
        if ($r) {
            $sheng = $r[0]->sheng; // 省
            $city = $r[0]->city; // 市
            $quyu = $r[0]->quyu; // 县
                                 
            // 查询省的编号
            $r01=$this->getModel('AdminCgGroup')->where(['GroupID'=>['=',$sheng]])->fetchAll('G_CName');
            if ($r01) {
                $province = $r01[0]->G_CName;
            } else {
                $province = '';
            }
            $province = $r01[0]->G_CName;
            // 根据省查询市
            $r02=$this->getModel('AdminCgGroup')->where(['GroupID'=>['=',$city]])->fetchAll('G_CName');
            if ($r02) {
                $city = $r02[0]->G_CName;
            } else {
                $city = '';
            }
            
            // 根据市查询县
            $r03=$this->getModel('AdminCgGroup')->where(['GroupID'=>['=',$quyu]])->fetchAll('G_CName');
            if ($r03) {
                $county = $r03[0]->G_CName;
            } else {
                $county = '';
            }
            
            echo json_encode(array(
                                    'adds' => $r,'province' => $province,'city' => $city,'county' => $county
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'info' => '手机号码有误！'
            ));
        }
        
        return;
    }

}