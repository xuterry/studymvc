<?php
namespace app\admin\controller;

use core\Request;
use core\Session;

class Draw extends Index
{

    function __construct()
    {
        parent::__construct(); 
    }
    public function Index(Request $request) 
    {
        $pageto = $request->param('pageto'); 
        // 每页显示多少条数据
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : '10';
        // 页码
        $page = $request->param('page');

        $r02 = $this->getModel('lotteryParameters')->fetchAll();
        if (! empty($r02)) {
            $parameters = $r02[0]->parameters;
            $id11 = $r02[0]->id;
        } else {
            $parameters = '';
            $id11 = '';
        }       
        $r=$this->getModel('Draw')->order(['found_time'=>'desc'])
        ->paginator($pagesize);
        if (! empty($r)) {
            foreach ($r as $key => $value) {
                $id = $value->id;
                $draw_brandid = $value->draw_brandid;
                $start_time = $value->start_time;
                $end_time = $value->end_time;
                $r03=$this->getModel('Draw')->where(['id'=>['=',$id]])->fetchAll('*');
                $num = $r03[0]->num; // 每个团所需人数
                $spelling_number = $r03[0]->spelling_number; // 可抽中奖次数（默认为1）
                $collage_number = $r03[0]->collage_number; // 最少开奖团数（默认为1）               
               // $sql04 = "select role ,count(*) from lkt_draw_user where draw_id = $id group by role having count(*)>='$num'";
                $r04 = $this->getModel('drawUser')->group('role')->having("count(*)>='".$num."'")->getCount("draw_id=".$id,"*");               
                $r01=$this->getModel('ProductList')->where(['id'=>['=',$draw_brandid]])->fetchAll('product_title');
                if ($r01[0]->product_title) {
                    $r[$key]->draw_brandname = $r01[0]->product_title;
                } else {
                    $r[$key]->draw_brandname = 0;
                }
                $data_time = date('Y-m-d H:i:s', time());
                if ($end_time < $data_time && $start_time < $data_time) {
                    $hours = date('Y-m-d H:i:s', strtotime('-1 day'));
                    $r[$key]->status = "已结束";
                    if ($end_time >= $hours) {
                        $r[$key]->status1 = 1; // 可抽奖
                    } else {
                        $r[$key]->status1 = 2; // 不可抽奖
                    }
                } elseif ($end_time > $data_time && $start_time < $data_time) {
                    if ($r04 >= $collage_number) {
                        $r[$key]->status = "进行中可抽奖";
                        $r[$key]->status1 = 3; // 可抽奖
                    } else {
                        $r[$key]->status = "进行中不可抽奖";
                        $r[$key]->status1 = 4; // 不可抽奖
                    }
                } else {
                    $r[$key]->status = "未开始";
                    $r[$key]->status1 = 5; // 不可抽奖
                }
            }
        }
        $pages_show = $r->render();
       // dump($r[0]);
        $this->assign("list", $r);
        $this->assign("parameters", $parameters);
        $this->assign("id11", $id11);
        $this->assign('pages_show', $pages_show);
        $this->assign('pagesize', $pagesize);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_addsign($request)
    {
        $name = $request->param('huodongname'); // 活动名称
        $draw_brandid = $request->param('shangpin'); // 商品id
        $start_time = $request->param('start_time'); // 开始时间
        $end_time1 = $request->param('end_time'); // 结束时间
        $end_time = $end_time1 . " 23:59:59";
        $num = $request->param('num'); // 每个团所需人数
        $collage_number = $request->param('collage_number'); // 最少开奖团数
        $cishu = $request->param('cishu'); // 用户最多可参与次数
        $price = $request->param('price'); // 参与抽奖的价格
        $spelling_number = $request->param('spelling_number'); // 中奖次数
        $type = $request->param('type'); // 备注
                                         // $state = 0;
        $found_time = date('Y-m-d H:i:s', time());
        $res01 = $this->getModel('ProductList')
            ->where([
                        'id' => [
                                    '=',$draw_brandid
                        ]
        ])
            ->fetchAll('id,product_title,num');
        $res02 = $res01[0]->num;
        // print_r($res02);die;
        if ($res02 < $spelling_number) {
               $this->error('商品库存太少',$this->module_url.'/draw');
        } else {
            
            $r = $this->getModel('Draw')->insert([
                                                    'name' => $name,'draw_brandid' => $draw_brandid,'found_time' => $found_time,'start_time' => $start_time,'end_time' => $end_time,'num' => $num,'spelling_number' => $spelling_number,'collage_number' => $collage_number,'price' => $price,'cishu' => $cishu,'type' => $type
            ]);
            if ($r == 1) {
                echo 1;
                exit();
            }
        }
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function addsign(Request $request)
    {
        $request->method()=='post'&&$this->do_addsign($request);
        $res01=$this->getModel('ProductList')->fetchAll('id,product_title,num');
        $res = $res01 ? $res01 : 1;
        // print_r($res);die;
        
        $this->assign("res", $res);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function del(Request $request)
    {
        
        // 接收信息
        $id = intval($request->param('id')); // 插件id
        $res=$this->getModel('Draw')->delete($id,'id');
        echo $res;
        exit();
        if ($res > 0) {
            $this->success('删除成功！', $this->module_url . "/draw");
            return;
        }
        return;
    }

    public function modify(Request $request)
    {
        $request->method() == 'post' && $this->do_modify($request);        
        // 接收信息     
        $id = intval($request->param("id")); // 插件id                                             
        // 根据插件id，查询插件信息                                          
        // print_r($id);die;       
        $res1 = $this->getModel('Draw')->get($id, 'id');      
        $res['name'] = $res1[0]->name;     
        $res['id'] = $res1[0]->id;     
        $draw_brandid = $res1[0]->draw_brandid;       
        $res03=$this->getModel('ProductList')->where(['id'=>['=',$draw_brandid]])->fetchAll('id,product_title');     
        $res['product_title'] = $res03[0]->product_title;       
        $res['draw_brandid'] = $res1[0]->draw_brandid;       
        $res['start_time'] = substr($res1[0]->start_time,0,10);        
        $res['end_time'] = substr($res1[0]->end_time,0,10);       
        $res['num'] = $res1[0]->num;       
        $res['collage_number'] = $res1[0]->collage_number;       
        $res['cishu'] = $res1[0]->cishu;        
        $res['price'] = $res1[0]->price;       
        $res['spelling_number'] = $res1[0]->spelling_number;       
        $res['type'] = $res1[0]->type;        
        // print_r($res);die;       
        $res01=$this->getModel('ProductList')->fetchAll('id,product_title');      
        $res02 = $res01 ? $res01 : 1;       
        $this->assign("res", $res02);      
        $this->assign("mm", $res);       
        // print_r($res);die;
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        $name = $request->param('huodongname'); // 活动名称
        
        $draw_brandid = $request->param('shangpin'); // 商品id
        
        $start_time = $request->param('start_time'); // 开始时间
        
        $end_time1 = $request->param('end_time'); // 结束时间
        
        $end_time = $end_time1 . " 23:59:59";
        
        $num = $request->param('num'); // 每个团所需人数
        
        $collage_number = $request->param('collage_number'); // 最少开奖团数
        
        $cishu = $request->param('cishu'); // 用户最多可参与次数
        
        $price = $request->param('price'); // 参与抽奖的价格
        
        $spelling_number = $request->param('spelling_number'); // 中奖次数
        
        $type = $request->param('type'); // 备注
        
        $id = $request->param('id'); // ID
                                     
        // $state = 0;
        
        $found_time = date('Y-m-d H:i:s', time());        
        $r=$this->getModel('Draw')->saveAll(['name'=>$name,'draw_brandid'=>$draw_brandid,'found_time'=>$found_time,'start_time'=>$start_time,'end_time'=>$end_time,'num'=>$num,'spelling_number'=>$spelling_number,'collage_number'=>$collage_number,'price'=>$price,'cishu'=>$cishu,'type'=>$type],['id'=>['=',$id]]);     
        if ($r == 1) {           
            echo 1;
            exit();
        }      
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function operation(Request $request)
    {
        $id = $request->param('id'); // 活动ID       
        $r04=$this->getModel('Draw')->where(['id'=>['=',$id]])->fetchAll('num,spelling_number,collage_number');     
        $num = $r04[0]->num; // 每个团所需人数
        $spelling_number = $r04[0]->spelling_number; // 可抽中奖次数（默认为1）
        $collage_number = $r04[0]->collage_number; // 最少开奖团数（默认为1）
        // 查出符合参团通知的人
      //  $sql01 = "select role ,count(*) from lkt_draw_user where draw_id = $id group by role having count(*)>=$num";
        $r01 = $this->getModel('drawUser')->group('role')->having("count(*)>='".$num."'")
        ->fetchWhere("draw_id=".$id,"*","role");
                // print_r($r01);die;
        $r05=$this->getModel('DrawUser')->where(['draw_id'=>['=',$id],'lottery_status'=>['=','4']])->fetchAll('id');
        if (! empty($r01)) {
            foreach ($r01 as $key => $value) {
                $role = $value->role;
                if (! empty($role)) {
                 //  $sql02 = "select a.*,b.user_name from lkt_draw_user as a,lkt_user as b where draw_id = $id and role = '$role' and a.user_id = b.user_id ";
                    $r02 = $this->getModel('DrawUser')->alias('a')->join("user b","a.user_id=b.user_id")
                    ->fetchWhere("draw_id =".$id." and role='".$role."'","a.*,b.user_name");                    
                    if (! empty($r02)) {
                        foreach ($r02 as $key02 => $value02) { //
                            $val_id = $value02->id;
                            $lottery_status = $value02->lottery_status;
                            if ($r05 >= $spelling_number && $lottery_status != 4) { // 当中奖人大于或等于设定的中奖数时就把其他参与抽奖的状态改为未中奖,把订单部分的状态改成订单关闭
                                $r06=$this->getModel('DrawUser')->saveAll(['lottery_status'=>3],['id'=>['=',$val_id]]);
                                $r07=$this->getModel('Order')->saveAll(['status'=>6],['drawid'=>['=',$val_id]]);
                                //$sql08 = "update lkt_order set r_status ='6' where r_sNo =(select sNo from lkt_order where drawid = $val_id) ";
                                $r08 = $this->getModel("OrderDetails")->
                                where("r_sNo","=",function($query) use($val_id){
                                    $query->name('order')->where("drawid=".$val_id)->field('sNo');
                                })->update(['r_status'=>6]);
                            }
                            if ($lottery_status == 4) { // 当抽奖状态为中奖时,把订单部分的状态改成待发货
                                                     // $sql06 = "update lkt_draw_user set lottery_status ='3' where id = $val_id ";
                                                     // $r06 = $db->update($sql06);
                                $r07=$this->getModel('Order')->saveAll(['status'=>1],['drawid'=>['=',$val_id]]);
                                $r08 = $this->getModel("OrderDetails")->
                                where("r_sNo","=",function($query) use($val_id){
                                    $query->name('order')->where("drawid=".$val_id)->field('sNo');
                                })->update(['r_status'=>1]);
                            }
                           
                            $roleid = $value02->role;
                            $r06=$this->getModel('DrawUser')->where(['id'=>['=',$roleid]])->fetchAll('user_id');
                            $userid = $r06[0]->user_id;
                            $r04=$this->getModel('User')->alias('b')->where(['b.user_id'=>['=',$userid]])->fetchAll('b.user_name');
                            $role_name = $r04[0]->user_name;
                            // print_r($role_name);die;
                            $value02->role_name = $role_name;
                            $value02->product_title='';
                            $rr[] = $value02;
                        }
                    }
                }
            }
        } else {
            $this->error('未达到开奖条件，不能抽奖！', $this->module_url . "/draw");
            return;
        }
        
        if ($r05 >= $spelling_number) { // 当中奖人大于或等于设定的中奖数时就把其他未参与抽奖的状态改为参团失败
            $r06=$this->getModel('DrawUser')->saveAll(['lottery_status'=>2],['draw_id'=>['=',$id],'lottery_status'=>['<>','4'],'lottery_status'=>['<>','3']]);
            $this->wxrefundapi($id);
        }
        
        $r03=$this->getModel('Draw')->where(['id'=>['=',$id]])->fetchAll('name');
        $name = $r03[0]->name;
        // print_r($rr);
        // print_r($name);
        // print_r($spelling_number);
        // print_r($r05);
        // die;
        $this->assign("list", $rr);
        $this->assign("name", $name);
        $this->assign("spelling_number", $spelling_number);
        $this->assign("r05", $r05);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    /*
     * 发送请求 @param $id string 订单号 return array
     */
    private function wxrefundapi($id)
    {
        // 通过微信api进行退款流程
       // $sql = "select m.*,t.p_name from (select d.id,d.user_id,o.sNo,o.z_price,o.trade_no,o.pay from lkt_draw_user as d left join lkt_order as o on d.id=o.drawid where d.draw_id='$id' and lottery_status =2) as m left join lkt_order_details as t on m.sNo=t.r_sNo";
        $res = $this->getModel('DrawUser')->alias('d')->join("order o","d.id=o.drawid")
        ->join("order_details t","o.sNo=t.r_sNo")
        ->field("d.*,t.p_name,o.sNo,o.z_price,o.trade_no,o.pay")->where("d.draw_id='".$id."' and lottery_status =2")
        ->select();
             
        if ($res) {
            foreach ($res as $k => $v) {
                if ($v->pay == 'wxPay') {
                    $refund = date('Ymd') . mt_rand(10000, 99999) . substr(time(), 5);
                    $parma = array(
                                'appid' => 'wx9d12fe23eb053c4f','mch_id' => '1499256602','nonce_str' => $this->createNoncestr(),'out_refund_no' => $refund,'out_trade_no' => $v->trade_no,'total_fee' => $v->z_price * 100,'refund_fee' => $v->z_price * 100,'op_user_id' => '1499256602'
                    );
                    $parma['sign'] = $this->getSign($parma);
                    $xmldata = $this->arrayToXml($parma);
                    $xmlresult = $this->postXmlSSLCurl($xmldata, 'https://api.mch.weixin.qq.com/secapi/pay/refund');
                    $result = $this->xmlToArray($xmlresult);
                } else if ($v->pay == 'wallet_Pay') {
                    $result=$this->getModel('User')->where(['user_id'=>['=',$v->user_id]])->inc('money',$v->z_price)->update();
                    
                    if ($result > 0) {
                        $openid=$this->getModel('User')->fetchWhere(['user_id'=>['=',$v->user_id]],'wx_id');
                        $openid = $openid[0]->wx_id;
                        $v->openid = $openid;
                      //  $from = $db->select("select fromid from lkt_draw_user_fromid where open_id='$openid' and id=(select max(id) from lkt_draw_user_fromid where open_id='$openid')");
                        $from=$this->getModel('DrawUserFromid')->where("open_id ='".$openid."'")
                        ->where("id","=",function($query) use($openid){ 
                           $query->where("open_id ='".$openid."'")->max('id');
                        })->select();
                        $fromid = ! empty($from) ? $from[0]->fromid : '';
                        $this->Send_success($v, $fromid);
                    }
                }
                
                if ($result > 0 || ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS')) {
                    $rs=$this->getModel('DrawUser')->saveAll(['lottery_status'=>5],['id'=>['=',$v->id]]);
                }
            }
        }
    }

    public function parameters(Request $request)
    {
        $request->method() == 'post' && $this->do_parameters($request);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_parameters($request)
    {
        $parameters = $request->param('parameters'); // 活动名称
        
        $id = $request->param('id11'); // ID
        
        $admin_name = Session::get('admin_id');
        
        if (! empty($admin_name)) {
            
            $re=$this->getModel('Admin')->where(['name'=>['=',$admin_name]])->fetchAll('id');
            
            $re01 = $re[0]->id;
        }
        
        $admin_id = $re01 ? $re01 : 0;
        
        // print_r($parameters);die;
        
        if (! empty($id)) {
            
            $r=$this->getModel('LotteryParameters')->saveAll(['parameters'=>$parameters,'user_id'=>$admin_id],['id'=>['=',$id]]);
            
            if ($r == 1) {
                
                echo 1;
                exit();
            }
        } else {
            
            $r=$this->getModel('LotteryParameters')->insert(['parameters'=>$parameters,'user_id'=>$admin_id]);
            
            if ($r == 1) {
                
                echo 2;
                exit();
            }
        }
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function whether(Request $request)
    {
        
        // 接收信息
        $id = $request->param('id'); // 插件id
                                     // 根据插件id,查询查询状态
        $lottery_status = $request->param('lottery_status');
        $userid = $request->param('userid');
        $r03=$this->getModel('DrawUser')->saveAll(['lottery_status'=>$lottery_status],['id'=>['=',$userid]]);
        // print_r($r03);die;
        if ($r03 > 0) {
            $this->success('修改成功！', $this->module_url . "/draw/operation&id=$id");
            return;
        } else {
            $this->error('修改失败！', $this->module_url . "/draw/operation&id=$id");
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