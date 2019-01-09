<?php
namespace app\admin\controller;

use core\Request;
use core\Session;

class Userlist extends Index
{
 
    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        $admin_id = Session::get('admin_id');
        $name = addslashes(trim($request->param('name'))); // 用户名
        $tel = addslashes(trim($request->param('tel'))); // 联系电话
        $source = addslashes(trim($request->param('source'))); // 来源
        $startdate = $request->param("startdate");
        $enddate = $request->param("enddate");
        // 导出
        $pageto = $request->param('pageto');
        // 每页显示多少条数据
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : '10';
        // 页码
        $page = $request->param('page');
        if ($page) {
            $start = ($page - 1) * 10;
        } else {
            $page = 1;
            $start = 0;
        }
        $condition = '';
        if ($name != '') {
            $condition .= " and user_name like '%$name%' ";
        }
        
        if ($tel != '') {
            $condition .= " and mobile = '$tel' ";
        }
        if ($source != 0) {
            $condition .= " and source = '$source' ";
        }
        
        if ($startdate != '') {
            $condition .= "and Register_data >= '$startdate 00:00:00' ";
        }
        if ($enddate != '') {
            $condition .= "and Register_data <= '$enddate 23:59:59' ";
        }
        
        if ($pageto == 'all') {
            $this->recordAdmin($admin_id, ' 导出用户列表全部数据 ', 4);
        } else if ($pageto == 'ne') {
            $this->recordAdmin($admin_id, ' 导出用户列表第' . $page . '页' . $pagesize . '条数据 ', 4);
        }
       if($pageto=='all')
           $r=$this->getModel('User')->where($condition)->fetchOrder(['Register_data'=>'desc']);
       else{
           $config['path'] = $this->module_url . "/userlist/Index";
           parse_str("name=" . urlencode($name) . "&tel=" . urlencode($tel) . "&source=" . urlencode($source) . "&startdate=" . urlencode($startdate) . "&enddate=" . urlencode($enddate),$urls);
           $config['query']=$urls;
           $r=$this->getModel('User')->where($condition)->order(['Register_data'=>'desc'])->paginator($pagesize,$config);
           $pages_show=$r->render(); 
       }
        // 查询订单数
        foreach ($r as $key => $value) {
            $r1 = $this->getModel('Order')->where(['user_id'=>['=',$value->user_id]])->sum('z_price');
            if ($r) {
                $r[$key]->z_price = $r1;
            } else {
                $r[$key]->z_price = 0;
            }
            $r[$key]->z_num = $this->getModel('Order')->getCount("user_id = '".$value->user_id."'",'id');
        }
        
        $this->assign('pageto', $pageto);
        $this->assign("name", $name);
        $this->assign("tel", $tel);
        $this->assign("source", $source);
        $this->assign("list", $r);
        $this->assign('startdate', $startdate);
        $this->assign('enddate', $enddate);
        $this->assign('pages_show', $pages_show);
        $this->assign('pagesize', $pagesize);
        !empty($pageto)&&$this->pagetoExcel();
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function Send(Request $request)
    {
        
        // $senderid = $request->param('senderid') ;// 发件人ID
        $recipientid = $request->param('recipientid'); // 收件人ID
        $title = $request->param("title"); // 标题
        $content = $request->param("content"); // 内容
        $admin_id = Session::get('admin_id');
        $res=$this->getModel('Admin')->where(['name'=>['=',$admin_id]])->fetchAll('id');
        $senderid = $res[0]->id;
        // print_r($request);die;
        // $recipientid = '11,39,40';
        // $senderid = '34';
        // $title ='测试';
        // $content ='测试，测试，测试';
        
        if (empty($senderid)) {
            echo json_encode(array(
                                    'status' => 105,'err' => '未传发件人ID'
            ));
            exit();
        }
        if (empty($recipientid)) {
            echo json_encode(array(
                                    'status' => 105,'err' => '未传收件人ID'
            ));
            exit();
        }
        // print_r($recipientid);die;
        $recip = explode(',', $recipientid); // 字符串转一维数组
        $cor = count($recip);
        // print_r($cor);die;
        $conr = 0;
        foreach ($recip as $key => $value) {
            $datatime = date('Y-m-d H:i:s', time());
            $r=$this->getModel('SystemMessage')->insert(['senderid'=>$senderid,'recipientid'=>$value,'title'=>$title,'content'=>$content,'time'=>$datatime],false);
            $conr += $r;
        }
        // print_r($cor);die;
        if ($cor == $conr) {
            // echo json_encode(array('status'=>200,'err'=>'发送成功！'));
            // exit;
            $this->success('发送成功！', $this->module_url . "/userlist");
            return;
        } else {
            $res =$this->getModel('SystemMessage')->delete($datatime,'time');
            // echo json_encode(array('status'=>101,'err'=>'发送失败！'));
            // exit;
            $this->error('发送失败！', $this->module_url . "/userlist");
            return;
        }
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function del(Request $request)
    {
        
        // 接收信息
        $id = $request->param('id'); // id
                                     // 根据新闻id，删除新闻信息
        $admin_id = Session::get('admin_id');
                
        $re = $this->getModel('order')->alias('a')->join('user b','a.user_id=b.user_id')
        ->where("b.id = '".$id."'")->count('a.id');
        
        if ($re > 0) { // 有订单，不能删除
            $this->recordAdmin($admin_id, '删除用户 ' . $id . ' 失败', 24);
            $res = array(
                        'status' => '3','info' => '有订单，不能删除！'
            );
            echo json_encode($res);
            exit();
        } else {
            $a = $this->recordAdmin($admin_id, ' 删除用户 ' . $id, 24);
            $del_rs=$this->getModel('User')->delete($id,'id');
            $res = array(
                        'status' => '1','info' => '删除成功！'
            );
            // echo json_encode($res);
            echo $del_rs;
            exit();
        }
    }

    public function recharge(Request $request)
    {
        $user_id = addslashes(trim($request->param('user_id'))); // 用户user_id
        $types = trim($request->param('m'));
        $price = trim($request->param('price'));
        $rs = $this->getModel('User')->fetchWhere("user_id ='".$user_id."'",$types);
        $rprice = $rs[0]->$types; // 原来的
        if ($price < 0) {
            if ($rprice + $price >= 0) {
                $res=$this->getModel('User')->where(['user_id'=>['=',$user_id]])
                ->dec($types,$price)->update();
                // 添加日志
                if ($res) {
                    if ($types == 'money') {
                        $event = '系统扣除' . $price . '余额'; 
                        
                        $rr=$this->getModel('Record')->insert(['user_id'=>$user_id,'money'=>$price,'oldmoney'=>$rprice,'add_date'=>nowDate(),'event'=>$event,'type'=>11]);
                    } else if ($types == 'consumer_money') {
                        $event = '系统扣除' . $price . '消费金';
                        
                        $rr=$this->getModel('Record')->insert(['user_id'=>$user_id,'money'=>$price,'oldmoney'=>$rprice,'add_date'=>nowDate(),'event'=>$event,'type'=>18]);
                        $event1 = '系统扣除' . $price . '消费金';
                        $insert_rs=$this->getModel('DistributionRecord')->insert(['user_id'=>$user_id,'from_id'=>0,'money'=>$price,'level'=>0,'event'=>$event1,'type'=>6,'add_date'=>nowDate()]);
                    } else {
                        $event = '系统扣除' . $price . "积分";
                        
                        $rr=$this->getModel('Record')->insert(['user_id'=>$user_id,'money'=>$price,'oldmoney'=>$rprice,'add_date'=>nowDate(),'event'=>$event,'type'=>17]);
                        
                        $event1 = "系统扣除" . $price . "积分";
                        $insert_rs=$this->getModel('SignRecord')->insert(['user_id'=>$user_id,'sign_score'=>$price,'record'=>$event1,'sign_time'=>nowDate(),'type'=>5]);
                    }
                }
            } else {
                $res = 0;
            }
        } else {
            $res=$this->getModel('User')->where(['user_id'=>['=',$user_id]])
            ->inc($types,$price)->update();
            // 添加日志 类型 0:登录/退出 1:充值 2:提现 3:分享4:余额消费 5:退款 6红包提现 7佣金 8管理佣金 9 待定 10 消费金 11 系统扣款
            if ($res) {
                if ($types == 'money') {
                    $event = $user_id . '系统充值' . $price . '余额';
                    
                    $rr=$this->getModel('Record')->insert(['user_id'=>$user_id,'money'=>$price,'oldmoney'=>$rprice,'event'=>$event,'type'=>14]);
                } else if ($types == 'consumer_money') {
                    $event = $user_id . '系统充值' . $price . '消费金';
                    
                    $rr=$this->getModel('Record')->insert(['user_id'=>$user_id,'money'=>$price,'oldmoney'=>$rprice,'event'=>$event,'type'=>16]);
                    
                    $event1 = '系统充值' . $price . '消费金';
                    $insert_rs=$this->getModel('DistributionRecord')->insert(['user_id'=>$user_id,'from_id'=>0,'money'=>$price,'level'=>0,'event'=>$event1,'type'=>13,'add_date'=>nowDate()]);
                } else {
                    $event = $user_id . '系统充值' . $price . "积分";
                    
                    $rr=$this->getModel('Record')->insert(['user_id'=>$user_id,'money'=>$price,'oldmoney'=>$rprice,'event'=>$event,'type'=>15]);
                    
                    $event1 = "系统充值" . $price . "积分";
                    $insert_rs=$this->getModel('SignRecord')->insert(['user_id'=>$user_id,'sign_score'=>$price,'record'=>$event1,'sign_time'=>nowDate(),'type'=>6]);
                }
            } else {
                $res = 0; 
            }
        }
        echo $res;
        return;
    }

    public function seng(Request $request)
    {
        $recipientid = $request->param('id');
        $this->assign("recipientid", $recipientid);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function view(Request $request)
    {
        $request->method() == 'post' && $this->do_view($request);
        
        $id = $request->param("id");
        $r = $this->getModel('User')->get($id, 'id');
        if (! $r) {
            $r = $this->getModel('User')->get($id, 'user_id');
        }
        $this->assign('user', $r);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_view($request)
    {
        
        // 接收信息
        $id = intval($request->param('id'));
        
        $sort = floatval(trim($request->param('sort')));
        
        $content = addslashes($request->param('content'));
        
        $Article_title = trim($request->param('Article_title'));
        
        // 判断是否重新上传过图片 -》 将临时文件复制到upload_image目录下
        $file=$request->file('imgurl');
        $imgURL = $file['tmp_name'];
        
        if ($imgURL) {
            
            $imgURL_name = $file['name'];
            if(empty($uploadImg=Session::get('uploadImg'))){
                $uploadImg=$this->getConfig()[0]->uploadImg;
            }
            move_uploaded_file($imgURL, "../upfile/$imgURL_name");
            
        } else {
            
            $imgURL_name = '';
        }
        
        // 检查文章标题是否重复
        
        $r=$this->getModel('Article')->where(['Article_title'=>['=',$Article_title],'Article_id'=>['<>',$id]])->fetchAll('1');
        
        if ($r && count($r) > 0) {
            
            $this->error('{$Article_title} 已经存在，请选用其他标题进行修改！', '');
        }
        
        // 更新数据表
        
        $r=$this->getModel('Article')->saveAll(['Article_title'=>$Article_title,'sort'=>$sort,'Article_imgurl'=>$imgURL_name,'content'=>$content],['Article_id'=>['=',$id]]);        
        if ($r == false) {
            
            $this->error('未知原因，文章修改失败！', $this->module_url . "/Article");
        } else {
            
            $this->success('文章修改成功！', $this->module_url . "/Article");
        }
        
        return;
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