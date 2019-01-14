<?php
namespace app\api\controller;
use core\Request;
use core\Session;

class Sign extends Api
{
    function __construct()
    {
        parent::__construct();
    }
    private function isSign($user_id)
    {
        $start = date("Y-m-d 00:00:00");
        $end = date("Y-m-d 23:59:59");        
        $r_num=$this->getModel('SignRecord')->where(['user_id'=>['=',$user_id],'sign_time'=>['>',$start]])->where(['sign_time'=>['<',$end],'type'=>['=','0']])->fetchAll('sign_time',1);
       return $r_num?1:0;
    }
    public function index (Request $request)
    {    
        $openid = trim($request->param('openid')); // 微信id
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
        
        // 查询签到参数
        $r=$this->getModel('SignConfig')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $imgurl = $img . $r[0]->imgurl; // 签到图片
            $min_score = $r[0]->min_score; // 最少领取积分
            $max_score = $r[0]->max_score; // 最大领取积分
            $continuity_three = $r[0]->continuity_three; // 连续签到7天
            $continuity_twenty = $r[0]->continuity_twenty; // 连续签到20天
            $activity_overdue = $r[0]->activity_overdue; // 连续签到30天
        } else {
            $imgurl = ''; // 签到图片
            $min_score = ''; // 最少领取积分
            $max_score = ''; // 最大领取积分
            $continuity_three = ''; // 连续签到7天
            $continuity_twenty = ''; // 连续签到20天
            $activity_overdue = ''; // 连续签到30天
        }
        $num = 0;
        
        // 根据微信id,查询用户id,用户积分
        $rr=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id');
        if ($rr) {
            $user_id = $rr[0]->user_id;
            // 查询正在进行的签到活动
            $rrr=$this->getModel('SignActivity')->where(['status'=>['=','1']])->fetchAll('*');
            if ($rrr) {
                $starttime = date("Ymd", strtotime($rrr[0]->starttime)); // 开始时间
                $endtime = date("Ymd", strtotime($rrr[0]->endtime)); // 结束时间
                $nowdate=date("Ymd");
                if($nowdate-$endtime<0)
                    exit(json_encode(['status'=>0,'data'=>'sign disable']));
                $day = $endtime - $starttime; // 活动天数
                
                $start_1 = date("Y-m-d 00:00:00"); // 开始时间
                $end_1 = date("Y-m-d 23:59:59"); // 结束时间
                                                                     // 根据用户id, 查询昨天签到记录
                $rrrr=$this->getModel('SignRecord')->where(['user_id'=>['=',$user_id],'sign_time'=>['>',$start_1]])
                ->where(['sign_time'=>['<',$end_1],'type'=>['=','0']])->fetchAll('*');
                $num=0;
                $is_sign=0;
                if ($rrrr) { // 有数据,就循环查询连续签到几天
                    for ($i = 0; $i <= $day; $i ++) {
                        $start = date("Y-m-d 00:00:00", strtotime("-$i day"));
                        $end = date("Y-m-d 23:59:59", strtotime("-$i day"));
                        
                        $r_num=$this->getModel('SignRecord')->where(['user_id'=>['=',$user_id],'sign_time'=>['>',$start]])->where(['sign_time'=>['<',$end],'type'=>['=','0']])->fetchAll('sign_time');
                        if (empty($r_num)) {
                            break;
                        }
                        $num++;
                        if(substr($r_num[0]->sign_time,0,10)==date("Y-m-d"))
                            $is_sign=1;
                    }
                }
            }
            $is_sign&&exit(json_encode(['status'=>0,'data'=>'today signed']));
            if ($continuity_three != 0 && $num == 7) { // 当设置了连续7天奖励 并且 连续签到7天
                $sign_score = $continuity_three;
            } else if ($continuity_twenty != 0 && $num == 20) { // 当设置了连续20天奖励 并且 连续签到20天
                $sign_score = $continuity_twenty;
            } else if ($activity_overdue != 0 && $num == 30) { // 当设置了连续30天奖励 并且 连续签到30天
                $sign_score = $activity_overdue;
            } else {
                $sign_score = rand($min_score, $max_score);
            }
            
            $record = "会员" . $user_id . "签到领取" . $sign_score . "积分";
            $r_0=$this->getModel('SignRecord')->insert(['user_id'=>$user_id,'sign_score'=>$sign_score,'record'=>$record,'sign_time'=>nowDate(),'type'=>0]);
            if ($r_0 > 0) {
                $r_1=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->fetchAll('score');
                $score = $r_1[0]->score + $sign_score;
                $update_rs=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->inc('score',$sign_score)->update();
                
                $sign_time = [];
                $r_2=$this->getModel('SignRecord')->where(['user_id'=>['=',$user_id],'type'=>['=','0']])->fetchAll('sign_time');
                if ($r_2) {
                    foreach ($r_2 as $k => $v) {
                        $y = date("Y", strtotime($v->sign_time));
                        $m = date("m", strtotime($v->sign_time));
                        $d = date("d", strtotime($v->sign_time));
                        if ($m < 10) {
                            $m = str_replace("0", "", $m);
                        }
                        if ($d < 10) {
                            $d = str_replace("0", "", $d);
                        }
                        $sign_time[$k] = $y . $m . $d;
                    }
                }
                echo json_encode(array(
                                        'status' => 1,'sign_score' => $sign_score,'score' => $score,'sign_time' => $sign_time,'imgurl' => $imgurl,'num' => $num
                ));
                exit();
            } else {
                echo json_encode(array(
                                        'status' => 0,'err' => '系统繁忙！'
                ));
                exit();
            }
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '系统繁忙！'
            ));
            exit();
        }
    }

    public function sign (Request $request)
    {     
        $openid = trim($request->param('openid')); // 微信id
        $year = trim($request->param('year')); // 年
        $month = trim($request->param('month')); // 月
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
        
        // 查询签到参数
        $r=$this->getModel('SignConfig')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $imgurl = $img . $r[0]->imgurl; // 签到图片
        } else {
            $imgurl = $img; // 签到图片
        }
        
        // 根据微信id,查询用户id,用户积分
        $rr=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id');
        if ($rr) {
            $user_id = $rr[0]->user_id;
            $details = '';
            // 查询正在进行的签到活动
            $rrr=$this->getModel('SignActivity')->where(['status'=>['=','1']])->fetchAll('*');
            $sign_state=0;
            if ($rrr) {
                $sign_state=1;
                $starttime = date("Ymd", strtotime($rrr[0]->starttime)); // 开始时间
                $endtime = date("Ymd", strtotime($rrr[0]->endtime)); // 结束时间
                $details = $rrr[0]->detail;
                $nowdate=date("Ymd");
                if($nowdate>$endtime)
                    $sign_state=0;
                $day = $endtime - $starttime; // 活动天数
            } else {
                $day = 1;
            }
            $start_1 = date("Y-m-d 00:00:00", strtotime("-1 day")); // 昨天开始时间
            $end_1 = date("Y-m-d 23:59:59", strtotime("-1 day")); // 昨天结束时间
            $num = 0;
            // 根据用户id, 查询昨天签到记录
            $rrrr=$this->getModel('SignRecord')->where(['user_id'=>['=',$user_id],'sign_time'=>['>',$start_1]])
            ->where(['sign_time'=>['<',$end_1],'type'=>['=','0']])->fetchAll('*');            
            if ($rrrr) { // 有数据,就循环查询连续签到几天
                for ($i = 1; $i <= $day; $i ++) {
                    $start = date("Y-m-d 00:00:00", strtotime("-$i day"));
                    $end = date("Y-m-d 23:59:59", strtotime("-$i day"));
                    
                    $r_num=$this->getModel('SignRecord')->where(['user_id'=>['=',$user_id],'sign_time'=>['>',$start]])
                    ->where(['sign_time'=>['<',$end],'type'=>['=','0']])->fetchAll('*');
                    if (empty($r_num)) {
                        break;
                    }
                    $num++;
                }
            }
            $is_sign=$this->isSign($user_id);
            $startdate=$year.'-'.(strlen($month)>1?$month:'0'.$month).'-00 00:00:00';
           // $startdate = date("Y-m-00 00:00:00",$time); // 月开始时间
            $enddate = date('Y-m-d 23:59:59', strtotime("$startdate +1 month +1 day")); // 月结束时间
            if(intval(substr($enddate,5,2))!=$month)
                $enddate=substr($enddate,0,8).'01 00:00:00';
           //echo $startdate.' '. $enddate;
            $y_time = date('Y', strtotime(date("Y-m-d"))); // 本年年份
            $m_time = date('m', strtotime(date("Y-m-d"))); // 本月月份
            if ($m_time < 10) {
                $m_time = str_replace("0", "", $m_time);
            }
            if ($year > $y_time || ($year==$y_time&&$month > $m_time)) {
                echo json_encode(array(
                                        'status' => 1,'sign_time' => '','imgurl' => $imgurl,'num' => $num,'details' => $details,'is_sign'=>$is_sign,'sign_state'=>$sign_state
                ));
                exit();
            }
            
            $sign_time = [];
            $r_2=$this->getModel('SignRecord')->where(['user_id'=>['=',$user_id],'sign_time'=>['>',$startdate]])
            ->where(['sign_time'=>['<',$enddate],'type'=>['=','0']])->fetchAll('sign_time');
            if ($r_2) {
                foreach ($r_2 as $k => $v) {
                    $y = date("Y", strtotime($v->sign_time));
                    $m = date("m", strtotime($v->sign_time));
                    $d = date("d", strtotime($v->sign_time));
                    if ($m < 10) {
                        $m = str_replace("0", "", $m);
                    }
                    if ($d < 10) {
                        $d = str_replace("0", "", $d);
                    }
                    $sign_time[$k] = $y . $m . $d;
                }
                echo json_encode(array(
                    'status' => 1,'is_sign'=>$is_sign,'sign_time' => $sign_time,'imgurl' => $imgurl,'num' => $num,'details' => $details,'sign_state'=>$sign_state
                ));
                exit();
            } else {
                echo json_encode(array(
                    'status' => 0,'err' => '暂无签到记录！','num' => 0,'details' => $details,'is_sign'=>$is_sign,'sign_state'=>$sign_state
                ));
                exit();
            }
        } else {
            echo json_encode(array(
                'status' => 0,'err' => '系统繁忙！','is_sign'=>$is_sign,'sign_state'=>$sign_state
            ));
            exit();
        }
    }

    public function integral (Request $request)
    {
        
        
        $openid = trim($request->param('openid')); // 微信id
                                                          // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        $logo = $img . $r_1[0]->logo;
        // 根据微信id,查询用户id、积分
        $r_2=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id,score');
        if ($r_2) {
            $user_id = $r_2[0]->user_id; // 用户id
            $score = $r_2[0]->score; // 用户积分
            $list_1=$this->getModel('SignRecord')->where(['user_id'=>['=',$user_id]])->fetchOrder(['sign_time'=>'desc'],'sign_score,sign_time,type');
            $r_3 = [];
            if ($list_1) {
                foreach ($list_1 as $k => $v) {
                    if ($v->type == 0 || $v->type == 2 || $v->type == 4 || $v->type == 6 || $v->type == 7) {
                        $v->sign_time = date("Y-m-d", strtotime($v->sign_time));
                        $r_3[] = $v;
                    }
                }
            }
            $r_4 = [];
            if ($list_1) {
                foreach ($list_1 as $k => $v) {
                    if ($v->type == 1 || $v->type == 3 || $v->type == 5) {
                        $v->sign_time = date("Y-m-d", strtotime($v->sign_time));
                        $r_4[] = $v;
                    }
                }
            }
            $r01=$this->getModel('SoftwareJifen')->where(['id'=>['=','1']])->fetchAll('switch');
            $switch = $r01[0]->switch;
            
            $rules=$this->getModel('SoftwareJifen')->where(['id'=>['=','1']])->fetchAll('*');
            $rule = $rules[0]->rule;
            
            echo json_encode(array(
                                    'status' => 1,'logo' => $logo,'rule' => $rule,'score' => $score,'sign' => $r_3,'consumption' => $r_4,'switch' => $switch
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '系统繁忙！'
            ));
            exit();
        }
    }

    public function transfer_jifen (Request $request)
    {
            
        $user_id = $request['user_id'];
        $openid = $request['openid'];
        $jifen = $request['jifen'];
        $date_time = date('Y-m-d H:i:s', time());
        if ($jifen <= 0 || $jifen == '') {
            echo json_encode(array(
                                    'status' => 1,'err' => '正确填写转账金额'
            ));
            exit();
        } else {
            $r001=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id,money'); // 本人
            if ($r001) {
                $user_id001 = $r001[0]->user_id;
                $r01=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->dec('score',$jifen)->update(); // 本人
                $r02=$this->getModel('User')->where(['user_id'=>['=',$user_id]])->inc('score',$jifen)->update(); // 好友
                // 本人
                $r0001=$this->getModel('SignRecord')->insert(['user_id'=>$user_id001,'sign_score'=>$jifen,'record'=>'转积分给好友','sign_time'=>$date_time,'type'=>3]);
                // 好友
                $r0002=$this->getModel('SignRecord')->insert(['user_id'=>$user_id,'sign_score'=>$jifen,'record'=>'好友转积分','sign_time'=>$date_time,'type'=>4]);
                if ($r01 > 0 && $r02 > 0) {
                    echo json_encode(array(
                                            'status' => 1,'err' => '转账成功！'
                    ));
                    exit();
                } else {
                    echo json_encode(array(
                                            'status' => 0,'err' => '转账失败！'
                    ));
                    exit();
                }
            } else {
                echo json_encode(array(
                                        'status' => 0,'err' => '系统繁忙！'
                ));
                exit();
            }
        }
    }

}