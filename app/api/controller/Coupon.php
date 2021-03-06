<?php
namespace app\api\controller;
use core\Request;
class Coupon extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function index (Request $request)
    {
        
        
        $openid = trim($request->param('openid')); // 微信id
                                                          // 查询用户id
        $user=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id,Register_data');
        $user_id = $user[0]->user_id;
        $Register_data = $user[0]->Register_data; // 注册时间
        
        $start_time_1 = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d'), date('Y'))); // 今天开始时间
        $end_time_1 = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1); // 今天结束时间
        $time = date('Y-m-d H:i:s'); // 当前时间
                                     
        // 根据活动为开启状态,查询活动列表,根据开始时间降序排列
        $r_1=$this->getModel('CouponActivity')->where(['status'=>['=','1']])->fetchOrder(['start_time'=>'desc'],'*');
        $rew_1 = 0;
        $arr = [];
        if ($r_1) {
            foreach ($r_1 as $k => $v) {
                $rew_1 = $k;
                $activity_id = $v->id; // 活动id
                $activity_type = $v->activity_type; // 活动类型
                $product_class_id = $v->product_class_id; // 商品类型
                $product_id = $v->product_id; // 商品id
                $start_time_2 = $v->start_time; // 活动开始时间
                $end_time_2 = $v->end_time; // 活动结束时间
                $money = $v->money; // 满减金额
                $z_money = $v->z_money; // 满减金额
                $num = $v->num; // 活动金额个数
                $v->start_time = date('Y-m-d', strtotime($v->start_time)); // 活动开始时间
                $v->end_time = date('Y-m-d', strtotime($v->end_time)); // 活动结束时间
                
                if ($activity_type == 1) {
                    $v->limit = '无门槛使用'; // 限制
                } else if ($activity_type == 2) {
                    if ($product_class_id == 0) {
                        $v->limit = '无门槛使用'; // 限制
                    } else {
                        if ($product_class_id != 0) {
                            $arr_1 = explode('-', $product_class_id);
                            $arr_1 = array_filter($arr_1);
                            $arr_1 = array_values($arr_1);
                            $count = count($arr_1) - 1;
                            $product_class_id = $arr_1[$count];
                            // 根据商品分类id,查询分类id、分类名称
                            $rr=$this->getModel('ProductClass')->where(['cid'=>['=',$product_class_id]])->fetchAll('cid,pname');
                            $v->cid = $rr[0]->cid; // 商品分类id
                            $v->pname = $rr[0]->pname; // 商品分类名称
                            if ($product_id != 0) {
                                $rrr=$this->getModel('ProductList')->where(['id'=>['=',$product_id]])->fetchAll('id,product_title');
                                $v->p_id = $rrr[0]->id;
                                $v->product_title = $rrr[0]->product_title;
                                $v->limit = '只能在' . $rrr[0]->product_title . '商品中使用'; // 限制
                            } else {
                                $v->p_id = 0;
                                $v->product_title = '';
                                $v->limit = '只能在' . $v->pname . '类使用'; // 限制
                            }
                        } else {
                            $v->cid = 0; // 商品分类id
                            $v->pname = ''; // 商品分类名称
                            $v->limit = '无门槛使用'; // 限制
                        }
                    }
                } else {
                    $v->limit = '满' . $z_money; // 限制
                }
                
                // 根据用户id,活动id ,查询优惠券表
                $r_2=$this->getModel('Coupon')->where(['user_id'=>['=',$user_id],'hid'=>['=',$activity_id]])->fetchAll('*');
                if ($r_2) {
                    $v->point = '已经领取';
                    $v->end_time = date('Y-m-d', strtotime('+1 week', strtotime($Register_data)));
                } else {
                    // var_dump($activity_type);
                    if ($activity_type == 1) { // 活动为(注册)类型
                        if ($Register_data > $start_time_2) {
                            $v->end_time = date('Y-m-d', strtotime('+1 week', strtotime($Register_data)));
                            if ($time > $v->end_time) {
                                $v->point = '您来晚了';
                            } else {
                                $v->point = '领取';
                            }
                        } else {
                            $v->point = '您来晚了';
                            $v->end_time = date('Y-m-d', strtotime('+1 week', strtotime($start_time_2)));
                        }
                    } else {
                        if ($num > 0) {
                            $v->point = '领取';
                        } else {
                            $v->point = '您来晚了';
                        }
                    }
                }
                
                // 判断活动是否过期
                if ($end_time_2 <= $time && $activity_type != 1) {
                    // 过期,根据活动id修改活动状态
                    $update_rs=$this->getModel('CouponActivity')->saveAll(['status'=>3],['id'=>['=',$activity_id]]);
                    $v->point = '已经结束';
                }
                $arr[$k] = $v;
            }
        }
        // 根据活动为未开启状态,查询活动列表,根据开始时间升序排列
        $rr_1=$this->getModel('CouponActivity')->where(['status'=>['=','0']])->fetchOrder(['start_time'=>'asc'],'*');
        if ($rr_1) {
            foreach ($rr_1 as $k => $v) {
                $id_2 = $v->id; // 活动id
                $activity_type = $v->activity_type; // 活动类型
                $start_time_3 = $v->start_time; // 活动开始时间
                $end_time_3 = $v->end_time; // 活动结束时间
                $v->start_time = date('Y-m-d', strtotime($v->start_time)); // 开始时间
                $v->end_time = date('Y-m-d', strtotime($v->end_time)); // 结束时间
                $product_class_id = $v->product_class_id; // 活动指定商品类型id
                $product_id = $v->product_id; // 活动指定商品id
                
                if ($activity_type == 1) {
                    $v->limit = '无门槛使用'; // 限制
                } else if ($activity_type == 2) {
                    if ($product_class_id == 0) {
                        $v->limit = '无门槛使用'; // 限制
                    } else {
                        if ($product_class_id != 0) {
                            $arr_1 = explode('-', $product_class_id);
                            $arr_1 = array_filter($arr_1);
                            $arr_1 = array_values($arr_1);
                            $count = count($arr_1) - 1;
                            $product_class_id = $arr_1[$count];
                            // 根据商品分类id,查询分类id、分类名称
                            $rr=$this->getModel('ProductClass')->where(['cid'=>['=',$product_class_id]])->fetchAll('cid,pname');
                            if ($rr) {
                                $v->cid = $rr[0]->cid; // 商品分类id
                                $v->pname = $rr[0]->pname; // 商品分类名称
                                if ($product_id != 0) {
                                    $rrr=$this->getModel('ProductList')->where(['id'=>['=',$product_id]])->fetchAll('id,product_title');
                                    $v->p_id = $rrr[0]->id;
                                    $v->product_title = $rrr[0]->product_title;
                                    $v->limit = '只能在' . $rrr[0]->product_title . '商品中使用'; // 限制
                                } else {
                                    $v->p_id = 0;
                                    $v->product_title = '';
                                    $v->limit = '只能在' . $v->pname . '类使用'; // 限制
                                }
                            }
                        } else {
                            $v->cid = 0; // 商品分类id
                            $v->pname = ''; // 商品分类名称
                            $v->limit = '无门槛使用'; // 限制
                        }
                    }
                } else {
                    $v->limit = '满' . $z_money; // 限制
                }
                
                // 判断活动是否开启
                if ($start_time_3 <= $time && $activity_type != 1) {
                    // 开启,根据活动id,修改活动状态
                    $update_rs=$this->getModel('CouponActivity')->saveAll(['status'=>1],['id'=>['=',$id_2]]);
                    $v->point = '领取';
                } else {
                    $v->point = '敬请期待';
                }
                $rew_2 = ++ $rew_1;
                $arr[$rew_2] = $v;
            }
        } else {
            $rew_2 = $rew_1;
        }
        
        // 查询优惠券插件配置
        $r=$this->getModel('CouponConfig')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $activity_overdue = $r[0]->activity_overdue; // 活动过期删除时间
        }
        
        // 根据活动为结束状态,查询活动列表,根据结束时间降序排列
        $rr_2=$this->getModel('CouponActivity')->where(['status'=>['=','3']])->fetchOrder(['end_time'=>'desc'],'*');
        if ($rr_2) {
            foreach ($rr_2 as $k => $v) {
                $id = $v->id; // 活动id
                $activity_type = $v->activity_type; // 活动类型
                
                $v->start_time = date('Y-m-d', strtotime($v->start_time)); // 开始时间
                $v->end_time = date('Y-m-d', strtotime($v->end_time)); // 结束时间
                $product_class_id = $v->product_class_id; // 活动指定商品类型id
                $product_id = $v->product_id; // 活动指定商品id
                if ($activity_overdue != 0) {
                    $time = date('Y-m-d H:i:s');
                    $end_time = date('Y-m-d', strtotime(" +$activity_overdue day", strtotime($v->end_time))); // 活动过期删除时间
                                                                                                            // 当 当前时间大于活动过期保留时间,删除活动
                    if ($time > $end_time && $activity_type != 1) {
                        $delete_rs=$this->getModel('CouponActivity')->delete($id,'id');
                    }
                }
                if ($activity_type == 1) {
                    $v->limit = '无门槛使用'; // 限制
                } else if ($activity_type == 2) {
                    if ($product_class_id == 0) {
                        $v->limit = '无门槛使用'; // 限制
                    } else {
                        if ($product_class_id != 0) {
                            $arr_1 = explode('-', $product_class_id);
                            $arr_1 = array_filter($arr_1);
                            $arr_1 = array_values($arr_1);
                            $count = count($arr_1) - 1;
                            $product_class_id = $arr_1[$count];
                            // 根据商品分类id,查询分类id、分类名称
                            $rr=$this->getModel('ProductClass')->where(['cid'=>['=',$product_class_id]])->fetchAll('cid,pname');
                            if ($rr) {
                                $v->cid = $rr[0]->cid; // 商品分类id
                                $v->pname = $rr[0]->pname; // 商品分类名称
                                if ($product_id != 0) {
                                    $rrr=$this->getModel('ProductList')->where(['id'=>['=',$product_id]])->fetchAll('id,product_title');
                                    $v->p_id = $rrr[0]->id;
                                    $v->product_title = $rrr[0]->product_title;
                                    $v->limit = '只能在' . $rrr[0]->product_title . '商品中使用'; // 限制
                                } else {
                                    $v->p_id = 0;
                                    $v->product_title = '';
                                    $v->limit = '只能在' . $v->pname . '类使用'; // 限制
                                }
                            }
                        } else {
                            $v->cid = 0; // 商品分类id
                            $v->pname = ''; // 商品分类名称
                            $v->limit = '无门槛使用'; // 限制
                        }
                    }
                } else {
                    $v->limit = '满' . $z_money; // 限制
                }
                $v->point = '已经结束';
                $rew_3 = ++ $rew_2;
                $arr[$rew_3] = $v;
            }
        }
        
        echo json_encode(array(
                                'list' => $arr
        ));
        exit();
    }

    public function receive (Request $request)
    {
        
        
        $openid = trim($request->param('openid')); // 微信id
        $id = trim($request->param('id')); // 活动id
                                                  
        // 查询用户id
        $user=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id,Register_data');
        $user_id = $user[0]->user_id; // 用户id
        $Register_data = $user[0]->Register_data; // 注册时间
                                                  
        // 根据活动id,查询活动
        $r_1=$this->getModel('CouponActivity')->where(['id'=>['=',$id]])->fetchAll('*');
        if ($r_1) {
            $activity_type = $r_1[0]->activity_type; // 活动类型
            $money = $r_1[0]->money; // 金额
            $z_money = $r_1[0]->z_money; // 满减金额
            $num = $r_1[0]->num; // 数量
            $end_time = $r_1[0]->end_time; // 活动结束时间
            if ($activity_type != 1) {
                if ($num != '') {
                    if ($num != 0) {
                        // 根据活动id,修改活动信息
                        $update_rs=$this->getModel('CouponActivity')->saveAll(['num'=>($num-1)],['id'=>['=',$id]]);
                        
                        // 在优惠券表里添加一条数据
                        $insert_rs=$this->getModel('Coupon')->insert(['user_id'=>$user_id,'money'=>$money,'add_time'=>nowDate(),'expiry_time'=>$end_time,'hid'=>$id]);
                    } else {
                        echo json_encode(array(
                                                'status' => 0,'info' => '您来晚了！'
                        ));
                        exit();
                    }
                }
            } else {
                $r=$this->getModel('CouponConfig')->where(['id'=>['=','1']])->fetchAll('*');
                $coupon_validity = $r[0]->coupon_validity;
                $time = date('Y-m-d', strtotime('+7 day'));
                // 在优惠券表里添加一条数据
                $insert_rs=$this->getModel('Coupon')->insert(['user_id'=>$user_id,'money'=>$money,'add_time'=>nowDate(),'expiry_time'=>$time,'hid'=>$id]);
            }
            echo json_encode(array(
                                    'status' => 1,'info' => '您领取了' . $money . '！'
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'info' => '参数错误！'
            ));
            exit();
        }
    }

    public function mycoupon (Request $request)
    {
        
        
        $openid = trim($request->param('openid')); // 微信id
                                                          
        // 根据微信id,查询用户id
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id');
        if (! $r) {
            echo json_encode(array(
                                    'status' => 0,'info' => '暂无数据'
            ));
            exit();
        }
        $user_id = $r[0]->user_id;
        // 查询配置表(公司logo)
        $r_c=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $company = $r_c[0]->company; // 公司名称
        
        $list = '';
        
        // 查询优惠券插件配置
        $r=$this->getModel('CouponConfig')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $coupon_overdue = $r[0]->coupon_overdue; // 优惠券过期删除时间
        }
        
        // 根据用户id,查询优惠券表
        $rr=$this->getModel('Coupon')->where(['user_id'=>['=',$user_id]])->fetchOrder(['type'=>'asc','add_time'=>'asc'],'*');
        if ($rr) {
            foreach ($rr as $k => $v) {
                $id = $v->id; // 优惠券id
                $hid = $v->hid; // 活动id
                
                $expiry_time = $v->expiry_time; // 优惠券到期时间
                $time = date('Y-m-d H:i:s', time()); // 当前时间
                
                $v->add_time = substr($v->add_time, 0, 10); // pdd
                $v->expiry_time = substr($v->expiry_time, 0, 10); // pdd
                
                if ($v->type == 0) {
                    $v->point = '立即使用';
                } else if ($v->type == 1) {
                    $v->point = '使用中';
                } else if ($v->type == 2) {
                    $v->point = '已使用';
                } else if ($v->type == 3) {
                    $v->point = '已过期';
                }
                // 根据活动id,查询活动信息
                $rrr=$this->getModel('CouponActivity')->where(['id'=>['=',$hid]])->fetchAll('*');
                if ($rrr) {
                    $v->name = $rrr[0]->name; // 活动名称
                    $activity_type = $rrr[0]->activity_type; // 类型
                    $z_money = $rrr[0]->z_money;
                    $product_class_id = $rrr[0]->product_class_id; // 分类id
                    $product_id = $rrr[0]->product_id; // 商品id
                    if ($activity_type == 1) {
                        $v->limit = '无门槛使用'; // 限制
                    } else if ($activity_type == 2) {
                        if ($product_class_id == 0) {
                            $v->limit = '无门槛使用'; // 限制
                        } else {
                            if ($product_class_id != 0) {
                                $arr_1 = explode('-', $product_class_id);
                                $arr_1 = array_filter($arr_1);
                                $arr_1 = array_values($arr_1);
                                $count = count($arr_1) - 1;
                                $product_class_id = $arr_1[$count];
                                // 根据商品分类id,查询分类id、分类名称
                                $rr=$this->getModel('ProductClass')->where(['cid'=>['=',$product_class_id]])->fetchAll('cid,pname');
                                $v->cid = $rr[0]->cid; // 商品分类id
                                $v->pname = $rr[0]->pname; // 商品分类名称
                                if ($product_id != 0) {
                                    $rrr=$this->getModel('ProductList')->where(['id'=>['=',$product_id]])->fetchAll('id,product_title');
                                    $v->p_id = $rrr[0]->id;
                                    $v->product_title = $rrr[0]->product_title;
                                    $v->limit = '只能在' . $rrr[0]->product_title . '商品中使用'; // 限制
                                } else {
                                    $v->p_id = 0;
                                    $v->product_title = '';
                                    $v->limit = '只能在' . $v->pname . '类使用'; // 限制
                                }
                            } else {
                                $v->cid = 0; // 商品分类id
                                $v->pname = ''; // 商品分类名称
                                $v->limit = '无门槛使用'; // 限制
                            }
                        }
                    } else {
                        $v->limit = '满' . $z_money; // 限制
                    }
                } else {
                    $v->name = $company;
                }
                
                if ($expiry_time < $time) { // 已过期
                                          // 根据用户id,修改优惠券表的状态
                    $update_rs=$this->getModel('Coupon')->saveAll(['type'=>3],['user_id'=>['=',$user_id],'id'=>['=',$id],'type'=>['<','>']]);
                    $v->type = 3;
                }
                if ($coupon_overdue != 0) {
                    $time_r = date("Y-m-d H:i:s", strtotime("$expiry_time   +$coupon_overdue   day")); // 优惠券过期删除时间
                                                                                                      // 过期时间超过1天,删除这条信息
                    if ($time_r < $time) {
                        // 根据用户id、优惠券id、优惠券类型为过期,删除这条信息
                        $delete_rs=$this->getModel('Coupon')->delete($id,'id');
                    }
                }
                $list[] = $v;
            }
        }
        
        if ($list != '') {
            echo json_encode(array(
                                    'status' => 1,'list' => $list
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'info' => '暂无数据'
            ));
            exit();
        }
    }

    public function immediate_use (Request $request)
    {
        
        
        $id = trim($request->param('id')); // 优惠券id
        $openid = trim($request->param('openid')); // 微信id
                                                          // 根据微信id,查询用户id
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id');
        $user_id = $r[0]->user_id;
        
        // 根据用户id,查询优惠券表里在使用中的优惠券
        $rr=$this->getModel('Coupon')->where(['user_id'=>['=',$user_id],'type'=>['=','1']])->fetchAll('*');
        if ($rr) {
            foreach ($rr as $k => $v) {
                $coupon_id = $v->id; // 优惠券id
                $hid = $v->hid; // 活动id
                                // 根据优惠券id,查询订单表
                $rr=$this->getModel('Order')->where(['coupon_id'=>['=',$coupon_id]])->fetchAll('id');
                if (empty($rr)) {
                    // 优惠券没有绑定
                    if ($coupon_id == $id) { // 传过来的优惠券id 与 查询没绑定的优惠券id 相等
                                           // 根据优惠券id,修改优惠券状态(未使用)
                        $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$id]]);
                        echo json_encode(array(
                                                'status' => 0
                        ));
                        exit();
                    } else { // 传过来的优惠券id 与 查询没绑定的优惠券id 不相等
                           // 根据查询没绑定的优惠券id,修改优惠券状态(未使用)
                        $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$coupon_id]]);
                        // 根据传过来的优惠券id,修改优惠券状态(未使用)
                        $update_rs=$this->getModel('Coupon')->saveAll(['type'=>1],['id'=>['=',$id]]);
                        echo json_encode(array(
                                                'status' => 1
                        ));
                        exit();
                    }
                }
            }
        } else {
            // 没有数据,就直接把优惠券状态改成(使用中)
            $update_rs=$this->getModel('Coupon')->saveAll(['type'=>1],['id'=>['=',$id]]);
            echo json_encode(array(
                                    'status' => 1
            ));
            exit();
        }
    }

    public function my_coupon (Request $request)
    {
        
        
        $openid = trim($request->param('openid')); // 微信id
        $cart_id = trim($request->param('cart_id')); // 购物车id
        
        $typestr = trim($cart_id, ','); // 移除两侧的逗号
        $typeArr = explode(',', $typestr); // 字符串打散为数组
        $zong = 0;
        
        // 根据微信id,查询用户id
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id');
        $user_id = $r[0]->user_id; // 用户id
                                   
        // 根据用户id,查询收货地址
        $r_a=$this->getModel('UserAddress')->where(['uid'=>['=',$user_id]])->fetchAll('id');
        $address = [];
        $yunfei = 0;
        if (! empty($r_a)) {
            // 根据用户id、默认地址,查询收货地址信息
            $r_e=$this->getModel('UserAddress')->where(['uid'=>['=',$user_id],'is_default'=>['=','1']])->fetchAll('*');
            if (! empty($r_e)) {
                $address = (array) $r_e['0']; // 收货地址
            } else {
                // 根据用户id、默认地址,查询收货地址信息
                $aaaid = $r_a[0]->id;
                $r_e=$this->getModel('UserAddress')->where(['id'=>['=',$aaaid]])->fetchAll('*');
                if ($r_e) {
                    $address = (array) $r_e['0']; // 收货地址
                    $update_rs=$this->getModel('UserAddress')->saveAll(['is_default'=>1],['id'=>['=',$aaaid]]);
                }
            }
        }
        
        foreach ($typeArr as $key => $value) {
            // 联合查询返回购物信息
            $r_c=$this->getModel('Cart')->alias('a')->join('product_list m','a.Goods_id=m.id','LEFT')->fetchWhere(['c.num'=>['>','0'],'m.status'=>['=','0'],'a.id'=>['=',$value]],'a.Goods_num,a.Goods_id,a.id,m.product_title,m.volume,c.price,c.attribute,c.img,c.yprice,m.freight,m.product_class');
            if ($r_c) {
                $product = (array) $r_c['0']; // 转数组
                $attribute = unserialize($product['attribute']);
                $product_id[] = $product['Goods_id']; // 商品id数组
                $product_class[] = $product['product_class']; // 商品分类数组
                $size = '';
                foreach ($attribute as $ka => $va) {
                    $size .= ' ' . $va;
                }
                $Goods_id = $product['Goods_id'];
                $num = $product['Goods_num']; // 产品数量
                $price = $product['price']; // 产品价格
                $product['size'] = $size; // 产品价格
                $zong += $num * $price; // 产品总价
                                      // 计算运费
                $yunfei = $yunfei + $this->freight($product['freight'], $product['Goods_num'], $address);
                $res[$key] = $product;
            }
        }
        $order_zong = $zong + $yunfei; // 订单总价
        
        $rew_1 = 0;
        // 根据用户id,查询优惠券状态为使用中的数据
        $r_1=$this->getModel('Coupon')->where(['user_id'=>['=',$user_id],'type'=>['=','1']])->fetchAll('*');
        
        if ($r_1) {
            foreach ($r_1 as $k => $v) {
                $id = $v->id; // 优惠券id
                $hid = $v->hid;
                // 根据优惠券id,查询订单
                $rr=$this->getModel('Order')->where(['coupon_id'=>['=',$id]])->fetchAll('id');
                if (empty($rr)) { // 没有数据,表示该优惠券没绑定
                                // 根据用户id,查询优惠券状态为使用中的数据
                    $r_2=$this->getModel('Coupon')->where(['user_id'=>['=',$user_id],'id'=>['=',$id]])->fetchAll('id,money');
                    if ($r_2) {
                        $r_2[0]->point = '正在使用';
                        $arr[0] = $r_2[0];
                        break;
                    }
                }
            }
        }
        // 根据用户id,查询优惠券状态为(未使用),以优惠券过期时间顺序排列
        $rr=$this->getModel('Coupon')->where(['user_id'=>['=',$user_id],'type'=>['=','0']])->fetchOrder(['expiry_time'=>'asc'],'id,money,hid');
        
        if ($rr) {
            foreach ($rr as $k => $v) {
                $rew_2 = ++ $rew_1;
                
                $hid = $v->hid;
                $money = $v->money; // 优惠券金额
                                    
                // 根据优惠券活动id，查询活动
                $rr1=$this->getModel('CouponActivity')->where(['id'=>['=',$hid]])->fetchAll('*');
                $activity_type = $rr1[0]->activity_type; // 类型
                $product_class_id = $rr1[0]->product_class_id; // 分类id
                $product_id1 = $rr1[0]->product_id; // 商品id
                $z_money = $rr1[0]->z_money; // 满减金额
                if ($activity_type == 1) {
                    if ($money < $order_zong) {
                        $v->point = '立即使用';
                        $arr[$rew_2] = $v;
                    }
                } else if ($activity_type == 3) {
                    if ($order_zong > $z_money) {
                        $v->point = '立即使用';
                        $arr[$rew_2] = $v;
                    }
                } else {
                    if ($product_class_id == 0) { // 当没设置商品分类
                        if ($money < $order_zong) {
                            $v->point = '立即使用';
                            $arr[$rew_2] = $v;
                        }
                    } else { // 当设置商品分类
                           // 根据活动指定的商品分类查询所有商品的分类
                        $rr_1=$this->getModel('ProductList')->where(['product_class'=>['like',"%$product_class_id%"]])->fetchAll('product_class');
                        if ($rr_1) {
                            $calss_status = 1; // 商品属于优惠券指定的分类
                            foreach ($rr_1 as $k1 => $v1) {
                                $rr_list[$k1] = $v1->product_class;
                            }
                            foreach ($product_class as $k2 => $v2) {
                                if (! in_array($v2, $rr_list)) {
                                    $calss_status = 0; // 商品不属于优惠券指定的分类
                                    break;
                                }
                            }
                            if ($calss_status == 1) { // 当商品属于优惠券指定的分类
                                $product_status = 1; // 商品属于优惠券指定商品
                                if ($product_id1 != 0) { // 当优惠券指定了商品
                                    foreach ($product_id as $k3 => $v3) {
                                        if ($product_id1 != $v3) {
                                            $product_status = 0;
                                            break;
                                        }
                                    }
                                }
                                if ($product_status == 1) {
                                    if ($money < $order_zong) {
                                        $v->point = '立即使用';
                                        $arr[$rew_2] = $v;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        ksort($arr);
        
        if ($arr != '') {
            echo json_encode(array(
                                    'list' => $arr
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'info' => '暂无数据'
            ));
            exit();
        }
    }

    public function getvou (Request $request)
    {
        
        
        
        $cart_id = trim($request->param('cart_id')); // 购物车id
        $coupon_money = trim($request->param('coupon_money')); // 付款金额
        $openid = trim($request->param('openid')); // 微信id
        $coupon_id = trim($request->param('coupon_id')); // 优惠券id
                                                                // 根据活动id,查询活动信息
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id');
        if (! $r) {
            echo json_encode(array(
                                    'status' => 0,'info' => '暂无数据'
            ));
            exit();
        }
        $user_id = $r[0]->user_id; // 用户id
        
        $typestr = trim($cart_id, ','); // 移除两侧的逗号
        $typeArr = explode(',', $typestr); // 字符串打散为数组
        $zong = 0;
        foreach ($typeArr as $key => $value) {
            // 联合查询返回购物信息
            $r_c=$this->getModel('Cart')->alias('a')->join('product_list m','a.Goods_id=m.id','LEFT')->fetchWhere(['a.id'=>['=',$value]],'a.Goods_num,a.Goods_id,a.id,c.price');
            if ($r_c) {
                $product = (array) $r_c['0']; // 转数组
                $num = $product['Goods_num']; // 产品数量
                $price = $product['price']; // 产品价格
                $zong += $num * $price; // 产品总价
                $res[$key] = $product;
            }
        }
        
        // 根据用户id、优惠劵状态为使用中，查询没绑定的优惠劵
        $r_1=$this->getModel('Coupon')->alias('a')->where(['(select'=>['count(1)','AS'],'user_id'=>['=',$user_id],'a.type'=>['=','1']])->field('a.*')->order(['1'=>'asc'])->select();
        empty($r_1)&&$r_1='';
        if ($r_1) { // 有没绑定的优惠劵id
            $id = $r_1[0]->id; // 没绑定的优惠劵id
            if ($coupon_id) { // 传过来的优惠劵id
                if ($id == $coupon_id) { // 当前在使用的优惠劵id与传过来的优惠劵相等
                    $money = $r_1[0]->money; // 优惠券金额
                    $coupon_money = $coupon_money + $money; // 付款金额+上优惠卷金额
                                                          // 当获取的优惠券id 与 状态为(使用中的id) 相等时,改状态为(未使用)
                    $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$coupon_id]]);
                    echo json_encode(array(
                                            'status' => 1,'id' => '','money' => '','zong' => $zong,'coupon_money' => $coupon_money
                    ));
                    exit();
                } else { // 当前在使用的优惠劵id与传过来的优惠劵不相等
                       // 查询当前在使用的优惠券金额
                    $r_3=$this->getModel('Coupon')->where(['id'=>['=',$id]])->fetchAll('*');
                    $ymoney = $r_3[0]->money; // 原优惠券金额
                    $coupon_money = $coupon_money + $ymoney; // 优惠后金额 加上 原优惠金额
                                                             // 查询传过来的优惠券金额
                    $r_2=$this->getModel('Coupon')->where(['id'=>['=',$coupon_id]])->fetchAll('*');
                    if ($r_2) {
                        $money = $r_2[0]->money; // 优惠券金额
                    } else {
                        $money = 0;
                    }
                    
                    if ($coupon_money > $money) { // 当付款金额大于优惠劵金额
                                                // 改原状态为(使用中 变为 未使用)
                        $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$id]]);
                        
                        // 改获取id状态为(未使用 变为 使用中)
                        $update_rs=$this->getModel('Coupon')->saveAll(['type'=>1],['id'=>['=',$coupon_id]]);
                        $coupon_money = $coupon_money - $money;
                        echo json_encode(array(
                                                'status' => 1,'id' => $coupon_id,'money' => $money,'zong' => $zong,'coupon_money' => $coupon_money
                        ));
                        exit();
                    } else { // 当付款金额大于优惠劵金额
                        $coupon_money = $coupon_money - $ymoney;
                        echo json_encode(array(
                                                'status' => 0,'id' => $id,'money' => $ymoney,'zong' => $zong,'coupon_money' => $coupon_money,'err' => '优惠券金额太大！'
                        ));
                        exit();
                    }
                }
            } else { // 传过来的优惠劵id,不存在
                   // 查询优惠券金额
                $r_2=$this->getModel('Coupon')->where(['id'=>['=',$id]])->fetchAll('*');
                if ($r_2) {
                    $money = $r_2[0]->money; // 优惠券金额
                } else {
                    $money = 0;
                }
                
                echo json_encode(array(
                                        'status' => 1,'id' => $id,'money' => $money,'zong' => $zong,'coupon_money' => $coupon_money
                ));
                exit();
            }
        } else { // 没有没绑定的优惠劵
            if ($coupon_id) { // 传过来的优惠劵id存在
                            // 根据传过来的优惠劵id,查询优惠金额
                $r=$this->getModel('Coupon')->where(['id'=>['=',$coupon_id]])->fetchAll('*');
                if ($r) {
                    $money = $r[0]->money; // 优惠劵金额
                } else {
                    $money = 0; // 优惠劵金额
                }
                
                if ($money < $coupon_money) {
                    $coupon_money = $coupon_money - $money; // 付款金额-优惠劵金额
                                                            // 根据传过来的优惠劵id, 修改优惠劵状态（使用中）
                    $update_rs=$this->getModel('Coupon')->saveAll(['type'=>1],['id'=>['=',$coupon_id]]);
                    echo json_encode(array(
                                            'status' => 1,'id' => $coupon_id,'money' => $money,'zong' => $zong,'coupon_money' => $coupon_money
                    ));
                    exit();
                } else {
                    echo json_encode(array(
                                            'status' => 0,'id' => '','money' => '','zong' => $zong,'coupon_money' => $coupon_money,'err' => '优惠券金额太大！'
                    ));
                    exit();
                }
            } else { // 没有传优惠劵id过来
                echo json_encode(array(
                                        'status' => 1,'id' => '','money' => '','zong' => $zong,'coupon_money' => $coupon_money
                ));
                exit();
            }
        }
    }

    public function freight($freight, $num, $address)
    {
        $r_1=$this->getModel('Freight')->where(['id'=>['=',$freight]])->fetchAll('*');
        if ($r_1) {
            $rule = $r_1[0];
            $yunfei = 0;
            if (empty($address)) {
                return 0;
            } else {
                $sheng = $address['sheng'];
                $r_2=$this->getModel('AdminCgGroup')->where(['GroupID'=>['=',$sheng]])->fetchAll('G_CName');
                if ($r_2) {
                    $city = $r_2[0]->G_CName;
                    $rule_1 = $r_1[0]->freight;
                    $rule_2 = unserialize($rule_1);
                    
                    foreach ($rule_2 as $key => $value) {
                        $citys_str = $value['name'];
                        $citys_array = explode(',', $citys_str);
                        $citys_arrays = [];
                        foreach ($citys_array as $k => $v) {
                            $citys_arrays[$v] = $v;
                        }
                        if (array_key_exists($city, $citys_arrays)) {
                            if ($num > $value['three']) {
                                $yunfei += $value['two'];
                                $yunfei += ($num - $value['three']) * $value['four'];
                            } else {
                                $yunfei += $value['two'];
                            }
                        }
                    }
                    return $yunfei;
                } else {
                    return 0;
                }
            }
        } else {
            return 0;
        }
    }

}