<?php
namespace app\admin\controller;

use core\Request;
use core\Session;

class Comments extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        $ordtype = array(
                        '0' => '全部','GOOD' => '好评','NOTBAD' => '中评','BAD' => '差评'
        );
        $otype = isset($_GET['otype']) && $_GET['otype'] !== '' ? $_GET['otype'] : false;
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : false;
        $ostatus = isset($_GET['ostatus']) && $_GET['ostatus'] !== '' ? $_GET['ostatus'] : false;
        $sNo = isset($_GET['sNo']) && $_GET['sNo'] !== '' ? $_GET['sNo'] : false;
        $condition = ' 1=1';
        if ($otype) {
            $condition .= " and c.CommentType = '$otype' ";
        }
        
        $pageto = $request->param('pageto'); // 导出
        $pagesize = $request->param('pagesize'); // 每页显示多少条数据
        empty($pagesize)&&$pagesize=10;
        $page = $request->param('page'); // 页码
        
        $startdate = $request->param("startdate");
        $enddate = $request->param("enddate");
        if ($startdate != '') {
            $condition .= " and c.add_time >= '$startdate 00:00:00' ";
        }
        if ($enddate != '') {
            $condition .= " and c.add_time <= '$enddate 23:59:59' ";
        }
        
        if (strlen($status) == 1) {
            if ($status !== false) {
                $cstatus = intval($status);
                $condition .= " and a.r_status=$cstatus";
            }
        } else if (strlen($status) > 1) {
            if ($status !== false) {
                $cstatus = intval(substr($status, 1));
                $condition .= " and a.ptstatus=$cstatus";
            }
        }
        if ($ostatus !== false) {
            $costatus = intval(substr($ostatus, 1)); 
            $condition .= " and a.r_status=$costatus";
        }
        if ($sNo !== false)
            $condition .= ' and a.r_sNo like "%' . $sNo . '%" ';
        $pageshow='';
        $res1=$this->getModel('OrderDetails')->alias('a')->join('comments c','a.r_sNo=c.oid')->join('order o','a.r_sNo=o.sNo','LEFT')->where($condition)
        ->order(['c.add_time'=>'desc'])
        ->field('a.id as odid,a.r_sNo,a.p_price,a.p_name,a.r_status,c.*,o.otype')
        ->paginator($pagesize,$this->getUrlConfig($request->url));
        if ($res1) {
            $pageshow=$res1->render();
            foreach ($res1 as $k => $v) {
                if (! $v->anonymous || $v->anonymous == '') {
                    $v->anonymous = '匿名';
                }
                $v->CommentType1='';
                $v->drawid=0;

                    if ($v->CommentType == 5) {
                        $res1[$k]->CommentType1 = 'GOOD';
                    } elseif ($v->CommentType == 4) {
                        $res1[$k]->CommentType1 = 'GOOD';
                    } elseif ($v->CommentType == 3) {
                        $res1[$k]->CommentType1 = 'NOTBAD';
                    } elseif ($v->CommentType == 2) {
                        $res1[$k]->CommentType1 = 'BAD';
                    } elseif ($v->CommentType == 1) {
                        $res1[$k]->CommentType1 = 'BAD';
                    } elseif ($v->CommentType == 'GOOD') {
                        $res1[$k]->CommentType1 = 'GOOD';
                    } elseif ($v->CommentType == 'NOTBAD') {
                        $res1[$k]->CommentType1 = 'NOTBAD';
                    } else {
                        $res1[$k]->CommentType1 = 'BAD';
                    }
           }
            
            
            $this->assign("startdate", $startdate);
            $this->assign("enddate", $enddate);
            $this->assign("ordtype", $ordtype);
            $this->assign("order", $res1);
            $this->assign("sNo", $sNo);
            $this->assign("otype", $otype);
            $this->assign("status", $status);
            $this->assign("ostatus", $ostatus);
            $this->assign('pageto', '');
            $this->assign('pages_show',$pageshow);
        }
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function modify(Request $request)
    {
        $request->method() == 'post' && $this->do_modify($request);
        
        $id = addslashes(trim($request->param('id')));
        
        $r_c=$this->getModel('Comments')->alias('a')->join('user m','a.uid=m.wx_id','LEFT')->fetchWhere(['a.id'=>['=',$id]],'a.id,a.add_time,a.content,a.CommentType,a.size,m.user_name,m.headimgurl');
        $cid = $r_c[0]->id;
        $headimgurl = $r_c[0]->headimgurl;
        $user_name = $r_c[0]->user_name;
        $content = $r_c[0]->content;
        $add_time = $r_c[0]->add_time;
        $CommentType = $r_c[0]->CommentType;
        
        $this->assign("cid", $cid);
        $this->assign("add_time", $add_time);
        $this->assign("CommentType", $CommentType);
        $this->assign("headimgurl", $headimgurl);
        $this->assign("user_name", $user_name);
        $this->assign("content", $content);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        $admin_id = Session::get('admin_id');
        
        $id = addslashes(trim($request->param('id')));
        $comment_input = addslashes(trim($request->param('comment_input')));
        $comment_type = addslashes(trim($request->param('comment_type')));
        
        $up=$this->getModel('Comments')->saveAll(['content'=>$comment_input,'CommentType'=>$comment_type],['id'=>['=',$id]]);
        $this->recordAdmin($admin_id, ' 修改评论id为 ' . $id . ' 的信息 ', 2);
        
        echo $up;
        exit();
    }

    public function reply(Request $request)
    {
        $request->method() == 'post' && $this->do_reply($request);
        
        $id = addslashes(trim($request->param('id')));
        
        $r_c=$this->getModel('Comments')->alias('a')->join('user m','a.uid=m.wx_id','LEFT')->fetchWhere(['a.id'=>['=',$id]],'a.id,a.add_time,a.content,a.CommentType,a.size,m.user_name,m.headimgurl');
        
        $cid = $r_c[0]->id;
        
        $headimgurl = $r_c[0]->headimgurl;
        
        $user_name = $r_c[0]->user_name;
        
        $content = $r_c[0]->content;
        
        $add_time = $r_c[0]->add_time;
        
        $CommentType = $r_c[0]->CommentType;
        
        $this->assign("cid", $cid);
        
        $this->assign("add_time", $add_time);
        
        $this->assign("CommentType", $CommentType);
        
        $this->assign("headimgurl", $headimgurl);
        
        $this->assign("user_name", $user_name);
        
        $this->assign("content", $content);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_reply($request)
    {
        $admin_id = Session::get('admin_id');
        
        $id = addslashes(trim($request->param('id')));
        
        $comment_input = addslashes(trim($request->param('comment_input')));
        
        $up=$this->getModel('ReplyComments')->insert(['cid'=>$id,'uid'=>$admin_id,'content'=>$comment_input,'add_time'=>nowDate()]);
        $this->recordAdmin($admin_id, ' 回复评论id为 ' . $id . ' 的信息', 8);
        
        echo $up;
        
        exit();
    }
   public function del(Request $request){
       if($request->method()=='post'){
           $admin_id = Session::get('admin_id');       
           // 接收信息
           $id = intval($request->post('id'));
           $res = $this->getModel('comments')->delete($id,'id');      
           $this->recordAdmin($admin_id,' 删除评论id为 '.$id.' 的信息',3);       
           echo $res;
           exit;
       }
       exit('-1');
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