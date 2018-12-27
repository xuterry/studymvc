<?php
namespace app\api\controller;
use core\Request;

class AddFavorites extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function index (Request $request)
    {    
        $openid = $request['openid']; // 微信id
        $pid = $request['pid']; // 产品id
                              // 根据微信id,查询用户id
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id');
        $user_id = $r[0]->user_id;
        // 根据用户id,产品id,查询收藏表
        $r=$this->getModel('UserCollection')->where(['user_id'=>['=',$user_id],'p_id'=>['=',$pid]])->fetchAll('*');
        if ($r) {
            echo json_encode(array(
                                    'status' => 0,'err' => '已收藏！'
            ));
            exit();
        } else {
            // 在收藏表里添加一条数据
            $r=$this->getModel('UserCollection')->insert(['user_id'=>$user_id,'p_id'=>$pid,'add_time'=>nowDate()]);
            if ($r) {
                echo json_encode(array(
                                        'status' => 1,'succ' => '收藏成功!','id' => $r
                ));
                exit();
            } else {
                echo json_encode(array(
                                        'status' => 0,'err' => '网络繁忙！'
                ));
                exit();
            }
        }
        
        return;
    }

    public function collection (Request $request)
    {        
        
        $openid = $request['openid']; // 微信id
                                    // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        
        // 根据微信id,查询用户id
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id');
        $user_id = $r[0]->user_id;
        
        $r=$this->getModel('UserCollection')->alias('l')->join('product_list a','l.p_id=a.id','left')->join('configure c','a.id=c.pid','RIGHT')->where(['l.user_id'=>['=',$user_id],'a.num'=>['>','0']])->order(['l.add_time'=>'desc'])->fetchGroup('c.pid','l.id,a.id as pid,a.product_title,a.imgurl as img,min(c.price) as price');
        $arr = [];
        if ($r) {
            foreach ($r as $k => $v) {
                $array = (array) $v;
                $pid = $array['pid'];
                
                $array['price'] = $v->price;
                $array['imgurl'] = $img . $v->img;
                $v = (object) $array;
                $arr[$k] = $v;
            }
            echo json_encode(array(
                                    'status' => 1,'list' => $arr
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 1,'list' => ''
            ));
            exit();
        }
        return;
    }

    public function removeFavorites (Request $request)
    {
           
        $id = $request['id']; // 收藏id
                            // 根据收藏id,删除收藏表信息
        $r=$this->getModel('UserCollection')->delete($id,'id');
        if ($r > 0) {
            echo json_encode(array(
                                    'status' => 1,'succ' => '已取消！'
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

    public function alldel (Request $request)
    {     
        $openid = trim($request->param('openid')); // 微信id
        $r_user=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id');
        $userid = $r_user['0']->user_id;
        $r=$this->getModel('UserCollection')->delete($userid,'user_id');
        if ($r) {
            echo json_encode(array(
                                    'status' => 1,'succ' => '删除成功！'
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '删除失败！'
            ));
            exit();
        }
    }

}