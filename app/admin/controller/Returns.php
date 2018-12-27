<?php
namespace app\admin\controller;

use core\Request;
use core\Response;
use core\Session;

class Returns extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        $p_name = addslashes(trim($request->param('p_name'))); // 产品名称
        $startdate = $request->param("startdate");
        $enddate = $request->param("enddate");
        $pageto = $request->param('pageto'); // 导出
        $sort_name = $request->param('sort_name'); // 排序名称
        $sort = $request->param('sort'); // 升/降
        
        $r_type = trim($request->param('r_type'));
        $condition = ' r_status = 4 ';
        if ($p_name != '') {
            $condition .= " and r_sNo like '%$p_name%' ";
        }
        if ($r_type) {
            
            if ($r_type == 1) {
                $condition .= " and r_type = '0' ";
            } else if ($r_type == 2) {
                $condition .= " and (r_type = '1' OR r_type = '6') ";
            } else if ($r_type == 3) {
                $condition .= " and (r_type = '2' OR r_type = '8') ";
            } else if ($r_type == 4) {
                $condition .= " and r_type = '3' ";
            } else if ($r_type == 5) {
                $condition .= " and (r_type = '4' OR r_type = '9') ";
            } else {
                $condition .= " and r_type = '5' ";
            }
        }
        if ($startdate != '') {
            $condition .= "and arrive_time >= '$startdate 00:00:00' ";
        }
        if ($enddate != '') {
            $condition .= "and arrive_time <= '$enddate 23:59:59' ";
        }
        $con = '';
        foreach ($_GET as $key => $value001) {
            $con .= "&$key=$value001";
        }
        // 查询插件表
        $total = $this->getModel('OrderDetails')
            ->where($condition)
            ->fetchAll('*');
        // 导出
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : 10;
        // 页码
        $page = $request->param('page');
        if ($page) {
            $start = ($page - 1) * $pagesize;
        } else {
            $start = 0;
        }
        // $sql .= " order by add_time desc limit $start,$pagesize ";
        // $r = $db->select($sql);
        $url = '".$this->module_url."/return' . $con;
        if ($pageto == 'all') { // 导出全部
            $r = $this->getModel('OrderDetails')
                ->where($condition)
                ->fetchOrder([
                                '$sort' => 'asc'
            ], '*');
        } else if ($pageto == 'ne') { // 导出本页
            $r = $this->getModel('OrderDetails')
                ->where($condition)
                ->fetchOrder([
                                '$sort' => 'asc'
            ], '*', "$start,$pagesize");
        } else {
            $r = $this->getModel('OrderDetails')
                ->where($condition)
                ->paginator($pagesize,$this->getUrlConfig($request->url));
        }
        $this->assign("r_type", $r_type);
        $this->assign("pages_show", $r->render());
        $this->assign("p_name", $p_name);
        $this->assign("startdate", $startdate);
        $this->assign("enddate", $enddate);
        $this->assign("list", $r);
        $this->assign('pageto', $pageto);
        if ($pageto != '') {
            $r = time();
            $str = $this->fetch('excel');
            return Response::instance($str, 'excel', 200, [
                                                                "Content-Disposition" => "attachment;filename=orders-" . $r . ".xls",'content-type' => "application/msexcel;charset=utf-8"
            ]);
            exit();
        }
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }


    public function addsign(Request $request)
    {
        $id = $request->param('id');
        $sNo = $request->param('sNo');
        // 运费
        $r02 = $this->getModel('Express')->fetchAll();
        if (isset($request['otype'])) {
            $this->assign("otype", $request['otype']);
        } else {
            $this->assign("otype", 'yb');
        }
        $this->assign("express", $r02);
        $this->assign("id", $id);
        $this->assign("sNo", $sNo);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function examine(Request $request)
    {
        $request->method()=='post'&&$this->do_examine($request);
        $id = intval($request->param('id'));
        $type = intval($request->param('type'));
        // 查询订单信息
        $res_p = $this->getModel('OrderDetails')
            ->where([
                        'id' => [
                                    '=',$id
                        ]
        ])
            ->fetchAll('p_price,user_id,r_sNo,re_money');
        if (empty($res_p))
            exit();
        $p_price = $res_p[0]->p_price;
        $user_id = $res_p[0]->user_id;
        $sNo = $res_p[0]->r_sNo;
        $re_money = $res_p[0]->re_money;
        if ($re_money == '0.00' || empty($re_money)) {
            // 判断单个商品退款是否有使用优惠
            $order_res = $this->getModel('Order')
                ->alias('a')
                ->join('order_details m', 'a.sNo=m.r_sNo', 'left')
                ->fetchWhere(['m.id'=>['=',$id],
                                'm.r_status'=>['=','4']
            ], 'a.id,m.freight,a.trade_no,a.sNo,a.pay,a.z_price,a.user_id,a.allow,a.spz_price,a.reduce_price,a.coupon_price,m.p_price,a.consumer_money');
            // echo $sql_id;exit;
            $allow = $order_res[0]->allow;
            $reduce_price = $order_res[0]->reduce_price;
            $coupon_price = $order_res[0]->coupon_price;
            $pay = $order_res[0]->pay;
            $consumer_money = $order_res[0]->consumer_money;
            $spz_price = $order_res[0]->spz_price;
            $youhui_price = floatval($allow) + floatval($reduce_price) + floatval($coupon_price);
            $freight = $order_res[0]->freight; // 运费
            $res_o = $this->getModel('OrderDetails')
                ->getCount([
                            'r_sNo' => [
                                            '=',$sNo
                            ]
            ],'id');
            
            $res_d = $this->getModel('Order')
                ->getCount([
                            'sNo' => [
                                        '=',$sNo
                            ]
            ],'id');
            
            // 如果订单下面的商品都处在同一状态,那就改订单状态为已完成
            if ($res_d == $res_o) {
                // 如果订单数量相等 则修
                $price = $order_res[0]->z_price;
            } else {
                $price = number_format($order_res[0]->z_price / $spz_price * $p_price, 2);
            }
            
            if ($price <= 0 && $pay == 'consumer_pay' && $consumer_money > 0) {
                $price = $consumer_money;
            }
            
            if ($freight) {
                $price = $price - $freight;
            }
        } else {
            $price = $re_money;
        }
        
        echo $price;
        exit();
    }

    private function do_examine($request)
    {
        $admin_id = Session::get('admin_id');
        $M = $this->getModel('OrderDetails');
        // 开启事务
        $M->conn()->startTrans();
        
        $r = $this->getConfig();
        if ($r) {
            $appid = $r[0]->appid;
            // 小程序唯一标识
            $appsecret = $r[0]->appsecret;
            // 小程序的 app secret
            $company = $r[0]->company;
            $mch_key = $r[0]->mch_key; // 商户key
            $mch_id = $r[0]->mch_id; // 商户mch_id
            $cert_path=$r[0]->mch_cert;
        }
        $time = date("Y-m-d h:i:s", time());
        $id = intval($request->param('id'));
        // 订单详情id
        $m = intval($request->param('m'));
        // 参数
        $text = trim($request->param('text'));
        
        $price = trim($request->param('price'));
        
        // text拒绝理由
        $r = $this->getModel('Notice')
            ->where([
                        'id' => [
                                    '=','1'
                        ]
        ])
            ->fetchAll('*');
        $template_id = $r[0]->refund_res;
        $res = 1;
        if ($m == 1 || $m == 4 || $m == 9) {
            $res = $this->getModel('OrderDetails')->saveAll([
                                                                'r_type' => $m
            ], [
                    'id' => [
                                '=',$id
                    ]
            ]);
            if ($m == 9 || $m == 4) {
                
                $order_res = $this->getModel('Order')
                    ->alias('a')
                    ->join('order_details m', 'a.sNo=m.r_sNo', 'LEFT')
                    ->fetchWhere([
                                    'm.id' => [
                                                '=',$id
                                    ],'m.r_status' => [
                                                        '=','4'
                                    ]
                ], 'a.id,a.trade_no,a.sNo,a.pay,a.z_price,a.user_id,a.allow,a.spz_price,a.reduce_price,a.coupon_price,m.p_price,a.consumer_money');
                
                if ($order_res) {
                    $pay = $order_res[0]->pay;
                    $user_id = $order_res[0]->user_id;
                    $consumer_money = $order_res[0]->consumer_money;
                    // print_r($pay);die;
                    if ($pay == 'wallet_Pay' || $pay == 'wallet_pay') {
                        // 查询订单信息
                        
                        $res_p = $this->getModel('OrderDetails')
                            ->where([
                                        'id' => [
                                                    '=',$id
                                        ]
                        ])
                            ->fetchAll('p_price,user_id,r_sNo');
                        $p_price = $res_p[0]->p_price;
                        $user_id = $res_p[0]->user_id;
                        $sNo = $res_p[0]->r_sNo;
                        
                        $res_o = $this->getModel('OrderDetails')
                            ->getCount([
                                        'r_sNo' => [
                                                        '=',$sNo
                                        ],'r_status' => [
                                                            '=','4'
                                        ]
                        ],'id');
                        $res_d = $this->getModel('OrderDetails')
                            ->getCount([
                                        'r_sNo' => [
                                                        '=',$sNo
                                        ]
                        ],'id');
                        // 如果订单下面的商品都处在同一状态,那就改订单状态为已完成
                        if ($res_d == $res_o) {
                            // 如果订单数量相等 则修
                            // 根据订单号、用户id,修改订单状态(6 订单关闭)
                            $r_u = $this->getModel('Order')->saveAll([
                                                                        'status' => 6
                            ], [
                                    'sNo' => [
                                                '=',$sNo
                                    ]
                            ]);
                        }
                        
                        // 判断单个商品退款是否有使用优惠
                        if (empty($price)) {
                            $allow = $order_res[0]->allow;
                            $reduce_price = $order_res[0]->reduce_price;
                            $coupon_price = $order_res[0]->coupon_price;
                            $spz_price = $order_res[0]->spz_price;
                            $youhui_price = floatval($allow) + floatval($reduce_price) + floatval($coupon_price);
                            // 如果订单下面的商品都处在同一状态,那就改订单状态为已完成
                            if ($res_d == $res_o) {
                                // 如果订单数量相等 则修
                                $price = $order_res[0]->z_price;
                            } else {
                                $price = number_format($order_res[0]->z_price / $spz_price * $p_price, 2);
                            }
                        }
                        
                        // 修改订单状态为关闭
                        $res1 = $this->getModel('OrderDetails')->saveAll([
                                                                            'r_status' => 6
                        ], [
                                'id' => [
                                            '=',$id
                                ]
                        ]);
                        
                        $user_id = $res_p[0]->user_id;
                        $sNo = $res_p[0]->r_sNo;
                        
                        // 修改用户余额
                        $res = $this->getModel('User')->where->inc('money', $price)->update();
                        // 添加日志
                        $event = $user_id . '退款' . $price . '元余额';
                        $rr = $this->getModel('Record')->insert([
                                                                    'user_id' => $user_id,'money' => $price,'oldmoney' => $price,'event' => $event,'type' => 5
                        ]);
                        // 发送推送信息
                        if ($rr < 1 || $res1 < 1 || $res < 1) {
                            $M->conn()->rollback();
                            echo 0;
                            exit();
                        }
                        // 查询openid
                        $res_openid = $this->getModel('User')
                            ->where([
                                        'user_id' => [
                                                        '=',$user_id
                                        ]
                        ])
                            ->fetchAll('wx_id');
                        $openid = $res_openid[0]->wx_id;
                        $froms = $this->get_fromid($openid);
                        $form_id = $froms['fromid'];
                        $page = 'pages/index/index';
                        // 消息模板id
                        $send_id = $template_id;
                        $keyword1 = array(
                                        'value' => $sNo,"color" => "#173177"
                        );
                        $keyword2 = array(
                                        'value' => $company,"color" => "#173177"
                        );
                        $keyword3 = array(
                                        'value' => $time,"color" => "#173177"
                        );
                        $keyword4 = array(
                                        'value' => '退款成功',"color" => "#173177"
                        );
                        $keyword5 = array(
                                        'value' => $price . '元',"color" => "#173177"
                        );
                        $keyword6 = array(
                                        'value' => '预计24小时内到账',"color" => "#173177"
                        );
                        $keyword7 = array(
                                        'value' => '原支付方式',"color" => "#173177"
                        );
                        // 拼成规定的格式
                        $o_data = array(
                                        'keyword1' => $keyword1,'keyword2' => $keyword2,'keyword3' => $keyword3,'keyword4' => $keyword4,'keyword5' => $keyword5,'keyword6' => $keyword6,'keyword7' => $keyword7
                        );
                        
                        $res1 = $this->Send_Prompt($appid, $appsecret, $form_id, $openid, $page, $send_id, $o_data);
                        
                        $this->get_fromid($openid, $form_id);
                        // var_dump($res);
                    } else if ($pay == 'combined_Pay') {
                        
                        $trade_no = $order_res[0]->trade_no;
                        $sNo = $order_res[0]->sNo;
                        $p_price = $order_res[0]->p_price;
                        $user_id = $order_res[0]->user_id;
                        // 判断单个商品退款是否有使用优惠
                        
                        $allow = $order_res[0]->allow;
                        $reduce_price = $order_res[0]->reduce_price;
                        $coupon_price = $order_res[0]->coupon_price;
                        
                        $spz_price = $order_res[0]->spz_price;
                        $youhui_price = floatval($allow) + floatval($reduce_price) + floatval($coupon_price);
                        
                        $res_o = $this->getModel('OrderDetails')
                            ->getCount([
                                        'r_sNo' => [
                                                        '=',$sNo
                                        ]
                        ],'id');
                        // 如果订单下面的商品都处在同一状态,那就改订单状态为已完成
                        $total_fee = $order_res[0]->z_price;
                        if ($res_o <= 1) {
                            // 如果订单数量相等 则修
                            $z_price = $order_res[0]->z_price;
                        } else {
                            $z_price = number_format($order_res[0]->z_price / $spz_price * $p_price, 2);
                        }
                        if (! empty($price)) {
                            $z_price = $price;
                        }
                        // 组合支付时判断按照比例退款
                        $zhres = $this->getModel('Combined')->get($sNo, 'order_id');
                        $weixin_pay = $zhres[0]->weixin_pay;
                        $balance_pay = $zhres[0]->balance_pay;
                        $consumer_pay = $zhres[0]->consumer_pay;
                        $total = $zhres[0]->total;
                        $openid = $zhres[0]->user_id;
                        
                        $refund_wx = number_format($weixin_pay / $total * $z_price, 2);
                        $refund_ye = number_format($balance_pay / $total * $z_price, 2);
                        $refund_cm = number_format($consumer_pay / $total * $z_price, 2);
                        $wxres_t = '';
                        if ($refund_wx > 0 && ! empty($refund_wx)) {
                            // 按照比例退款 --- 调起微信退款
                            $wxtk_res = $this->wxrefundapi($trade_no, $sNo . $id, $weixin_pay * 100, $refund_wx * 100, $appid, $mch_id, $mch_key,$cert_path);
                            $user_id = $order_res[0]->user_id;
                            $event = $user_id . '微信退款' . $refund_wx . '元余额-' . json_encode($wxtk_res);
                            $rr = $this->getModel('Record')->insert([
                                                                        'user_id' => $user_id,'money' => $refund_wx,'event' => $event,'type' => 5
                            ]);
                            if ($wxtk_res['result_code'] == 'SUCCESS') {
                                $wxres_t = $wxtk_res['result_code'];
                            }
                            if ($rr) {
                                $M->conn()->rollback();
                                echo 0;
                                exit();
                            }
                        }
                        
                        if ($refund_cm) {
                            // 修改用户消费金
                            $res = $this->getModel('User')->where->inc('consumer_money', $refund_cm)->update();
                            // 添加日志
                            $event = $user_id . '退款' . $refund_cm . '消费金';
                            $rr = $this->getModel('DistributionRecord')->insert([
                                                                                    'user_id' => $user_id,'from_id' => $user_id,'money' => $refund_cm,'sNo' => '','level' => 0,'event' => $event,'type' => 5,'add_date' => nowDate()
                            ]);
                            if ($rr < 1) {
                                $M->conn()->rollback();
                                // echo 'rollback1';
                                echo 0;
                                exit();
                            }
                        }
                        
                        // 修改用户余额
                        $res = $this->getModel('User')->where->inc('money', $refund_ye)->update();
                        // 添加日志
                        $event = $user_id . '退款' . $refund_ye . '元余额';
                        $rr = $this->getModel('Record')->insert([
                                                                    'user_id' => $user_id,'money' => $refund_ye,'oldmoney' => $refund_ye,'event' => $event,'type' => 5
                        ]);
                        
                        if ($rr < 1 || $res < 1) {
                            $M->conn()->rollback();
                            echo 0;
                            exit();
                        }
                        
                        if ($wxres_t == 'SUCCESS' || $rr) {
                            $froms = $this->get_fromid($openid);
                            $form_id = $froms['fromid'];
                            $page = 'pages/index/index';
                            // 消息模板id
                            $send_id = $template_id;
                            $keyword1 = array(
                                            'value' => $sNo,"color" => "#173177"
                            );
                            $keyword2 = array(
                                            'value' => $company,"color" => "#173177"
                            );
                            $keyword3 = array(
                                            'value' => $time,"color" => "#173177"
                            );
                            $keyword4 = array(
                                            'value' => '退款成功',"color" => "#173177"
                            );
                            $keyword5 = array(
                                            'value' => $z_price . '元',"color" => "#173177"
                            );
                            $keyword6 = array(
                                            'value' => '预计24小时内到账',"color" => "#173177"
                            );
                            $keyword7 = array(
                                            'value' => '原支付方式',"color" => "#173177"
                            );
                            // 拼成规定的格式
                            $o_data = array(
                                            'keyword1' => $keyword1,'keyword2' => $keyword2,'keyword3' => $keyword3,'keyword4' => $keyword4,'keyword5' => $keyword5,'keyword6' => $keyword6,'keyword7' => $keyword7
                            );
                            
                            $res = $this->Send_Prompt($appid, $appsecret, $form_id, $openid, $page, $send_id, $o_data);
                            
                            $this->get_fromid($openid, $form_id);
                            
                            $res_o = $this->getModel('OrderDetails')
                                ->getCount([
                                            'r_sNo' => [
                                                            '=',$sNo
                                            ],'r_status' => [
                                                                '=','4'
                                            ]
                            ],'id');
                            if ($res_o <= 1) {
                                // 根据订单号、用户id,修改订单状态(6 订单关闭)
                                $r_u = $this->getModel('Order')->saveAll([
                                                                            'status' => 6
                                ], [
                                        'sNo' => [
                                                    '=',$sNo
                                        ]
                                ]);
                            }
                            
                            // 根据订单号,查询商品id、商品名称、商品数量
                            $r_o = $this->getModel('OrderDetails')
                                ->where([
                                            'r_sNo' => [
                                                            '=',$sNo
                                            ]
                            ])
                                ->fetchAll('p_id,num,p_name,sid');
                            // 根据订单号,修改订单详情状态(订单关闭)
                            $r_d = $this->getModel('OrderDetails')->saveAll([
                                                                                'r_status' => 6
                            ], [
                                    'r_sNo' => [
                                                    '=',$sNo
                                    ]
                            ]);
                            if ($r_d < 1) {
                                $M->conn()->rollback();
                                echo 0;
                                exit();
                            }
                            // 退款后还原商品数量
                            foreach ($r_o as $key => $value) {
                                $pid = $value->p_id;
                                // 商品id
                                $num = $value->num;
                                // 商品数量
                                $sid = $value->sid;
                                // 商品属性id
                                // 根据商品id,修改商品数量
                                $r_p = $this->getModel('Configure')->where->inc('num', $num)->update();
                                // 根据商品id,修改卖出去的销量
                                $r_x = $this->getModel('ProductList')->where->dec('volume', $num)
                                    ->inc('num', $num)
                                    ->update();
                                if ($r_x < 1 || $r_p < 1) {
                                    $M->conn()->rollback();
                                    echo 0;
                                    exit();
                                }
                            }
                            if ($r_d && $r_o) {
                                $res = 1;
                            } else {
                                $res = 0;
                            }
                        } else {
                            $res = 0;
                        }
                    } else if ($pay == 'consumer_pay') {
                        
                        $trade_no = $order_res[0]->trade_no;
                        $sNo = $order_res[0]->sNo;
                        $p_price = $order_res[0]->p_price;
                        
                        if ($price && $price > 0) {
                            $consumer_money = $price;
                        }
                        // 修改用户消费金
                        // $consumer_money = number_format($consumer_money / $total * $z_price, 2);
                        $res = $this->getModel('User')->where->inc('consumer_money', $consumer_money)->update();
                        // 添加日志
                        $event = $user_id . '退款' . $consumer_money . '消费金';
                        $rr = $this->getModel('DistributionRecord')->insert([
                                                                                'user_id' => $user_id,'from_id' => $user_id,'money' => $consumer_money,'sNo' => '','level' => 0,'event' => $event,'type' => 5,'add_date' => nowDate()
                        ]);
                        if ($rr < 1) {
                            $M->conn()->rollback();
                            // echo 'rollback1';
                            echo 0;
                            exit();
                        }
                        
                        // 判断单个商品退款是否有使用优惠
                        $res_openid = $this->getModel('User')
                            ->where([
                                        'user_id' => [
                                                        '=',$user_id
                                        ]
                        ])
                            ->fetchAll('wx_id');
                        $openid = $res_openid[0]->wx_id;
                        $froms = $this->get_fromid($openid);
                        $form_id = $froms['fromid'];
                        $page = 'pages/index/index';
                        // 消息模板id
                        $send_id = $template_id;
                        $keyword1 = array(
                                        'value' => $sNo,"color" => "#173177"
                        );
                        $keyword2 = array(
                                        'value' => $company,"color" => "#173177"
                        );
                        $keyword3 = array(
                                        'value' => $time,"color" => "#173177"
                        );
                        $keyword4 = array(
                                        'value' => '退款成功',"color" => "#173177"
                        );
                        $keyword5 = array(
                                        'value' => $consumer_money . '元消费金',"color" => "#173177"
                        );
                        $keyword6 = array(
                                        'value' => '预计24小时内到账',"color" => "#173177"
                        );
                        $keyword7 = array(
                                        'value' => '原支付方式',"color" => "#173177"
                        );
                        // 拼成规定的格式
                        $o_data = array(
                                        'keyword1' => $keyword1,'keyword2' => $keyword2,'keyword3' => $keyword3,'keyword4' => $keyword4,'keyword5' => $keyword5,'keyword6' => $keyword6,'keyword7' => $keyword7
                        );
                        
                        $res = $this->Send_Prompt($appid, $appsecret, $form_id, $openid, $page, $send_id, $o_data);
                        $this->get_fromid($openid, $form_id);
                        $res_o = $this->getModel('OrderDetails')
                            ->getCount([
                                        'r_sNo' => [
                                                        '=',$sNo
                                        ],'r_status' => [
                                                            '=','4'
                                        ]
                        ],'id');
                        if ($res_o <= 1) {
                            // 根据订单号、用户id,修改订单状态(6 订单关闭)
                            $r_u = $this->getModel('Order')->saveAll([
                                                                        'status' => 6
                            ], [
                                    'sNo' => [
                                                '=',$sNo
                                    ]
                            ]);
                        }
                        
                        // 根据订单号,查询商品id、商品名称、商品数量
                        $r_o = $this->getModel('OrderDetails')
                            ->where([
                                        'r_sNo' => [
                                                        '=',$sNo
                                        ]
                        ])
                            ->fetchAll('p_id,num,p_name,sid');
                        // 根据订单号,修改订单详情状态(订单关闭)
                        $r_d = $this->getModel('OrderDetails')->saveAll([
                                                                            'r_status' => 6
                        ], [
                                'r_sNo' => [
                                                '=',$sNo
                                ]
                        ]);
                        if ($r_d < 1) {
                            $M->conn()->rollback();
                            echo 0;
                            exit();
                        }
                        // 退款后还原商品数量
                        foreach ($r_o as $key => $value) {
                            $pid = $value->p_id;
                            // 商品id
                            $num = $value->num;
                            // 商品数量
                            $sid = $value->sid;
                            // 商品属性id
                            // 根据商品id,修改商品数量
                            $r_p = $this->getModel('Configure')->where->inc('num', $num)->update();
                            // 根据商品id,修改卖出去的销量
                            $r_x = $this->getModel('ProductList')->where->dec('volume', $num)
                                ->inc('num', $num)
                                ->update();
                            if ($r_d < 1 || $r_p < 1) {
                                $M->conn()->rollback();
                                echo 0;
                                exit();
                            }
                        }
                        if ($r_d && $r_o) {
                            $res = 1;
                        } else {
                            $res = 0;
                        }
                    } else {
                        $trade_no = $order_res[0]->trade_no;
                        $sNo = $order_res[0]->sNo;
                        
                        $p_price = $order_res[0]->p_price;
                        $user_id = $order_res[0]->user_id;
                        // 判断单个商品退款是否有使用优惠
                        
                        $allow = $order_res[0]->allow;
                        $reduce_price = $order_res[0]->reduce_price;
                        $coupon_price = $order_res[0]->coupon_price;
                        
                        $spz_price = $order_res[0]->spz_price;
                        $youhui_price = floatval($allow) + floatval($reduce_price) + floatval($coupon_price);
                        
                        $res_o = $this->getModel('OrderDetails')
                            ->getCount([
                                        'r_sNo' => [
                                                        '=',$sNo
                                        ]
                        ],'id');
                        
                        $total_fee = $order_res[0]->z_price;
                        if ($res_o <= 1) {
                            // 如果订单数量相等 则修
                            $z_price = $order_res[0]->z_price;
                        } else {
                            $z_price = number_format($order_res[0]->z_price / $spz_price * $p_price, 2);
                        }
                        
                        if (! empty($price)) {
                            $z_price = $price;
                        }
                        // 调起微信退款
                        $wxtk_res = $this->wxrefundapi($trade_no, $sNo . $id, $total_fee * 100, $z_price * 100, $appid, $mch_id, $mch_key,$cert_path);
                         //dump($wxtk_res);
                        $user_id = $order_res[0]->user_id;
                        $event = $user_id . '微信退款' . $z_price . '元余额-' . json_encode($wxtk_res);
                        $rr = $this->getModel('Record')->insert([
                                                                    'user_id' => $user_id,'money' => $z_price,'event' => $event,'type' => 5
                        ]);
                        if ($rr < 1) {
                            $M->conn()->rollback();
                            echo 0;
                            exit();
                        }
                        
                        if ($wxtk_res['return_code'] == 'SUCCESS') {
                            
                            // 查询openid
                            $res_openid = $this->getModel('User')
                                ->where([
                                            'user_id' => [
                                                            '=',$user_id
                                            ]
                            ])
                                ->fetchAll('wx_id');
                            $openid = $res_openid[0]->wx_id;
                            $froms = $this->get_fromid($openid);
                            $form_id = $froms['fromid'];
                            $page = 'pages/index/index';
                            // 消息模板id
                            $send_id = $template_id;
                            $keyword1 = array(
                                            'value' => $sNo,"color" => "#173177"
                            );
                            $keyword2 = array(
                                            'value' => $company,"color" => "#173177"
                            );
                            $keyword3 = array(
                                            'value' => $time,"color" => "#173177"
                            );
                            $keyword4 = array(
                                            'value' => '退款成功',"color" => "#173177"
                            );
                            $keyword5 = array(
                                            'value' => $z_price . '元',"color" => "#173177"
                            );
                            $keyword6 = array(
                                            'value' => '预计24小时内到账',"color" => "#173177"
                            );
                            $keyword7 = array(
                                            'value' => '原支付方式',"color" => "#173177"
                            );
                            // 拼成规定的格式
                            $o_data = array(
                                            'keyword1' => $keyword1,'keyword2' => $keyword2,'keyword3' => $keyword3,'keyword4' => $keyword4,'keyword5' => $keyword5,'keyword6' => $keyword6,'keyword7' => $keyword7
                            );
                            
                            $res = $this->Send_Prompt($appid, $appsecret, $form_id, $openid, $page, $send_id, $o_data);
                            
                            $this->get_fromid($openid, $form_id);
                            
                            $res_o = $this->getModel('OrderDetails')
                                ->getCount([
                                            'r_sNo' => [
                                                            '=',$sNo
                                            ],'r_status' => [
                                                                '=','4'
                                            ]
                            ],'id');
                            if ($res_o <= 1) {
                                // 根据订单号、用户id,修改订单状态(6 订单关闭)
                                $r_u = $this->getModel('Order')->saveAll([
                                                                            'status' => 6
                                ], [
                                        'sNo' => [
                                                    '=',$sNo
                                        ]
                                ]);
                            }
                            
                            // 根据订单号,查询商品id、商品名称、商品数量
                            $r_o = $this->getModel('OrderDetails')
                                ->where([
                                            'r_sNo' => [
                                                            '=',$sNo
                                            ]
                            ])
                                ->fetchAll('p_id,num,p_name,sid');
                            // 根据订单号,修改订单详情状态(订单关闭)
                            $r_d = $this->getModel('OrderDetails')->saveAll([
                                                                                'r_status' => 6
                            ], [
                                    'r_sNo' => [
                                                    '=',$sNo
                                    ]
                            ]);
                            if ($r_d < 1) {
                                $M->conn()->rollback();
                                echo 0;
                                exit();
                            }
                            // 退款后还原商品数量
                            foreach ($r_o as $key => $value) {
                                $pid = $value->p_id;
                                // 商品id
                                $num = $value->num;
                                // 商品数量
                                $sid = $value->sid;
                                // 商品属性id
                                
                                // 根据商品id,修改商品数量
                                $r_p = $this->getModel('Configure')->where->inc('num', $num)->update();
                                // 根据商品id,修改卖出去的销量
                                $r_x = $this->getModel('ProductList')->where->dec('volume', $num)
                                    ->inc('num', $num)
                                    ->update();
                                if ($r_d < 1 || $r_p < 1) {
                                    $M->conn()->rollback();
                                    echo 0;
                                    exit();
                                }
                            }
                            if ($r_u && $r_d && $r_o) {
                                $res = 1;
                            } else {
                                $res = 0;
                            }
                        } else {
                            $res = 0;
                        }
                    }
                } else {
                    $res = 0;
                }
            }
        } else {
            if ($m == 8) {
                $order_res = $this->getModel('Order')
                    ->alias('a')
                    ->join('order_details m', 'a.sNo=m.r_sNo', 'LEFT')
                    ->fetchWhere([
                                    'm.id' => [
                                                '=',$id
                                    ],'a.status' => [
                                                        '=','4'
                                    ]
                ], 'a.id,a.trade_no,a.sNo,a.pay,a.z_price,a.user_id');
                $sNo = $order_res[0]->sNo;
                $z_price = $order_res[0]->z_price;
                $user_id = $order_res[0]->user_id;
                // 根据订单号、用户id,修改订单状态
                $res1 = $this->getModel('Order')->saveAll([
                                                                'status' => 1
                ], [
                        'sNo' => [
                                    '=',$sNo
                        ]
                ]);
                
                // 根据订单号,修改订单详情状态
                $res2 = $this->getModel('OrderDetails')->saveAll([
                                                                    'r_status' => 1,'r_content' => $text
                ], [
                        'r_sNo' => [
                                        '=',$sNo
                        ]
                ]);
                
                if ($res1 < 1 || $res2 < 1) {
                    $M->conn()->rollback();
                    echo 0;
                    exit();
                }
                // 查询openid
                $res_openid = $this->getModel('User')
                    ->where([
                                'user_id' => [
                                                '=',$user_id
                                ]
                ])
                    ->fetchAll('wx_id');
                $openid = $res_openid[0]->wx_id;
                $froms = $this->get_fromid($openid);
                $form_id = $froms['fromid'];
                $page = 'pages/index/index';
                // 消息模板id
                $send_id = $template_id;
                $keyword1 = array(
                                'value' => $sNo,"color" => "#173177"
                );
                $keyword2 = array(
                                'value' => $company,"color" => "#173177"
                );
                $keyword3 = array(
                                'value' => $time,"color" => "#173177"
                );
                $keyword4 = array(
                                'value' => '退款失败',"color" => "#173177"
                );
                $keyword5 = array(
                                'value' => $z_price . '元',"color" => "#173177"
                );
                $keyword6 = array(
                                'value' => $text,"color" => "#173177"
                );
                $keyword7 = array(
                                'value' => '系统更改订单状态',"color" => "#173177"
                );
                // 拼成规定的格式
                $o_data = array(
                                'keyword1' => $keyword1,'keyword2' => $keyword2,'keyword3' => $keyword3,'keyword4' => $keyword4,'keyword5' => $keyword5,'keyword6' => $keyword6,'keyword7' => $keyword7
                );
                
                $res = $this->Send_Prompt($appid, $appsecret, $form_id, $openid, $page, $send_id, $o_data);
                $this->get_fromid($openid, $form_id);
            } else {
                $text = htmlentities($request->param('text'));
                $res = $this->getModel('OrderDetails')->saveAll([
                                                                    'r_type' => $m,'r_content' => $text
                ], [
                        'id' => [
                                    '=',$id
                        ]
                ]);
                if ($res < 1) {
                    $M->conn()->rollback();
                    echo 0;
                    exit();
                }
            }
        }
        
        if ($res) {
            $this->recordAdmin($admin_id, ' 批准订单详情id为 ' . $id . ' 退货 ', 9);
            $M->conn()->commit();
            echo 1;
        } else {
            $this->recordAdmin($admin_id, ' 批准订单详情id为 ' . $id . ' 退货失败 ', 9);
          //  echo 'rollback-3-';
            $M->conn()->rollback();
            echo 0;
        }
        
        exit();
    }

    public function Send_Prompt($appid, $appsecret, $form_id, $openid, $page, $send_id, $o_data)
    {
        $AccessToken = $this->getAccessToken($appid, $appsecret);
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $AccessToken;
        $data = json_encode(array(
                                'access_token' => $AccessToken,'touser' => $openid,'template_id' => $send_id,'form_id' => $form_id,'page' => $page,'data' => $o_data
        ));
        $da = $this->httpsRequest($url, $data);
        return $da;
    }

    public function get_fromid($openid, $type = '')
    {
        if (empty($type)) {
            $fromidres = $this->getModel('UserFromid')
                ->field('fromid,open_id')
                ->where("open_id", '=', function ($query) use ($openid) {
                $query->name('user_fromid')
                    ->where('open_id', '=', $openid)
                    ->max('id');
            })
                ->select();
            if ($fromidres) {
                $fromid = $fromidres[0]->fromid;
                $arrayName = array(
                                'openid' => $openid,'fromid' => $fromid
                );
                return $arrayName;
            } else {
                return array(
                            'openid' => $openid,'fromid' => '123456'
                );
            }
        } else {
            $re2 = $this->getModel('UserFromid')->delete($type, 'fromid');
            return $re2;
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

    function getAccessToken($appID, $appSerect)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appID . "&secret=" . $appSerect;
        // 时效性7200秒实现
        // 1.当前时间戳
        $currentTime = time();
        // 2.修改文件时间
        $fileName = "accessToken";
        // 文件名
        if (is_file($fileName)) {
            $modifyTime = filemtime($fileName);
            if (($currentTime - $modifyTime) < 7200) {
                // 可用, 直接读取文件的内容
                $accessToken = file_get_contents($fileName);
                return $accessToken;
            }
        }
        // 重新发送请求
        $result = $this->httpsRequest($url);
        $jsonArray = json_decode($result, true);
        // 写入文件
        $accessToken = $jsonArray['access_token'];
        file_put_contents($fileName, $accessToken);
        return $accessToken;
    }

    /*
     * 发送请求 @param $ordersNo string 订单号 @param $refund string 退款单号 @param $price float 退款金额 return array
     */
    private function wxrefundapi($ordersNo, $refund, $total_fee, $price, $appid, $mch_id, $mch_key,$cert_path='')
    {
        // 通过微信api进行退款流程
        $parma = array(
                    'appid' => $appid,'mch_id' => $mch_id,'nonce_str' => $this->createNoncestr(),'out_refund_no' => $refund,'out_trade_no' => $ordersNo,'total_fee' => $total_fee,'refund_fee' => $price,'op_user_id' => $appid
        );
        $parma['sign'] = $this->getSign($parma, $mch_key);
        $xmldata = $this->arrayToXml($parma);
        $xmlresult = $this->postXmlSSLCurl($xmldata, 'https://api.mch.weixin.qq.com/secapi/pay/refund',$cert_path);
        $result = $this->xmlToArray($xmlresult);
        return $result;
    }

    /*
     * 生成随机字符串方法
     */
    protected function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i ++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /*
     * 对要发送到微信统一下单接口的数据进行签名
     */
    protected function getSign($Obj, $mch_key)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        // 签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        // 签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $mch_key;
        // 签名步骤三：MD5加密
        $String = md5($String);
        // 签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    /*
     * 排序并格式化参数方法，签名时需要使用
     */
    protected function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            // $buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    // 数组转字符串方法
    protected function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    protected function xmlToArray($xml)
    {
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    // 需要使用证书的请求
    private function postXmlSSLCurl($xml, $url, $cert_path='',$second = 30)
    {
        $ch = curl_init();
        // 超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        // 这里设置代理，如果有的话
        // curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        // curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // 设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // 设置证书
        // 使用证书：cert 与 key 分别属于两个.pem文件
        // 默认格式为PEM，可以注释
        $cert = check_file(PUBLIC_PATH.DS.$cert_path.'/apiclient_cert.pem');
        $key = check_file(PUBLIC_PATH.DS.$cert_path.'/apiclient_key.pem');
        
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        // 默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
        // post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        // 返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl error errorcode:$error" . "<br>";
            curl_close($ch);
            return false;
        }
    }

    private function do_addsign($request)
    {
		
        $admin_id = Session::get('admin_id');
        $sNo = trim($request -> param('sNo')); // 订单号
        $id = trim($request -> param('id')); // 订单id
		$trade = intval($request -> param('trade')) - 1;
		$express_id = $request -> param('express'); // 快递公司id
		$courier_num = $request -> param('courier_num'); // 快递单号
		$otype = addslashes(trim($request -> param('otype'))); // 类型
		$express_name = $request -> param('express_name'); // 快递公司名称

		$time = date('Y-m-d H:i:s', time());
		$data=[];
		if (!empty($express_id)) {
			$data['express_id']=$express_id;
		}else{
            echo 2;
            exit;
        }
		if (!empty($courier_num)) {
		     $data['courier_num'] =$courier_num;
		}else{
            echo 3;
            exit;
        }
		$data['deliver_time']= $time;;

		if ($otype == 'yb') {

			$r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
			if ($r) {
				$appid = $r[0] -> appid;
				// 小程序唯一标识
				$appsecret = $r[0] -> appsecret;
				// 小程序的 app secret
				$company = $r[0] -> company;
			}
			$res_o=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$sNo],'r_status'=>['=','4']])->fetchAll('id');
			if ($res_o <= 1) {
				$rl=$this->getModel('Order')->saveAll(['status'=>$trade],['sNo'=>['=',$sNo]]);
			}
			$rd=$this->getModel('OrderDetails')->saveAll(['r_status'=>$data],['id'=>['=',$id]]);
			//查询订单信息
			$res_p=$this->getModel('Order')->alias('o')->join('order_details d','o.sNo=d.r_sNo','left')->fetchWhere(['d.id'=>['=',$id]],'o.id,o.user_id,o.sNo,d.p_name,o.name,o.address');
			foreach ($res_p as $key => $value) {
				$p_name = $value -> p_name;
				$user_id = $value -> user_id;
				$address = $value -> address;
				$name = $value -> name;
				$order_id = $value -> id;
				//查询openid
				$res_openid=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->fetchAll('wx_id');
				$openid = $res_openid[0] -> wx_id;
				$froms = $this -> get_fromid($openid);
				$form_id = $froms['fromid'];
				$page = 'pages/order/detail?orderId=' . $order_id;
				//消息模板id

				$r=$this->getModel('Notice')->where(['id'=>['=','1']])->fetchAll('*');
				$template_id = $r[0] -> order_delivery;

				$send_id = $template_id;
				$keyword1 = array('value' => $express_name, "color" => "#173177");
				$keyword2 = array('value' => $time, "color" => "#173177");
				$keyword3 = array('value' => $p_name, "color" => "#173177");
				$keyword4 = array('value' => $sNo, "color" => "#173177");
				$keyword5 = array('value' => $address, "color" => "#173177");
				$keyword6 = array('value' => $courier_num, "color" => "#173177");
				$keyword7 = array('value' => $name, "color" => "#173177");
				//拼成规定的格式
				$o_data = array('keyword1' => $keyword1, 'keyword2' => $keyword2, 'keyword3' => $keyword3, 'keyword4' => $keyword4, 'keyword5' => $keyword5, 'keyword6' => $keyword6, 'keyword7' => $keyword7);
				$res = $this -> Send_Prompt($appid, $appsecret, $form_id, $openid, $page, $send_id, $o_data);
				$this -> get_fromid($openid, $form_id);
			}
			if ($rl > 0 && $rd > 0) {
                $this->recordAdmin($admin_id,' 使订单号为 '.$sNo.' 的订单发货 ',7);
                echo 1;
				exit;
			}else{
                $this->recordAdmin($admin_id,'发货失败 ',7); 
                echo 0;
				exit;
			}

		} else if ($otype == 'pt') {
			$rl=$this->getModel('Order')->saveAll(['status'=>2],['sNo'=>['=','']]);
			$rd = $this->getModel('orderDetails')->save($data,$sNo,'r_sNo');
			$msgres=$this->getModel('Order')->alias('o')->join('order_details d','o.sNo=d.r_sNo','left')->fetchWhere(['o.sNo'=>['=',$sNo]],'o.id,o.user_id,o.sNo,d.p_name,o.name,o.address');
			if (!empty($msgres))
				$msgres = $msgres[0];
			$uid = $msgres -> user_id;
			$openid=$this->getModel('User')->fetchWhere(['user_id'=>['=',$uid]],'wx_id');
			$msgres -> uid = $openid[0] -> wx_id;
			$compres=$this->getModel('Express')->where(['id'=>['=',$express_id]])->fetchAll('kuaidi_name');
			if (!empty($compres))
				$msgres -> company = $compres[0] -> kuaidi_name;
			$fromid = $this->getModel('userFromid')->field('fromid')->where("open_id","=",function($query) use($msgres){
			   $query->name('user_fromid')->where('open_id','=',$msgres->uid)->max('id'); 
			})->select();
			if (!empty($fromid))
				$msgres -> fromid = $fromid[0] -> fromid;
			$msgres -> courier_num = $courier_num;

			if ($rl > 0 && $rd > 0) {
				$r=$this->getModel('Notice')->where(['id'=>['=','1']])->fetchAll('*');
				$template_id = $r[0] -> order_delivery;
				$res = $this -> Send_success($msgres, $template_id);

                $this->recordAdmin($admin_id,' 使订单号为 '.$sNo.' 的订单发货 ',7);

                echo 1;
				exit();
			}
			echo "string2";exit;
		}
	exit;
	}

	
    private function do_set($request)
    {
        $name = trim($request -> param('name'));
        $tel = trim($request -> param('tel'));

        if(strlen($tel) >15){
            $this->error('手机号码格式错误！',$this->module_url."/returns/set");
            exit;
        }
        $user_id=Session::get('admin_id');
        
        $address = trim($request -> param('address'));
        
        $sheng=trim($request->post('Select1'));
        $shi=trim($request->post('Select2'));       
        $quyu=trim($request->post('Select3'));
        
        $sheng2=trim($request->post('s_name'));
        $shi2=trim($request->post('c_name'));
        $quyu2=trim($request->post('q_name'));
        $r=$this->getModel('UserAddress')->where(['uid'=>['=',$user_id]])->fetchAll('*');
        if($r){
            $r=$this->getModel('UserAddress')
            ->saveAll(['name'=>$name,'tel'=>$tel,'address'=>$address,'sheng'=>$sheng,'city'=>$shi,'quyu'=>$quyu,'address_xq'=>$sheng2.$shi2.$quyu2.$address]
                ,['uid'=>['=',$user_id]]);
        }else{
            $r=$this->getModel('UserAddress')->insert(['uid'=>$user_id,'name'=>$name,'tel'=>$tel,'address'=>$address,'sheng'=>$sheng,'city'=>$shi,'quyu'=>$quyu,'address_xq'=>$sheng2.$shi2.$quyu2.$address]);
        }
        if($r ==false) {
            $this->error('未知原因，修改失败！',$this->module_url."/returns/set");
            
        } else {
            $this->success('修改成功！',$this->module_url."/returns/set");
        }
    }

    
    public function set(Request $request)
    {
        $request->method()=='post'&&$this->do_set($request);
        $user_id=Session::get('admin_id');
        $r = $this->getModel('UserAddress')
            ->where([
                        'uid' => [
                                    '=',$user_id
                        ]
        ])
            ->fetchAll('*');
        if ($r) {
            $r = $r[0];
        }else 
            $r=(object)['name'=>'','address'=>'','tel'=>'','sheng'=>'','city'=>'','quyu'=>''];
        // var_dump($r,$r->name);
        $this->assign("list", $r);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function view(Request $request)
    {
        // 接收信息
        $id = intval($request->param("id")); // 产品id
                                             
        // 根据产品id，查询产品产品信息
        
        $r = $this->getModel('OrderDetails')
            ->where([
                        'id' => [
                                    '=',$id
                        ]
        ])
            ->fetchAll('*');
        
        $this->assign("list", $r);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
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