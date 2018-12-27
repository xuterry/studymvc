<?php
namespace app\api\controller;
use core\Request;

class Draw extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function getformid (Request $request)
    {
        
        
        $uid = addslashes(trim($request->param('userid')));
        $formid = addslashes(trim($request->param('from_id')));      
        $fromres = $this->getModel('UserFromid')->getCount("open_id = $uid","*");
        $fromres = intval($fromres[0]->have);
        $lifetime = date('Y-m-d H:i:s', time());
        if ($formid != 'the formId is a mock one') {
            if ($fromres < 8) {
                $addres=$this->getModel('DrawUserFromid')->insert(['open_id'=>$uid,'fromid'=>$formid,'lifetime'=>$lifetime]);
            } else {
                return false;
            }
        }
    }

    public function ceshi (Request $request)
    {
        
        
        // 现在时间的前一天
        $datetime = date('Y-m-d H:i:s', time() - 24 * 60 * 60);
        // 现在时间的前七天
        $datetime1 = date('Y-m-d H:i:s', time() - 7 * 24 * 60 * 60);
        // 删除超过七天的数据
        $delres=$this->getModel('DrawUserFromid')->delete($datetime1,'lifetime');
        // 过去五分钟
        $oldtime = date('Y-m-d H:i:s', time() - 5 * 60 - 24 * 60 * 60);
        $re01=$this->getModel('Draw')->where(['end_time'=>['>','=']])->fetchAll('*');
        if (! empty($re01)) {
            foreach ($re01 as $key01 => $value01) {
                $draw_id = $value01->id; // 活动ID
                $name = $value01->name; // 活动名称
                $draw_brandid = $value01->draw_brandid; // 活动名称
                $re03=$this->getModel('ProductList')->where(['id'=>['=',$draw_brandid]])->fetchAll('product_title');
                $product_title = $re03[0]->product_title; // 活动商品
                $re02=$this->getModel('DrawUser')->where(['draw_id'=>['=',$draw_id]])->fetchAll('*');
                
                if (! empty($re02)) { // 存在参加活动的订单
                    foreach ($re02 as $key02 => $value02) {
                        $id = $value02->id; // ID
                        $user_id = $value02->user_id; // 用户ID
                        
                        $re04=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->fetchAll('wx_id');
                        $openid = $re04[0]->wx_id;
                        $re05=$this->getModel('DrawUserFromid')->where(['open_id'=>['=',$openid]])->fetchOrder(['lifetime'=>'asc'],'fromid');
                        if (! empty($re05)) { // 存在符合条件的fromid
                            $fromid = $re05[0]->fromid; // 状态
                            $lottery_status = $value02->lottery_status; // 状态
                            $time = $value02->time; // 中奖时间
                            if ($lottery_status == 4) {
                                $rew[$key01][$key02]['lottery_status'] = '抽奖成功';
                            } elseif ($lottery_status == 2) {
                                $rew[$key01][$key02]['lottery_status'] = '参团失败';
                            } else {
                                $rew[$key01][$key02]['lottery_status'] = '抽奖失败';
                            }
                            $rew[$key01][$key02]['product_title'] = $product_title;
                            $rew[$key01][$key02]['name'] = $name;
                            $rew[$key01][$key02]['time'] = $time;
                            $rew[$key01][$key02]['openid'] = $openid;
                            $rew[$key01][$key02]['fromid'] = $fromid;
                        }
                    }
                    $this->Send_success($rew);
                }
            }
        }
    }

    public function Send_success($rew)
    {
        
        
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid; // 小程序唯一标识
            $appsecret = $r[0]->appsecret; // 小程序的 app secret
            $AccessToken = $this->getAccessToken($appid, $appsecret);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $AccessToken;
        }
        
        foreach ($rew as $k => $v) {
            foreach ($v as $key => $value) {
                $lottery_status = $value[''];
                $product_title = $value['product_title'];
                $name = $value['name'];
                $time = $value['time'];
                $openid = $value['openid'];
                $fromid = $value['fromid'];
                $lottery_status = $value['lottery_status'];
                $data = array();
                $data['access_token'] = $AccessToken;
                $data['touser'] = $openid;
                $r=$this->getModel('Notice')->where(['id'=>['=','1']])->fetchAll('*');
                $template_id = $r[0]->lottery_res;
                $data['template_id'] = $template_id;
                $data['form_id'] = $fromid;
                $minidata = array(
                                'keyword1' => array(
                                                    'value' => $name,'color' => "#173177"
                                ),'keyword2' => array(
                                                    'value' => $product_title,'color' => "#173177"
                                ),'keyword3' => array(
                                                    'value' => $time,'color' => "#173177"
                                ),'keyword4' => array(
                                                    'value' => $lottery_status,'color' => "#173177"
                                )
                );
                $data['data'] = $minidata;
                $data = json_encode($data);
                
                $da = $this->httpsRequest($url, $data);
                $delete_rs=$this->getModel('DrawUserFromid')->delete($fromid,'fromid');
                var_dump(json_encode($da));
            }
        }
    }

    public function getdraw (Request $request)
    {
        
        
        
        $openid = trim($request->param('openid')); // 本人微信id
        $referee_openid = trim($request->param('referee_openid')); // 本人微信id
        $order_id = trim($request->param('order_id')); // 订单id
                                                              
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        
        $arr = [];
        $user = [];
        // 根据订单id,查询订单号和抽奖id
        $r_1=$this->getModel('Order')->where(['id'=>['=',$order_id]])->fetchAll('sNo,drawid');
        $sNo = $r_1[0]->sNo; // 订单号
        $arr['drawid'] = $r_1[0]->drawid; // 抽奖id
                                          // 根据抽奖id,查询抽奖活动id
        $rr=$this->getModel('DrawUser')->where(['id'=>['=',$arr[drawid]]])->fetchAll('draw_id,role');
        $arr['draw_id'] = $rr[0]->draw_id; // 抽奖活动id
        $role = $rr[0]->role; // 角色
                              
        // 根据抽奖id,查询那些用户参加了
        $rrr=$this->getModel('DrawUser')->where(['role'=>['=',$role]])->fetchOrder(['id'=>'asc'],'user_id');
        
        $user_num = count($rrr);
        foreach ($rrr as $k => $v) {
            $r=$this->getModel('User')->where(['user_id'=>['=',$v->user_id]])->fetchAll('user_id,wx_name,headimgurl');
            $user[] = $r[0];
        }
        $r_2=$this->getModel('OrderDetails')->alias('a')->join('configure c','a.sid=c.id','LEFT')->fetchWhere(['a.r_sNo'=>['=',$sNo]],'a.p_id,a.p_price,a.sid,c.img,c.yprice');
        $p_id = $r_2[0]->p_id; // 商品id
        $arr['p_id'] = $r_2[0]->p_id; // 商品id
        $arr['p_price'] = $r_2[0]->p_price; // 商品抽奖价格
        $arr['yprice'] = $r_2[0]->yprice; // 商品原价
        $arr['sid'] = $r_2[0]->sid; // 商品属性id
        $arr['img'] = $img . $r_2[0]->img; // 商品图片
        
        $r_4=$this->getModel('ProductList')->where(['id'=>['=',$p_id]])->fetchAll('product_title');
        $arr['product_title'] = $r_4[0]->product_title; // 商品名称
        
        $r_3=$this->getModel('Draw')->where(['draw_brandid'=>['=',$p_id]])->fetchAll('num,end_time');
        $arr['num'] = $r_3[0]->num; // 参加抽奖人数
        $arr['user_num'] = $arr['num'] - $user_num; // 参团还差的人数
        
        $arr['time'] = strtotime($r_3[0]->end_time) - time(); // 抽奖活动结束时间
        if (count($user) < $r_3[0]->num) {
            $arr['draw_status'] = 0; // 参团中
            if ($arr['time'] < 0) {
                $arr['draw_status'] = 2; // 参团失败
            }
        } else {
            $arr['draw_status'] = 1; // 参团成功
        }
        if ($referee_openid != '' && $referee_openid != 'undefined') {
            if ($referee_openid == $openid) {
                $arr['draw_type'] = true;
            } else {
                $arr['draw_type'] = false;
            }
        } else {
            if (count($user) == 1) {
                $arr['draw_type'] = true;
            } else {
                $arr['draw_type'] = false;
            }
        }
        $r_r=$this->getModel('ProductList')->where(['id'=>['=',$arr[p_id]]])->fetchAll('num');
        $arr['stock'] = $r_r[0]->num;
        /* 获取商品属性 */
        $commodityAttr = [];
        $r_size=$this->getModel('Configure')->where(['pid'=>['=',$arr[p_id]]])->fetchAll('*');
        $array_price = [];
        $array_yprice = [];
        if ($r_size) {
            foreach ($r_size as $key => $value) {
                $array_price[$key] = $value->price;
                $array_yprice[$key] = $value->yprice;
                $attrValueList[0] = array(
                                        'attrKey' => '型号','attrValue' => $value->name
                );
                $attrValueList[1] = array(
                                        'attrKey' => '规格','attrValue' => $value->size
                );
                $attrValueList[2] = array(
                                        'attrKey' => '颜色','attrValue' => $value->color
                );
                $cimgurl = $img . $value->img;
                $commodityAttr[$key] = array(
                                            'priceId' => $value->id,'price' => $value->price,'stock' => $value->num,'img' => $cimgurl,'attrValueList' => $attrValueList
                );
            }
        }
        /* 获取商品属性 */
        echo json_encode(array(
                                'status' => 1,'arr' => $arr,'user' => $user,'commodityAttr' => $commodityAttr
        ));
        exit();
    }

}