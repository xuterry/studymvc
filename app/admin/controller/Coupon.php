<?php
namespace app\admin\controller;

use core\Request;
use core\Session;

class Coupon extends Index
{
    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        $activity_type = addslashes(trim($request->param('activity_type'))); // 活动类型
        $name = addslashes(trim($request->param('name'))); // 标题
        
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : '10';
        // 每页显示多少条数据
        $page = $request->param('page');
        
        
        $r_1 = $this->getModel('CouponConfig')->get('1', 'id');
        
        $activity_overdue = $r_1[0]->activity_overdue; // 活动过期删除时间
        $condition = ' recycle = 0 ';
        if ($activity_type != '' && $activity_type != 0) {
            $condition .= " and activity_type = '$activity_type'";
        }
        
        if ($name != '') {
            $condition .= " and name like '%$name%'";
        }
        
        $r=$this->getModel('CouponActivity')->where($condition)->order(['add_time'=>'desc'])
        ->paginator($pagesize);
        $list = array();
        $time = date('Y-m-d H:i:s'); // 当前时间
        if ($r) {
            foreach ($r as $k => $v) {
                $id = $v->id; // 活动id
                $activity_type1 = $v->activity_type; // 活动类型
                
                if ($activity_type1 == 1) {
                    $v->activity_type = '注册';
                    $v->end_time = '永久有效';
                } else if ($activity_type1 == 2) {
                    $v->activity_type = '节日/活动';
                } else if ($activity_type1 == 3) {
                    $v->activity_type = '满减';
                }
                $time_1 = date("Y-m-d H:i:s", strtotime("+$activity_overdue day", strtotime($v->end_time))); // 活动过期删除时间
                                                                                                           
                // 当前时间大于活动结束时间
                if ($v->end_time < $time && $activity_type1 != 1) {
                    // 根据id,修改活动状态
                    $update_rs=$this->getModel('CouponActivity')->saveAll(['status'=>3],['id'=>['=',$id]]);
                    $v->status = 3;
                }
                // 当前时间大于活动过期删除时间,删除这条数据
                if ($time_1 < $time && $activity_type1 != 1) {
                    $update_rs=$this->getModel('CouponActivity')->saveAll(['recycle'=>1],['id'=>['=',$id]]);
                }
                $rr=$this->getModel('Coupon')->where(['hid'=>['=',$id]])->fetchAll('id');
                $v->num = count($rr);
            }
            $list = $r;
        }
        
        $pages_show = $r->render();
        
        $this->assign("activity_type", $activity_type);
        $this->assign("name", $name);
        $this->assign("list", $list);
        $this->assign('pages_show', $pages_show);
        $this->assign('pagesize', $pagesize);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function add(Request $request)
    {
        $request->method() == 'post' && $this->do_add($request);
        
        $r=$this->getModel('ProductClass')->where(['sid'=>['=','0']])->fetchAll('cid,pname');
        
        $res = '<option value="0" >全部</option>';
        foreach ($r as $key => $value) {
            $c = '-' . $value->cid . '-';
            $res .= '<option  value="-' . $value->cid . '-">' . $value->pname . '</option>';
            // 循环第一层
            $r_e=$this->getModel('ProductClass')->where(['sid'=>['=',$value->cid]])->fetchAll('cid,pname');
            if ($r_e) {
                $hx = '-----';
                foreach ($r_e as $ke => $ve) {
                    $cone = $c . $ve->cid . '-';
                    $res .= '<option  value="' . $cone . '">' . $hx . $ve->pname . '</option>';
                    // 循环第二层
                    $r_t=$this->getModel('ProductClass')->where(['sid'=>['=',$ve->cid]])->fetchAll('cid,pname');
                    if ($r_t) {
                        $hxe = $hx . '-----';
                        foreach ($r_t as $k => $v) {
                            $ctow = $cone . $v->cid . '-';
                            $res .= '<option  value="' . $ctow . '">' . $hxe . $v->pname . '</option>';
                        }
                    }
                }
            }
        }
        
        $this->assign("list", $res);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_add($request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收数据
        $name = addslashes(trim($request->param('name'))); // 活动名称
        $activity_type = addslashes(trim($request->param('activity_type'))); // 活动类型
        $product_class_id = addslashes(trim($request->param('product_class_id'))); // 活动指定商品类型
        $product_id = addslashes(trim($request->param('product_id'))); // 活动指定商品
        $money = addslashes(trim($request->param('money'))); // 金额
        $z_money = addslashes(trim($request->param('z_money'))); // 总金额
        $num = addslashes(trim($request->param('num'))); // 数量
        $start_time = $request->param('start_time'); // 活动开始时间
        $end_time = $request->param('end_time'); // 活动结束时间
        if ($name == '') {
            $this->error('活动名称不能为空！', '');
        }
        
        // 检查产品标题是否重复
        $r=$this->getModel('CouponActivity')->where(['name'=>['=',$name]])->fetchAll('1');
        if ($r && count($r) > 0) {
            $this->error('{$name} 活动名称已经存在！', '');
        }
        
        if ($money == '') {
            $this->error('金额不能为空！', '');
        }
        if ($num == '' || $num <= 0) {
            $num = 99999999999;
        }
        
        if ($start_time == '' && $activity_type != 1) {
            $this->error('活动开始时间不能为空！', '');
        }
        
        if ($end_time == '' && $activity_type != 1) {
            $this->error('活动结束时间不能为空！', '');
        }
        
        if ($start_time >= $end_time && $activity_type != 1) {
            $this->error('活动开始时间不能大于等于活动结束时间！', '');
        }
        
        $time = date('Y-m-d H:i:s');
        if ($time >= $end_time && $activity_type != 1) {
            $this->error('活动还没开始就已经结束！', '');
        }
        if ($activity_type == 1) {
            // 添加活动
            $rr=$this->getModel('CouponActivity')->insert(['name'=>$name,'activity_type'=>$activity_type,'product_class_id'=>$product_class_id,'product_id'=>$product_id,'money'=>$money,'num'=>$num,'add_time'=>nowDate(),'start_time'=>$time,'status'=>1]);
        } else {
            // 活动开始时间大于当前时间,活动还没开始
            if ($start_time > $time) {
                if ($activity_type == 2) {
                    // 添加活动
                    $rr=$this->getModel('CouponActivity')->insert(['name'=>$name,'activity_type'=>$activity_type,'product_class_id'=>$product_class_id,'product_id'=>$product_id,'money'=>$money,'num'=>$num,'add_time'=>nowDate(),'start_time'=>$start_time,'end_time'=>$end_time,'status'=>0]);
                } else {
                    // 添加活动
                    $rr=$this->getModel('CouponActivity')->insert(['name'=>$name,'activity_type'=>$activity_type,'product_class_id'=>$product_class_id,'product_id'=>$product_id,'money'=>$money,'z_money'=>$z_money,'num'=>$num,'add_time'=>nowDate(),'start_time'=>$start_time,'end_time'=>$end_time,'status'=>0]);
                }              
            } else {
                if ($activity_type == 2) {
                    // 添加活动
                    $rr=$this->getModel('CouponActivity')->insert(['name'=>$name,'activity_type'=>$activity_type,'product_class_id'=>$product_class_id,'product_id'=>$product_id,'money'=>$money,'num'=>$num,'add_time'=>nowDate(),'start_time'=>$start_time,'end_time'=>$end_time,'status'=>1]);
                    
                } else {
                    // 添加活动
                    $rr=$this->getModel('CouponActivity')->insert(['name'=>$name,'activity_type'=>$activity_type,'product_class_id'=>$product_class_id,'product_id'=>$product_id,'money'=>$money,'z_money'=>$z_money,'num'=>$num,'add_time'=>nowDate(),'start_time'=>$start_time,'end_time'=>$end_time,'status'=>1]);
                    
                }
            }
        }
        if ($rr == false) {
            $this->recordAdmin($admin_id, ' 添加活动失败 ', 1);
            // echo $sql;exit;
            $this->error('未知原因，活动添加失败！', $this->module_url . "/coupon");
        } else {
            $this->recordAdmin($admin_id, ' 添加活动成功 ', 1);
            
            $this->success('活动添加成功！', $this->module_url . "/coupon");
        }
        return;
    }

    public function ajax(Request $request)
    {
        $product_class_id = addslashes(trim($request->param('product_class_id'))); // 商品类型id
        if ($product_class_id) {
            $r=$this->getModel('ProductList')->where(['product_class'=>['like',"%$product_class_id%"]])->fetchAll('id,product_title');
            if ($r) {
                $res = '<option value="0" >全部</option>';
                foreach ($r as $key => $value) {
                    $res .= "<option value='{$value->id}'>{$value->product_title}</option>";
                }
                echo $res;
                exit();
            } else {
                $res = 0;
                echo $res;
            }
        } else {
            $res = 0;
            echo $res;
        }
        
        return;
    }

    private function do_configs($request)
    {
        
        // 接收信息
        $plug_ins_id = intval($request->param('plug_ins_id'));
        $software_id = intval($request->param('software_id'));
        $activity_overdue = addslashes(trim($request->param('activity_overdue'))); // 优惠券活动删除日期
         $coupon_validity = addslashes(trim($request->param('coupon_validity'))); // 优惠券有效期
        $coupon_overdue = addslashes($request->param('coupon_overdue')); // 优惠券过期时间
        if (is_numeric($activity_overdue) == '') {
            $this->error('优惠券活动删除日期请输入数字!', '');
        }
        if ($activity_overdue < 0) {
            $this->error('优惠券活动删除日期不能为负数!', '');
        }
        // if(is_numeric($coupon_validity) == ''){
        // $this->error('优惠券有效期请输入数字!','');
        //
        // }
        // if($coupon_validity <= 0){
        // $this->error('优惠券有效期不能为负数或0!','');
        //
        // }
        if (is_numeric($coupon_overdue) == '') {
            $this->error('优惠券过期时间请输入数字!', '');
        }
        if ($coupon_overdue < 0) {
            $this->error('优惠券过期时间不能为负数!', '');
        }
        $r = $this->getModel('CouponConfig')
            ->where([
                        'plug_ins_id' => [
                                            '=',$plug_ins_id
                        ]
        ])
            ->fetchAll('*');
        if ($r) {
            $r_1=$this->getModel('CouponConfig')->saveAll(['software_id'=>$software_id,'activity_overdue'=>$activity_overdue,'coupon_validity'=>$coupon_validity,'coupon_overdue'=>$coupon_overdue,'modify_date'=>nowDate()],['plug_ins_id'=>['=',$plug_ins_id]]);
            //$r_1 = $db->update($sql);
            if ($r_1 == false) {
                $this->error('未知原因，优惠券参数修改失败！', $this->module_url . "/plug_ins");
            } else {
                $this->success('优惠券参数修改成功！', $this->module_url . "/plug_ins");
            }
        } else {
            $r_1= $sql=$this->getModel('CouponConfig')->insert(['software_id'=>$software_id,'plug_ins_id'=>$plug_ins_id,'activity_overdue'=>$activity_overdue,'coupon_validity'=>$coupon_validity,'coupon_overdue'=>$coupon_overdue,'modify_date'=>nowDate()]);
           // $r_1 = $db->insert($sql);
            if ($r_1 == false) {
                $this->error('未知原因，优惠券参数添加失败！', $this->module_url . "/plug_ins");
            } else {
                $this->success('优惠券参数添加成功！', $this->module_url . "/plug_ins");
            }
        }
        return;
    }

    public function configs(Request $request)
    {
        $request->method()=='post'&&$this->do_configs($request);
        $plug_ins_id = intval($request->param("id")); // 插件id
        $software_id = intval($request->param("software_id")); // 软件id
        
        $r = $this->getModel('CouponConfig')->get($plug_ins_id, 'plug_ins_id ');
        if ($r) {
            $activity_overdue = $r[0]->activity_overdue;
            // $coupon_validity = $r[0]->coupon_validity;
            $coupon_overdue = $r[0]->coupon_overdue;
        } else {
            $activity_overdue = 2;
            // $coupon_validity = 7;
            $coupon_overdue = 2;
        }
        $this->assign('plug_ins_id', $plug_ins_id);
        $this->assign('software_id', $software_id);
        $this->assign('activity_overdue', $activity_overdue);
        // $this->assign('coupon_validity', $coupon_validity);
        $this->assign('coupon_overdue', $coupon_overdue);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function coupon(Request $request)
    {
        $r_1 = $this->getModel('CouponConfig')->get('1', 'id');
        $activity_overdue = $r_1[0]->activity_overdue; // 活动过期删除时间
        
        $name = addslashes(trim($request->param('name'))); // 用户id
        
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : '10';
        // 每页显示多少条数据
        $page = $request->param('page');
        
        $condition = '1 = 1';
        if ($name != '') {
            $condition .= " and b.name like '%$name%'";
        }
        
        $time = date('Y-m-d H:i:s'); // 当前时间

        
        $r=$this->getModel('Coupon')->alias('a')->join('coupon_activity b','a.hid=b.id','LEFT')->where($condition)
        ->order(['a.add_time'=>'desc'])
        ->field('a.*,b.name')->paginator($pagesize);
        if ($r) {
            foreach ($r as $k => $v) {
                $id = $v->id; // 优惠券id
                $hid = $v->hid; // 活动id
                $expiry_time = $v->expiry_time; // 到期时间             
                $rr = $this->getModel('CouponConfig')->get('1', 'id');
                $coupon_overdue = $rr[0]->coupon_overdue; // 优惠券过期删除时间
                $time_1 = date("Y-m-d H:i:s", strtotime("+$coupon_overdue day", strtotime($expiry_time))); // 优惠券过期删除时间
                                                                                                         
                // 当前时间大于活动结束时间,优惠券已过期
                if ($time > $expiry_time) {
                    $update_rs=$this->getModel('Coupon')->saveAll(['status'=>3],['id'=>['=',$id]]);
                    $v->status = 3;
                }
                // 当前时间大于优惠券过期删除时间,就删除这条数据
                if ($time_1 < $time) {
                    $delete_rs=$this->getModel('Coupon')->delete($id,'id');
                }             
                if ($v->name) {
                    $v->name = $v->name; // 活动名称
                } else {
                    // 查询配置信息
                    $rrr = $this->getConfig();
                    $v->name = $rrr[0]->company; // 公司名称
                }
            }
        }
        $pages_show =$r->render();      
        $this->assign("list", $r);
        $this->assign("name", $name);
        $this->assign("pages_show", $pages_show);
        $this->assign("pagesize", $pagesize);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function del(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收信息
        $id = intval($request->param('id')); // 活动id
                                             
        // 根据产品id，删除产品信息
        $update_rs=$this->getModel('CouponActivity')->saveAll(['recycle'=>1],['id'=>['=',$id]]);
        
        $this->recordAdmin($admin_id, ' 删除商品id为 ' . $id . ' 的信息', 3);
        
        echo 1;
        return;
    }

    public function modify(Request $request)
    {
        $request->method() == 'post' && $this->do_modify($request);
        
        // 接收信息
        $id = intval($request->param("id")); // 活动id
        $res = '';
        $r = $this->getModel('CouponActivity')->get($id, 'id');
        if ($r) {
            $activity_type = $r[0]->activity_type; // 活动类型
            $product_class_id = $r[0]->product_class_id; // 活动指定商品类型id
            $product_id = $r[0]->product_id; // 活动指定商品id
            $name = $r[0]->name; // 活动名称
            $money = $r[0]->money; // 金额
            $z_money = $r[0]->z_money; // 总金额
            $num = $r[0]->num; // 数量
            $start_time = $r[0]->start_time; // 开始时间
            $end_time = $r[0]->end_time; // 结束时间
            $status = $r[0]->status; // 结束时间
        }
        
        if ($product_class_id != 0) { // 当活动指定商品类型id不为0
            $arr = explode('-', $product_class_id);
            $arr = array_filter($arr);
            $arr = array_values($arr);
            $count = count($arr) - 1;
            $product_class_id = $arr[$count];
            
            // 根据商品分类id,查询分类id、分类名称
            $rr=$this->getModel('ProductClass')->where(['cid'=>['=',$product_class_id]])->fetchAll('cid,pname');
            $cid = $rr[0]->cid; // 商品分类id
            $pname = $rr[0]->pname; // 商品分类名称
            $hx = '-----';
            if (count($arr) == 1) {
                $res = "<option value='{$cid}'>{$pname}</option>";
            } else if (count($arr) == 2) {
                $res = "<option value='{$cid}'>{$hx}{$pname}</option>";
            } else if (count($arr) == 3) {
                $res = "<option value='{$cid}'>{$hx}{$hx}{$pname}</option>";
            }
            $res .= "<option value='0' >全部</option>";
            if ($product_id != 0) {
                $rrr=$this->getModel('ProductList')->where(['id'=>['=',$product_id]])->fetchAll('id,product_title');
                $p_id = $rrr[0]->id;
                $product_title = $rrr[0]->product_title;
                $rew = "<option value='{$p_id}' >{$product_title}</option>";
                $rew .= "<option value='0' >全部</option>";
            } else {
                $rew = "<option value='0' >全部</option>";
            }
            
            $rrr_1=$this->getModel('ProductList')->where(['product_class'=>['like',"%$product_class_id%"]])->fetchAll('id,product_title');
            if ($rrr_1) {
                foreach ($rrr_1 as $k => $v) {
                    $rew .= "<option value='{$v->id}'>{$v->product_title}</option>";
                }
            }
        } else {
            $res = "<option value='0' >全部</option>";
            $rew = '';
        }
        // 查询所有一级分类
        $r_1=$this->getModel('ProductClass')->where(['sid'=>['=','0']])->fetchAll('cid,pname');
        foreach ($r_1 as $key => $value) {
            $c = '-' . $value->cid . '-';
            $res .= '<option  value="-' . $value->cid . '-">' . $value->pname . '</option>';
            // 循环第一层
            $r_e=$this->getModel('ProductClass')->where(['sid'=>['=',$value->cid]])->fetchAll('cid,pname');
            if ($r_e) {
                $hx = '-----';
                foreach ($r_e as $ke => $ve) {
                    $cone = $c . $ve->cid . '-';
                    $res .= '<option  value="' . $cone . '">' . $hx . $ve->pname . '</option>';
                    // 循环第二层
                    $r_t=$this->getModel('ProductClass')->where(['sid'=>['=',$ve->cid]])->fetchAll('cid,pname');
                    if ($r_t) {
                        $hxe = $hx . '-----';
                        foreach ($r_t as $k => $v) {
                            $ctow = $cone . $v->cid . '-';
                            $res .= '<option  value="' . $ctow . '">' . $hxe . $v->pname . '</option>';
                        }
                    }
                }
            }
        }
        $this->assign('id', $id);
        $this->assign("activity_type", $activity_type);
        $this->assign('product_class_id', isset($product_class_id) ? $product_class_id : '');
        $this->assign('product_id', isset($product_id) ? $product_id : '');
        $this->assign("name", isset($name) ? $name : '');
        $this->assign('content', isset($content) ? $content : '');
        $this->assign('money', isset($money) ? $money : '');
        $this->assign('z_money', isset($z_money) ? $z_money : '');
        $this->assign('num', isset($num) ? $num : '');
        $this->assign('start_time', isset($start_time) ? $start_time : '');
        $this->assign('end_time', isset($end_time) ? $end_time : '');
        $this->assign('list', $res);
        $this->assign('list1', $rew);
        $this->assign('status', $status);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收数据
        $id = addslashes(trim($request->param('id'))); // 活动id
        $status = $request->param('status'); // 状态
        
        if ($status == 1) {
            $r_1=$this->getModel('CouponActivity')->where(['id'=>['=',$id]])->fetchAll('activity_type,product_class_id,product_id');
            $activity_type = $r_1[0]->activity_type;
            $product_class_id = $r_1[0]->product_class_id;
            $product_id = $r_1[0]->product_id;
        } else {
            $activity_type = addslashes(trim($request->param('activity_type'))); // 活动类型
            $product_class_id = addslashes(trim($request->param('product_class_id'))); // 活动指定商品类型
            $product_id = addslashes(trim($request->param('product_id'))); // 活动指定商品
        }
        $name = addslashes(trim($request->param('name'))); // 活动名称
        $money = addslashes(trim($request->param('money'))); // 金额
        $z_money = addslashes(trim($request->param('z_money'))); // 总金额
        $num = addslashes(trim($request->param('num'))); // 数量
        $start_time = $request->param('start_time'); // 活动开始时间
        $end_time = $request->param('end_time'); // 活动结束时间
        empty($start_time)&&$start_time=nowDate();
        empty($end_time)&&$end_time=nowDate();
        
        if ($name == '') {
            $this->error('活动名称不能为空！', $this->module_url.'/modity?id='.$id);
        }
        
        // 检查产品标题是否重复
        $r=$this->getModel('CouponActivity')->where(['name'=>['=',$name]])->fetchAll('1');
        if ($r && count($r) > 0) {
            $this->error($name.'活动名称已经存在！', $this->module_url.'/modity?id='.$id);
        }
        
        if ($money == '') {
            $this->error('金额不能为空！', $this->module_url.'/modity?id='.$id);
        }
        
        if ($num == '' || $num <= 0) {
            $num = 99999999999;
        }
        
        if ($start_time == '' && $activity_type != 1) {
            $this->error('活动开始时间不能为空！', $this->module_url.'/modity?id='.$id);
        }
        
        if ($end_time == '' && $activity_type != 1) {
            $this->error('活动结束时间不能为空！', $this->module_url.'/modity?id='.$id);
        }
        if ($start_time >= $end_time && $activity_type != 1) {
            $this->error('活动开始时间不能大于等于活动结束时间！', $this->module_url.'/modity?id='.$id);
        }
        
        $time = date('Y-m-d H:i:s');
        
        if ($time >= $end_time && $activity_type != 1) {
            $this->error('活动还没开始就已经结束！', $this->module_url.'/modity?id='.$id);
        }
        if ($activity_type == 1) {
            $r=$this->getModel('CouponActivity')->saveAll(['name'=>$name,'activity_type'=>$activity_type,'product_class_id'=>$product_class_id,'product_id'=>$product_id,'money'=>$money,'num'=>$num,'add_time'=>$time,'end_time'=>$end_time],['id'=>['=',$id]]);
        } else {
            if ($start_time > $time) {
                // 更新数据表
                $r=$this->getModel('CouponActivity')->saveAll(['name'=>$name,'activity_type'=>$activity_type,'product_class_id'=>$product_class_id,'product_id'=>$product_id,'money'=>$money,'z_money'=>$z_money,'num'=>$num,'start_time'=>$start_time,'end_time'=>$end_time,'add_time'=>$time,'status'=>0],['id'=>['=',$id]]);
            } else {
                // 更新数据表
                $r=$this->getModel('CouponActivity')->saveAll(['name'=>$name,'activity_type'=>$activity_type,'product_class_id'=>$product_class_id,'product_id'=>$product_id,'money'=>$money,'z_money'=>$z_money,'num'=>$num,'start_time'=>$start_time,'end_time'=>$end_time,'add_time'=>$time,'status'=>1],['id'=>['=',$id]]);
            }
        }
        
        if ($r == false) {
            $this->recordAdmin($admin_id, ' 修改活动id为 ' . $id . ' 失败 ', 2);
            
            $this->error('未知原因，活动修改失败！', $this->module_url . "/coupon");
        } else {
            $this->recordAdmin($admin_id, ' 修改活动id为 ' . $id . ' 成功 ', 2);
            
            $this->success('活动修改成功！', $this->module_url . "/coupon");
        }
        return;
    }

    public function see(Request $request)
    {
        $user_id = $request->param('user_id'); // 用户id
        
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : '10';
        // 每页显示多少条数据
        $page = $request->param('page');
            
        
        $r_1 = $this->getModel('CouponConfig')->get('1', 'id');
        $coupon_overdue = $r_1[0]->coupon_overdue; // 优惠券过期删除时间
        $time = date('Y-m-d H:i:s'); // 当前时间
        
        
        $r=$this->getModel('Coupon')->alias('a')->join('coupon_activity b','a.hid=b.id','LEFT')
        ->where(['user_id'=>['=',$user_id]])
        ->order(['a.add_time'=>'desc'])->order('a.*,b.name')->paginator($pagesize);
        if ($r) {
            foreach ($r as $k => $v) {
                $id = $v->id; // 到期时间
                $expiry_time = $v->expiry_time; // 到期时间
                $time_1 = date("Y-m-d H:i:s", strtotime("+$coupon_overdue day", strtotime($expiry_time))); // 优惠券过期删除时间
                                                                                                         // 当前时间大于活动结束时间,优惠券已过期
                if ($time > $expiry_time) {
                    $update_rs=$this->getModel('Coupon')->saveAll(['status'=>3],['id'=>['=',$id]]);
                    $v->status = 3;
                }
                // 当前时间大于优惠券过期删除时间,就删除这条数据
                if ($time_1 < $time) {
                    $delete_rs=$this->getModel('Coupon')->delete($id,'id');
                }
                if ($v->name) {
                    $v->name = $v->name; // 活动名称
                } else {
                    // 查询配置信息
                    $rrr = $this->getConfig();
                    $v->name = $rrr[0]->company; // 公司名称
                }
                $list[] = $v;
            }
        }
        $url = $this->module_url . "/finance/Index&user_id=" . urlencode($user_id);
        $pages_show = $r->render();
        
        $this->assign("list", $list);
        $this->assign("pages_show", $pages_show);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function whether(Request $request)
    {
        
        // 接收信息
        $id = intval($request->param('id')); // 活动id
        
        $r=$this->getModel('CouponActivity')->where(['id'=>['=',$id]])->fetchAll('status');
        if ($r[0]->status == 1) {
            $res=$this->getModel('CouponActivity')->saveAll(['status'=>2],['id'=>['=',$id]]);
            echo $res;
            exit();
            $this->success('禁用成功！', $this->module_url . "/coupon");
            return;
        } else {
            $res=$this->getModel('CouponActivity')->saveAll(['status'=>1],['id'=>['=',$id]]);
            echo $res;
            exit();
            $this->success('启用成功！', $this->module_url . "/coupon");
            return;
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