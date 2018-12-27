<?php
namespace app\api\controller;
use core\Request;

class Notice extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function index (Request $request)
    {    
        // 获取新闻id
        $id = $request['id'];
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        
        // 根据新闻id,查询新闻数据
        $r=$this->getModel('SetNotice')->where(['id'=>['=',$id]])->fetchAll('*');
        
        if ($r) {
           // $url = 'http://' . $request->host; // 根目录
            $r['0']->Article_imgurl = $img . $r['0']->img_url; // 图片
            $content = $r[0]->detail;
          // $ArticleImg = preg_replace('/(<img.+?src=")(.*?)/', '$1' . $url . '$2', $content);
            $r[0]->content = $content;
            $r[0]->Article_title=$r[0]->name;
            echo json_encode(array(
                                    'status' => 1,'article' => $r
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '网络繁忙！'
            ));
            exit();
        }
        return;
    }

    public function share (Request $request)
    {          
        // 获取信息
        $id = $request['id']; // 文章id
        $openid = $request['openid']; // 微信id
        /* ----- 分享成功 ----- */
        // 根据文章id,修改文章分享次数
        $r=$this->getModel('Article')->where(['Article_id'=>['=',$id]])->inc('share_num',1)->update();
        // 根据wx_id,修改会员分享次数
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->inc('share_num',1)->update();
        // 根据wx_id查询会员id
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
        $user_id = $r[0]->user_id;
        $event = $user_id . '分享了文章' . $id;
        // 在操作列表添加一条分享信息
        $r=$this->getModel('Record')->insert(['user_id'=>$user_id,'event'=>$event,'type'=>3]);
        
        /* ----- 是否进入红包 ----- */
        // 根据文章id,查询文章信息
        $r=$this->getModel('Article')->where(['Article_id'=>['=',$id]])->fetchAll('*');
        $total_amount = $r[0]->total_amount; // 红包总金额
        $total_num = $r[0]->total_num; // 红包数量
        if ($total_amount != 0 && $total_num != 0) {
            $start_time = date("Y-m-d 00:00:00"); // 当天开始时间
            $end_time = date("Y-m-d 00:00:00", strtotime("+1 day")); // 明天开始时间
                                                                    // 微信id和当天时间内,查询分享列表
            $r=$this->getModel('Share')->where(['wx_id'=>['=',$openid],'Article_id'=>['=',$id],'$start_time'=>['<','='],'share_add'=>['<',$end_time]])->fetchAll('*');
            
            if (empty($r)) { // 没数据,可以领红包
                echo json_encode(array(
                                        'status' => 1,'err' => '分享成功！','info' => 1
                ));
                exit();
            } else { // 有数据,跳过红包
                echo json_encode(array(
                                        'status' => 1,'err' => '分享成功！','info' => 0
                ));
                exit();
            }
        } else {
            echo json_encode(array(
                                    'status' => 1,'err' => '商家忘设红包！','info' => 0
            ));
            exit();
        }
        return;
    }

}