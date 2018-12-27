<?php
namespace app\api\controller;
use core\Request;
use core\Session;

class User extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function index (Request $request)
    {         
        // 获取信息
        $openid = $request['openid']; // 微信id
                                    // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r_1) {
            $company = $r_1['0']->company;
            $logo = $r_1['0']->logo;
            $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
            $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        } else {
            $company = '';
            $logo = '';
            $uploadImg_domain = '';
            $uploadImg = '';
        }
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        $logo = $img . $logo;
        
        // 获取文章信息
       // $r_2=$this->getModel('Article')->fetchAll('Article_id,Article_prompt,Article_title');
        
        // 查询会员信息
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
        if ($r) {
            $user['headimgurl'] = $r[0]->headimgurl;
            $user['wx_name'] = $r[0]->wx_name;
            $user['user_id'] = $r[0]->user_id;
            $wx_name = $r[0]->user_id;
        } else {
            $user['headimgurl'] = '';
            $user['wx_name'] = '';
            $user['user_id'] = '';
            $wx_name = '';
            $user['status']='0';
            exit(json_encode($user));
        }
        
        // 查询会员信息
        $ru=$this->getModel('UserDistribution')->alias('d')->join('user u','d.pid=u.user_id','LEFT')->fetchWhere(['d.user_id'=>['=',$wx_name]],'u.user_name');
        if ($ru) {
            $tjr = '经纪人:' . $ru[0]->user_name;
        } else {
            $tjr = false;
        }
        
        // 个人中心小红点
        $num_arr = [
                        0,1,2,3,4
        ];
        $res_order = [];
        foreach ($num_arr as $key => $value) {
            if ($value == '4') {
                $order_num=$this->getModel('OrderDetails')->where(['r_status'=>['=',$value],'user_id'=>['=',$wx_name]])->fetchAll('num');
                $res_order[$key] = $order_num;
            } else {
                if ($value == 1) {
                    $re=$this->getModel('Order')->where(['status'=>['=',$value],'user_id'=>['=',$wx_name]])->fetchAll('drawid');
                    if (! empty($re)) { // 未发货
                        foreach ($re as $key001 => $value001) {
                            $drawid = $value001->drawid;
                            if ($drawid > 0) {
                                $ddd=$this->getModel('DrawUser')->where(['id'=>['=',$drawid]])->fetchAll('lottery_status,draw_id');
                                if (! empty($ddd)) {
                                    $lottery_status = $ddd[0]->lottery_status;
                                    if ($lottery_status != 4) {
                                        // 抽奖成功
                                        unset($re[$key001]);
                                    }
                                }
                            }
                        }
                    }
                    $res_order[$key] = sizeof($re);
                } else {
                    $order_num=$this->getModel('Order')->where(['status'=>['=',$value],'user_id'=>['=',$wx_name]])->fetchAll('num');
                    $res_order[$key] = $order_num;
                }
            }
        }
        // 控制红包显示
        $rfhb=$this->getModel('RedPacketUsers')->where(['user_id'=>['=',$wx_name]])->fetchAll('user_id');
        // 查询插件表里,状态为启用的插件
        $r_c=$this->getModel('PlugIns')->where(['status'=>['=','1'],'type'=>['=','0'],'software_id'=>['=','3']])->fetchOrder(['sort'=>'asc'],'id,subtitle_name,subtitle_image,subtitle_url');
        if ($r_c) {
            foreach ($r_c as $k => $v) {
                $v->subtitle_image = $img . $v->subtitle_image;
                if (strpos($v->subtitle_name, '红包') !== false) {
                    if (! $rfhb) {
                        unset($r_c[$k]);
                    }
                }
            }
        }
        $support = '提供技术支持';
        // 状态 0：未付款 1：未发货 2：待收货 3：待评论 4：退货 5:已完成 6 订单关闭 9拼团中 10 拼团失败-未退款 11 拼团失败-已退款
        // 抽奖状态（0.参团中 1.待抽奖 2.参团失败 3.抽奖失败 4.抽奖成功）
        echo json_encode(array(
                                'status' => 1,'support' => $support,'tjr' => $tjr,'user' => $user,'th' => $res_order['4'],'dfk_num' => $res_order['0'],'dfh_num' => $res_order['1'],'dsh_num' => $res_order['2'],'dpj_num' => $res_order['3'],'company' => $company,'logo' => $logo,'article' =>'','plug_ins' => $r_c
        ));
        exit();
        return;
    }
   private function getOpenId($code)
   {
       if(Session::has('openid'))
           return Session::get('openid');
           $r=$this->getConfig();
           if ($r) {
               $appid = $r[0]->appid; // 小程序唯一标识
               $appsecret = $r[0]->appsecret; // 小程序的 app secret
           }
           $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $appsecret . '&js_code=' . $code . '&grant_type=authorization_code';
           $res=$this->Curl($url);
           $user = (array) json_decode($res);
           if(!empty($user['openid'])){
           Session::set('openid',$user['openid']); 
           return $user['openid'];
           }
          return '';
   }
    public function material (Request $request)
    {
        // 获取信息
        $code=$request->post('code');
        $openid = $request['openid']; // 微信id
        if(empty($openid)||$openid=='undefined'){
            !empty($code)&&$openid=$this->getOpenId($code);
            if(empty($openid))
            exit( json_encode(array(
                'status' => 0,'info' => '无授权信息'
            )));
        }
        $nickName = $request['nickName']; // 微信昵称
        $avatarUrl = $request['avatarUrl']; // 微信头像
        $gender = $request['gender']; // 性别
                                    // 根据微信id,修改用户昵称、微信昵称、微信头像、性别
        $r=$this->getModel('User')->saveAll(['user_name'=>$nickName,'wx_name'=>$nickName,'sex'=>$gender,'headimgurl'=>$avatarUrl],['wx_id'=>['=',$openid]]);
        
        echo json_encode(array(
                                'status' => 1,'info' => '资料已更新','openid'=>$openid
        ));
        exit();
        return;
    }

    public function verify_paw (Request $request)
    {
        
        
        $openid = $request->param('openid');
        $ypwd = $request->param('password');
        $and = '';
        if ($ypwd) {
            $ypwd = md5($ypwd);
            $and = "AND password = '$ypwd' ";
        }
        // 验证密码是否存在 或是否设置
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('password');
        if ($r) {
            $pasw = $r[0]->password; // password
            if (! empty($pasw)) {
                echo json_encode(array(
                                        'status' => 1,'succ' => 'OK'
                ));
                exit();
            } else {
                echo json_encode(array(
                                        'status' => 0,'err' => 'NO'
                ));
                exit();
            }
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => 'NO1'
            ));
            exit();
        }
    }

    public function details (Request $request)
    {
        
        
        // 接收信息
        $openid = $request['openid']; // 微信id
                                    // 查询单位
        $r_1=$this->getModel('FinanceConfig')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r_1) {
            $user['min_amount'] = $r_1[0]->min_amount; // 最小提现金额
            $user['max_amount'] = $r_1[0]->max_amount; // 最大提现金额
            $user['unit'] = $r_1[0]->unit; // 单位
            $user['multiple'] = $r_1[0]->multiple; // 提现倍数
        } else {
            $user['min_amount'] = 0; // 最小提现金额
            $user['max_amount'] = 0; // 最大提现金额
            $user['unit'] = 0; // 单位
            $user['multiple'] = 0; // 提现倍数
        }
        
        // 查询会员信息
        $r_2=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
        if ($r_2) {
            $user_id = $r_2[0]->user_id; // 用户id
            $user_name = $r_2[0]->user_name; // 用户昵称
            $user['money'] = $r_2[0]->money; // 用户余额
            if ($user['money'] == '') {
                $user['money'] = 0;
            }
            $r_3=$this->getModel('UserBankCard')->where(['user_id'=>['=',$user_id],'is_default'=>['=','1']])->fetchAll('*');
            if ($r_3) {
                $user['Bank_name'] = $r_2[0]->Bank_name; // 银行名称
                $user['Cardholder'] = $r_2[0]->Cardholder; // 持卡人
                $user['Bank_card_number'] = $r_2[0]->Bank_card_number; // 银行卡号
            } else {
                $user['Bank_name'] = ''; // 银行名称
                $user['Cardholder'] = ''; // 持卡人
                $user['Bank_card_number'] = ''; // 银行卡号
            }
        }
        
        // 根据推荐人等于会员编号,查询推荐人总数
        $r_3=$this->getModel('User')->where(['Referee'=>['=',$user_id]])->fetchAll('count(Referee) as a');
        $user['invitation_num'] = $r_3[0]->a;
        // 根据微信id,查询分享列表里的礼券总和
        $r_4=$this->getModel('Share')->where(['wx_id'=>['=',$openid]])->fetchAll('sum(coupon) as a');
        if ($r_4[0]->a == '') {
            $user['coupon'] = 0;
        } else {
            $user['coupon'] = $r_4[0]->a;
        }
        // 根据用户id、类型为充值,查询操作列表-----消费记录
        $r_5=$this->getModel('Record')->where(['user_id'=>['=',$user_id]])->fetchOrder(['add_date'=>'desc'],'money,add_date,type');
        $list_1 = [];
        if ($r_5) {
            foreach ($r_5 as $k => $v) {
                if ($v->type == 1 || $v->type == 4 || $v->type == 5 || $v->type == 6 || $v->type == 12 || $v->type == 13 || $v->type == 14) {
                    $v->time = substr($v->add_date, 0, strrpos($v->add_date, ':'));
                    $list_1[$k] = $v;
                }
            }
        }
        $r_6=$this->getModel('Record')->where(['user_id'=>['=',$user_id],'type'=>['=','21']])->fetchOrder(['add_date'=>'desc'],'money,add_date');
        if ($r_6) {
            foreach ($r_6 as $k => $v) {
                $v->time = substr($v->add_date, 0, strrpos($v->add_date, ':'));
            }
            $list_2 = $r_6;
        } else {
            $list_2 = '';
        }
        echo json_encode(array(
                                'status' => 1,'user' => $user,'list_1' => $list_1,'list_2' => $list_2
        ));
        exit();
        
        return;
    }

    public function secret_key (Request $request)
    {
        
        
        // 接收信息
        $encryptedData = $request->param('encryptedData'); // 加密数据
        $iv = $request->param('iv'); // 加密算法
        $sessionKey = $request->param('sessionId'); // 会话密钥
        if ($encryptedData == '' || $iv == '') {
            echo json_encode(array(
                                    'status' => 0,'info' => '手机号码没获取!'
            ));
            exit();
        } else {
            // 查询小程序配置
            $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
            if ($r) {
                $appid = $r[0]->appid; // 小程序唯一标识
            } else {
                $appid = '';
            }
            
            include_once "wxBizDataCrypt.php";
            $data = '';
            $pc = new WXBizDataCrypt($appid, $sessionKey);
            $errCode = $pc->decryptData($encryptedData, $iv, $data);
            if ($errCode == 0) {
                $arr = json_decode($data, true);
                $mobile = $arr['phoneNumber'];
                echo json_encode(array(
                                        'status' => 1,'info' => $mobile
                ));
                exit();
            } else {
                echo json_encode(array(
                                        'status' => 0,'info' => '系统繁忙!'
                ));
                exit();
            }
        }
    }

    public function withdrawals (Request $request)
    {
        
        
        // 接收信息
        $money = $request['money']; // 金额
        $min_amount = $request['min_amount']; // 最少提现金额
        $max_amount = $request['max_amount']; // 最大提现金额
        $amoney = $request['amoney']; // 提现金额
        $Bank_name = $request['Bank_name']; // 银行名称
        $Cardholder = $request['Cardholder']; // 持卡人
        $Bank_card_number = $request['Bank_card_number']; // 银行卡号
        $openid = $request['openid']; // 微信id
        $mobile = $request['mobile']; // 联系电话
                                    // 提现金额不为数字
        if (is_numeric($amoney) == false) {
            echo json_encode(array(
                                    'status' => 0,'info' => '请输入数字!'
            ));
            exit();
        }
        // 根据微信id,查询会员金额
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
        if ($r) {
            $money = $r[0]->money; // 会员金额
                                   
            // 提现金额是否小于等于0,或者大于现有金额
            if ($amoney > $money || $amoney <= 0) {
                echo json_encode(array(
                                        'status' => 0,'info' => '输入金额不正确!'
                ));
                exit();
            }
            // 提现金额小于最小提现金额
            if ($amoney < $min_amount) {
                echo json_encode(array(
                                        'status' => 0,'info' => '提现金额过少!'
                ));
                exit();
            }
            // 提现金额大于最大提现金额
            if ($amoney > $max_amount) {
                echo json_encode(array(
                                        'status' => 0,'info' => '提现金额过多!'
                ));
                exit();
            }
            // 银行卡号不为数字
            if (is_numeric($Bank_card_number) == false) {
                echo json_encode(array(
                                        'status' => 0,'info' => '请输入卡号!'
                ));
                exit();
            }
            // 根据卡号,查询银行名称
           // require_once ('bankList.php');
            $banklist=include(APP_PATH.DS.$this->module_path.DS.'controller'.DS.'bankList.php');
            $r = $this->bankInfo($Bank_card_number, $banklist);
            if ($r == '') {
                echo json_encode(array(
                                        'status' => 0,'info' => '卡号不正确!'
                ));
                exit();
            } else {
                $name = strstr($r, '银行', true) . "银行";
                if ($name != $Bank_name) {
                    echo json_encode(array(
                                            'status' => 0,'info' => '银行信息不匹配!'
                    ));
                    exit();
                }
            }
            // 查询提现参数表(手续费)
            $r=$this->getModel('FinanceConfig')->where(['id'=>['=','1']])->fetchAll('*');
            $multiple = $r[0]->multiple;
            $tax = $r[0]->service_charge; // 设置的手续费参数
            $jine = $amoney; // 提现金额
                             // 开启整数倍提现
            if ($multiple) {
                if ($amoney % $multiple == 0) {} else {
                    echo json_encode(array(
                                            'status' => 0,'info' => '提现金额需要是' . $multiple . '的倍数'
                    ));
                    exit();
                }
            }
            
            $cost = $amoney * $tax; // 实际的手续费
            $amoney = $amoney - $cost; // 实际提现金额
                                       // 根据wx_id查询会员id
            $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('money,user_name,user_id');
            $user_name = $r[0]->user_name; // 用户名
            $user_id = $r[0]->user_id; // user_id
                                        // 根据用户id和未核审,查询数据
            $rnum=$this->getModel('Withdraw')->where(['status'=>['=','0'],'user_id'=>['=',$user_id]])->fetchAll('count(id) as a');
            $count = $rnum[0]->a; // 条数
            if ($count > 0) {
                echo json_encode(array(
                                        'status' => 0,'info' => '已有正在审核的申请'
                ));
                exit();
            } else {
                // 根据银行名称、卡号，查询用户银行卡信息
                $r1=$this->getModel('UserBankCard')->where(['Bank_name'=>['=',$Bank_name],'Bank_card_number'=>['=',$Bank_card_number],'user_id'=>['=',$user_id]])->fetchAll('id,Cardholder');
                if ($r1) {
                    $bank_id = $r1[0]->id;
                    if ($Cardholder != $r1[0]->Cardholder) {
                        echo json_encode(array(
                                                'status' => 0,'info' => '持卡人信息错误'
                        ));
                        exit();
                    }
                } else {
                    $bank_id=$this->getModel('UserBankCard')->insert(['user_id'=>$user_id,'Cardholder'=>$Cardholder,'Bank_name'=>$Bank_name,'Bank_card_number'=>$Bank_card_number,'mobile'=>$mobile,'add_date'=>nowDate(),'is_default'=>1]);
                }
                $res=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->dec('money',$jine)->update();
                // 在提现列表里添加一条数据
                $res=$this->getModel('Withdraw')->insert(['name'=>$user_name,'user_id'=>$user_id,'wx_id'=>$openid,'mobile'=>$mobile,'bank_id'=>$bank_id,'money'=>$amoney,'s_charge'=>$cost,'status'=>0,'add_date'=>nowDate()]);
                if ($res == 1) {
                    $event = $user_id . '申请提现' . $jine . '元余额';
                    $user_money = $r[0]->money;
                    $insert_rs=$this->getModel('Record')->insert(['user_id'=>$user_id,'money'=>$jine,'oldmoney'=>$user_money,'event'=>$event,'type'=>2]);
                    
                    echo json_encode(array(
                                            'status' => 1,'info' => '申请成功!'
                    ));
                    exit();
                } else {
                    echo json_encode(array(
                                            'status' => 0,'info' => '申请失败!'
                    ));
                    exit();
                }
            }
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '网络繁忙!'
            ));
            exit();
        }
        
        return;
    }

    public function verify_bank (Request $request)
    {
        
        $Bank_card_number = $request->param('Bank_card_number');
        // 根据卡号,查询银行名称
        $bankList=include(APP_PATH.DS.$this->module_path.DS.'controller'.DS.'bankList.php');
        $r = $this->bankInfo($Bank_card_number, $bankList);
        if ($r == '') {
            echo json_encode(array(
                                    'status' => 0,'err' => '卡号不正确!'
            ));
            exit();
        } else {
            $name = strstr($r, '银行', true) . "银行";
            echo json_encode(array(
                                    'status' => 1,'bank_name' => $name
            ));
            exit();
        }
    }

    function bankInfo($card, $bankList)
    {
        $card_8 = substr($card, 0, 8);
        if (isset($bankList[$card_8])) {
            return $bankList[$card_8];
        }
        $card_6 = substr($card, 0, 6);
        if (isset($bankList[$card_6])) {
            return $bankList[$card_6];
        }
        $card_5 = substr($card, 0, 5);
        if (isset($bankList[$card_5])) {
            return $bankList[$card_5];
        }
        $card_4 = substr($card, 0, 4);
        if (isset($bankList[$card_4])) {
            return $bankList[$card_4];
        }
        return '';
    }

    public function share (Request $request)
    {
        
        
        // 接收信息
        $n = $request['n']; // 参数
        $id = $request['id']; // 新闻id
        $openid = $request['openid']; // 微信id
        
        if ($n == 0) {
            // 根据新闻id,查询新闻信息
            $r=$this->getModel('NewsList')->where(['id'=>['=',$id]])->fetchAll('*');
            if ($r) {
                $total_amount = $r[0]->total_amount; // 红包总金额
                $total_num = $r[0]->total_num; // 红包数量
                $wishing = $r[0]->wishing; // 祝福语
                $min = 0.01; // 每个人最少能收到0.01元
                if (! empty($total_amount) && $total_num != 1) {
                    $safe_total = ($total_amount - ($total_num - 1) * $min) / ($total_num - 1); // 随机安全上限
                    $money = mt_rand($min * 100, $safe_total * 100) / 100; // 红包金额
                    $total_amount = $total_amount - $money; // 剩余金额
                                                        // 把剩余金额替换原数据库金额
                    $update_rs=$this->getModel('NewsList')->saveAll(['total_amount'=>$total_amount,'total_num'=>($total_num-1)],['id'=>['=',$id]]);
                    // 根据wxid,查询会员信息
                    $rr=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
                    if ($rr) {
                        $user_id = $rr[0]->user_id; // 用户id
                        $wx_name = $rr[0]->wx_name; // 微信昵称
                        $sex = $rr[0]->sex; // 性别
                                            // 在分享列表添加一条数据
                        $insert_rs=$this->getModel('Share')->insert(['user_id'=>$user_id,'wx_id'=>$openid,'wx_name'=>$wx_name,'sex'=>$sex,'type'=>$n,'Article_id'=>$id,'coupon'=>$money]);
                        
                        $update_rs=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->inc('money',$money)->update();
                        
                        // 添加日志
                        $ymoney = $r[0]->money;
                        $event = $user_id . '分享获得了' . $money . '元';
                        $rr=$this->getModel('Record')->insert(['user_id'=>$user_id,'money'=>$money,'oldmoney'=>$ymoney,'event'=>$event,'type'=>3]);
                        
                        $text = $wx_name . '领取了' . $money . '元';
                        echo json_encode(array(
                                                'status' => 1,'text' => $money,'wishing' => $wishing
                        ));
                        exit();
                    } else {
                        echo json_encode(array(
                                                'status' => 0,'err' => '网络繁忙!'
                        ));
                        exit();
                    }
                } else {
                    $text = "红包已抢完";
                    $wishing = '';
                    echo json_encode(array(
                                            'status' => 1,'text' => $text,'wishing' => $wishing
                    ));
                    exit();
                }
            } else {
                echo json_encode(array(
                                        'status' => 0,'err' => '网络繁忙!'
                ));
                exit();
            }
        } else if ($n == 1) {
            // 根据文章id,查询文章信息
            $r=$this->getModel('Article')->where(['Article_id'=>['=',$id]])->fetchAll('*');
            if ($r) {
                $total_amount = $r[0]->total_amount; // 红包总金额
                $total_num = $r[0]->total_num; // 红包数量
                $wishing = $r[0]->wishing; // 祝福语
                $min = 0.01; // 每个人最少能收到0.01元
                if (! empty($total_amount) && $total_num != 1) {
                    $safe_total = ($total_amount - ($total_num - 1) * $min) / ($total_num - 1); // 随机安全上限
                    $money = mt_rand($min * 100, $safe_total * 100) / 100; // 红包金额
                    $total_amount = $total_amount - $money; // 剩余金额
                                                        // 把剩余金额替换原数据库金额
                    $update_rs=$this->getModel('Article')->saveAll(['total_amount'=>$total_amount,'total_num'=>($total_num-1)],['Article_id'=>['=',$id]]);
                    // 根据wxid,查询会员信息
                    $rr=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
                    if ($rr) {
                        $user_id = $rr[0]->user_id; // 用户id
                        $wx_name = $rr[0]->wx_name; // 微信昵称
                        $sex = $rr[0]->sex; // 性别
                                            
                        // 在分享列表添加一条数据
                        $insert_rs=$this->getModel('Share')->insert(['user_id'=>$user_id,'wx_id'=>$openid,'wx_name'=>$wx_name,'sex'=>$sex,'type'=>$n,'Article_id'=>$id,'coupon'=>$money]);
                        
                        $update_rs=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->inc('money',$money)->update();
                        
                        echo json_encode(array(
                                                'status' => 1,'text' => $money,'wishing' => $wishing
                        ));
                        exit();
                    } else {
                        echo json_encode(array(
                                                'status' => 0,'err' => '网络繁忙!'
                        ));
                        exit();
                    }
                } else {
                    $text = "红包已抢完";
                    $wishing = '';
                    echo json_encode(array(
                                            'status' => 1,'text' => $text,'wishing' => $wishing
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
        return;
    }

    public function AddressManagement (Request $request)
    {
        
        
        // 接收信息
        $openid = $request['openid']; // 微信id
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
        if ($r) {
            $user_id = $r[0]->user_id;
            $user_name = $r[0]->user_name;
            $mobile = $r[0]->mobile;
            $detailed_address = $r[0]->detailed_address;
            $province = $r[0]->province;
            $city = $r[0]->city;
            $county = $r[0]->county;
            $sheng = [];
            $shi = [];
            $xian = [];
            // 查询省
            $rr=$this->getModel('AdminCgGroup')->where(['G_ParentID'=>['=','0']])->fetchAll('*');
            if ($rr) {
                foreach ($rr as $k => $v) {
                    $result = array();
                    $result['GroupID'] = $v->GroupID; // 编号
                    $result['G_CName'] = $v->G_CName; // 省名
                    $result['G_ParentID'] = $v->G_ParentID; // 类型
                    $sheng[] = $result;
                    unset($result); // 销毁指定变量
                }
            }
            
            // 查询市
            $rr=$this->getModel('AdminCgGroup')->where(['G_ParentID'=>['=','2']])->fetchAll('*');
            if ($rr) {
                foreach ($rr as $k => $v) {
                    $result = array();
                    $result['GroupID'] = $v->GroupID; // 编号
                    $result['G_CName'] = $v->G_CName; // 市名
                    $result['G_ParentID'] = $v->G_ParentID; // 类型
                    $shi[] = $result;
                    unset($result); // 销毁指定变量
                }
            }
            
            // 查询县
            $rr=$this->getModel('AdminCgGroup')->where(['G_ParentID'=>['=','35']])->fetchAll('*');
            if ($rr) {
                foreach ($rr as $k => $v) {
                    $result = array();
                    $result['GroupID'] = $v->GroupID; // 编号
                    $result['G_CName'] = $v->G_CName; // 县名
                    $result['G_ParentID'] = $v->G_ParentID; // 类型
                    $xian[] = $result;
                    unset($result); // 销毁指定变量
                }
            }
            
            echo json_encode(array(
                                    'status' => 1,'sheng' => $sheng,'shi' => $shi,'xian' => $xian
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0
            ));
            exit();
        }
        return;
    }

    public function getCityArr (Request $request)
    {
              
        $count = $request['count']; // 接收前台传过来省的行数
        $count=$count?:0;
        // 查询省的编号
        $r=$this->getModel('AdminCgGroup')->where(['G_ParentID'=>['=','0']])->fetchAll('*');
        if ($r) {
            $GroupID = $r[$count]->GroupID; // 根据行数,获取第几条数据
        } else {
            $GroupID = 0;
        }
        $shi = [];
        
        // 根据省查询市
        $r=$this->getModel('AdminCgGroup')->where(['G_ParentID'=>['=',$GroupID]])->fetchAll('*');
        if ($r) {
            foreach ($r as $k => $v) {
                $result = array();
                $result['GroupID'] = $v->GroupID; // 编号
                $result['G_CName'] = $v->G_CName; // 市名
                $result['G_ParentID'] = $v->G_ParentID; // 类型
                $shi[] = $result;
                unset($result); // 销毁指定变量
            }
        }
        echo json_encode(array(
                                'status' => 1,'shi' => $shi
        ));
        exit();
        return;
    }

    public function getCountyInfo (Request $request)
    {
        
        
        $count = $request['count']; // 接收前台传过来省的行数
        $column = $request['column']; // 接收前台传过来市的行数
                                    // 查询省的编号
        $r=$this->getModel('AdminCgGroup')->where(['G_ParentID'=>['=','0']])->fetchAll('*');
        if ($r) {
            $GroupID = $r[$count]->GroupID; // 根据行数,获取第几条数据
        } else {
            $GroupID = 0;
        }
        $xian = [];
        // 根据省查询市
        $r=$this->getModel('AdminCgGroup')->where(['G_ParentID'=>['=',$GroupID]])->fetchAll('*');
        if ($r) {
            $GroupID = $r[$column]->GroupID; // 根据行数,获取第几条数据
        } else {
            $GroupID = 0;
        }
        // 根据市查询县
        $r=$this->getModel('AdminCgGroup')->where(['G_ParentID'=>['=',$GroupID]])->fetchAll('*');
        if ($r) {
            foreach ($r as $k => $v) {
                $result = array();
                $result['GroupID'] = $v->GroupID; // 编号
                $result['G_CName'] = $v->G_CName; // 县名
                $result['G_ParentID'] = $v->G_ParentID; // 类型
                $xian[] = $result;
                unset($result); // 销毁指定变量
            }
        }
        
        echo json_encode(array(
                                'status' => 1,'xian' => $xian
        ));
        exit();
        return;
    }

    public function Preservation (Request $request)
    {
        
        
        $sheng = $request['sheng'];
        $shi = $request['shi'];
        $xuan = $request['xuan'];
        
        // 查询省的编号
        $r=$this->getModel('AdminCgGroup')->where(['G_ParentID'=>['=','0']])->fetchAll('*');
        if ($r) {
            $GroupID = $r[$sheng]->GroupID;
            $province = $r[$sheng]->G_CName;
        } else {
            $GroupID = 0;
            $province = '';
        }
        
        // 根据省查询市
        $r=$this->getModel('AdminCgGroup')->where(['G_ParentID'=>['=',$GroupID]])->fetchAll('*');
        if ($r) {
            $GroupID = $r[$shi]->GroupID;
            $city = $r[$shi]->G_CName;
        } else {
            $GroupID = 0;
            $city = '';
        }
        
        // 根据市查询县
        $r=$this->getModel('AdminCgGroup')->where(['G_ParentID'=>['=',$GroupID]])->fetchAll('*');
        if ($r) {
            $GroupID = $r[$xuan]->GroupID;
            $county = $r[$xuan]->G_CName;
        } else {
            $GroupID = 0;
            $county = '';
        }
        
        echo json_encode(array(
                                'status' => 1,'province' => $province,'city' => $city,'county' => $county
        ));
        exit();
        
        return;
    }

    public function SaveAddress (Request $request)
    {
        
        
        // 获取小程序传过来的值
        $openid = $request['openid'];
        $user_name = $request['user_name']; // 联系人
        $mobile = $request['mobile']; // 联系电话
        $province = $request['province']; // 省
        $city = $request['city']; // 市
        $county = $request['county']; // 县
        $address = $request['address']; // 详细地址
                                      // 查询省的编号
        $r=$this->getModel('AdminCgGroup')->where(['G_CName'=>['=',$province]])->fetchAll('GroupID');
        if ($r) {
            $sheng = $r[0]->GroupID;
        } else {
            $sheng = 0;
        }
        // 查询市的编号
        $r=$this->getModel('AdminCgGroup')->where(['G_CName'=>['=',$city]])->fetchAll('GroupID');
        if ($r) {
            $shi = $r[0]->GroupID;
        } else {
            $shi = 0;
        }
        // 查询县的编号
        $r=$this->getModel('AdminCgGroup')->where(['G_CName'=>['=',$county]])->fetchAll('GroupID');
        if ($r) {
            $xian = $r[0]->GroupID;
        } else {
            $xian = 0;
        }
        if (preg_match("/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\\d{8}$/", $mobile)) {
            // 根据微信id,查询会员id
            $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
            if ($r) {
                $user_id = $r[0]->user_id; // 用户id
                $address_xq = $province . $city . $county . $address; // 带省市县的详细地址
                $r=$this->getModel('UserAddress')->where(['uid'=>['=',$user_id]])->fetchAll('id');
                if ($r) {
                    $rr=$this->getModel('UserAddress')->insert(['name'=>$user_name,'tel'=>$mobile,'sheng'=>$sheng,'city'=>$shi,'quyu'=>$xian,'address'=>$address,'address_xq'=>$address_xq,'uid'=>$user_id,'is_default'=>0]);
                } else {
                    $rr=$this->getModel('UserAddress')->insert(['name'=>$user_name,'tel'=>$mobile,'sheng'=>$sheng,'city'=>$shi,'quyu'=>$xian,'address'=>$address,'address_xq'=>$address_xq,'uid'=>$user_id,'is_default'=>1]);
                }
                if ($rr >= 0) {
                    echo json_encode(array(
                                            'status' => 1,'info' => '保存成功'
                    ));
                    exit();
                } else {
                    echo json_encode(array(
                                            'status' => 0,'info' => '未知原因,修改失败！'
                    ));
                    exit();
                }
            } else {
                echo json_encode(array(
                                        'status' => 0,'err' => '网络繁忙!'
                ));
                exit();
            }
        } else {
            echo json_encode(array(
                                    'status' => 0,'info' => '手机号码有误！'
            ));
            exit();
        }
        return;
    }

    public function selectuser (Request $request)
    {
        
        
        $user_id = $request['user_id'];
        $openid = $request['openid'];
        $r=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->fetchAll('*');
        if ($r) {
            $user['wx_name'] = $r[0]->wx_name;
            $user['headimgurl'] = $r[0]->headimgurl;
            $user['user_id'] = $r[0]->user_id;
        } else {
            $user['wx_name'] = '';
            $user['headimgurl'] = '';
            $user['user_id'] = '';
        }
        $r001=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
        if ($r001) {
            $user['money'] = $r001[0]->money;
            $user['score'] = $r001[0]->score;
        } else {
            $user['money'] = 0;
            $user['score'] = 0;
        }
        
        // 查询余额参数表
        $r0001=$this->getModel('FinanceConfig')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r0001) {
            $transfer_multiple = $r0001[0]->transfer_multiple;
            $user['transfer_multiple'] = $transfer_multiple;
        } else {
            $transfer_multiple = 0;
            $user['transfer_multiple'] = '';
        }
        
        if (! empty($r)) {
            // $user['wx_name'] = $r[0]->wx_name;
            // $user['headimgurl'] = $r[0]->headimgurl;
            // $user['user_id'] = $r[0]->user_id;
            // $user['money'] = $r001[0]->money;
            // $user['score'] = $r001[0]->score;
            // $user['transfer_multiple'] = $transfer_multiple;
            echo json_encode(array(
                                    'status' => 1,'user' => $user
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '没有该用户'
            ));
            exit();
        }
    }

    public function transfer (Request $request)
    {
        
        $M=$this->getModel('user');
        // 开启事务
        $M->starttrans();
        $user_id = $request['user_id'];
        $openid = $request['openid'];
        $money = $request['money'];
        $date_time = date('Y-m-d H:i:s', time());
        if ($money <= 0 || $money == '') {
            echo json_encode(array(
                                    'status' => 1,'err' => '正确填写转账金额'
            ));
            exit();
        } else {
            // 查询余额参数表
            $r=$this->getModel('FinanceConfig')->where(['id'=>['=','1']])->fetchAll('*');
            if ($r) {
                $transfer_multiple = $r[0]->transfer_multiple;
                if ($transfer_multiple) {
                    if ($money % $transfer_multiple == 0) {} else {
                        echo json_encode(array(
                                                'status' => 0,'err' => '转账金额需要是' . $transfer_multiple . '的倍数'
                        ));
                        exit();
                    }
                }
            }
            
            $r001=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id,money'); // 本人
            if ($r001) {
                $user_id001 = $r001[0]->user_id;
                $money001 = $r001[0]->money;
            } else {
                $user_id001 = '';
                $money001 = 0;
            }
            
            $r002=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->fetchAll('money'); // 好友
            if ($r002) {
                $money002 = $r002[0]->money;
            } else {
                $money002 = 0;
            }
            
            $r01=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->dec('money',$money)->update(); // 本人
            $r02=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->inc('money',$money)->update(); // 好友
            $r0001=$this->getModel('Record')->insert(['user_id'=>$user_id001,'money'=>$money,'oldmoney'=>$money001,'add_date'=>$date_time,'event'=>'转账给好友','type'=>12]);
            // 好友
            $r0002=$this->getModel('Record')->insert(['user_id'=>$user_id,'money'=>$money,'oldmoney'=>$money002,'add_date'=>$date_time,'event'=>'好友转账','type'=>13]);
            if ($r01 > 0 && $r02 > 0) {
                $M->commit();
                echo json_encode(array(
                                        'status' => 1,'err' => '转账成功！'
                ));
                exit();
            } else {
                $M->rollback();
                echo json_encode(array(
                                        'status' => 0,'err' => '转账失败！'
                ));
                exit();
            }
        }
    }

    public function perfect_index (Request $request)
    {
        
        
        $user_id = trim($request->param('user_id')); // 微信id
        $r002=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->fetchAll('real_name as name,mobile,sex,province,city,county,wechat_id,birthday'); // 好友
        if ($r002) {
            if (empty($r002[0]->name) || empty($r002[0]->mobile)) {
                echo json_encode(array(
                                        'status' => 1,'data' => $r002[0],'binding' => 0
                ));
            } else {
                echo json_encode(array(
                                        'status' => 1,'data' => $r002[0],'binding' => 1
                ));
            }
        } else {
            echo json_encode(array(
                                    'status' => 0
            ));
        }
        exit();
    }

    public function perfect (Request $request)
    {
        
        
        $user_id = trim($request->param('user_id')); // 微信id
        $name = trim($request->param('name')); // 姓名
        $mobile = trim($request->param('mobile')); // mobile
        $province = trim($request->param('province')); // province
        $city = trim($request->param('city')); // city
        $county = trim($request->param('county')); // county
        $wx_id = trim($request->param('wx_id')); // wx_id
        $sex = trim($request->param('sex')); // sex
        $date = trim($request->param('date')); // date
        
        $name = base64_encode($name);
        $name = base64_decode($name);
        
        $r02=$this->getModel('User')->saveAll(['real_name'=>$name,'mobile'=>$mobile,'sex'=>$sex,'province'=>$province,'city'=>$city,'county'=>$county,'wechat_id'=>$wx_id,'birthday'=>$date],['user_id'=>['=',$user_id]]);
        if ($r02) {
            echo json_encode(array(
                                    'status' => 1,'succ' => '修改成功！'
            ));
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '修改失败！'
            ));
        }
        exit();
    }

}