<?php
namespace app\api\controller;
use core\Request;
use core\Session;
class App extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function index (Request $request)
    {
           
        // 获取临时凭证
        $code = $request['code'];
        
        // 查询小程序配置
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid; // 小程序唯一标识
            $appsecret = $r[0]->appsecret; // 小程序的 app secret
        }
        if (! $code) {
            echo json_encode(array(
                                    'status' => 0,'err' => '无受权信息'
            ));
            exit();
        }
        if (! $appid || ! $appsecret) {
            echo json_encode(array(
                                    'status' => 0,'err' => '无受权信息！'
            ));
            exit();
        }
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $appsecret . '&js_code=' . $code . '&grant_type=authorization_code';
        $res=$this->Curl($url);
        $user = (array) json_decode($res);
        Session::set('openid',$user['openid']); 
        $r=$this->getModel('BackgroundColor')->where(['status'=>['=','1']])->fetchAll('*');
        $user['bgcolor'] = empty($r[0]->color)?'#ff0000':$r[0]->color;
        echo json_encode($user);
        exit();
        return;
    }

    public function user (Request $request)
    {
           
        $software_id = trim($request->param('software_id')); // 软件名
        $edition = trim($request->param('edition')); // 版本号
        
        $wxname = $request['nickName']; // 微信昵称
        $headimgurl = $request['headimgurl']; // 微信头像
        $sex = $request['sex']; // 性别
        $openid = $request['openid']; // 微信id
        $pid = $request['p_openid']; // 推荐人微信id
                                   
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        // 生成密钥
        $access_token = '';
        $str = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890';
        for ($i = 0; $i < 32; $i ++) {
            $access_token .= $str[rand(0, 61)];
        }
        // 判断是否存在推荐人微信id
        if ($pid == '' || $pid == 'undefined') {
            $Referee = false;
        } else {
            if (strlen($pid) == '32') {
                $r=$this->getModel('User')->where(['wx_id'=>['=',$pid]])->fetchAll('*');
                $Referee = $r[0]->user_id;
            } else {
                $Referee = $pid;
            }
        }
        
        // 根据wxid,查询会员信息
        $rr=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
        if (! empty($rr)) {
            $update_rs=$this->getModel('User')->saveAll(['access_token'=>$access_token],['wx_id'=>['=',$openid]]);
            $user_id = $rr[0]->user_id;
            
            $event = '会员' . $user_id . '登录';
            // 在操作列表里添加一条会员登录信息
            $r=$this->getModel('Record')->insert(['user_id'=>$user_id,'event'=>$event,'type'=>0]);
            
            // 查询订单设置表
            $r=$this->getModel('OrderConfig')->where(['id'=>['=','1']])->fetchAll('*');
            $order_overdue = $r[0]->order_overdue; // 未付款订单保留时间
            $unit = $r[0]->unit; // 未付款订单保留时间单位
            if ($order_overdue != 0) {
                if ($unit == '天') {
                    $time01 = date("Y-m-d H:i:s", strtotime("-$order_overdue day")); // 订单过期删除时间
                } else {
                    $time01 = date("Y-m-d H:i:s", strtotime("-$order_overdue hour")); // 订单过期删除时间
                }
                // 根据用户id，订单为未付款，订单添加时间 小于 未付款订单保留时间,查询订单表
                $r_c=$this->getModel('Order')->where(['user_id'=>['=',$user_id],'status'=>['=','0'],'add_time'=>['<',$time01]])->fetchAll('*');
                // 有数据，循环查询优惠券id,修改优惠券状态
                if ($r_c) {
                    foreach ($r_c as $key => $value) {
                        $coupon_id = $value->coupon_id; // 优惠券id
                        if ($coupon_id != 0) {
                            // 根据优惠券id,查询优惠券信息
                            $r_c=$this->getModel('Coupon')->where(['id'=>['=',$coupon_id]])->fetchAll('*');
                            $expiry_time = $r_c[0]->expiry_time; // 优惠券到期时间
                            $time = date('Y-m-d H:i:s'); // 当前时间
                            if ($expiry_time <= $time) {
                                // 根据优惠券id,修改优惠券状态(已过期)
                                $update_rs=$this->getModel('Coupon')->saveAll(['type'=>3],['id'=>['=',$coupon_id]]);
                            } else {
                                // 根据优惠券id,修改优惠券状态(未使用)
                                $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$coupon_id]]);
                            }
                        }
                    }
                }
                // 根据用户id、订单未付款、添加时间小于前天时间,就删除订单信息
                $re01=$this->getModel('Order')->delete($time01,'add_time');
                // 根据用户id、订单未付款、添加时间小于前天时间,就删除订单详情信息
                $re02=$this->getModel('OrderDetails')->delete($time01,'add_time');
            }
            // 设置抽奖订单的时间超过抽奖时间的订单状态改成相对应的发货中或者交易结束
            $re0012=$this->getModel('Order')->where(['user_id'=>['=',$user_id],'drawid'=>['>','0']])->fetchAll('id,drawid,sNo');
            $time02 = date("Y-m-d H:i:s", strtotime('+1 day'));
            if (! empty($re0012)) {
                foreach ($re0012 as $key0012 => $value0012) {
                    $draw_id = $value0012->drawid;
                    $id = $value0012->id;
                    $sNo = $value0012->sNo;
                    $re001201 = $this->getModel('draw')->alias('a')->join("draw_user b","a.id=b.draw_id")
                    ->where("a.id = $draw_id and end_time < '$time02' and b.user_id = '$user_id' and lottery_status != 4")
                    ->select();
                    if (! empty($re001201)) {
                        foreach ($re001201 as $key001201 => $value001201) {
                            $time03 = $value001201->time;
                            $re001202=$this->getModel('Order')->saveAll(['status'=>4],['sNo'=>['=',$sNo]]);
                            
                            $re001203=$this->getModel('OrderDetails')->saveAll(['r_status'=>6],['r_sNo'=>['=',$sNo]]);
                        }
                    }
                }
            }
        } else {
            // 查询会员列表的最大id
            //$sql = "select max(id) as userid from lkt_user";
            $r = $this->getModel('user')->max('id');
            $rr = $r;
            // $user_id = $rr+1;
            $user_id = 'user' . ($rr + 1);
            // 在会员列表添加一条数据
            
            // 默认头像和名称
            if (empty($wxname) || $wxname == 'undefined') {
                $wxname = 'test';
            }
            if (empty($headimgurl) || $headimgurl == 'undefined') {
                $headimgurl = 'https://lg-8tgp2f4w-1252524862.cos.ap-shanghai.myqcloud.com/moren.png';
            }
            
            if (empty($sex) || $sex == 'undefined') {
                $sex = '0';
            }
            $r=$this->getModel('User')->insert(['user_id'=>$user_id,'user_name'=>$wxname,'headimgurl'=>$headimgurl,'wx_name'=>$wxname,'sex'=>$sex,'wx_id'=>$openid,'Referee'=>$Referee,'access_token'=>$access_token,'img_token'=>$access_token,'source'=>1]);
            
            // 查询首次注册所获积分
            $r_1001=$this->getModel('SoftwareJifen')->where(['id'=>['=','1']])->fetchAll('jifennum');
            $jifennum = $r_1001[0]->jifennum;
            // 添加积分到用户表
            $update_rs=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->inc('score',$jifennum)->update();
            
            // 在积分操作列表里添加一条会员首次登录信息获取积分的信息
            $record = '会员' . $user_id . '首次关注获得积分' . $jifennum;
            $r=$this->getModel('SignRecord')->insert(['user_id'=>$user_id,'sign_score'=>$jifennum,'record'=>$record,'sign_time'=>nowDate(),'type'=>2]);
            
            $event = '会员' . $user_id . '登录';
            // 在操作列表里添加一条会员登录信息
            $r=$this->getModel('Record')->insert(['user_id'=>$user_id,'event'=>$event,'type'=>0]);
        }

        // 根据软件名称，查询软件id和名称
       // $r_software=$this->getModel('Software')->where(['name'=>['=',$software_name],'edition'=>['=',$edition],'type'=>['=','0']])->fetchAll('id');

        // 查询插件表里,状态为启用的插件
        $r_c=$this->getModel('PlugIns')->where(['status'=>['=','1'],'type'=>['=','0'],'software_id'=>['=',$software_id]])->fetchAll('*');
        if ($r_c) {
            foreach ($r_c as $k => $v) {
                $v->image = $img . $v->image;
                if (strpos($v->name, '优惠券') !== false) { // 判断字符串里是否有 优惠券
                    $v->name = '优惠券';
                    $coupon[$k] = 1;
                } else {
                    $coupon[$k] = 0;
                }
                if ($v->name == '钱包') {
                    $wallet[$k] = 1;
                } else {
                    $wallet[$k] = 0;
                }
                if ($v->name == '签到') {
                    $sign[$k] = 1;
                } else {
                    $sign[$k] = 0;
                }
                $r_c[$k]=$v;
            }
            $time_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d'), date('Y'))); // 当前时间
            $day_time=date("Y-m-d 00:00:00");                                                                           // 查询签到活动
            $r_activity=$this->getModel('SignActivity')->where(['status'=>['=','1']])->fetchAll('*');
            if ($r_activity) {
                $sign_image = $img . $r_activity[0]->image; // 签到弹窗图
                $endtime = $r_activity[0]->endtime; // 签到结束时间
                if ($endtime <= $time_start) { // 当前时间大于签到结束时间
                    $sign_status = 0; // 不用弹出签名框
                } else {
                    // 根据用户id、签到时间大于当天开始时间,查询签到记录
                    $r_sign=$this->getModel('SignRecord')->where(['user_id'=>['=',$user_id],'sign_time'=>['>',$day_time],'type'=>['=','0']])->fetchAll('*');
                    if ($r_sign) {
                        $sign_status = 1; // 有数据,代表当天签名了,不用弹出签名框
                    } else {
                        $sign_status = 0; // 没数据,代表当天还没签名,弹出签名框
                    }
                }
            } else {
                $sign_image = '';
                $sign_status = 0;
            }
            
            $rr=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
            $nickName = $rr[0]->wx_name;
            $avatarUrl = $rr[0]->headimgurl;
            echo json_encode(array(
                                    'access_token' => $access_token,'user_id' => $user_id,'plug_ins' => $r_c,'coupon' => in_array(1, $coupon),'wallet' => in_array(1, $wallet),'sign' => in_array(1, $sign),'sign_status' => $sign_status,'sign_image' => $sign_image,'nickName' => $nickName,'avatarUrl' => $avatarUrl
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'plug_ins' => ''
            ));
            exit();
        }
        return;
    }

    public function get_plug (Request $request)
    {
        header("Content-type: text/html; charset=utf-8");
               
        // 查询插件表里,状态为启用的插件
        $r_c=$this->getModel('PlugIns')->where(['status'=>['=','1'],'type'=>['=','0']])->fetchAll('name,image');
        
        $coupon = false;
        $wallet = false;
        $integral = false;
        $red_packet = false;
        $pays[0] = array(
                        'name' => '微信支付','value' => 'wxPay','icon' => '/images/wxzf.png','checked' => true
        );
        
        if ($r_c) {
            foreach ($r_c as $k => $v) {
                if (strpos($v->name, '劵') !== false) {
                    // 判断字符串里是否有 优惠劵
                    $v->name = '优惠劵';
                    $coupon = true;
                }
                if ($v->name == '钱包') {
                    $wallet = true;
                    $arrayName = array(
                                    'name' => '钱包支付','value' => 'wallet_Pay','icon' => '/images/qbzf.png','checked' => false
                    );
                    @array_push($pays, $arrayName);
                }
                if ($v->name == '签到') {
                    $integral = true;
                }
                if ($v->name == '发红包') {
                    $red_packet = true;
                }
            }
            
            echo json_encode(array(
                                    'status' => 1,'pays' => $pays,'coupon' => $coupon,'wallet' => $wallet,'integral' => $integral,'red_packet' => $red_packet
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'pays' => $pays,'coupon' => $coupon,'wallet' => $wallet,'integral' => $integral,'red_packet' => $red_packet
            ));
            exit();
        }
    }

    public function secToTime($times)
    {
        $result = '00:00:00';
        if ($times > 0) {
            $hour = floor($times / 3600);
            $minute = floor(($times - 3600 * $hour) / 60);
            $second = floor((($times - 3600 * $hour) - 60 * $minute) % 60);
            $result = $hour . ':' . $minute . ':' . $second;
        }
        return $result;
    }

}