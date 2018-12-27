<?php
namespace app\admin\controller;

use core\Request;
use core\Session;
use core\Response;

class Orderslist extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    public function Detail(Request $request)
    {
        $id = intval($request->param('id')); // 订单id
        
        $r = $this->getConfig();
        
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        
        $res = $this->getModel('OrderDetails')
            ->field("u.user_name,l.sNo,l.name,l.mobile,l.sheng,l.shi,l.z_price,l.xian,l.status,l.address,l.pay,l.trade_no,l.coupon_id,l.reduce_price,l.coupon_price,l.allow,l.drawid,l.otype,d.user_id,d.p_id,d.p_name,d.p_price,d.num,d.unit,d.add_time,d.deliver_time,d.arrive_time,d.r_status,d.content,d.express_id,d.courier_num,d.sid,d.size,d.freight")
            ->alias('d')
            ->join("lkt_order l", "l.sNo=d.r_sNo", 'left')
            ->join("lkt_user u", "u.user_id=l.user_id", 'left')
            ->where('l.id', '=', $id)
            ->select();
        
        $num = count($res);
        $data = array();
        $reduce_price = 0; // 满减金额
        $coupon_price = 0; // 优惠券金额
        $allow = 0; // 积分
        foreach ($res as $k => $v) {
            $res[$k]->index = $k;
            $sid = $v->sid;
            $data['user_name'] = $v->user_name; // 联系人
            $data['name'] = $v->name; // 联系人
            
            $data['sNo'] = $v->sNo; // 订单号
            
            $data['mobile'] = $v->mobile; // 联系电话
            
            $data['address'] = $v->address; // 详细地址
            
            $data['add_time'] = $v->add_time; // 添加时间
            
            $data['z_price'] = $v->z_price; // 添加时间
            
            $data['user_id'] = $v->user_id; // 用户id
            
            $data['deliver_time'] = $v->deliver_time; // 发货时间
            
            $data['arrive_time'] = $v->arrive_time; // 到货时间
            
            $data['r_status'] = $v->r_status; // 订单详情状态
            
            $data['status01'] = $v->r_status; // 订单详情状态
            
            $data['gstatus'] = $v->status; // 订单详情状态
            
            $data['otype'] = $v->otype; // 订单类型
            
            $data['content'] = $v->content; // 退货原因
            
            $data['express_id'] = $v->express_id; // 快递公司id
            
            $data['courier_num'] = $v->courier_num; // 快递单号
            
            $data['drawid'] = $v->drawid; // 抽奖ID
            $reduce_price = $v->reduce_price; // 满减金额
            $coupon_price = $v->coupon_price; // 优惠券金额
            $allow = $v->allow; // 积分
            
            $data['paytype'] = $v->pay; // 支付方式
            
            $data['trade_no'] = $v->trade_no; // 微信支付交易号
            $data['freight'] = $v->freight; // 运费
            
            $data['id'] = $id;
            
            // 根据产品id,查询产品主图
            
            $img = $this->getModel('ProductList')->fetchWhere([
                                                                    'id' => [
                                                                                '=',$v->p_id
                                                                    ]
            ], 'imgurl');
            
            if (! empty($img)) {
                
                $v->pic = $img[0]->imgurl;
                
                $res[$k] = $v;
            }
            
            $res[$k]->z_price = $v->num * $v->p_price;
            
            $user_id = $v->user_id; // 用户id
            
            $drawid = $v->drawid; // 抽奖ID
            
            $add_time = $v->add_time; // 添加时间
            
            if (! empty($drawid) && $drawid != 0) {
                
                $r07 = $this->getModel('DrawUser')
                    ->where([
                                'id' => [
                                            '=',$drawid
                                ]
                ])
                    ->fetchAll('*');
                
                if (! empty($r07)) {
                    
                    $lottery_status = $r07[0]->lottery_status;
                    
                    // print_r($r07);die;
                    
                    $data['lottery_status'] = $lottery_status;
                } else {
                    
                    $data['lottery_status'] = 7;
                }
            } else {
                
                $data['lottery_status'] = 7;
            }
        }
        
        if (isset($data['express_id'])) {
            
            $exper_id = $data['express_id'];
            
            // 根据快递公司id,查询快递公司表信息
            
            $r03 = $this->getModel('Express')
                ->where([
                            'id' => [
                                        '=',$exper_id
                            ]
            ])
                ->fetchAll('*');
            $data['express_name'] = $r03[0]->kuaidi_name; // 快递公司名称
        } else {
            
            $data['express_name'] = '';
        }
        
        if ($data['otype'] == 'pt') {
            
            switch ($data['gstatus']) {
                
                case 0:
                    
                    $data['r_status'] = '未付款';
                    
                    break;
                
                case 9:
                    
                    $data['r_status'] = '拼团中';
                    
                    break;
                
                case 1:
                    
                    $data['r_status'] = '拼团成功-未发货';
                    
                    break;
                
                case 2:
                    
                    $data['r_status'] = '拼团成功-已发货';
                    
                    break;
                
                case 3:
                    
                    $data['r_status'] = '拼团成功-已签收';
                    
                    break;
                
                case 10:
                    
                    $data['r_status'] = '拼团失败-未退款';
                    
                    break;
                
                case 11:
                    
                    $data['r_status'] = '拼团失败-已退款';
                    
                    break;
            }
        } else {
            
            if ($data['r_status'] == 0) {
                
                $data['r_status'] = '未付款';
            } else if ($data['r_status'] == 1) {
                
                $data['r_status'] = '未发货';
            } else if ($data['r_status'] == 2) {
                
                $data['r_status'] = '已发货';
            } else if ($data['r_status'] == 3) {
                
                $data['r_status'] = '待评论';
            } else if ($data['r_status'] == 4) {
                
                $data['r_status'] = '退货';
            } else if ($data['r_status'] == 5) {
                
                $data['r_status'] = '已完成';
            } else if ($data['r_status'] == 6) {
                
                $data['r_status'] = '订单关闭';
            } else if ($data['r_status'] == 12) {
                
                $data['r_status'] = '已完成';
            }
        }
        
        $status = 0;
        
        $sNo = trim($request->param('sNo'));
        
        $trade = intval($request->param('trade')) - 1;
        
        $express_id = $request->param('kuaidi'); // 快递公司id
        
        $courier_num = $request->param('danhao'); // 快递单号
        
        $freight = $request->param('yunfei'); // 运费
        
        $time = date('Y-m-d h:i:s', time());
        
        if (! empty($sNo) && ! empty($trade)) {
            
            $r01 = $this->getModel('Order')
                ->where([
                            'sNo' => [
                                        '=',$sNo
                            ]
            ])
                ->fetchAll('*');
            
            $data['status01'] = $r01[0]->status; // 根据订单号查询该订单的状态
        }
        
        $rl = $this->getModel('Order')->saveAll([
                                                    'status' => $trade
        ], [
                'sNo' => [
                            '=',$sNo
                ]
        ]);
        
        $rd = $this->getModel('OrderDetails')->saveAll([
                                                            'r_status' => $trade
        ], [
                'r_sNo' => [
                                '=',$sNo
                ]
        ]);
        
        if ($rl > 0 && $rd > 0) {
            
            echo json_encode(array(
                                    'status' => 1,'msg' => '操作成功!'
            ));
            exit();
        }
        
        $r02 = $this->getModel('express')->fetchAll();
        
        // 佣金信息
        $rlud = $this->getModel('distribution')
            ->field("a.*,b.user_name,b.headimgurl")
            ->alias('a')
            ->join("lkt_user b", "a.user_id = b.user_id")
            ->where("a.sNo = '" . $data['sNo'] . "' and a.level >0")
            ->order([
                        'level' => 'asc'
        ])
            ->select();
        if (empty($rlud)) {
            $this->assign("fenxiaoshang", 1);
        } else {
            foreach ($rlud as $keyl => $valuel) {
                $user_id = $valuel->user_id;
                $dd[$keyl]['level'] = $valuel->level;
                $dd[$keyl]['money'] = $valuel->money;
                $dd[$keyl]['user_name'] = $valuel->user_name;
                $dd[$keyl]['headimgurl'] = $valuel->headimgurl;
            }
            $this->assign("fenxiaoshang", $dd);
        }
        
        $reduce_price = 0; // 满减金额
        $coupon_price = 0; // 优惠券金额
        $allow = 0; // 积分
        $this->assign("express", $r02);
        $this->assign("uploadImg", $uploadImg);
        $this->assign("data", $data);
        $this->assign("detail", $res);
        $this->assign("express", $r02);
        $this->assign("reduce_price", $reduce_price);
        $this->assign("coupon_price", $coupon_price);
        $this->assign("allow", $allow);
        $this->assign("num", $num);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function Index(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        $ordtype = array(
                        't0' => '全部订单','t1' => '普通订单','t2' => '拼团订单','t3' => '抽奖订单'
        );
        $data = array(
                    '未付款','未发货','已发货','待评论','退货','已签收'
        );
        $otype = isset($request['otype']) && $request['otype'] !== '' ? $request['otype'] : false;
        $status = isset($request['status']) && $request['status'] !== '' ? $request['status'] : false;
        $ostatus = isset($request['ostatus']) && $request['ostatus'] !== '' ? $request['ostatus'] : false;
        $sNo = isset($request['sNo']) && $request['sNo'] !== '' ? $request['sNo'] : false;
        $brand = trim($request->param('brand'));
        $prostr = '';
        $URL = '';
        $con = '';
        foreach ($request->get as $key => $value001) {
            $con .= "&$key=$value001";
        }
        if ($brand) {
            $prostr .= " and lpl.brand_id = '$brand'";
        }
        $brand_str = '';
        $r01 = $this->getModel('BrandClass')->fetchAll('brand_id,brand_name');
        foreach ($r01 as $key => $value) {
            if ($brand == $value->brand_id) {
                $brand_str .= "<option selected='selected' value='$value->brand_id'>$value->brand_name</option>";
            } else {
                $brand_str .= "<option value='$value->brand_id'>$value->brand_name</option>";
            }
        }
        
        $condition = ' 1=1';
        
        $pageto = $request->param('pageto');
        // 导出
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : '10';
        // 每页显示多少条数据
        $page = $request->param('page');
        
        // 页码
        if ($page) {
            $start = ($page - 1) * 10;
        } else {
            $start = 0;
        }
        
        $source = trim($request->param('source'));
        $source_str = '';
        if ($source == 1) {
            $condition .= " and o.source = '1' ";
            $source_str .= "<option selected='selected' value='1'>小程序</option><option value='2'>APP</option>";
        } else if ($source == 2) {
            $condition .= " and o.source = '2' ";
            $source_str .= "<option value='1'>小程序</option><option selected='selected' value='2'>APP</option>";
        } else {
            $source_str .= "<option value='1'>小程序</option><option value='2'>APP</option>";
        }
        
        $startdate = $request->param("startdate");
        $enddate = $request->param("enddate");
        if ($startdate != '') {
            $condition .= " and add_time >= '$startdate 00:00:00' ";
        }
        if ($enddate != '') {
            $condition .= " and add_time <= '$enddate 23:59:59' ";
        }
        
        if ($otype == 't1') {
            $condition .= " and o.otype!='pt' and o.drawid=0";
        } else if ($otype == 't2') {
            $condition .= " and o.otype='pt'";
        } else if ($otype == 't3') {
            $condition .= " and o.otype!='pt' and o.drawid>0";
        }
        
        if (strlen($status) == 1) {
            if ($status !== false) {
                $cstatus = intval($status);
                $condition .= " and o.status=$cstatus";
            }
        } else if (strlen($status) > 1) {
            if ($status !== false) {
                $cstatus = intval(substr($status, 1));
                $condition .= " and o.ptstatus=$cstatus";
            }
        }
        if ($ostatus !== false) {
            $costatus = intval(substr($ostatus, 1));
            $condition .= " and o.status=$costatus";
        }
        if ($sNo !== false)
            $condition .= ' and (o.sNo like "%' . $sNo . '%" or o.name like "%' . $sNo . '%" or o.mobile like "%' . $sNo . '%")';
        $class = '';
        foreach ($data as $k => $v) {
            if ($status === false) {
                $class .= '<option value="' . $k . '">' . $v . '</option>';
            } else {
                $ystatus = intval($status);
                if ($ystatus === $k) {
                    $class .= '<option selected="selected" value="' . $k . '">' . $v . '</option>';
                } else {
                    $class .= '<option value="' . $k . '">' . $v . '</option>';
                }
            }
        }
        
        // $uploadImg=$this->getModel('Order')->alias('o')->join('user lu','o.user_id=lu.user_id','left')->where($condition)->fetchOrder(['add_time'=>'desc'],'SUM(o.z_price) as z_price,COUNT(o.id) as num');
        if (empty($uploadImg)) {
            $rcf = $this->getConfig();
            $uploadImg = $rcf[0]->uploadImg;
        }
        $this->assign("uploadImg", $uploadImg);
        $resd_total = $this->getModel('Order')
            ->alias('o')
            ->where($condition)
            ->join("user lu", "o.user_id=lu.user_id", 'left')
            ->fetchOrder([
                            'add_time' => 'desc'
        ], "SUM(o.z_price) as z_price,COUNT(o.id) as num");
        $total = $resd_total[0]->num;
        $data1['num'] = $total;
        $data1['numprice'] = $resd_total[0]->z_price;
        
        if ($pageto == 'all') {
            $res1 = $this->getModel('Order')
                ->alias('o')
                ->join('user lu', 'o.user_id=lu.user_id', 'left')
                ->where($condition)
                ->fetchOrder([
                                'add_time' => 'desc'
            ], 'o.id,o.consumer_money,o.sNo,o.name,o.sheng,o.shi,o.xian,o.source,o.address,o.add_time,o.mobile,o.z_price,o.status,o.reduce_price,o.coupon_price,o.allow,o.drawid,o.otype,o.ptstatus,o.spz_price,o.pay,o.drawid,lu.user_name,o.user_id');
            
            $this->recordAdmin($admin_id, ' 导出订单全部信息 ', 4);
        } else {
            $res1 = $this->getModel('Order')
                ->alias('o')
                ->join('user lu', 'o.user_id=lu.user_id', 'left')
                ->where($condition)
                ->order([
                            'add_time' => 'desc'
            ])
                ->field('o.id,o.consumer_money,o.sNo,o.name,o.sheng,o.shi,o.xian,o.source,o.address,o.add_time,o.mobile,o.z_price,o.status,o.reduce_price,o.coupon_price,o.allow,o.drawid,o.otype,o.ptstatus,o.spz_price,o.pay,o.drawid,lu.user_name,o.user_id')
                ->paginator($pagesize,$this->getUrlConfig($request->url));
            
            if ($pageto == 'ne') {
                $this->recordAdmin($admin_id, ' 导出订单第 ' . $page . ' 的信息 ', 4);
            }
        }
        $url = '".$this->module_url."/orderslist' . $con;
        if ($pageto == 'all')
            $pages_show = '';
        else
            $pages_show = $res1->render();
        // $pages_show = $db->multipage('".$this->module_url."/orderslist'.$con,ceil($total/$pagesize),$page, $para = '');
        
        // 获取目前设置的分销商品
        $distribution_products = $this->getModel('productList')
            ->alias('a')
            ->join('configure c', 'a.id=c.pid', 'right')
            ->where("a.is_distribution = 1 and a.num >0")
            ->fetchGroup("c.pid", 'a.id');
        foreach ($distribution_products as $key => $value) {
            $distribution_products[$key] = $value->id;
        }
        $distribution_products = (array) $distribution_products;
        foreach ($res1 as $k => $v) {
            $freight = 0;
            $res1[$k]->weixin_pay ='';
            $res1[$k]->balance_pay = '';
            $res1[$k]->total = 0;
            $res1[$k]->statu = $res1[$k]->status;
            $zqprice = 0;
            $order_id = $v->sNo;
            $pay = $v->pay;
            // $res1[$k] ->consumer_money = $vp->consumer_money;
            if ($pay == 'combined_Pay') {
                $pres = $this->getModel('CombinedPay')
                    ->where([
                                'order_id' => [
                                                '=',$order_id
                                ]
                ])
                    ->fetchAll('weixin_pay,balance_pay,total');
                foreach ($pres as $kp => $vp) {
                    $res1[$k]->weixin_pay = $vp->weixin_pay;
                    $res1[$k]->balance_pay = $vp->balance_pay;
                    $res1[$k]->total = $vp->total;
                }
            }
            
            $user_id = $v->user_id;
            $products = $this->getModel("orderDetails")
                ->alias('lod')
                ->join("product_list lpl", "lpl.id=lod.p_id", 'left')
                ->fetchWhere("r_sNo = '" . $v->sNo . "'" . $prostr, "lpl.imgurl,lpl.product_title,lpl.product_number,lod.p_price,lod.unit,lod.num,lod.size,lod.p_id,lod.courier_num,lod.express_id,lod.freight");
            $res1[$k]->freight = $freight;
            if (sizeof($products) > 0) {
                foreach ($products as $kd => $vd) {
                    $freight += $vd->freight;
                }
                $res1[$k]->products = $products;
                
                if ($v->otype == 'pt') {
                    switch ($v->status) {
                        case 0:
                            $res1[$k]->status = '未付款';
                            $res1[$k]->bgcolor = '#f5b1aa';
                            break;
                        case 9:
                            $res1[$k]->status = '拼团中';
                            $res1[$k]->bgcolor = '#f5b199';
                            break;
                        case 1:
                            $res1[$k]->status = '拼团成功-未发货';
                            $res1[$k]->bgcolor = '#f0908d';
                            break;
                        case 2:
                            $res1[$k]->status = '拼团成功-已发货';
                            $res1[$k]->bgcolor = '#f0908d';
                            break;
                        case 3:
                            $res1[$k]->status = '拼团成功-已签收';
                            $res1[$k]->bgcolor = '#f0908d';
                            break;
                        case 5:
                            $res1[$k]->status = '已签收';
                            $res1[$k]->bgcolor = '#f7b977';
                            break;
                        case 10:
                            $res1[$k]->status = '拼团失败-未退款';
                            $res1[$k]->bgcolor = '#ee827c';
                            break;
                        case 11:
                            $res1[$k]->status = '拼团失败-已退款';
                            $res1[$k]->bgcolor = '#ee827c';
                            break;
                    }
                } else {
                    switch ($v->status) {
                        case 0:
                            $res1[$k]->status = '未付款';
                            $res1[$k]->bgcolor = '#f5b1aa';
                            break;
                        case 1:
                            $res1[$k]->status = '未发货';
                            $res1[$k]->bgcolor = '#f09199';
                            break;
                        case 2:
                            $res1[$k]->status = '已发货';
                            $res1[$k]->bgcolor = '#f19072';
                            break;
                        case 3:
                            $res1[$k]->status = '待评论';
                            $res1[$k]->bgcolor = '#e4ab9b';
                            break;
                        case 4:
                            $res1[$k]->status = '退货';
                            $res1[$k]->bgcolor = '#e198b4';
                            break;
                        case 6:
                            $res1[$k]->status = '订单关闭';
                            $res1[$k]->bgcolor = '#ffbd8b';
                            break;
                        case 5:
                            $res1[$k]->status = '已完成';
                            $res1[$k]->bgcolor = '#f7b977';
                            break;
                        case 12:
                            $res1[$k]->status = '已完成';
                            $res1[$k]->bgcolor = '#f7b977';
                            break;
                    }
                }
                $res1[$k]->kuaidi_name ='';             
                if ($products[0]->express_id) {
                    $exper_id = $products[0]->express_id;
                    $r03 = $this->getModel('Express')
                        ->where([
                                    'id' => [
                                                '=',$exper_id
                                    ]
                    ])
                        ->fetchAll('*');
                    $res1[$k]->kuaidi_name = $r03[0]->kuaidi_name; // 快递公司名称
                }
                
                $str = '';
                $res1[$k]->yongjin = $str;
            }
            $res1[$k]->freight = $freight;
        }
        $r02 = $this->getModel('Express')->fetchAll('*');
        $this->assign("express", $r02);
        $res = 1;
        $this->assign('res', $res);
        $this->assign("source", $source_str);
        $this->assign("brand_str", $brand_str);
        $this->assign("startdate", $startdate);
        $this->assign("enddate", $enddate);
        $this->assign("ordtype", $ordtype);
        $this->assign("class", $class);
        $this->assign("order", $res1);
        $this->assign("sNo", $sNo);
        $this->assign("otype", $otype);
        $this->assign("status", $status);
        $this->assign("ostatus", $ostatus);
        $this->assign('pageto', $pageto);
        $this->assign('now_data', nowDate());
        $this->assign('pages_show', $pages_show);
        $this->assign('data1', $data1);
        if ($pageto != '') {
            $r = time();
            $str = $this->fetch('excel');
            return Response::instance($str, 'excel', 200, [
                                                                "Content-Disposition" => "attachment;filename=orders-" . $r . "xls",'content-type' => "application/msexcel;charset=utf-8"
            ]);
            exit();
        }
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function deliver(Request $request)
    {
        $sNo = $request->post('sNo');
        $otype = $request->post('otype');
        $danhao = $request->post('danhao');
        $kuaidi = $request->post('kuaidi');
        if (! empty($sNo)) {
            try {
                if ($this->getModel('order')->save([
                                                        'status' => 2
                ], $sNo, 'sNo')) {
                    if ($this->getModel('orderDetails')->save([
                                                                    'r_status' => 2,'express_id' => $kuaidi,'courier_num' => $danhao,'deliver_time' => nowDate()
                    ], $sNo, 'r_sNo'))
                        $this->success('操作成功', $this->module_url . '/orderslist');
                }
            } catch (\Exception $e) {
                $this->getModel('order')->save([
                                                    'status' => 1
                ], $sNo, 'sNo');
            }
        }
        $this->error('操作失败', $this->module_url . '/orderslist');
    }

    public function Modify(Request $request)
    {
       $request->method()=='post'&&$this->do_Modify($request);

        $request->method() == 'post' && $this->do_Modify($request);
        
        $sNo = addslashes(trim($request->param('sNo')));
        empty($sNo) && exit();
        $res = $this->getModel('Order')
            ->where([
                        'sNo' => [
                                    '=',$sNo
                        ]
        ])
            ->fetchAll('id,sNo,name,mobile,sheng,shi,xian,address');
        $data = array();
        if (! empty($res))
            $data = (array) $res[0];
        
        $this->assign("class", $data);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_Modify($request)
    {
        $admin_id = Session::get('admin_id');
        $name = addslashes(trim($request->param('name')));
        $mobile = addslashes(trim($request->param('mobile')));
        $sheng = addslashes(trim($request->param('Select1')));
        $shi = addslashes(trim($request->param('Select2')));
        $xian = addslashes(trim($request->param('Select3')));
        $address = addslashes(trim($request->param('address')));
        $r1 = $this->getModel('AdminCgGroup')->fetchWhere([
                                                                'GroupID' => [
                                                                                '=',$sheng
                                                                ]
        ], 'G_CName');
        $r1 = $r1[0]->G_CName;
        $r2 = $this->getModel('AdminCgGroup')->fetchWhere([
                                                                'GroupID' => [
                                                                                '=',$shi
                                                                ]
        ], 'G_CName');
        $r2 = $r2[0]->G_CName;
        $r3 = $this->getModel('AdminCgGroup')->fetchWhere([
                                                                'GroupID' => [
                                                                                '=',$xian
                                                                ]
        ], 'G_CName');
        $r3 = $r3[0]->G_CName;
        
        $address = $r1 . $r2 . $r3 . $address;
        $sNo = addslashes(trim($request->param('id')));
        $sid = addslashes(trim($request->param('sid')));
        
        $up = $this->getModel('Order')->saveAll([
                                                    'name' => $name,'mobile' => $mobile,'sheng' => $sheng,'shi' => $shi,'xian' => $xian,'address' => $address
        ], [
                'sNo' => [
                            '=',$sNo
                ]
        ]);
        if ($up > 0) {
            $this->recordAdmin($admin_id, ' 修改订单号为 ' . $sNo . ' 的信息 ', 2);
            
            $this->success('修改成功！', $this->module_url . '/orderslist/modify?sNo=' . $sNo);
        } else {
            $this->recordAdmin($admin_id, ' 修改订单号为 ' . $sNo . ' 的信息失败 ', 2);
            
            $this->error('修改失败！', $this->module_url . '/orderslist/modify?sNo=' . $sNo);
        }
    }

    public function Status(Request $request)
    {
        $beizhu = addslashes(trim($request->param('admin')));
        
        $sNo = addslashes(trim($request->param('sNo')));
        
        $trade = intval($request->param('trade'));
        
        $rl = $this->getModel('Order')->saveAll([
                                                    'status' => $trade
        ], [
                'sNo' => [
                            '=',$sNo
                ]
        ]);
        
        $rd = $this->getModel('OrderDetail')->saveAll([
                                                            'status' => $trade
        ], [
                'sNo' => [
                            '=',$sNo
                ]
        ]);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function addsign(Request $request)
    {
       $request->method()=='post'&&$this->do_addsign($request);

        $id = $request->param('id');
        // 运费
        $r02 = $this->getModel('express')->fetchAll();
        if (isset($request['otype'])) {
            $this->assign("otype", $request['otype']);
        } else {
            $this->assign("otype", 'yb');
        }
        $this->assign("express", $r02);
        $this->assign("id", $id);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function ajax(Request $request)
    {
        $GroupID = addslashes(trim($request->param('GroupID')));
        $strID = "";
        $r = $this->getModel('adminCgGroup')->fetchWhere([
                                                            'G_ParentID' => [
                                                                                '=',$GroupID
                                                            ]
        ]);
        if ($r) {
            
            foreach ($r as $list) {
                
                $strID .= $list->G_CName . "," . $list->GroupID . "|";
            }
        }
        echo $strID;
        return;
    }
    private function do_configs($request) {
        
        $admin_id = Session::get('admin_id');
        $days = addslashes(trim($request->param('days'))); // 承若天数
        $content = addslashes(trim($request->param('content'))); // 承若内容
        $back = addslashes(trim($request->param('back'))); // 退货时间
        $order_overdue = trim($request->param('order_failure')); // 订单过期删除时间
        $unit = addslashes(trim($request->param('unit'))); // 单位
        $company=trim($request->param('company'));
        if($days != ''){
            if(is_numeric($days)){
                if($days <= 0){
                    $this->error('承若天数不能为负数或零!','');
                }
            }else{
                $this->error('承若天数请输入数字!','');
            }
            if($content == ''){
                $this->error('承若内容不能为空!','');
            }
        }
        if(is_numeric($back) == ''){
            $this->error('退货时间请输入数字!','');
        }
        if($back <= 0){
            $this->error('退货时间不能为负数或零!','');
        }
        if(is_numeric($order_overdue) == ''){
            $this->error('订单过期删除时间请输入数字!','');
        }
        if($order_overdue < 0){
            $this->error('订单过期删除时间不能为负数!','');
        }

        $r=$this->getModel('OrderConfig')->fetchAll('*');
        if($r){
            $days = $days ? $days:0;
            $r_1=$this->getModel('OrderConfig')->saveAll(['days'=>$days,'content'=>$content,'back'=>$back,'order_failure'=>$order_overdue,'company'=>$company,'unit'=>$unit,'modify_date'=>nowDate()],['id'=>['=','1']]);
            if($r_1 ==false) {
                $this->recordAdmin($admin_id,' 修改订单设置失败 ',2);
                
                $this->error('未知原因，订单设置修改失败！',$this->module_url."/orderslist/configs");
            } else {
                $this->recordAdmin($admin_id,' 修改订单设置 ',2);
                
                $this->success('订单设置修改成功！',$this->module_url."/orderslist/configs");
            }
        }else{
            $r_1=$this->getModel('OrderConfig')->insert(['days'=>$days,'content'=>$content,'back'=>$back,'order_failure'=>$order_overdue,'company'=>$company,'unit'=>$unit,'modify_date'=>nowDate()]);
            if($r_1 ==false) {
                $this->recordAdmin($admin_id,' 修改订单设置失败 ',2);               
                $this->error('未知原因，订单设置添加失败！',$this->module_url."/orderslist/configs");
            } else {
                $this->recordAdmin($admin_id,' 修改订单设置 ',2);                
                $this->success('订单设置添加成功！',$this->module_url."/orderslist/configs");
            }
        }
        exit;
    }
    
    public function configs(Request $request)
    {
       $request->method()=='post'&&$this->do_configs($request);

        $r = $this->getModel('OrderConfig')
            ->where([
                        'id' => [
                                    '=','1'
                        ]
        ])
            ->fetchAll('*');
        if ($r) {
            $content = $r[0]->content;
            $back = $r[0]->back;
            $order_failure = $r[0]->order_failure;
            $company = $r[0]->company;
            $order_overdue = $r[0]->order_overdue;
            $unit = $r[0]->unit;
            if ($r[0]->days == 0) {
                $days = '';
            } else {
                $days = $r[0]->days;
            }
        } else {
            $days = '';
            $content = '';
            $back = 2;
            $order_failure = 2;
            $company = '天';
            $order_overdue = 2;
            $unit = '天';
        }
        $this->assign("days", $days);
        $this->assign("content", $content);
        $this->assign("back", $back);
        $this->assign("order_failure", $order_failure);
        $this->assign("company", $company);
        $this->assign("order_overdue", $order_overdue);
        $this->assign("unit", $unit);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function delorder(Request $request)
    {
       $request->method()=='post'&&$this->do_delorder($request);

        $request->method() == 'post' && $this->do_delorder($request);
        
        exit();
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_delorder($request)
    {
        $ids = trim($request->param('ids'));
        
        // $arr = explode(',',$ids);

        $res = $this->getModel('Order')
            ->alias('o')
            ->field("o.id,o.drawid,o.sNo,o.ptcode,o.pid,d.lottery_status")
            ->join("drawUser d", "o.drawid=d.id", 'left')
            ->where("o.id in (" . $ids . ")")
            ->select();
        
        $gcode = $this->getModel('GroupBuy')
            ->field('status')
            ->where('status', '=', function ($query) {
            $query->name('group_buy')
                ->field('status')
                ->where("is_show=1");
        })
            ->select();
        $gid = ! empty($gcode) ? $gcode[0]->status : 1;
        
        $group = array();
        
        $draw = array();
        
        foreach ($res as $k => $v) { // 过滤掉还没结束的拼团订单，和还没得到结果的抽奖订单
            
            if ($gid == $v->pid) {
                
                $group[] = $v->sNo;
                
                unset($res[$k]);
            }
            
            if (in_array($v->lottery_status, array(
                                                    0,1,2,4
            )) && $v->lottery_status !== null) { // 过滤还没出结果的抽奖订单
                
                $draw[] = $v->sNo;
                
                unset($res[$k]);
            }
        }
        
        $msg = '删除了 ' . count($res) . ' 笔订单';
        
        if (! empty($group) || ! empty($draw)) {
            
            $msg .= ',已保留了 ' . count($group) . ' 笔活动未结束的拼团订单, ' . count($draw) . ' 笔未出结果的抽奖订单.';
        }
        
        foreach ($res as $key => $value) {
            
            $delo = $this->getModel('Order')->deleteWhere([
                                                                'sNo' => [
                                                                            '=',$value->sNo
                                                                ]
            ]);
            
            $deld = $this->getModel('OrderDetails')->deleteWhere([
                                                                    'r_sNo' => [
                                                                                    '=',$value->sNo
                                                                    ]
            ]);
            
            $delg = $this->getModel('GroupOpen')->deleteWhere([
                                                                    'ptcode' => [
                                                                                    '=',$value->ptcode
                                                                    ]
            ]);
            
            $delc = $this->getModel('DrawUser')->deleteWhere([
                                                                'id' => [
                                                                            '=',$value->drawid
                                                                ]
            ]);
        }
        
        echo json_encode(array(
                                'code' => 1,'msg' => $msg
        ));
        exit();
    }

    public function kuaidishow(Request $request)
    {
        // 获取信息
        $r_sNo = trim($request->param('r_sNo')); // 订单详情id
        $courier_num = $request->param('courier_num');
        // 根据订单详情id,修改订单详情
        $r = $this->getModel('OrderDetails')
            ->where([
                        'r_sNo' => [
                                        '=',$r_sNo
                        ]
        ])
            ->fetchAll('express_id,courier_num');
        $res = [
                    'code' => - 1,'data' => 'parms error'
        ];
        if (! empty($r[0]->express_id) && ! empty($r[0]->courier_num)) {
            $express_id = $r[0]->express_id; // 快递公司ID
            $courier_num = $r[0]->courier_num; // 快递单号
            $r01 = $this->getModel('Express')
                ->where([
                            'id' => [
                                        '=',$express_id
                            ]
            ])
                ->fetchAll('*');
            $type = $r01[0]->type; // 快递公司代码
            $kuaidi_name = $r01[0]->kuaidi_name;
            $url = "http://www.kuaidi100.com/query?type=$type&postid=$courier_num";
            $res = $this->Curl($url);
            $res_1 = json_decode($res);
            if (empty($res_1->data)) {
                $res = array(
                            'code' => 0,'data' => [
                                                    $res_1->message
                            ]
                );
            } else {
                $res = array(
                            'code' => 1,'data' => $res_1->data
                );
            }
        } else {
            $res = array(
                        'code' => 0,'data' => '信息错误'
            );
        }
        echo json_encode($res);
        exit();
    }

    private function do_addsign($request)
    {
        $admin_id = Session::get('admin_id');
        // 开启事务
        $M=$this->getModel('OrderDetails');
        $M->conn()->startTrans();
        $sNo = trim($request->param('sNo')); // 订单号
        $trade = intval($request->param('trade')) - 1;
        $express_id = $request->param('express'); // 快递公司id
        
        $courier_num = $request->param('courier_num'); // 快递单号
        
        $otype = addslashes(trim($request->param('otype'))); // 类型
        $express_name = $request->param('express_name'); // 快递公司名称
        
        $time = date('Y-m-d H:i:s', time());
        $data = [];
        if (! empty($express_id)) {
            $data['express_id'] = $express_id;
        } else {
            $M->conn()->rollback();
            echo 2;
            exit();
        }
        if (! empty($courier_num)) {
            $rr = $this->getModel('OrderDetails')
                ->where([
                            'r_sNo' => [
                                            '<>',$sNo
                            ],'express_id' => [
                                                '=',$express_id
                            ],'courier_num' => [
                                                    '=',$courier_num
                            ]
            ])
                ->fetchAll('id');
            if ($rr) {
                $M->conn()->rollback();
                echo 0;
                exit();
            } else {
                $data['courier_num'] = $courier_num;
            }
        } else {
            $M->conn()->rollback();
            echo 3;
            exit();
        }
        $data['deliver_time'] = $time;
        // var_dump($otype);
        if ($otype == 'yb') {
            
            $r = $this->getModel('Config')
                ->where([
                            'id' => [
                                        '=','1'
                            ]
            ])
                ->fetchAll('*');
            if ($r) {
                $appid = $r[0]->appid;
                // 小程序唯一标识
                $appsecret = $r[0]->appsecret;
                // 小程序的 app secret
                $company = $r[0]->company;
            }
            $rl = $this->getModel('Order')->saveAll([
                                                        'status' => $trade
            ], [
                    'sNo' => [
                                '=',$sNo
                    ]
            ]);
            if ($rl < 1) {
               $M->conn()->rollback();
                echo 0;
                exit();
            }
            $data['r_status'] = $trade;
            $rd = $this->getModel('OrderDetails')->saveAll($data, [
                                                                        'r_sNo' => [
                                                                                        '=',$sNo
                                                                        ]
            ]);
            if ($rd < 1) {
               $M->conn()->rollback();
                echo 0;
                exit();
            }
            // 查询订单信息
            $res_p = $this->getModel('Order')
                ->alias('o')
                ->join('order_details d', 'o.sNo=d.r_sNo', 'left')
                ->fetchWhere([
                                'o.sNo' => [
                                                '=',$sNo
                                ]
            ], 'o.id,o.user_id,o.sNo,d.p_name,o.name,o.address');
            foreach ($res_p as $key => $value) {
                $p_name = $value->p_name;
                $user_id = $value->user_id;
                $address = $value->address;
                $name = $value->name;
                $order_id = $value->id;
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
                $page = 'pages/order/detail?orderId=' . $order_id;
                // 消息模板id
                
                $r = $this->getModel('Notice')
                    ->where([
                                'id' => [
                                            '=','1'
                                ]
                ])
                    ->fetchAll('*');
                $template_id = $r[0]->order_delivery;
                
                $send_id = $template_id;
                $keyword1 = array(
                                'value' => $express_name,"color" => "#173177"
                );
                $keyword2 = array(
                                'value' => $time,"color" => "#173177"
                );
                $keyword3 = array(
                                'value' => $p_name,"color" => "#173177"
                );
                $keyword4 = array(
                                'value' => $sNo,"color" => "#173177"
                );
                $keyword5 = array(
                                'value' => $address,"color" => "#173177"
                );
                $keyword6 = array(
                                'value' => $courier_num,"color" => "#173177"
                );
                $keyword7 = array(
                                'value' => $name,"color" => "#173177"
                );
                // 拼成规定的格式
                $o_data = array(
                                'keyword1' => $keyword1,'keyword2' => $keyword2,'keyword3' => $keyword3,'keyword4' => $keyword4,'keyword5' => $keyword5,'keyword6' => $keyword6,'keyword7' => $keyword7
                );
                $res = $this->Send_Prompt($appid, $appsecret, $form_id, $openid, $page, $send_id, $o_data);
                $this->get_fromid($openid, $form_id);
            }
            
            $this->recordAdmin($admin_id, ' 使订单号为 ' . $sNo . ' 的订单发货 ', 7);
            $M->conn->commit();
            echo 1;
            exit();
        } else if ($otype == 'pt') {
            $rl = $this->getModel('Order')->saveAll([
                                                        'status' => 2
            ], [
                    'sNo' => [
                                '=',''
                    ]
            ]);
            $rd = $M->save($data,['r_sNo'=>['=',$sNo]]);
            $msgres = $this->getModel('Order')
                ->alias('o')
                ->join('order_details d', 'o.sNo=d.r_sNo', 'left')
                ->fetchWhere([
                                'o.sNo' => [
                                                '=',$sNo
                                ]
            ], 'o.id,o.user_id,o.sNo,d.p_name,o.name,o.address');
            if (! empty($msgres))
                $msgres = $msgres[0];
            $uid = $msgres->user_id;
            $openid = $this->getModel('User')->fetchWhere([
                                                                'user_id' => [
                                                                                '=',$uid
                                                                ]
            ], 'wx_id');
            $msgres->uid = $openid[0]->wx_id;
            $compres = $this->getModel('Express')
                ->where([
                            'id' => [
                                        '=',$express_id
                            ]
            ])
                ->fetchAll('kuaidi_name');
            if (! empty($compres))
                $msgres->company = $compres[0]->kuaidi_name;
            $fromid = $this->getModel('UserFromid')
                ->where([
                            'open_id' => [
                                            '=',$msgres->uid
                            ],'id' => [
                                        '=','(select'
                            ]
            ])
                ->fetchAll('fromid');
            if (! empty($fromid))
                $msgres->fromid = $fromid[0]->fromid;
            $msgres->courier_num = $courier_num;
            
            if ($rl > 0 && $rd > 0) {
                $r = $this->getModel('Notice')
                    ->where([
                                'id' => [
                                            '=','1'
                                ]
                ])
                    ->fetchAll('*');
                $template_id = $r[0]->order_delivery;
                $res = $this->Send_success($msgres, $template_id);
                $this->recordAdmin($admin_id, ' 使订单号为 ' . $sNo . ' 的订单发货 ', 7);
                $M->conn->commit();
                echo 1;
                exit();
            }
        }
       $M->conn()->rollback();
        echo 0;
        exit();
    }

    public function Send_success($arr, $template_id)
    {
        $r = $this->getModel('Config')
            ->where([
                        'id' => [
                                    '=','1'
                        ]
        ])
            ->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid;
            // 小程序唯一标识
            $appsecret = $r[0]->appsecret;
            // 小程序的 app secret
            $AccessToken = $this->getAccessToken($appid, $appsecret);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $AccessToken;
        }
        
        $data = array();
        $data['access_token'] = $AccessToken;
        $data['touser'] = $arr->uid;
        $data['template_id'] = $template_id;
        $data['form_id'] = $arr->fromid;
        $data['page'] = "pages/order/detail?orderId=$arr->id";
        $minidata = array(
                        'keyword1' => array(
                                            'value' => $arr->company,'color' => "#173177"
                        ),'keyword2' => array(
                                            'value' => date('Y-m-d H:i:s', time()),'color' => "#173177"
                        ),'keyword3' => array(
                                            'value' => $arr->p_name,'color' => "#173177"
                        ),'keyword4' => array(
                                            'value' => $arr->sNo,'color' => "#FF4500"
                        ),'keyword5' => array(
                                            'value' => $arr->address,'color' => "#FF4500"
                        ),'keyword6' => array(
                                            'value' => $arr->courier_num,'color' => "#173177"
                        ),'keyword7' => array(
                                            'value' => $arr->name,'color' => "#173177"
                        )
        );
        $data['data'] = $minidata;
        $data = json_encode($data);
        
        $da = $this->Curl($url, $data);
        $del_rs = $this->getModel('UserFromid')->delete($arr->fromid, 'fromid');
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
        $result = $this->Curl($url);
        $jsonArray = json_decode($result, true);
        // 写入文件
        $accessToken = $jsonArray['access_token'];
        file_put_contents($fileName, $accessToken);
        return $accessToken;
    }

    public function Send_Prompt($appid, $appsecret, $form_id, $openid, $page, $send_id, $o_data)
    {
        $AccessToken = $this->getAccessToken($appid, $appsecret);
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $AccessToken;
        $data = json_encode(array(
                                'access_token' => $AccessToken,'touser' => $openid,'template_id' => $send_id,'form_id' => $form_id,'page' => $page,'data' => $o_data
        ));
        $da = $this->Curl($url, $data);
        return $da;
    }

    public function get_fromid($openid, $type = '')
    {
        if (empty($type)) {
            $fromidres = $this->getModel('UserFromid')
                ->where([
                            'open_id' => [
                                            '=',$openid
                            ],'id' => [
                                        '=','(select'
                            ]
            ])
                ->fetchAll('fromid,open_id');
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

    function changePassword(Request $request)
    {
        $this->redirect($this->module_url . '/index/changePassword');
    }

    function maskContent(Request $request)
    {
        $this->redirect($this->module_url . '/index/maskContent');
    }
}