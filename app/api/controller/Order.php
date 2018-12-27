<?php
namespace app\api\controller;
use core\Request;

class Order extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function return_type (Request $request)
    {
        
        
        $id = trim($request->param('id')); // 订单id
        $oid = trim($request->param('oid')); // 订单号
        $r=$this->getModel('OrderDetails')->where(['id'=>['=',$id]])->fetchAll('r_status');
        if ($r) {
            $status = $r[0]->r_status;
        } else {
            $status = '';
        }
        // 状态 0：未付款 1：未发货 2：待收货 3：待评论 4：退货 5:已完成 6 订单关闭 9拼团中 10 拼团失败-未退款 11 拼团失败-已退款''',
        // itemList: ['退货退款', '仅退款','换货'],
        // itemList_text:'退货退款',
        // tapIndex:1
        $arrayType1 = array(
                            'text' => '退货退款','id' => '1'
        );
        $arrayType2 = array(
                            'text' => '仅退款','id' => '2'
        );
        $arrayType3 = array(
                            'text' => '换货','id' => '3'
        );
        $arrayType = [
                        $arrayType1,$arrayType2,$arrayType3
        ];
        
        $itemList_text = '退货退款';
        $tapIndex = 1;
        if ($status == 1) {
            $arrayType = [
                            $arrayType2
            ];
            $itemList_text = '仅退款';
            $tapIndex = 2;
        } else if ($status == 2) {
            $arrayType = [
                            $arrayType1,$arrayType2
            ];
            $itemList_text = '退货退款';
            $tapIndex = 1;
        } else {}
        echo json_encode(array(
                                'status' => 1,'arrayType' => $arrayType,'itemList_text' => $itemList_text,'tapIndex' => $tapIndex
        ));
        exit();
    }

    public function index (Request $request)
    {
        
        
        // 查询系统参数
        $res = "";
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r_1) {
            $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
            $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
            if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
                $img = $uploadImg_domain . $uploadImg; // 图片路径
            } else { // 不存在
                $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
            }
        } else {
            $img = '';
        }
        
        // 查询当前正在执行的团信息
        // $group=$this->getModel('GroupBuy')->fetchWhere(['is_show'=>['=','1']],'group_buy');
        // if(!empty($group)) list($groupmsg) = $group;
        
        // 获取信息
        $openid = $request['openid']; // 微信id
        $order_type = $request['order_type']; // 类型
        $otype = $request['otype']; // 类型
        if ($otype == 'pay5') {
            $res = "and a.drawid > 0 "; // 一分钱抽奖
        } else if ($otype == 'pay6') {
            // if(!empty($groupmsg)) $res = "and a.otype='pt' and a.pid='$groupmsg->status'"; // 我的拼团
            $res = "and a.otype='pt'"; // 我的拼团
        } else {
            $res = "";
        }
        if (! empty($order_type) && $order_type != $otype) {
            if ($otype == 'pay6') {
                // 拼团的状态没和其他订单状态共用字段，分开判断
                if ($order_type == 'payment') {
                    $res .= "and a.status = 0 "; // 未付款
                } else if ($order_type == 'send') {
                    $res .= "and a.status = 1 "; // 未发货
                } else if ($order_type == 'receipt') {
                    $res .= "and a.status = 2 "; // 待收货
                } else if ($order_type == 'evaluate') {
                    $res .= "and a.status = 3 "; // 待评论
                } else {
                    $res = "";
                }
            } else {
                if ($order_type == 'payment') {
                    $status = 0;
                    $res .= "and a.status = '$status'"; // 未付款
                } else if ($order_type == 'send') {
                    $status = 1;
                    $res .= "and a.status = '$status' "; // 未发货
                } else if ($order_type == 'receipt') {
                    $status = 2;
                    $res .= "and a.status = '$status'"; // 待收货
                } else if ($order_type == 'evaluate') {
                    $status = 3;
                    $res .= "and a.status = '$status'"; // 待评论
                } else {
                    $res = "";
                }
            }
        }
        
        // 根据微信id,查询用户id
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
        if ($r) {
            $user_id = $r[0]->user_id;
        } else {
            $user_id = '';
        }
        
        $order = array();
        // 根据用户id和前台参数,查询订单表 (id、订单号、订单价格、添加时间、订单状态、优惠券id)
        $r=$this->getModel('Orderasa')->where(['user_id'=>['=',$user_id]])->fetchOrder(['add_time'=>'desc'],'id,z_price,sNo,add_time,status,coupon_id,pid,drawid,ptcode');
        
        if ($order_type == 'send') { // 未发货
            if (! empty($r)) {
                foreach ($r as $key001 => $value001) {
                    $drawid = $value001->drawid;
                    if ($drawid > 0) {
                        $ddd=$this->getModel('DrawUser')->where(['id'=>['=',$drawid]])->fetchAll('lottery_status,draw_id');
                        $lottery_status = $ddd[0]->lottery_status;
                        if ($lottery_status != 4) { // 抽奖成功
                            unset($r[$key001]);
                        }
                    }
                }
            }
        }
        $plugopen=$this->getModel('PlugIns')->where(['type'=>['=','0'],'software_id'=>['=','3'],'name'=>['like','%拼团%']])->fetchAll('status');
        $plugopen = ! empty($plugopen) ? $plugopen[0]->status : 0;
        
        if ($r) {
            foreach ($r as $k => $v) {
                $rew = [];
                $rew['id'] = $v->id; // 订单id
                $rew['z_price'] = $v->z_price; // 订单价格
                $rew['sNo'] = $v->sNo; // 订单号
                $sNo = $v->sNo; // 订单号
                $rew['add_time'] = $v->add_time; // 订单时间
                $rew['status'] = $v->status; // 订单状态
                $rew['coupon_id'] = $v->coupon_id; // 优惠券id
                $rew['pid'] = $v->pid; // 拼团ID
                $rew['role'] = $v->drawid; // 抽奖
                $rew['ptcode'] = $v->ptcode; // 拼团号
                $rew['plugopen'] = $plugopen; // 拼团是否开启（0 未启用 1.启用）
                $coupon_id = $v->coupon_id; // 优惠券id
                if (! empty($rew['role'])) {
                    $role = $rew['role'];
                    
                    $add_time = $rew['add_time'];
                    $ddd=$this->getModel('DrawUser')->where(['id'=>['=',$role]])->fetchAll('lottery_status,draw_id');
                    if (! empty($ddd)) {
                        $lottery_status = $ddd[0]->lottery_status;
                        $rew['lottery_status'] = $lottery_status;
                        $draw_id = $ddd[0]->draw_id;
                        $rew['drawid'] = $draw_id;
                    }
                    if ($rew['status'] == 0) {
                        $rew['lottery_status1'] = '等待买家付款';
                    } elseif ($rew['status'] == 1) {
                        if ($lottery_status == 0) {
                            $rew['lottery_status1'] = '抽奖中-已参团';
                        } elseif ($lottery_status == 1) {
                            $rew['lottery_status1'] = '抽奖中';
                        } elseif ($lottery_status == 2) {
                            $rew['lottery_status1'] = '抽奖失败';
                        } elseif ($lottery_status == 4) {
                            $rew['lottery_status1'] = '抽奖成功-待发货';
                        } else {
                            $rew['lottery_status1'] = '抽奖失败';
                        }
                    } elseif ($rew['status'] == 2) {
                        $rew['lottery_status1'] = '抽奖成功-待发货';
                    } elseif ($rew['status'] == 6) {
                        if ($lottery_status == 2) {
                            $rew['lottery_status1'] = '参团失败订单关闭';
                        } else {
                            $rew['lottery_status1'] = '抽奖失败订单关闭';
                        }
                    }
                } else {
                    $rew['lottery_status1'] = '';
                    $rew['lottery_status'] = '';
                    $rew['drawid'] = 0;
                }
                
                if ($coupon_id == 0) { // 优惠券id为0
                    $rew['total'] = $rew['z_price']; // 总价为订单价格
                } else {
                    // 根据优惠券id,查询优惠券信息
                    $rr=$this->getModel('Coupon')->where(['id'=>['=',$coupon_id]])->fetchAll('*');
                    // var_dump($sql);
                    if ($rr) {
                        $expiry_time = $rr[0]->expiry_time; // 优惠券到期时间
                        $money = $rr[0]->money; // 优惠券金额
                        $time = date('Y-m-d H:i:s'); // 当前时间
                        if ($expiry_time <= $time) {
                            // 优惠券过期
                            // 根据优惠券id,修改优惠券状态
                            $update_rs=$this->getModel('Coupon')->saveAll(['type'=>3],['id'=>['=',$coupon_id]]);
                            $rew['info'] = 0;
                        } else { // 优惠券没过期
                            $rew['info'] = 1;
                        }
                        $rew['total'] = $rew['z_price'] + $money; // 总价为 订单价格+优惠券价格
                    } else {
                        $rew['total'] = $rew['z_price']; // 总价为订单价格
                    }
                }
                
                $rew['pname'] = '';
                
                // 根据订单号,查询订单详情
                $rew['list']=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$sNo]])->fetchAll('*');
                $product = [];
                if ($rew['list']) {
                    foreach ($rew['list'] as $key => $values) {
                        if (strpos($values->r_sNo, 'PT') !== false) {
                            $man_num=$this->getModel('GroupBuy')->fetchWhere(['status'=>['=',$v->pid]],'group_buy');
                            $rew['man_num'] = ! empty($man_num) ? $man_num[0]->man_num : 0;
                            $rew['pro_id'] = $values->p_id;
                        }
                        $rew['pname'] .= $values->p_name; // 订单内商品
                        $p_id = $values->p_id; // 产品id
                        $arr = (array) $values;
                        // 根据产品id,查询产品列表 (产品图片)
                        $rrr=$this->getModel('ProductList')->where(['id'=>['=',$p_id]])->fetchAll('imgurl');
                        if ($rrr) {
                            $img_res = $rrr['0']->imgurl;
                            $url = $img . $img_res; // 拼图片路径
                            $arr['imgurl'] = $url;
                            $product[$key] = (object) $arr;
                        }
                        $r_status = $values->r_status; // 订单详情状态
                        
                        $res_o=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$sNo],'r_status'=>['<','>']])->fetchAll('id');
                        
                        $res_d=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$sNo]])->fetchAll('id');
                        
                        // 如果订单下面的商品都处在同一状态,那就改订单状态为已完成
                        if ($res_o == $res_d) {
                            // 如果订单数量相等 则修改父订单状态
                            $r=$this->getModel('Order')->saveAll(['status'=>$r_status],['sNo'=>['=',$sNo]]);
                        }
                        if ($r_status > 0) {
                            $rew['status'] = $r_status;
                        }
                    }
                    $rew['list'] = $product;
                }
                $order[] = $rew;
            }
        } else {
            $order = '';
        }
        
        echo json_encode(array(
                                'status' => 1,'order' => $order
        ));
        exit();
        return;
    }

    public function recOrder (Request $request)
    {
        
        
        // 获取信息
        $id = trim($request->param('id')); // 订单详情id
        $time = date('Y-m-d H:i:s');
        
        $rew=$this->getModel('OrderDetails')->alias('a')->join('order b','a.id=$idandb.sNo=a.r_sNo','inner')->fetchWhere("1=1",'b.drawid,b.sNo');
        if ($rew) {
            if ($rew[0]->drawid > 0) {
                $sNo = $rew[0]->sNo;
                // 根据订单详情id,修改订单详情
                $r=$this->getModel('OrderDetails')->saveAll(['r_status'=>6,'arrive_time'=>$time],['id'=>['=',$id]]);
                // 根据订单号,修改订单表
                $rew02=$this->getModel('Order')->saveAll(['status'=>6,'arrive_time'=>$time],['sNo'=>['=',$sNo]]);
            } else {
                // 根据订单详情id,修改订单详情
                $r=$this->getModel('OrderDetails')->saveAll(['r_status'=>3,'arrive_time'=>$time],['id'=>['=',$id]]);
            }
            if ($r > 0) {
                echo json_encode(array(
                                        'status' => 1,'err' => '操作成功!'
                ));
                exit();
            } else {
                echo json_encode(array(
                                        'status' => 0,'err' => '操作失败!'
                ));
                exit();
            }
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '网络繁忙!'
            ));
            exit();
        }
        return;
    }

    public function ok_Order (Request $request)
    {
        
        
        // 获取信息
        $sNo = trim($request->param('sNo')); // 订单号
        $time = date('Y-m-d H:i:s');
        // 查询订单是不是抽奖订单，要是抽奖订单确认收货就直接关闭订单
        $rew=$this->getModel('Order')->where(['sNo'=>['=',$sNo]])->fetchAll('drawid,otype');
        if ($rew) {
            if ($rew[0]->drawid > 0) {
                $r_1=$this->getModel('OrderDetails')->saveAll(['r_status'=>6,'arrive_time'=>$time],['r_sNo'=>['=',$sNo]]);
                $r_2=$this->getModel('Order')->saveAll(['status'=>6],['sNo'=>['=',$sNo]]);
            } else {
                
                $r_1=$this->getModel('OrderDetails')->saveAll(['r_status'=>3,'arrive_time'=>$time],['r_sNo'=>['=',$sNo],'r_status'=>['=','2']]);
                
                if ($rew[0]->otype == 'pt')
                    $r_1 = 1;
                $r_2=$this->getModel('Order')->saveAll(['status'=>3],['sNo'=>['=',$sNo]]);
            }
            if ($r_1 > 0 && $r_2 > 0) {
                echo json_encode(array(
                                        'status' => 1,'err' => '操作成功!'
                ));
                exit();
            } else {
                echo json_encode(array(
                                        'status' => 0,'err' => '操作失败!'
                ));
                exit();
            }
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '网络繁忙!'
            ));
            exit();
        }
        return;
    }

    public function logistics (Request $request)
    {
        
        
        // 获取信息
        $id = trim($request->param('id')); // 订单详情id
        $details = $request->param('details');
        $type = trim($request->param('type'));
        if ($type) {
            $r=$this->getModel('TwelveDrawUserAddress')->where(['oid'=>['=',$id]])->fetchAll('kd_num as express_id,kdid as courier_num');
        } else {
            // 根据订单详情id,修改订单详情
            if ($details) {
                $r=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$id]])->fetchAll('express_id,courier_num');
            } else {
                $r=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$id]])->fetchAll('express_id,courier_num');
            }
        }
        if ($r) {
            if (! empty($r[0]->express_id) && ! empty($r[0]->courier_num)) {
                $express_id = $r[0]->express_id; // 快递公司ID
                $courier_num = $r[0]->courier_num; // 快递单号
                $r01=$this->getModel('Express')->where(['id'=>['=',$express_id]])->fetchAll('*');
                $type = $r01[0]->type; // 快递公司代码
                $kuaidi_name = $r01[0]->kuaidi_name;
                $url = "http://www.kuaidi100.com/query?type=$type&postid=$courier_num";
                $res = $this->httpsRequest($url);
                $res_1 = json_decode($res);
                echo json_encode(array(
                                        'status' => 1,'res_1' => $res_1,'name' => $kuaidi_name,'courier_num' => $courier_num
                ));
                exit();
            } else {
                echo json_encode(array(
                                        'status' => 0,'err' => '暂未查到!'
                ));
                exit();
            }
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '网络繁忙!'
            ));
            exit();
        }
    }

    function httpsRequest($url, $data = null)
    {
        // 1.初始化会话
        $ch = curl_init();
        // 2.设置参数: url + header + 选项
        // 设置请求的url
        curl_setopt($ch, CURLOPT_URL, $url);
        // 保证返回成功的结果是服务器的结果
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (! empty($data)) {
            // 发送post请求
            curl_setopt($ch, CURLOPT_POST, 1);
            // 设置发送post请求参数数据
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        // 3.执行会话; $result是微信服务器返回的JSON字符串
        $result = curl_exec($ch);
        // 4.关闭会话
        curl_close($ch);
        return $result;
    }

    public function removeOrder (Request $request)
    {
        
        
        // 获取信息
        $openid = $request->param('openid'); // 微信id
        $id = trim($request->param('id')); // 订单id
        
        $rr=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id');
        $user_id = $rr[0]->user_id; // 用户id
                                    
        // 根据订单id,查询订单列表(订单号)
        $r=$this->getModel('Order')->where(['id'=>['=',$id],'user_id'=>['=',$user_id]])->fetchAll('z_price,sNo,status,coupon_id,consumer_money');
        if ($r) {
            $z_price = $r[0]->z_price; // 订单价
            $sNo = $r[0]->sNo; // 订单号
            $status = $r[0]->status; // 订单状态
            $coupon_id = $r[0]->coupon_id; // 优惠券id
            $consumer_money = $r[0]->consumer_money; // 消费金
            if ($coupon_id != 0) {
                // 根据优惠券id,查询优惠券信息
                $r_c=$this->getModel('Coupon')->where(['id'=>['=',$coupon_id]])->fetchAll('*');
                $expiry_time = $r_c[0]->expiry_time; // 优惠券到期时间
                $time = date('Y-m-d H:i:s'); // 当前时间
                if ($expiry_time <= $time) {
                    // 根据优惠券id,修改优惠券状态
                    $update_rs=$this->getModel('Coupon')->saveAll(['type'=>2],['id'=>['=',$coupon_id]]);
                } else {
                    // 根据优惠券id,修改优惠券状态
                    $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$coupon_id]]);
                }
            }
            if ($consumer_money != 0) {
                $update_rs=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->inc('consumer_money',$consumer_money)->update();
                $event = $user_id . '退回' . $consumer_money . '消费金';
                $beres1=$this->getModel('DistributionRecord')->insert(['user_id'=>$user_id,'from_id'=>$user_id,'money'=>$consumer_money,'sNo'=>$sNo,'level'=>0,'event'=>$event,'type'=>5,'add_date'=>nowDate()]);
            }
            
            // 根据订单号,删除订单表信息
            $r_2=$this->getModel('Order')->delete($sNo,'sNo');
            // 根据订单号,删除订单详情信息
            $r_1=$this->getModel('OrderDetails')->delete($sNo,'r_sNo');
            if ($r_1 > 0 && $r_2 > 0) {
                if ($status == 1) {
                    $update_rs=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->inc('money',$z_price)->update();
                }
                echo json_encode(array(
                                        'status' => 1,'err' => '操作成功!'
                ));
                exit();
            } else {
                echo json_encode(array(
                                        'status' => 0,'err' => '操作失败!'
                ));
                exit();
            }
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '操作失败!'
            ));
            exit();
        }
        return;
    }

    public function order_details (Request $request)
    {
        
        
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        // 获取信息
        $id = $request['order_id']; // 订单id
        $type1 = $request['type1']; //
        
        $r=$this->getModel('Order')->where(['id'=>['=',$id]])->fetchAll('sNo,z_price,add_time,name,mobile,address,drawid,user_id,status,coupon_id,consumer_money,coupon_activity_name,pid,otype,ptcode,red_packet');
        
        if ($r) {
            $sNo = $r[0]->sNo; // 订单号
            $z_price = $r[0]->z_price; // 总价
            $add_time = $r[0]->add_time; // 订单时间
            $name = $r[0]->name; // 联系人
            $num = $r[0]->mobile; // 联系手机号
            $mobile = substr_replace($num, '****', 3, 4); // 隐藏操作
            $address = $r[0]->address; // 联系地址
            $role = $r[0]->drawid; // 抽奖id
            $user_id = $r[0]->user_id; // 成员id
            $status = $r[0]->status; // 订单状态
            $gstatus = $r[0]->status; // 订单状态
            $otype = $r[0]->otype; // 订单状态
            $ptcode = $r[0]->ptcode; // 订单状态
            $pid = $r[0]->pid; // 拼团ID
            $red_packet = $r[0]->red_packet; // 红包
                                             
            // 判断红包使用
            $red_packet=$red_packet?:0;
            if ($status) {
                $user_money = false;
            } else {
                $o_user_money_res=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->fetchAll('money');
                if ($o_user_money_res) {
                    $user_money = $o_user_money_res[0]->money;
                } else {
                    $user_money = false;
                }
            }
            
            $coupon_id = $r[0]->coupon_id; // 优惠券id
            $consumer_money = $r[0]->consumer_money; // 积分
            $coupon_activity_name = '';
            $coupon_activity_name = $r[0]->coupon_activity_name; // 满减活动名称
            if ($coupon_id) {
                $r_coupon=$this->getModel('Coupon')->where(['id'=>['=',$coupon_id]])->fetchAll('money');
                $coupon_money = $r_coupon[0]->money;
            } else {
                $coupon_money = 0;
            }
            if (! empty($role)) { // 存在抽奖订单
                $dd=$this->getModel('DrawUser')->where(['id'=>['=',$role]])->fetchAll('*');
                if (! empty($dd)) {
                    $lottery_status = $dd[0]->lottery_status;
                    $drawid = $dd[0]->draw_id;
                }
                if ($status == 0) {
                    $rew['lottery_status1'] = '待付款';
                } elseif ($status == 1) {
                    if ($lottery_status == 0) {
                        $lottery_status = '查看团详情';
                    } elseif ($lottery_status == 1) {
                        $lottery_status = '待抽奖';
                    } elseif ($lottery_status == 2) {
                        $lottery_status = '参团失败';
                    } elseif ($lottery_status == 4) {
                        $lottery_status = '待发货';
                    } else {
                        $lottery_status = '抽奖失败';
                    }
                } elseif ($status == 2) {
                    $lottery_status = '待收货';
                } elseif ($status == 6) {
                    if ($lottery_status == 2) {
                        $lottery_status = '订单关闭';
                    } else {
                        $lottery_status = '订单关闭';
                    }
                }
                $type1 = 11;
            } else {
                $wx_id = '';
                $lottery_status = '';
                $type1 = 22;
                $drawid = '';
            }
            $freight = 0;
            // 根据订单号,查询订单详情
            $list=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$sNo]])->fetchAll('*');
            if ($list) {
                foreach ($list as $key => $values) {
                    $freight += $values->freight;
                    // print_r($values->freight);
                    $p_id = $values->p_id; // 产品id
                    $sid = $values->sid; // 属性id
                    $arrive_time = $values->arrive_time;
                    $date = date('Y-m-d 00:00:00', strtotime('-7 days'));
                    if ($arrive_time != '') {
                        if ($arrive_time < $date) {
                            $values->info = 1;
                        } else {
                            $values->info = 0;
                        }
                    } else {
                        $values->info = 0;
                    }
                    $arr = (array) $values;
                    // 根据产品id,查询产品列表 (产品图片)
                    $rrr=$this->getModel('ProductList')->alias('a')->join('configure c','a.id=c.pid','LEFT')->fetchWhere(['a.id'=>['=',$p_id]],'img,product_title');
                    $url = $img . $rrr[0]->img; // 拼图片路径
                    $title = $rrr[0]->product_title;
                    $arr['imgurl'] = $url;
                    $arr['sid'] = $sid;
                    $product[$key] = (object) $arr;
                    
                    $r_status = $values->r_status; // 订单详情状态
                    if ($r_status) {
                        $res_o=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$sNo]])->fetchAll('id');
                        $res_d=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$sNo]])->fetchAll('id');
                        // 如果订单下面的商品都处在同一状态,那就改订单状态为已完成
                        if ($res_o == $res_d) {
                            // 如果订单数量相等 则修改父订单状态
                            $r=$this->getModel('Order')->saveAll(['status'=>$r_status],['sNo'=>['=',$sNo]]);
                        }
                        $status = $r_status;
                        $status1 = $r_status;
                    } else {
                        $status = $r_status;
                        $status1 = $r_status;
                    }
                    
                    if ($r) {
                        if ($r[0]->otype == 'pt') {
                            $product[$key]->r_status = $gstatus;
                        }
                    }
                    
                    $dr = $status1;
                }
                $list = $product;
            }
            $man_num = '';
            if ($r) {
                if ($r[0]->otype == 'pt') {
                    $man_num=$this->getModel('GroupBuy')->fetchWhere(['status'=>['=',$pid]],'group_buy');
                    $man_num = $man_num[0]->man_num;
                }
            }
            
            echo json_encode(array(
                                    'status' => 1,'id' => $id,'freight' => $freight,'sNo' => $sNo,'z_price' => $z_price,'name' => $name,'mobile' => $mobile,'address' => $address,'add_time' => $add_time,'rstatus' => $status,'list' => $list,'lottery_status' => $lottery_status,'type1' => $type1,'otype' => $otype,'man_num' => $man_num,'ptcode' => $ptcode,'dr' => $dr,'role' => $role,'title' => $title,'drawid' => $drawid,'p_id' => $p_id,'coupon_id' => $coupon_id,'coupon_money' => $coupon_money,'consumer_money' => $consumer_money,'user_money' => $user_money,'coupon_activity_name' => $coupon_activity_name,'pid' => $pid,'red_packet' => $red_packet
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '系统繁忙！'
            ));
            exit();
        }
        return;
    }

    public function ReturnData (Request $request)
    {
        
        
        // 获取信息
        $id = $request['id']; // 订单详情id
        $oid = $request['oid']; // 订单号
        $otype = $request['otype']; // 状态
                                  // $re_type = $request['re_type']; // 退货类型
        $re_type = trim($request->param('re_type'));
        $back_remark = htmlentities($request['back_remark']); // 退货原因
        
        $r=$this->getModel('OrderDetails')->saveAll(['r_status'=>4,'content'=>$back_remark,'r_type'=>0,'re_type'=>$re_type],['id'=>['=',$id]]);
        
        $res_o=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$oid]])->fetchAll('id');
        
        $res_d=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$oid]])->fetchAll('id');
        
        if ($res_o == $res_d) {
            // 如果订单数量相等 则修改父订单状态
            $r=$this->getModel('Order')->saveAll(['status'=>4],['sNo'=>['=',$oid]]);
        }
        if ($r > 0) {
            echo json_encode(array(
                                    'status' => 1,'succ' => '申请成功！'
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '系统繁忙！'
            ));
            exit();
        }
    }

    public function ReturnDataList (Request $request)
    {
        
        
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r_1) {
            $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
            $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
            if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
                $img = $uploadImg_domain . $uploadImg; // 图片路径
            } else { // 不存在
                $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
            }
        } else {
            $img = '';
        }
        
        // 获取信息
        $openid = $request['openid']; // 微信id
        $order_type = $request['order_type']; // 参数
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id');
        if ($r) {
            $user_id = $r[0]->user_id;
            if ($order_type == 'whole') {
                
                $list=$this->getModel('OrderDetails')->where(['user_id'=>['=',$user_id],'r_status'=>['=','4']])->fetchAll('*');
                if ($list) {
                    foreach ($list as $k => $v) {
                        $p_id = $v->p_id;
                        $arr = (array) $v;
                        // 根据产品id,查询产品列表 (产品图片)
                        $rrr=$this->getModel('ProductList')->where(['id'=>['=',$p_id]])->fetchAll('imgurl');
                        if ($rrr) {
                            $url = $img . $rrr[0]->imgurl; // 拼图片路径
                            $arr['imgurl'] = $url;
                            $product[$k] = (object) $arr;
                        }
                    }
                    $list = $product;
                    echo json_encode(array(
                                            'status' => 1,'list' => $list
                    ));
                    exit();
                } else {
                    echo json_encode(array(
                                            'status' => 0,'list' => ''
                    ));
                    exit();
                }
            } else if ($order_type == 'stay') {
                
                $list=$this->getModel('OrderDetails')->where(['user_id'=>['=',$user_id],'r_status'=>['=','4'],'r_type'=>['=','0']])->fetchAll('*');
                if ($list) {
                    foreach ($list as $k => $v) {
                        $p_id = $v->p_id;
                        $arr = (array) $v;
                        // 根据产品id,查询产品列表 (产品图片)
                        $rrr=$this->getModel('ProductList')->where(['id'=>['=',$p_id]])->fetchAll('imgurl');
                        if ($rrr) {
                            $url = $img . $rrr[0]->imgurl; // 拼图片路径
                            $arr['imgurl'] = $url;
                            $product[$k] = (object) $arr;
                        }
                    }
                    $list = $product;
                    echo json_encode(array(
                                            'status' => 1,'list' => $list
                    ));
                    exit();
                } else {
                    echo json_encode(array(
                                            'status' => 0,'list' => ''
                    ));
                    exit();
                }
            }
        } else {
            
            echo json_encode(array(
                                    'status' => 0,'list' => ''
            ));
            exit();
        }
        
        return;
    }

    public function back_send (Request $request)
    {
        
        
        // 获取信息
        $kdcode = trim($request->param('kdcode'));
        $kdname = trim($request->param('kdname'));
        $lxdh = trim($request->param('lxdh'));
        $lxr = trim($request->param('lxr'));
        $uid = trim($request->param('uid'));
        $oid = trim($request->param('oid'));
        
        $rid=$this->getModel('ReturnGoods')->insert(['name'=>$lxr,'tel'=>$lxdh,'express'=>$kdname,'express_num'=>$kdcode,'uid'=>$uid,'oid'=>$oid,'add_data'=>nowDate()]);
        
        $r=$this->getModel('OrderDetails')->saveAll(['r_type'=>3],['id'=>['=',$oid]]);
        
        if ($r) {
            echo json_encode(array(
                                    'status' => 1,'err' => '操作成功!'
            ));
            exit();
        } else {
            
            $delete_rs=$this->getModel('ReturnGoods')->delete($rid,'id');
            
            echo json_encode(array(
                                    'status' => 0,'err' => '操作失败!'
            ));
            exit();
        }
    }

    public function see_send (Request $request)
    {
        
        
        // 获取信息
        $r_1=$this->getModel('UserAddress')->where(['uid'=>['=','admin']])->fetchAll('address_xq,name,tel');
        if ($r_1) {
            $address = $r_1[0]->address_xq;
            $name = $r_1[0]->name;
            $phone = $r_1[0]->tel;
        } else {
            $address = '';
            $name = '';
            $phone = '';
        }
        
        $r_2=$this->getModel('Express')->fetchAll('*');
        
        if ($r_2) {
            echo json_encode(array(
                                    'status' => 1,'address' => $address,'name' => $name,'phone' => $phone,'express' => $r_2
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '操作失败!'
            ));
            exit();
        }
    }

    public function up_out_trade_no (Request $request)
    {
        
        
        $coupon_id = trim($request->param('coupon_id')); // 优惠券id
        $allow = trim($request->param('allow')); // 用户使用消费金
        $coupon_money = trim($request->param('coupon_money')); // 付款金额
        $order_id = trim($request->param('order_id')); // 订单号
        $user_id = trim($request->param('user_id')); // 微信id
        $d_yuan = trim($request->param('d_yuan')); // 抵扣余额
        $trade_no = trim($request->param('trade_no')); // 微信支付单号
        $pay = trim($request->param('pay'));
        $array = array(
                    'coupon_id' => $coupon_id,'allow' => $allow,'coupon_money' => $coupon_money,'order_id' => $order_id,'user_id' => $user_id,'d_yuan' => $d_yuan,'trade_no' => $trade_no,'pay' => $pay
        );
        $data = serialize($array);
        
        $r_u=$this->getModel('Order')->saveAll(['trade_no'=>$trade_no],['sNo'=>['=',$order_id]]);
        
        $rid=$this->getModel('OrderData')->insert(['trade_no'=>$trade_no,'data'=>$data,'addtime'=>nowDate()]);
        
        $yesterday = date("Y-m-d", strtotime("-1 day"));
        $delete_rs=$this->getModel('OrderData')->delete($yesterday,'addtime');
        
        echo json_encode(array(
                                'status' => $r_u
        ));
        exit();
    }

}