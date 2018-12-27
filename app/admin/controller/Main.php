<?php
namespace app\admin\controller;
use core\Request;
use core\Session;
use app\admin\model\Order;
use app\admin\model\User;
use app\admin\model\Record;
class Main extends Index
{

  function __construct()
  {
    parent::__construct();
  }
  public function Index(Request $request)
  {
          // var_dump(debug_backtrace());exit();
          $admin_id = Session::get('admin_id');         
          $version = $this->version;
              
          //状态查询
          $mon = date("Y-m");//当前月份
          //得到系统的年月
          $tmp_date=date("Ym");
          //切割出年份
          $tmp_year=substr($tmp_date,0,4);
          //切割出月份
          $tmp_mon =substr($tmp_date,4,2);
          $tmp_forwardmonth=mktime(0,0,0,$tmp_mon-1,1,$tmp_year);
          //得到当前月的上一个月
          $lastmon=date("Y-m",$tmp_forwardmonth);
          //今天
          $today = date("Y-m-d");
          //昨天
          $yesterday= date("Y-m-d",strtotime("-1 day"));
          //qiantian
          $qiantian= date("Y-m-d",strtotime("-2 day"));
          $sitian= date("Y-m-d",strtotime("-3 day"));
          $wutian= date("Y-m-d",strtotime("-4 day"));
          //liutian
          $liutian= date("Y-m-d",strtotime("-5 day"));
          //qitian
          $qitian= date("Y-m-d",strtotime("-6 day"));
          
          $today1 = date("m-d");
          //昨天
          $yesterday1= date("m-d",strtotime("-1 day"));
          //qiantian
          $qiantian1= date("m-d",strtotime("-2 day"));
          $sitian1= date("m-d",strtotime("-3 day"));
          $wutian1= date("m-d",strtotime("-4 day"));
          //liutian
          $liutian1= date("m-d",strtotime("-5 day"));
          //qitian
          $qitian1= date("m-d",strtotime("-6 day"));
          
          //--待付款
          $order=new Order($this->db_config,$this->module);
          $dfk = $order->getNum(0);
          //--待发货
          $dp = $order->getNum(1);
          //--待收货
          $yth = $order->getNum(2);
          //待评价订单
          $pj = $order->getNum(3);
          
          //退货订单
          $th = $order->getNum(4);
          //已完成订单
          $wc =$order->getNum(5);
          //当日的营业额
          $day_yy01 = $order->getBalance($today);
          $day_yy =$day_yy01?$day_yy01:0;
          //昨天的营业额
          $yes_yyy = $order->getBalance($yesterday);
          $yes_yy =$yes_yyy?$yes_yyy:0;
          //营业额百分比(当日减去前一天的值除以前一日的营业额)
          if($yes_yy > 0){
              $yingye_day = round(($day_yy-$yes_yy)/$yes_yy *100 , 2)."%";
          }else{
              $yingye_day = '0';
          }
          
          //当日的总订单
          $daydd = $order->getOrders($today);
          
          //昨天的总订单
          $yesdd = $order->getOrders($yesterday);
          //订单百分比(当日减去前一天的值除以前一日的订单)
          if($yesdd > 0){
              $dingdan_day = round(($daydd-$yesdd)/$yesdd *100 , 2)."%";
          }else{
              $dingdan_day = '0';
          }
          
          //这个月的营业额
          $tm0101 = $order->getBalance($mon);
          $tm01 = $tm0101?$tm0101:0;
          
          //这个月的总订单
          $tm = $order->getOrders($mon);
          
          //累计营业额
          $tm0202 = $order->getBalance('');
          $tm02 = $tm0202?$tm0202:0;
          //累计订单数
          $leiji_dd = $order->getOrders();
          
          //会员总数
          
          $user=new User($this->db_config,$this->module);
          
          $couhuiyuan= $user->getCount('','id');
          
          $couhuiyuan01= $user->getMember($today);
          
          $couhuiyuan02= $user->getMember($yesterday);
          
          $couhuiyuan03= $user->getMember($qiantian);
          
          $couhuiyuan04=$user->getMember($sitian);
          
          $couhuiyuan05= $user->getMember($wutian);
          
          $couhuiyuan06= $user->getMember($liutian);
          
          $couhuiyuan07= $user->getMember($qiantian);
        //  $notice = "select * from lkt_set_notice order by time desc";
      //    $res_notice= $db -> select($notice);//公告
          $res_notice=(new \app\admin\model\SetNotice($this->db_config))->fetchOrder(['time'=>'desc']);
          //访客人数
          $record=new Record($this->db_config,$this->module);
          
          
          $fangke= $record->getAccess();
          $fangke01= $record->getAccess($today);
          $fangke02= $record->getAccess($yesterday);
          if($fangke02 > 0){
              $fangkebizhi = round(($fangke01-$fangke02)/$fangke02 *100 , 2)."%";
          }else{
              $fangkebizhi = '0';
          }
          //本月
          $fangke03= $record->getAccess($mon);
          //订单统计
                           
          $couhuiyuan= $order->getCount('','id');
          
          $order01= $order->getOrders($today);
          
          $order02= $order->getOrders($yesterday);
          
          $order03= $order->getOrders($qiantian);
          
          $order04=$order->getOrders($sitian);
          
          $order05= $order->getOrders($wutian);
          
          $order06= $order->getOrders($liutian);
          
          $order07= $order->getOrders($qiantian);         
                                        
          $r_sql_uploadImg = (new \app\admin\model\Config($this->db_config))->fetch();
          $uploadImg = $r_sql_uploadImg[0]->uploadImg; // 图片上传位置
          $this->assign("uploadImg",$uploadImg);//--待付款
          $this->assign("version",$version);
          $this->assign("dfk",$dfk);//--待付款
          $this->assign("dp",$dp);//--待发货
          $this->assign("yth",$yth);//--待收货
          $this->assign("pj",$pj);//评价订单
          $this->assign("th",$th);//退货订单
          $this->assign("wc",$wc);//完成订单
          $this->assign("day_yy",$day_yy); //当日的营业额
          $this->assign("yes_yy",$yes_yy); //昨日的营业额
          $this->assign("yingye_day",$yingye_day);//当日的营业额百分比
          $this->assign("daydd",$daydd);//当日的总订单
          $this->assign("yesdd",$yesdd);//前日的总订单
          $this->assign("dingdan_day",$dingdan_day);//当日的订单百分比
          $this->assign("tm01",$tm01);//这个月的营业额
          $this->assign("tm",$tm);//这个月的总订单
          $this->assign("tm02",$tm02);//累计营业额
          $this->assign("leiji_dd",$leiji_dd);//累计订单数
          $this->assign("couhuiyuan01",$couhuiyuan01);//1会员统计
          $this->assign("couhuiyuan02",$couhuiyuan02);//2
          $this->assign("couhuiyuan03",$couhuiyuan03);//3
          $this->assign("couhuiyuan04",$couhuiyuan04);//4
          $this->assign("couhuiyuan05",$couhuiyuan05);//5
          $this->assign("couhuiyuan06",$couhuiyuan06);//6
          $this->assign("couhuiyuan07",$couhuiyuan07);//7
          $this->assign("today",$today1);//1
          $this->assign("yesterday",$yesterday1);//2
          $this->assign("qiantian",$qiantian1);//3
          $this->assign("sitian",$sitian1);//4
          $this->assign("wutian",$wutian1);//5
          $this->assign("liutian",$liutian1);//6
          $this->assign("qitian",$qitian1);//7
          $this->assign("res_notice",$res_notice);//公告
          $this->assign("couhuiyuan",$couhuiyuan);//会员总数
          
          
          //访客人数
          $this->assign("fangke",$fangke);//fangke总数
          $this->assign("fangke01",$fangke01);//
          $this->assign("fangke02",$fangke02);//
          //本月
          $this->assign("fangke03",$fangke03);//fangke总数
          $this->assign("fangkebizhi",$fangkebizhi);//fangke比值
          
          //订单统计
          $this->assign("order01",$order01);
          $this->assign("order02",$order02);//
          $this->assign("order03",$order03);//
          $this->assign("order04",$order04);//
          $this->assign("order05",$order05);//
          $this->assign("order06",$order06);//
          $this->assign("order07",$order07);//
          $authorization='授权情况';
          $this->assign("authorization",$authorization);//--待发货
          
        
    return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
  }

  function changePassword(Request $request)
  {
    $this->redirect($this->module_url.'/index/changePassword');
  }
   function maskContent(Request $request)
  {
    $this->redirect($this->module_url.'/index/maskContent');
  }
}