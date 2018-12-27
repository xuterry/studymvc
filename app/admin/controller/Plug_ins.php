<?php
namespace app\admin\controller;

use core\Request;
use core\Session;

class Plug_ins extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        $uploadImg = $this->getUploadImg();       
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : 10;
        $page = $request->param('page');
        $r=$this->getModel('PlugIns')->order(['add_time'=>'desc'])->paginator($pagesize,$this->getUrlConfig($request->url));
        $pages_show=$r->render();
        if ($r) {
            foreach ($r as $k => $v) {
                $software_id=0;
                if (strstr($v->name, '优惠劵') == true) {
                    $v->http = 'coupon';
                }
                if (strstr($v->name, '钱包') == true) {
                    $v->http = 'finance';
                }
                if (strstr($v->name, '签到') == true) {
                    $v->http = 'sign';
                }
                if (strstr($v->name, '抽奖') == true) {
                    $v->http = 'draw';
                }
                if (strstr($v->name, '拆红包') == true) {
                    $v->http = 'dismantling_envelopes';
                }
                if (strstr($v->name, '砍价') == true) {
                    $v->http = 'bargain';
                }
                if (strstr($v->name, '拼团') == true) {
                    $v->http = 'go_group';
                }
                if (strstr($v->name, '发红包') == true) {
                    $v->http = 'red_packet';
                }
                if($v->software_id!=$software_id){
                    $software_id=$v->software_id;
                    $rr=$this->getModel('Software')->where(['id'=>['=',$software_id]])->fetchAll('id,name');
                }
                if(!empty($rr)){    
                    $v->software_id=$rr[0]->id;
                    $v->software_name=$rr[0]->name;
                }else{
                    $v->software_id=0;
                    $v->software_name='平台';
                }
            }
            
        }
        
        $this->assign("pages_show", $pages_show);
        $this->assign("uploadImg", $uploadImg);
        $this->assign("list", $r);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function add(Request $request)
    {
        $request->method() == 'post' && $this->do_add($request);
        
        $r=$this->getModel('Software')->where(['type'=>['=','0']])->fetchAll('id,name');
        if ($r) {
            $rew = '<option value="0" >全部</option>';
            $arr = json_decode(json_encode($r), true);
            $new_arr = array();
            foreach ($arr as $k => $v) {
                if (array_key_exists($v['name'], $new_arr)) {
                    $new_arr[$v['name']] = $new_arr[$v['name']] . ',' . $v['id'];
                } else {
                    $new_arr[$v['name']] = $v['id'];
                }
            }
            foreach ($new_arr as $key => $value) {
                $arr_list['id'] = $value;
                $arr_list['name'] = $key;
                $rew .= "<option  value='" . $arr_list['id'] . "'>" . $arr_list['name'] . "</option>";
            }
        }
        $this->assign('list', $rew);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_add($request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收数据
        $name = addslashes(trim($request->param('name'))); // 首页插件名称
        $subtitle_name = addslashes(trim($request->param('subtitle_name'))); // 个人中心插件名称
        $type = addslashes(trim($request->param('type'))); // 软件类型
        $software_id = addslashes(trim($request->param('software_id'))); // 软件id
        $image = addslashes(trim($request->param('image'))); // 首页插件图片
        $subtitle_image = addslashes(trim($request->param('subtitle_image'))); // 个人中心插件图片
        $url = addslashes(trim($request->param('url'))); // 首页链接
        $subtitle_url = addslashes(trim($request->param('subtitle_url'))); // 个人中心链接
        $sort = trim($request->param('sort')); // 排序
        
        if ($image) {
            $image = preg_replace('/.*\//', '', $image);
        } else {
            $this->error('首页插件图标不能为空！', '');
        }
        if ($subtitle_image) {
            $subtitle_image = preg_replace('/.*\//', '', $subtitle_image);
        } else {
            $subtitle_image = $image;
        }
        if ($software_id) {
            $software_id_1 = trim($software_id, ','); // 移除两侧的逗号
            $software_id_2 = explode(',', $software_id_1); // 字符串打散为数组
        } else {
            $this->error('请选择软件!', '');
        }
        
        if ($name) {
            foreach ($software_id_2 as $key => $value) {
                // 根据插件名称查询插件表
                $r=$this->getModel('PlugIns')->where(['name'=>['=',$name],'type'=>['=',$type],'software_id'=>['=',$value]])->fetchAll('*');
                if ($r) {
                    $this->error('首页插件".$name."已存在!', '');
                }
                  
            }
        } else {
            $this->error('首页插件名称不能为空!', '');
        }
        
        if ($subtitle_name) {
            foreach ($software_id_2 as $key => $value) {
                // 根据插件名称查询插件表
                $r=$this->getModel('PlugIns')->where(['subtitle_name'=>['=',$subtitle_name],'type'=>['=',$type],'software_id'=>['=',$value]])->fetchAll('*');
                if ($r) {
                    $this->error('个人中心插件".$subtitle_name."已存在!', '');
                }
            }
        } else {
            $subtitle_name = $name;
        }
        if (empty($url)) {
            $this->error('首页链接不能为空！', '');
        }
        if (empty($subtitle_url)) {
            $subtitle_url = $url;
        }
        if (floor($sort) == $sort) {
            // 添加插件
            $r=$this->getModel('PlugIns')->insert(['name'=>$name,'software_id'=>$software_id,'subtitle_name'=>$subtitle_name,'type'=>$type,'image'=>$image,'subtitle_image'=>$subtitle_image,'url'=>$url,'subtitle_url'=>$subtitle_url,'add_time'=>nowDate(),'sort'=>$sort,'status'=>0]);
            if ($r == false) {
                $this->recordAdmin($admin_id, ' 添加插件失败 ', 1);
                
                $this->error('未知原因，添加失败！', '');
            } else {
                $this->recordAdmin($admin_id, ' 添加插件 ' . $name, 1);
                
                $this->success('添加成功！', $this->module_url . "/plug_ins");
            }
        } else {
            $this->error('排序不为整数！', '');
        }
        
        return;
    }

    public function ajax(Request $request)
    {
        $type = addslashes(trim($request->param('type')));
        
        $r=$this->getModel('Software')->where(['type'=>['=',$type]])->fetchAll('id,name');
        foreach ($r as $key => $value) {
            $res = '<option value="" >全部</option>';
            foreach ($r as $key => $value) {
                $res .= "<option value='{$value->id}'>{$value->name}</option>";
            }
            echo $res;
            exit();
        }
        return;
    }

    public function del(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收信息
        $id = intval($request->param('id')); // 插件id
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片路径
                                                                     
        // 根据插件id,查询插件
        $r = $this->getModel('PlugIns')->get($id, 'id');
        $image = $r[0]->image;
        $subtitle_image = $r[0]->subtitle_image;
        // @unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$image));
        // @unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$subtitle_image));
        // 根据轮播图id，删除轮播图信息
        $res=$this->getModel('PlugIns')->delete($id,'id');
        
        $this->recordAdmin($admin_id, ' 删除插件id为 ' . $id . ' 的信息', 3);
        echo $res;
        exit();
        $this->success('删除成功！', $this->module_url . "/plug_ins");
        return;
    }

    public function modify(Request $request)
    {
        $request->method() == 'post' && $this->do_modify($request);
        
        // 接收信息
        $id = intval($request->param("id")); // 插件id
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        $http = addslashes(trim($request->param('http'))); // 图片上传位置
        $http = $this->module_url . "/$http/config";
        
        // 根据插件id，查询插件信息
        $r = $this->getModel('PlugIns')->get($id, 'id');
        if ($r) {
            $software_id = $r[0]->software_id; // 软件id
            $name = $r[0]->name; // 首页插件名称
            $subtitle_name = $r[0]->subtitle_name; // 个人中心插件名称
            $type = $r[0]->type; // 软件类型
            $image = $r[0]->image; // 首页图标
            $subtitle_image = $r[0]->subtitle_image; // 个人中心图标
            $url = $r[0]->url; // 首页链接
            $subtitle_url = $r[0]->subtitle_url; // 个人中心链接
            $sort = $r[0]->sort; // 排序
        }
        $rr=$this->getModel('Software')->where(['id'=>['=',$software_id]])->fetchAll('id,name');
        if($rr)
        $software_name = $rr[0]->name;
        else 
           $software_name='平台';
        $rew = "<option value='$software_id' >$software_name</option>";
        
        $rrr=$this->getModel('Software')->where(['type'=>['=',$type]])->fetchAll('id,name');
        if ($rrr) {
            $rew .= '<option value="0" >全部</option>';
            $arr = json_decode(json_encode($rrr), true);
            $new_arr = array();
            foreach ($arr as $k => $v) {
                if (array_key_exists($v['name'], $new_arr)) {
                    $new_arr[$v['name']] = $new_arr[$v['name']] . ',' . $v['id'];
                } else {
                    $new_arr[$v['name']] = $v['id'];
                }
            }
            foreach ($new_arr as $key => $value) {
                $arr_list['id'] = $value;
                $arr_list['name'] = $key;
                $rew .= "<option  value='" . $arr_list['id'] . "'>" . $arr_list['name'] . "</option>";
            }
        }
        
        $this->assign('id', $id);
        $this->assign('uploadImg', $uploadImg);
        $this->assign('name', $name);
        $this->assign('subtitle_name', $subtitle_name);
        $this->assign('type', $type);
        $this->assign("image", $image);
        $this->assign("subtitle_image", $subtitle_image);
        $this->assign('url', $url);
        $this->assign('subtitle_url', $subtitle_url);
        $this->assign('sort', $sort);
        $this->assign('http', $http);
        $this->assign('list', $rew);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收信息
        $id = intval($request->param('id'));
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        $name = addslashes(trim($request->param('name'))); // 首页插件名称
        $subtitle_name = addslashes(trim($request->param('subtitle_name'))); // 个人中心插件名称
        $type = addslashes(trim($request->param('type'))); // 软件类型
        $software_id = addslashes(trim($request->param('software_id'))); // 软件id
        $image = addslashes($request->param('image')); // 新首页插件图标
        $oldpic1 = addslashes($request->param('oldpic1')); // 原首页插件图标
        $subtitle_image = addslashes($request->param('subtitle_image')); // 新个人中心插件图标
        $oldpic2 = addslashes($request->param('oldpic2')); // 原个人中心插件图标
        $url = addslashes(trim($request->param('url'))); // 首页链接
        $subtitle_url = addslashes(trim($request->param('subtitle_url'))); // 个人中心链接
        $sort = trim($request->param('sort')); // 排序
        
        if ($image) {
            $image = preg_replace('/.*\//', '', $image);
            if ($image != $oldpic1) {
                // @unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$oldpic1));
            }
        } else {
            $image = $oldpic1;
        }
        if ($subtitle_image) {
            $subtitle_image = preg_replace('/.*\//', '', $subtitle_image);
            if ($subtitle_image != $oldpic2) {
                // @unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$oldpic2));
            }
        } else {
            $subtitle_image = $oldpic2;
        }
        if ($software_id) {
            $software_id_1 = trim($software_id, ','); // 移除两侧的逗号
            $software_id_2 = explode(',', $software_id_1); // 字符串打散为数组
        } else {
            $this->error('请选择软件!', '');
        }
        if ($name) {
            foreach ($software_id_2 as $key => $value) {
                // 根据插件名称查询插件表
                $r=$this->getModel('PlugIns')->where(['name'=>['=',$name],'id'=>['<>',$id],'type'=>['=',$type],'software_id'=>['=',$value]])->fetchAll('*');
                if ($r) {
                    $this->error('首页插件".$name."已存在!', $this->module_url.'/plug_ins/modify?id='.$id);
                } 
            }
        } else {
            $this->error('首页插件名称不能为空!', '');
        }
        if ($subtitle_name) {
            foreach ($software_id_2 as $key => $value) {
                // 根据插件名称查询插件表
                $r=$this->getModel('PlugIns')->where(['subtitle_name'=>['=',$subtitle_name],'id'=>['<>',$id],'type'=>['=',$type],'software_id'=>['=',$value]])->fetchAll('*');
                if ($r) {
                    $this->error('个人中心插件".$subtitle_name."已存在!', $this->module_url.'/plug_ins/modify?id='.$id);
                } 
            }
        } else {
            $subtitle_name = $name;
        }
        if (empty($url)) {
            $this->error('首页链接不能为空！', $this->module_url.'/plug_ins/modify?id='.$id);
        }
        if (empty($subtitle_url)) {
            $subtitle_url = $url;
        }
        if (floor($sort) == $sort) {
            // 更新数据表
            $r=$this->getModel('PlugIns')->saveAll(['image'=>$image,'subtitle_image'=>$subtitle_image,'url'=>$url,'subtitle_url'=>$subtitle_url,'name'=>$name,'subtitle_name'=>$subtitle_name,'add_time'=>nowDate(),'sort'=>$sort,'type'=>$type,'software_id'=>$software_id],['id'=>['=',$id]]);
            
            if ($r == false) {
                $this->recordAdmin($admin_id, ' 修改插件id为 ' . $id . ' 的信息失败 ', 2);
                
                $this->error('未知原因，修改失败！', $this->module_url . "/plug_ins");
            } else {
                $this->recordAdmin($admin_id, ' 修改插件id为 ' . $id . ' 的信息 ', 2);
                
                $this->success('修改成功！', $this->module_url . "/plug_ins");
            }
        } else {
            $this->error('排序不为整数！', $this->module_url.'/plug_ins/modify?id='.$id);
        }
        return;
    }

    private function do_red_packet_modify($request)
    {
        $admin_id = Session::get('admin_id');
        
        $id = $request->param('id');
        $data['name'] = $request->param('name'); // 活动名称
        $data['bizhi'] = $request->param('bizhi'); // 红包金额与数量比值
        $data['send_redpacket'] = $request->param('send_redpacket'); // 可以发送红包个数
        $data['receive_redpacket'] = $request->param('receive_redpacket'); // 可以领取红包个数
        $data['bili'] = $request->param('bili'); // 红包抵用比例
        $data['tixian'] = $request->param('tixian'); // 红包是否可以提现
        $data['shixiao_time'] = $request->param('shixiao_time'); // 红包链接失效时间
        $data['shixiao_time1'] = $request->param('shixiao_time1'); // 红包失效时间
        $data['tixian_money'] = $request->param('tixian_money'); // 最小提现金额
        if (empty($data['name'])) {
            $this->error('活动名称不能为空！', $this->module_url . "/plug_ins/red_packet_modify");
        }
        if (empty($data['send_redpacket'])) {
            $this->error('请设置可以发送红包个数', $this->module_url . "/plug_ins/red_packet_modify");
        }
        
        if (empty($data['bizhi'])) {
            $this->error('请填写红包金额与数量比值', $this->module_url . "/plug_ins/red_packet_modify");
        }
        
        if (empty($data['receive_redpacket'])) {
            $this->error('请设置可以领取红包个数', $this->module_url . "/plug_ins/red_packet_modify");
        }
        
        if ($data['bili'] == '') {
            $this->error('请填写红包抵用比例', $this->module_url . "/plug_ins/red_packet_modify");
        }
        if ($data['bili'] > 100) {
            $this->error('抵用比例只能为0-100的正整数', $this->module_url . "/plug_ins/red_packet_modify");
        }
        if ($data['bili'] < 0) {
            $this->error('抵用比例只能为0-100的正整数', $this->module_url . "/plug_ins/red_packet_modify");
        }
        if (empty($data['tixian'])) {
            $this->error('请选择是否可以提现', $this->module_url . "/plug_ins/red_packet_modify");
        }
        if ($data['shixiao_time'] < 0) {
            $this->error('请设置红包链接失效时间', $this->module_url . "/plug_ins/red_packet_modify");
        }
        if (empty($data['shixiao_time1'])) {
            $this->error('请设置红包失效时间', $this->module_url . "/plug_ins/red_packet_modify");
        }
        if (empty($data['tixian_money'])) {
            $this->error('请设置红包提现的最小金额', $this->module_url . "/plug_ins/red_packet_modify");
        }
        $info = serialize($data);
        if (! empty($id)) {
            
            $r = $this->getModel('RedPacketConfig')->saveAll([
                                                                'sets' => $info
            ], [
                    'id' => [
                                '=',$id
                    ]
            ]);
            
            if ($r > 0) {
                $this->recordAdmin($admin_id, ' 修改拆红包参数 ', 2);
                
                $this->success('修改成功！', $this->module_url . "/plug_ins/red_packet_modify");
            }
        } else {
            $r = $this->getModel('RedPacketConfig')->insert([
                                                                'sets' => $info
            ]);
            
            if ($r > 0) {
                $this->recordAdmin($admin_id, ' 添加拆红包参数 ', 1);
                
                $this->success('添加成功！', $this->module_url . "/plug_ins/red_packet_modify");
            }
        }
    }

    public function red_packet_modify(Request $request)
    {
        $r_1 = $this->getModel('RedPacketConfig')->fetchAll();
        if (! empty($r_1)) {
            $re01 = unserialize($r_1[0]->sets);
            $id = $r_1[0]->id;
        } else {
            $re01 = 1;
            $id = "";
        }
        
        $this->assign("re", $re01);
        $this->assign("id", $id);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function whether(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收信息
        $id = intval($request->param('id')); // 插件id
                                             // 根据插件id,查询查询状态
        $r=$this->getModel('PlugIns')->where(['id'=>['=',$id]])->fetchAll('status');
        
        if ($r[0]->status == 1) {
            $res=$this->getModel('PlugIns')->saveAll(['status'=>0],['id'=>['=',$id]]);
            $this->recordAdmin($admin_id, ' 禁用插件id为 ' . $id, 5);
            echo $res;
            exit();
        } else {
            $res=$this->getModel('PlugIns')->saveAll(['status'=>1],['id'=>['=',$id]]);
            
            $this->recordAdmin($admin_id, ' 启用插件id为 ' . $id, 5);
            echo $res;
            exit();
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