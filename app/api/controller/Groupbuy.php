<?php
namespace app\api\controller;
use core\Request;

class Groupbuy extends Api
{
    function __construct()  
    { 
        parent::__construct(); 
    }
    public function grouphome (Request $request)
    {     
        $paegr = trim($request->param('page')); // '页面'
        $select = trim($request->param('select')); // 选中的方式 0 默认 1 销量 2价格
        if ($select == 0) {
            $select = 'p.id';
        } elseif ($select == 1) {
            $select = 's.sum';
        } else {
            $select = 'p.group_price';
        }
        
        $sort = trim($request->param('sort')); // 排序方式 1 asc 升序 0 desc 降序
        if ($sort) {
            $sort = ' asc ';
        } else {
            $sort = ' desc ';
        }
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        if (! $paegr) {
            $paegr = 1;
        }
        $start = ($paegr - 1) * 10;
        $end = $paegr * 10;
        
        $restime=$this->getModel('GroupBuy')->where(['is_show'=>['=','1']])->fetchAll('man_num,endtime,status');
        if (! empty($restime)) {
            // $endtime = date('Y-m-d H:i:s',$restime[0] -> endtime);
            $g_man = $restime[0]->man_num;
            $gid = $restime[0]->status;
            /*
            $sql = "select w.*,l.product_title as pro_name from (select z.*,c.img as image,c.price as market_price from 
(select p.id,min(p.attr_id) as attr_id,p.product_id,p.group_price,p.group_id,s.sum from lkt_group_product as p
 left join (select sum(m.num) as sum,m.p_id from
 (select o.num,d.p_id from lkt_order as o left join lkt_order_details as d on o.sNo=d.r_sNo
 where o.pid='$gid' and o.status>0) as m group by m.p_id) as s on p.product_id=s.p_id 
where p.group_id='$gid' group by p.product_id
 order by $select$sort limit $start,$end) 
as z left join lkt_configure as c on z.attr_id=c.id) as w left join lkt_product_list as l on w.product_id=l.id";
            $res = $db->select($sql);
            */
            $res=$this->getModel('groupProduct')->alias('p')
            ->join("order o","o.pid=p.group_id","left")   
            ->where("o.status>0")
            ->join("order_details d","o.sNo=d.r_sNo","left")
            ->join("configure c","c.id=p.attr_id","left")
            ->join("product_list l","p.product_id=l.id",'left')
            ->field("p.*,l.product_title as pro_name,c.img as image,c.price as market_price,p.id,min(p.attr_id) as attr_id
,p.product_id,p.group_price,p.group_id,sum(o.num) as sum,o.num,d.p_id")
           ->where("p.group_id='".$gid."'")           
            ->group("p.product_id")
            ->order($select.$sort)
            ->limit("$start,$end")
            ->select();
            if (! empty($res)) {
                foreach ($res as $k => $v) {
                    $res[$k] = $v;
                    $res[$k]->imgurl = $img . $v->image;
                    if ($v->sum === null)
                        $res[$k]->sum = 0;
                }
            }
            echo json_encode(array(
                                    'code' => 1,'list' => $res,'groupman' => $g_man,'groupid' => $gid
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'code' => 0
            ));
            exit();
        }
    }
    public function morepro (Request $request)
    {    
        $page = $request->param('page');
        $groupid = addslashes(trim($request->param('groupid')));
        
        $total = $page * 8;
        $res=$this->getModel('GroupProduct')->where(['group_id'=>['=',$groupid]])->fetchGroup('product_id','min(attr_id) as attr_id,product_id,group_price,group_id,pro_name,image,market_price',"$total,8");
        
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }      
        if (! empty($res)) {
            $groupid = $res[0]->group_id;
            $ressum=$this->getModel('Order')->alias('o')->join('order_details d','o.sNo=d.r_sNo','left')
            ->where(['o.pid'=>['=',$groupid],'o.status'=>['>','0']])
            ->fetchGroup('m.p_id','sum(m.num) as sum,o.num,d.p_id');
            
            foreach ($res as $k => $v) {
                $v->sum = 0;
                $res[$k] = $v;
                $res[$k]->imgurl = $img . $v->image;
                if (! empty($ressum)) {
                    foreach ($ressum as $ke => $val) {
                        if ($val->p_id == $v->product_id) {
                            $res[$k]->sum = $val->sum;
                        }
                    }
                }
            }
            echo json_encode(array(
                                    'code' => 1,'list' => $res
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'code' => 1,'list' => false
            ));
            exit();
        }
    }
    public function getgoodsdetail (Request $request)
    {      
        $gid = addslashes(trim($request->param('gid')));
        $group_id = addslashes(trim($request->param('group_id')));
        $user_id = addslashes(trim($request->param('userid')));
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        /*
        $sql = 'select m.*,c.num,c.img as image,c.price as market_price from
 (select min(attr_id) as attr_id,product_id,group_price,member_price,product_title as pro_name,classname,content
 from lkt_group_product left join lkt_product_list as l on product_id=l.id where group_id="' . $group_id . '" and product_id=' . $gid . ') 
as m left join lkt_configure as c on m.attr_id=c.id';
*/
        $guigeres = $this->getModel('groupProduct')->alias('m')
        ->join("product_list l","product_id=l.id",'left')
        ->join("configure c","m.attr_id=c.id",'left')
        ->fetchWhere("group_id=' ". $group_id . "' and product_id=' ". $gid ." '"
            ,"min(attr_id) as attr_id,m.*,product_id,group_price,member_price,product_title as pro_name,classname,content,c.num,c.img as image,c.price as market_price");
        list ($guigeres) = $guigeres;        
        $content = $guigeres->content;
        $newa = substr($uploadImg_domain, 0, strrpos($uploadImg_domain, '/'));
        if ($newa == 'http:/' || $newa == 'https:/') {
            $newa = $uploadImg_domain;
        }
        $guigeres->content = preg_replace('/(<img.+?src=")(.*?)/', "$1$newa$2", $content);      
        // $guigeres -> content = preg_replace('/(<img.+?src=")(.*?)/','$1//xiaochengxu.laiketui.com$2', $guigeres -> content);       
        $imgres=$this->getModel('ProductImg')->where(['product_id'=>['=',$gid]])->fetchAll('product_url');       
        $imgarr = [];      
        if (! empty($imgres)) {
            foreach ($imgres as $k => $v) {
                $imgarr[$k] = $img . $v->product_url;
            }
            $guigeres->image = $img . $guigeres->image;
            $guigeres->images = $imgarr;
        } else {
            $guigeres->image = $img . $guigeres->image;
            $imgarr[0] = $guigeres->image;
            $guigeres->images = $imgarr;
        }
        $contres=$this->getModel('GroupBuy')->where(['status'=>['=',$group_id]])->fetchAll('man_num,time_over,endtime,productnum');
        list ($contres) = $contres;
        $guigeres->man_num = $contres->man_num;       
        $commodityAttr = [];
        $r_size=$this->getModel('GroupProduct')->alias('g')->join('configure p','g.attr_id=p.id','left')->fetchWhere(['g.product_id'=>['=',$gid],'group_id'=>['=',$group_id]],'g.attr_id,g.product_id,g.group_price,g.member_price,p.attribute,p.num,p.price,p.yprice,p.img,p.id');        
        $array_price = [];
        $array_yprice = [];
        $skuBeanList = [];
        $attrList = [];
        if ($r_size) {           
            $attrList = [];
            $a = 0;
            $attr = [];
            foreach ($r_size as $key => $value) {
                $array_price[$key] = $value->price;
                $array_yprice[$key] = $value->yprice;
                $attribute = unserialize($value->attribute);
                $attnum = 0;
                $arrayName = [];
                foreach ($attribute as $k => $v) {
                    if (! in_array($k, $arrayName)) {
                        array_push($arrayName, $k);
                        $kkk = $attnum ++;
                        $attrList[$kkk] = array(
                                                'attrName' => $k,'attrType' => '1','id' => md5($k),'attr' => [],'all' => []
                        );
                    }
                }
            }            
            foreach ($r_size as $key => $value) {
                $attribute = unserialize($value->attribute);
                $attributes = [];
                $name = '';
                foreach ($attribute as $k => $v) {
                    $attributes[] = array(
                                        'attributeId' => md5($k),'attributeValId' => md5($v)
                    );
                    $name .= $v;
                }
                
                $cimgurl = $img . $value->img;
                
                $skuBeanList[$key] = array(
                                        'name' => $name,'imgurl' => $cimgurl,'cid' => $value->id,'member_price' => $value->member_price,'price' => $value->price,'count' => $value->num,'attributes' => $attributes
                );
                
                for ($i = 0; $i < count($attrList); $i ++) {
                    $attr = $attrList[$i]['attr'];
                    $all = $attrList[$i]['all'];
                    foreach ($attribute as $k => $v) {
                        if ($attrList[$i]['attrName'] == $k) {
                            $attr_array = array(
                                                'attributeId' => md5($k),'id' => md5($v),'attributeValue' => $v,'enable' => false,'select' => false
                            );
                            
                            if (empty($attr)) {
                                array_push($attr, $attr_array);
                                array_push($all, $v);
                            } else {
                                if (! in_array($v, $all)) {
                                    array_push($attr, $attr_array);
                                    array_push($all, $v);
                                }
                            }
                        }
                    }
                    $attrList[$i]['all'] = $all;
                    $attrList[$i]['attr'] = $attr;
                }
            }
        }       
        // 查询此商品评价记录
        $r_c=$this->getModel('Comments')->alias('a')->join('user m','a.uid=m.user_id','LEFT')->fetchWhere(['a.pid'=>['=',$gid],'m.wx_id'=>['<>','']],'a.id,a.add_time,a.content,a.CommentType,a.size,m.user_name,m.headimgurl',"2");
        $arr = [];
        if (! empty($r_c)) {
            foreach ($r_c as $key => $value) {
                $va = (array) $value;
                $va['time'] = substr($va['add_time'], 0, 10);
                // -------------2018-05-03 修改 作用:返回评论图片
                $comments_id = $va['id'];
                $comment_res=$this->getModel('CommentsImg')->where(['comments_id'=>['=',$comments_id]])->fetchAll('comments_url');
                $va['images'] = '';
                if ($comment_res) {
                    $va['images'] = $comment_res;
                    $array_c = [];
                    foreach ($comment_res as $kc => $vc) {
                        $url = $vc->comments_url;
                        $array_c[$kc] = array(
                                            'url' => $img . $url
                        );
                    }
                    $va['images'] = $array_c;
                }
                // -------------2018-07-27 修改
                $ad_res=$this->getModel('ReplyComments')->where(['cid'=>['=',$comments_id]])->fetchAll('content');
                if ($ad_res) {
                    $reply_admin = $ad_res[0]->content;
                } else {
                    $reply_admin = '';
                }
                
                $va['reply'] = $reply_admin;
                $obj = (object) $va;
                $arr[$key] = $obj;
            }
        }
        if (! empty($r_c)) {
            $goodnum=$this->getModel('Comments')->where(['pid'=>['=',$gid],'CommentType'=>['=','GOOD']])->fetchAll('count(*) as num');
            $com_num = array();
            $com_num['good'] = $goodnum[0]->num;
            $badnum=$this->getModel('Comments')->where(['pid'=>['=',$gid],'CommentType'=>['=','BAD']])->fetchAll('count(*) as num');
            $com_num['bad'] = $badnum[0]->num;
            $notbadnum=$this->getModel('Comments')->where(['pid'=>['=',$gid],'CommentType'=>['=','NOTBAD']])->fetchAll('count(*) as num');
            $com_num['notbad'] = $notbadnum[0]->num;
        } else {
            $com_num = array(
                            'bad' => 0,'good' => 0,'notbad' => 0
            );
        }      
        $res_kt=$this->getModel('GroupOpen')->alias('g')->join('user u','g.uid=u.wx_id','left')->fetchWhere(['g.group_id'=>['=',$group_id],'g.ptgoods_id'=>['=',$gid],'g.ptstatus'=>['=','1']],'g.ptcode,g.ptnumber,g.endtime,u.user_name,u.headimgurl');
        $groupList = [];
        if (! empty($res_kt)) {
            foreach ($res_kt as $key => $value) {
                $res_kt[$key]->leftTime = strtotime($value->endtime) - time();
                if (strtotime($value->endtime) - time() > 0) {
                    array_push($groupList, $res_kt[$key]);
                }
            }
        }
        $plugopen=$this->getModel('PlugIns')->where(['type'=>['=','0'],'software_id'=>['=','3'],'name'=>['like','%拼团%']])->fetchAll('status');
        $plugopen = ! empty($plugopen) ? $plugopen[0]->status : 0;
        
        $share = array(
                    'friends' => true,'friend' => false
        );
        echo json_encode(array(
                                'control' => $contres,'share' => $share,'detail' => $guigeres,'attrList' => $attrList,'skuBeanList' => $skuBeanList,'comments' => $arr,'comnum' => $com_num,'groupList' => $groupList,'isplug' => $plugopen
        ));
        exit();
    }
    public function getcomment (Request $request)
    {     
        $pid = intval($request->param('pid'));
        $page = intval($request->param('page'));
        $checked = intval($request->param('checked'));
        
        $page = $page * 8;
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        $condition = '';
        switch ($checked) {
            case 1:
                $condition .= " and a.CommentType='GOOD'";
                break;
            case 2:
                $condition .= " and a.CommentType='NOTBAD'";
                break;
            case 3:
                $condition .= " and a.CommentType='BAD'";
                break;
            default:
                $condition = '';
                break;
        }       
        // 查询此商品评价记录
        $r_c=$this->getModel('Comments')->alias('a')->join('user m','a.uid=m.user_id','LEFT')->fetchWhere($condition,'a.id,a.add_time,a.content,a.CommentType,a.size,m.user_name,m.headimgurl',"$page,8");
        $arr = [];
        if (! empty($r_c)) {
            foreach ($r_c as $key => $value) {
                $va = (array) $value;
                $va['time'] = substr($va['add_time'], 0, 10);
                // -------------2018-05-03 修改 作用:返回评论图片
                $comments_id = $va['id'];
                $comment_res=$this->getModel('CommentsImg')->where(['comments_id'=>['=',$comments_id]])->fetchAll('comments_url');
                $va['images'] = '';
                if ($comment_res) {
                    $va['images'] = $comment_res;
                    $array_c = [];
                    foreach ($comment_res as $kc => $vc) {
                        $url = $vc->comments_url;
                        $array_c[$kc] = array(
                                            'url' => $img . $url
                        );
                    }
                    $va['images'] = $array_c;
                }
                // -------------2018-07-27 修改
                $ad_res=$this->getModel('ReplyComments')->where(['cid'=>['=',$comments_id],'uid'=>['=','admin']])->fetchAll('content');
                if ($ad_res) {
                    $reply_admin = $ad_res[0]->content;
                } else {
                    $reply_admin = '';
                }
                
                $va['reply'] = $reply_admin;
                
                $obj = (object) $va;
                $arr[$key] = $obj;
            }
            
            echo json_encode(array(
                                    'comment' => $arr
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'comment' => false
            ));
            exit();
        }
    }
    public function getformid (Request $request)
    {     
        $uid = addslashes(trim($request->param('userid')));
        $formid = addslashes(trim($request->param('from_id')));
        
        $fromres=$this->getModel('UserFromid')->where(['open_id'=>['=',$uid]])->fetchAll('count(*) as have');
        $fromres = intval($fromres[0]->have);
        $lifetime = date('Y-m-d H:i:s', time() + 7 * 24 * 3600);
        if ($formid != 'the formId is a mock one') {
            if ($fromres < 8) {
                $addres=$this->getModel('UserFromid')->insert(['open_id'=>$uid,'fromid'=>$formid,'lifetime'=>$lifetime]);
            } else {
                return false;
            }
        }
    }

    public function payfor (Request $request)
    {
        
        
        $uid = addslashes(trim($request->param('uid')));
        $oid = addslashes(trim($request->param('oid')));
        $groupid = addslashes(trim($request->param('groupid')));
        $sizeid = intval(trim($request->param('sizeid')));
        
        // 根据用户id,查询收货地址
        $r_a=$this->getModel('UserAddress')->where(['uid'=>['=','(select']])->fetchAll('id');
        if ($r_a) {
            $arr['addemt'] = 0; // 有收货地址
                              // 根据用户id、默认地址,查询收货地址信息
            $r_e=$this->getModel('UserAddress')->where(['uid'=>['=','(select'],'is_default'=>['=','1']])->fetchAll('*');
            // $r_e = (array)$r_e['0'];
            $r_e = ! empty($r_e) ? (array) $r_e['0'] : array(); // 收货地址
            $arr['adds'] = $r_e;
        } else {
            $arr['addemt'] = 1; // 没有收货地址
            $arr['adds'] = ''; // 收货地址
        }
        /*
        $attrsql = "select m.*,l.product_title as pro_name from
 (select c.attribute,c.img as image,g.product_id,g.group_price,g.member_price,c.num from lkt_group_product as g 
left join lkt_configure as c on g.attr_id=c.id where g.group_id='$groupid' and g.attr_id=$sizeid) as m left join lkt_product_list as l on m.product_id=l.id";
*/
        $attrres =  $this->getModel('groupProduct')->alias('m')
        ->join("product_list l","product_id=l.id",'left')
        ->join("configure c","m.attr_id=c.id",'left')
        ->fetchWhere("group_id=' ". $groupid . "' and attr_id=' ". $sizeid ." '"
            ,"min(attr_id) as attr_id,m.*,product_id,group_price,member_price,product_title as pro_name,classname,content,c.num,c.img as image,c.price as market_price");
        
        list ($attrres) = $attrres;
        
        $attribute = unserialize($attrres->attribute);
        $size = '';
        foreach ($attribute as $ka => $va) {
            $size .= ' ' . $va;
        }
        $attrres->size = $size;
        
        // 查询系统参数
        
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        $attrres->image = $img . $attrres->image;
        
        $moneyres=$this->getModel('User')->where(['wx_id'=>['=',$userid]])->fetchAll('user_id,user_name,money');
        
        if (! empty($moneyres)) {
            list ($moneyres) = $moneyres;
            $money = $moneyres->money;
            $user_name = $moneyres->user_name;
            $userid = $moneyres->user_id;
        }
        $is_self=$this->getModel('Order')->where(['user_id'=>['=',$userid],'ptcode'=>['=',$oid]])->fetchAll('count(*) as isset');
        $is_self = $is_self[0]->isset;
        
        $groupres=$this->getModel('GroupBuy')->where(['status'=>['=',$groupid]])->fetchAll('status,man_num,time_over,groupnum,productnum');
        if (! empty($groupres)) {
            list ($groupres) = $groupres;
        }
        
        $haveres=$this->getModel('Order')->where(['pid'=>['=',$groupid],'user_id'=>['=',$userid],'ptstatus'=>['=','1']])->fetchAll('count(*) as have');
        
        if (! empty($haveres)) {
            $have = $haveres[0]->have;
        }
        $attrres->have = $have;
        
        echo json_encode(array(
                                'is_add' => $arr['addemt'],'buymsg' => $arr['adds'],'proattr' => $attrres,'money' => $money,'user_name' => $user_name,'groupres' => $groupres,'isself' => $is_self
        ));
        exit();
    }

    public function creatgroup (Request $request)
    {
        
        $M=$this->getModel('Order');
        $uid = addslashes(trim($request->param('uid')));
        $form_id = addslashes(trim($request->param('fromid')));
        $pro_id = intval(trim($request->param('pro_id')));
        $man_num = intval(trim($request->param('man_num')));
        $time_over = addslashes(trim($request->param('time_over')));
        $sizeid = intval(trim($request->param('sizeid')));
        $groupid = addslashes(trim($request->param('groupid')));
        $pro_name = addslashes(trim($request->param('ptgoods_name')));
        $price = (float) (trim($request->param('price')));
        $y_price = (float) (trim($request->param('d_price')));
        $name = addslashes(trim($request->param('name')));
        $sheng = intval(trim($request->param('sheng')));
        $shi = intval(trim($request->param('shi')));
        $quyu = intval(trim($request->param('quyu')));
        $address = addslashes(trim($request->param('address')));
        $tel = addslashes(trim($request->param('tel')));
        $lack = intval(trim($request->param('lack')));
        $buy_num = intval(trim($request->param('num')));
        $paytype = addslashes(trim($request->param('paytype')));
        $trade_no = addslashes(trim($request->param('trade_no')));
        $status = intval(trim($request->param('status')));
        $ordstatus = $status == 1 ? 9 : 0;
        
        $M->starttrans();
        $group_num = 'KT' . substr(time(), 5) . mt_rand(10000, 99999);
        
        $creattime = date('Y-m-d H:i:s');
        
        $time_over = explode(':', $time_over);
        
        $time_over = date('Y-m-d H:i:s', $time_over[0] * 3600 + $time_over[1] * 60 + time());
        
        $pro_size=$this->getModel('Configure')->fetchWhere(['id'=>['=',$sizeid]],'name,color,size');
        
        $pro_size = $pro_size[0]->name . $pro_size[0]->color . $pro_size[0]->size;
        
        $res1=$this->getModel('GroupOpen')->insert(['uid'=>$uid,'ptgoods_id'=>$pro_id,'ptcode'=>$group_num,'ptnumber'=>1,'addtime'=>$creattime,'endtime'=>$time_over,'ptstatus'=>$status,'group_id'=>$groupid]);
        
        if ($res1 < 1) {
            $M->rollback();
            echo json_encode(array(
                                    'code' => 0,'sql' => $istsql1
            ));
            exit();
        }
        
        $user_id=$this->getModel('User')->fetchWhere(['wx_id'=>['=',$uid]],'user_id');
        
        $uid = $user_id[0]->user_id;
        
        $ordernum = 'PT' . mt_rand(10000, 99999) . date('Ymd') . substr(time(), 5);
        
        $res2=$this->getModel('Order')->insert(['user_id'=>$uid,'name'=>$name,'mobile'=>$tel,'num'=>$buy_num,'z_price'=>$price,'sNo'=>$ordernum,'sheng'=>$sheng,'shi'=>$shi,'xian'=>$quyu,'address'=>$address,'pay'=>$paytype,'add_time'=>$creattime,'status'=>$ordstatus,'otype'=>pt,'ptcode'=>$group_num,'pid'=>$groupid,'ptstatus'=>$status,'trade_no'=>$trade_no]);
        if ($res2 < 1) {
            $M->rollback();
            echo json_encode(array(
                                    'code' => 0,'sql' => $istsql2
            ));
            exit();
        }      
        $res3=$this->getModel('OrderDetails')->insert(['user_id'=>$uid,'p_id'=>$pro_id,'p_name'=>$pro_name,'p_price'=>$y_price,'num'=>$buy_num,'r_sNo'=>$ordernum,'add_time'=>$creattime,'r_status'=>-1,'size'=>$pro_size,'sid'=>$sizeid]);
        if ($res3 < 1) {
            $M->rollback();
            echo json_encode(array(
                                    'code' => 0,'sql' => $istsql3
            ));
            exit();
        }    
        $idres=$this->getModel('Order')->fetchWhere(['sNo'=>['=',$ordernum]],'id');
        
        if (! empty($idres))
            $idres = $idres[0]->id;
        if ($res1 > 0 && $res2 > 0 && $res3 > 0) {
            $M->commit();
            echo json_encode(array(
                                    'order' => $ordernum,'gcode' => $group_num,'group_num' => $group_num,'id' => $idres,'code' => 1
            ));
            exit();
        } else {
            $M->rollback();
            echo json_encode(array(
                                    'code' => 0
            ));
            exit();
        }
    }
    public function cangroup (Request $request)
    {      
        $oid = addslashes(trim($request->param('oid')));
        $groupid = addslashes(trim($request->param('groupid')));
        $gid = addslashes(trim($request->param('gid')));
        $user_id = addslashes(trim($request->param('userid')));
        
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        
        $groupmsg=$this->getModel('GroupOpen')->fetchWhere(['ptcode'=>['=',$oid]],'uid,ptgoods_id,endtime,ptstatus,ptnumber');
        // if(!empty($groupmsg)) $isself = $groupmsg[0] -> uid;
        
        $userid=$this->getModel('User')->fetchWhere(['wx_id'=>['=',$user_id]],'user_id');
        $userid = $userid[0]->user_id;
        $isrecd=$this->getModel('Order')->fetchWhere(['ptcode'=>['=',$oid],'pid'=>['=',$groupid],'user_id'=>['=',$userid]],'count(*) as recd');
        $recd = $isrecd[0]->recd;
        
        if ($recd > 0) {
            $res=$this->getModel('GroupOpen')->alias('k')
            ->join('order p','k.ptcode=p.ptcode','right')
            ->join('order_details d','p.sNo=d.r_sNo','left')          
            ->fetchWhere(['p.ptcode'=>['=',$oid],'p.user_id'=>['=',$userid]],'k.*,d.p_name,d.p_price,d.sid,k.ptgoods_id,k.ptnumber,k.addtime as cantime,k.endtime,k.ptstatus,p.name,p.num,p.sNo,p.sheng,p.shi,p.xian,p.address,p.mobile,p.status');
            
            if ($res) {
                // var_dump($res);
                $ptgoods_id = $res[0]->ptgoods_id;
                $aa=$this->getModel('GroupProduct')->fetchWhere(['group_id'=>['=',$groupid],'product_id'=>['=',$ptgoods_id]],'min(group_price) as gprice');
                $res = $res[0];
                $image=$this->getModel('Configure')->fetchWhere(['id'=>['=',$res->sid]],'img,yprice');
                if($image){
                $res->img = $img . $image[0]->img;
                $res->yprice = $image[0]->yprice;
                $res->p_price = $aa[0]->gprice;
                }
            } else {
                $res = (object) array();
            }
            $res->isSelf = true;
        } else {
            $res = $groupmsg[0];
            $goods=$this->getModel('GroupProduct')->alias('m')->join('product_list l','m.product_id=l.id','left')->join('configure c','m.attr_id=c.id','left')->fetchWhere(['group_id'=>['=',$groupid],'product_id'=>['=',$res->ptgoods_id]],'m.*,l.product_title as pro_name m.*,c.num,c.img as image,c.yprice min(group_price) as gprice,attr_id,product_id');
            $res->p_name = $goods[0]->pro_name;
            $res->p_price = $goods[0]->gprice;
            
            $res->yprice = $goods[0]->yprice;
            $res->img = $img . $goods[0]->image;
            $res->p_num = $goods[0]->num;
            $res->isSelf = false;
        }
        $groupmember=$this->getModel('Order')->alias('i')->join('user u','i.user_id=u.user_id','left')->where(['i.ptcode'=>['=',$oid],'i.pid'=>['=',$groupid]])->fetchOrder(['i.id'=>'asc'],'i.user_id,u.headimgurl');
        
        $man_num=$this->getModel('GroupBuy')->fetchWhere(['status'=>['=',$groupid]],'productnum');
        if (isset($man_num[0])) {
            $res->productnum = $man_num[0]->productnum;
            $res->groupmember = $groupmember;
            $sumres=$this->getModel('Order')->alias('o')->join('order_details d','o.sNo=d.r_sNo','left')->fetchWhere(['d.p_id'=>['=',$res->ptgoods_id]],'count(o.sNo) as sum');
            
            if (! empty($sumres))
                $res->sum = $sumres[0]->sum;
            switch ($res->ptstatus) {
                case 1:
                    $res->groupStatus = '拼团中';
                    break;
                case 2:
                    $res->groupStatus = '拼团成功';
                    break;
                case 3:
                    $res->groupStatus = '拼团失败';
                    break;
                default:
                    $res->groupStatus = '未付歀';
                    break;
            }
            
            $res->leftTime = strtotime($res->endtime) - time();
            
            $r_size=$this->getModel('GroupProduct')->alias('g')->join('configure p','g.attr_id=p.id','left')->fetchWhere(['g.product_id'=>['=',$gid],'group_id'=>['=',$groupid]],'g.attr_id,g.product_id,g.group_price as price,g.member_price,p.attribute,p.num,p.img,p.yprice,p.id');
            $skuBeanList = [];
            $attrList = [];
            if ($r_size) {
                
                $attrList = [];
                $a = 0;
                $attr = [];
                foreach ($r_size as $key => $value) {
                    $array_price[$key] = $value->price;
                    $array_yprice[$key] = $value->yprice;
                    $attribute = unserialize($value->attribute);
                    $attnum = 0;
                    $arrayName = [];
                    foreach ($attribute as $k => $v) {
                        if (! in_array($k, $arrayName)) {
                            array_push($arrayName, $k);
                            $kkk = $attnum ++;
                            $attrList[$kkk] = array(
                                                    'attrName' => $k,'attrType' => '1','id' => md5($k),'attr' => [],'all' => []
                            );
                        }
                    }
                }
                foreach ($r_size as $key => $value) {
                    $attribute = unserialize($value->attribute);
                    $attributes = [];
                    $name = '';
                    foreach ($attribute as $k => $v) {
                        $attributes[] = array(
                                            'attributeId' => md5($k),'attributeValId' => md5($v)
                        );
                        $name .= $v;
                    }
                    $cimgurl = $img . $value->img;
                    $skuBeanList[$key] = array(
                                            'name' => $name,'imgurl' => $cimgurl,'cid' => $value->id,'price' => $value->price,'count' => $value->num,'attributes' => $attributes
                    );
                    
                    for ($i = 0; $i < count($attrList); $i ++) {
                        $attr = $attrList[$i]['attr'];
                        $all = $attrList[$i]['all'];
                        foreach ($attribute as $k => $v) {
                            if ($attrList[$i]['attrName'] == $k) {
                                $attr_array = array(
                                                    'attributeId' => md5($k),'id' => md5($v),'attributeValue' => $v,'enable' => false,'select' => false
                                );
                                if (empty($attr)) {
                                    array_push($attr, $attr_array);
                                    array_push($all, $v);
                                } else {
                                    if (! in_array($v, $all)) {
                                        array_push($attr, $attr_array);
                                        array_push($all, $v);
                                    }
                                }
                            }
                        }
                        $attrList[$i]['all'] = $all;
                        $attrList[$i]['attr'] = $attr;
                    }
                }
            }
            
            $plugopen=$this->getModel('PlugIns')->where(['type'=>['=','0'],'software_id'=>['=','3'],'name'=>['like','%拼团%']])->fetchAll('status');
            $plugopen = ! empty($plugopen) ? $plugopen[0]->status : 0;
            
            echo json_encode(array(
                                    'groupmsg' => $res,'groupMember' => $groupmember,'skuBeanList' => $skuBeanList,'attrList' => $attrList,'isplug' => $plugopen
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'groupmsg' => 0,'groupMember' => 0,'skuBeanList' => 0,'attrList' => 0,'isplug' => 0
            ));
            exit();
        }
    }

    public function can_order (Request $request)
    {
        $M=$this->getModel('Order');
        $M->starttrans();
        
        $uid = addslashes(trim($request->param('uid')));
        $form_id = addslashes(trim($request->param('fromid')));
        $oid = addslashes(trim($request->param('oid')));
        $pro_id = intval(trim($request->param('pro_id')));
        $sizeid = intval(trim($request->param('sizeid')));
        $groupid = addslashes(trim($request->param('groupid')));
        $man_num = intval(trim($request->param('man_num')));
        $pro_name = addslashes(trim($request->param('ptgoods_name')));
        $price = (float) (trim($request->param('price')));
        $y_price = (float) (trim($request->param('d_price')));
        $name = addslashes(trim($request->param('name')));
        $sheng = intval(trim($request->param('sheng')));
        $shi = intval(trim($request->param('shi')));
        $quyu = intval(trim($request->param('quyu')));
        $address = addslashes(trim($request->param('address')));
        $tel = addslashes(trim($request->param('tel')));
        $lack = intval(trim($request->param('lack')));
        $buy_num = intval(trim($request->param('num')));
        $paytype = addslashes(trim($request->param('paytype')));
        $trade_no = addslashes(trim($request->param('trade_no')));
        $status = intval(trim($request->param('status')));
        $ordstatus = $status == 1 ? 9 : 0;
        
        $creattime = date('Y-m-d H:i:s');
        $pro_size=$this->getModel('Configure')->fetchWhere(['id'=>['=',$sizeid]],'name,color,size');
        $pro_size = $pro_size[0]->name . $pro_size[0]->color . $pro_size[0]->size;
        $selres=$this->getModel('GroupOpen')->where(['group_id'=>['=',$groupid],'ptcode'=>['=',$oid]])->fetchAll('ptnumber,ptstatus,endtime');
        if (! empty($selres)) {
            $ptnumber = $selres[0]->ptnumber;
            $ptstatus = $selres[0]->ptstatus;
            $endtime = strtotime($selres[0]->endtime);
        }
        $ordernum = 'PT' . mt_rand(10000, 99999) . date('Ymd') . substr(time(), 5);
        $user_id=$this->getModel('User')->fetchWhere(['wx_id'=>['=',$uid]],'user_id');
        $uid = $user_id[0]->user_id;
        
        if ($endtime >= time()) {
            if (($ptnumber + 1) < $man_num) {
                
                $res2=$this->getModel('Order')->insert(['user_id'=>$uid,'name'=>$name,'mobile'=>$tel,'num'=>$buy_num,'z_price'=>$price,'sNo'=>$ordernum,'sheng'=>$sheng,'shi'=>$shi,'xian'=>$quyu,'address'=>$address,'pay'=>$paytype,'add_time'=>$creattime,'otype'=>pt,'ptcode'=>$oid,'pid'=>$groupid,'ptstatus'=>$status,'status'=>$ordstatus,'trade_no'=>$trade_no]);
                if ($res2 < 1) {
                    $M->rollback();
                    echo json_encode(array(
                                            'code' => 3,'sql' => $istsql2
                    ));
                    exit();
                }
                
                $res3=$this->getModel('OrderDetails')->insert(['user_id'=>$uid,'p_id'=>$pro_id,'p_name'=>$pro_name,'p_price'=>$y_price,'num'=>$buy_num,'r_sNo'=>$ordernum,'add_time'=>$creattime,'r_status'=>-1,'size'=>$pro_size,'sid'=>$sizeid]);
                if ($res3 < 1) {
                    $M->rollback();
                    echo json_encode(array(
                                            'code' => 3,'sql' => $istsql3
                    ));
                    exit();
                }
                
                $updres=$this->getModel('GroupOpen')->where(['group_id'=>['=',$groupid],'ptcode'=>['=',$oid]])->inc('ptnumber',1)->update();
                if ($updres < 1) {
                    $M->rollback();
                    echo json_encode(array(
                                            'code' => 3,'sql' => $sql
                    ));
                    exit();
                }
                
                $M->commit();
                $idres=$this->getModel('Order')->fetchWhere(['sNo'=>['=',$ordernum]],'order');
                if (! empty($idres))
                    $idres = $idres[0]->id;
                echo json_encode(array(
                                        'order' => $ordernum,'gcode' => $oid,'group_num' => $oid,'ptnumber' => $ptnumber,'id' => $idres,'endtime' => $endtime,'code' => 1
                ));
                exit();
            } else if (($ptnumber + 1) === $man_num) {
                $res2=$this->getModel('Order')->insert(['user_id'=>$uid,'name'=>$name,'mobile'=>$tel,'num'=>$buy_num,'z_price'=>$price,'sNo'=>$ordernum,'sheng'=>$sheng,'shi'=>$shi,'xian'=>$quyu,'address'=>$address,'pay'=>$paytype,'add_time'=>$creattime,'otype'=>pt,'ptcode'=>$oid,'pid'=>$groupid,'ptstatus'=>$status,'status'=>$ordstatus,'trade_no'=>$trade_no]);
                
                if ($res2 < 1) {
                    $M->rollback();
                    echo json_encode(array(
                                            'code' => 3,'sql' => $istsql2
                    ));
                    exit();
                }
                
                $res3=$this->getModel('OrderDetails')->insert(['user_id'=>$uid,'p_id'=>$pro_id,'p_name'=>$pro_name,'p_price'=>$y_price,'num'=>$buy_num,'r_sNo'=>$ordernum,'add_time'=>$creattime,'r_status'=>-1,'size'=>$pro_size,'sid'=>$sizeid]);
                if ($res3 < 1) {
                    $M->rollback();
                    echo json_encode(array(
                                            'code' => 3,'sql' => $istsql3
                    ));
                    exit();
                }
                
                $updres=$this->getModel('GroupOpen')->where(['group_id'=>['=',$groupid],'ptcode'=>['=',$oid]])->inc('ptnumber',1)->update(['ptstatus'=>2]);
                
                if ($updres < 1) {
                    $M->rollback();
                    echo json_encode(array(
                                            'code' => 3,'sql' => $sql
                    ));
                    exit();
                }
                $updres=$this->getModel('Order')->saveAll(['ptstatus'=>2,'status'=>1],['pid'=>['=',$groupid],'ptcode'=>['=',$oid]]);
                if ($updres < 1) {
                    $M->rollback();
                    echo json_encode(array(
                                            'code' => 3,'sql' => "update lkt_order set ptstatus=2,status=1 where pid='$groupid' and ptcode='$oid'"
                    ));
                    exit();
                }
                $msgres=$this->getModel('Order')->alias('o')->join('order_details d','m.sNo=d.r_sNo','left')->join('user u','o.user_id=u.user_id','left')->fetchWhere(['o.pid'=>['=',$groupid],'o.ptcode'=>['=',$oid]],'o.*,d.p_name,d.p_price,d.num,d.sid,u.wx_id as uid');
                
                foreach ($msgres as $k => $v) {
                    $updres=$this->getModel('Configure')->where(['id'=>['=',$v->sid]])->des('set',$v->num)->update();
                    if ($updres < 1) {
                        $M->rollback();
                        echo json_encode(array(
                                                'code' => 3,'sql' => "update lkt_configure set num=num-$v->num where id=$v->sid"
                        ));
                        exit();
                    }
                    $fromidres=$this->getModel('UserFromid')->where(['open_id'=>['=',$v->uid],'id'=>['=','(select']])->fetchAll('fromid,open_id');
                    foreach ($fromidres as $ke => $val) {
                        if ($val->open_id == $v->uid) {
                            $msgres[$k]->fromid = $val->fromid;
                        }
                    }
                }
                if ($res2 > 0 && $res3 > 0) {
                    $r=$this->getModel('Notice')->where(['id'=>['=','1']])->fetchAll('*');
                    $template_id = $r[0]->group_success;
                    
                    $this->Send_success($msgres, date('Y-m-d H:i:s', time()), $template_id, $pro_name);
                    $M->commit();
                    echo json_encode(array(
                                            'order' => $msgres,'gcode' => $oid,'code' => 2
                    ));
                    exit();
                }
            } else if ($ptnumber == $man_num) {
                $updres=$this->getModel('User')->where(['user_id'=>['=',$uid]])->inc('money',$price)->update();
                if ($updres < 1) {
                    $M->rollback();
                    echo json_encode(array(
                                            'code' => 3,'sql' => "update lkt_user set money=money+$price where user_id='$uid'"
                    ));
                    exit();
                }
                $M->commit();
                echo json_encode(array(
                                        'code' => 3
                ));
                exit();
            } else {}
        } else {
            $updres=$this->getModel('User')->where(['user_id'=>['=',$uid]])->inc('money',$price)->update();
            if ($updres < 1) {
                $M->rollback();
                echo json_encode(array(
                                        'code' => 3,'sql' => "update lkt_user set money=money+$price where user_id='$uid'"
                ));
                exit();
            }
            $M->commit();
            echo json_encode(array(
                                    'code' => 4
            ));
            exit();
        }
    }

    public function isgrouppacked (Request $request)
    {
        
        
        $oid = addslashes(trim($request->param('oid')));
        $selres=$this->getModel('GroupOpen')->where(['ptcode'=>['=',$oid]])->fetchAll('ptnumber');
        if ($selres) {
            $hasnum = $selres[0]->ptnumber;
            echo json_encode(array(
                                    'hasnum' => $hasnum
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'hasnum' => 0
            ));
            exit();
        }
    }

    public function ordermember (Request $request)
    {
        
        
        $uid = addslashes(trim($request->param('uid')));
        $page = intval(trim($request->param('page')));
        $cid = intval(trim($request->param('cid')));
        
        $pagesize = 5;
        $msgsta = $page * $pagesize;
        
        $condition = '';
        if ($cid > 0) {
            $condition .= ' and ptstatus=' . $cid;
        }
        $gmsg=$this->getModel('GroupBuy')->fetchWhere(['is_show'=>['=','1']],'group_buy');
        if (! empty($gmsg)) {
            $man_num = $gmsg[0]->man_num;
            $groupid = $gmsg[0]->status;
        }
        $res=$this->getModel('GroupInfo')->alias('i')->join('configure c','i.ptgoods_norm_id=c.id','left')->fetchWhere($condition,'i.pro_id,i.ptcode,i.ptordercode,i.ptrefundcode,i.ptgoods_name as name,i.ptstatus,i.per_price as totalPrice,i.orderstatus,c.img',"$msgsta,$pagesize");
        
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        
        foreach ($res as $k => $v) {
            $res[$k]->groupNum = $man_num;
            $res[$k]->img = $img . $v->img;
            $ressum=$this->getModel('GroupInfo')->where(['pid'=>['=',$groupid],'orderstatus'=>['>','0'],'pro_id'=>['=',$v->pro_id]])->fetchGroup('pro_id','count(pid) as sum');
            if (! empty($ressum))
                $res[$k]->sum = $ressum[0]->sum;
            switch ($v->orderstatus) {
                case 0:
                    $res[$k]->groupStatus = '未付歀';
                    break;
                case 1:
                    $res[$k]->groupStatus = '待成团';
                    break;
                case 2:
                    $res[$k]->groupStatus = '拼团成功-未发货';
                    break;
                case 3:
                    $res[$k]->groupStatus = '拼团成功-已发货';
                    break;
                case 4:
                    $res[$k]->groupStatus = '拼团成功-已签收';
                    break;
                case 5:
                    $res[$k]->groupStatus = '拼团失败-未退款';
                    break;
                default:
                    $res[$k]->groupStatus = '已退款';
                    break;
            }
        }
        
        echo json_encode(array(
                                'groupmsg' => $res,'groupid' => $groupid
        ));
        exit();
    }

    public function orderdetail (Request $request)
    {
        
        
        $oid = addslashes(trim($request->param('id')));
        $groupid = addslashes(trim($request->param('groupid')));
        // $oid = 'PT477202018052532946';
        // $groupid = '28548';
        $res=$this->getModel('Order')->alias('o')->join('configure c','m.sid=c.id','left')->join('order_details d','o.sNo=d.r_sNo','left')->fetchWhere(['o.pid'=>['=',$groupid],'o.sNo'=>['=',$oid]],'o.*,c.img o.user_id,o.ptcode,o.sNo,o.ptstatus,o.z_price,o.name,o.add_time,o.sheng,o.shi,o.xian,o.address,o.mobile,o.status,o.num,d.p_name,d.p_price,d.deliver_time,d.arrive_time,d.sid,d.express_id,d.courier_num');
        
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        $address = array();
        if (! empty($res)) {
            $res = $res[0];
            $address['username'] = $res->name;
            $address['tel'] = $res->mobile;
            $address['province'] = $res->sheng;
            $address['city'] = $res->shi;
            $address['county'] = $res->xian;
            $address['address'] = $res->address;
            $res->address = $address;
            if ($res->express_id > 0) {
                $express=$this->getModel('Express')->fetchWhere(['id'=>['=',$res->express_id]],'kuaidi_name');
                $express = $express[0]->kuaidi_name;
            } else {
                $express = '';
            }
            $res->express = $express;
            $res->img = $img . $res->img;
            
            $goodsattr=$this->getModel('Configure')->fetchWhere(['id'=>['=',$res->sid]],'name,color,size');
            $goodsattr = $goodsattr[0];
            $guige = array();
            $guige['pname'] = '规格';
            $guige['name'] = $goodsattr->name;
            $color = array();
            $color['pname'] = '颜色';
            $color['name'] = $goodsattr->color;
            $size = array();
            $size['pname'] = '尺寸';
            $size['name'] = $goodsattr->size;
            $prop = array();
            $prop[] = $guige;
            $prop[] = $color;
            $prop[] = $size;
            $res->goodsprop = $prop;
            switch ($res->status) {
                case 0:
                    $res->orderStatus = '待付歀';
                    break;
                case 9:
                    $res->orderStatus = '待成团';
                    break;
                case 1:
                    $res->orderStatus = '待发货';
                    break;
                case 2:
                    $res->orderStatus = '待收货';
                    break;
                case 3:
                    $res->orderStatus = '已完成';
                    break;
                case 10:
                    $res->orderStatus = '拼团失败';
                    break;
                case 11:
                    $res->orderStatus = '已退款';
                    break;
            }
        }
        
        echo json_encode($res);
        exit();
    }

    public function confirmreceipt (Request $request)
    {
        
        
        $oid = addslashes(trim($request->param('oid')));
        $groupid = addslashes(trim($request->param('groupid')));
        $updres=$this->getModel('Order')->saveAll(['status'=>3],['sNo'=>['=',$oid],'pid'=>['=',$groupid]]);
        
        if ($updres > 0) {
            echo json_encode(array(
                                    'code' => 1
            ));
            exit();
        }
    }

    function httpsRequest($url, $data = null)
    {
        // 1.初始化会话
        $ch = curl_init();
        // 2.设置参数: url + header + 选项
        // 设置请求的url
        curl_setopt($ch, CURLOPT_URL, $url);
        // 保证返回成功的结果是服务器的结果
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (! empty($data)) {
            // 发送post请求
            curl_setopt($ch, CURLOPT_POST, 1);
            // 设置发送post请求参数数据
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        // 3.执行会话; $result是微信服务器返回的JSON字符串
        $result = curl_exec($ch);
        // 4.关闭会话
        curl_close($ch);
        return $result;
    }

    public function Send_open (Request $request)
    {
        
        
        $openid = trim($request->param('user_id')); // --
        $form_id = trim($request->param('form_id')); // --
        $page = trim($request->param('page')); // --
                                                      // $oid = trim($request->param('oid'));
        $f_price = trim($request->param('price'));
        $f_price = $f_price . '元';
        $f_sNo = trim($request->param('order_sn'));
        $f_pname = trim($request->param('f_pname'));
        $opentime = date('Y-m-d H:i:s', time());
        $endtime = trim($request->param('endtime'));
        $sum = trim($request->param('sum'));
        $sum = $sum . '元';
        $member = trim($request->param('member'));
        $endtime = explode(':', $endtime);
        $endtime = $endtime[0] . '小时' . $endtime[1] . '分钟';
        
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid; // 小程序唯一标识
            $appsecret = $r[0]->appsecret; // 小程序的 app secret
            
            $opentime = array(
                            'value' => $opentime,"color" => "#173177"
            );
            $f_pname = array(
                            'value' => $f_pname,"color" => "#173177"
            );
            $f_sNo = array(
                        'value' => $f_sNo,"color" => "#173177"
            );
            $f_price = array(
                            'value' => $f_price,"color" => "#173177"
            );
            $endtime = array(
                            'value' => $endtime,"color" => "#173177"
            );
            $sum = array(
                        'value' => $sum,"color" => "#173177"
            );
            $member = array(
                            'value' => $member,"color" => "#173177"
            );
            $tishi = array(
                        'value' => '您可以邀请您的好友一起来拼团，邀请的人越多，成功的几率越高哦!',"color" => "#FF4500"
            );
            $o_data = array(
                            'keyword1' => $member,'keyword2' => $opentime,'keyword3' => $endtime,'keyword4' => $f_price,'keyword5' => $sum,'keyword6' => $f_sNo,'keyword7' => $f_pname,'keyword8' => array(
                                                                                                                                                                                                        'value' => '已开团-待成团',"color" => "#FF4500"
                            ),'keyword9' => $tishi
            );
            
            $AccessToken = $this->getAccessToken($appid, $appsecret);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $AccessToken;
            
            $r=$this->getModel('Notice')->where(['id'=>['=','1']])->fetchAll('*');
            $template_id = $r[0]->group_pay_success;
            
            $data = json_encode(array(
                                    'access_token' => $AccessToken,'touser' => $openid,'template_id' => $template_id,'form_id' => $form_id,'page' => $page,'data' => $o_data
            ));
            
            $da = $this->httpsRequest($url, $data);
            
            echo json_encode($da);
            
            exit();
        }
    }

    public function Send_can (Request $request)
    {
        
        
        $openid = trim($request->param('user_id')); // --
        $form_id = trim($request->param('form_id')); // --
        $page = trim($request->param('page')); // --
        $f_price = trim($request->param('price'));
        $f_price = $f_price . '元';
        $f_sNo = trim($request->param('order_sn'));
        $f_pname = trim($request->param('f_pname'));
        $opentime = date('Y-m-d H:i:s', time());
        $endtime = intval($request->param('endtime')) - time();
        $sum = trim($request->param('sum'));
        $sum = $sum . '元';
        $man_num = trim($request->param('man_num'));
        $hours = ceil($endtime / 3600);
        $minute = ceil(($endtime % 3600) / 60);
        $endtime = $hours . '小时' . $minute . '分钟';
        
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid; // 小程序唯一标识
            $appsecret = $r[0]->appsecret; // 小程序的 app secret
            
            $opentime = array(
                            'value' => $opentime,"color" => "#173177"
            );
            $f_pname = array(
                            'value' => $f_pname,"color" => "#173177"
            );
            $f_sNo = array(
                        'value' => $f_sNo,"color" => "#173177"
            );
            $f_price = array(
                            'value' => $f_price,"color" => "#173177"
            );
            $endtime = array(
                            'value' => $endtime,"color" => "#173177"
            );
            $sum = array(
                        'value' => $sum,"color" => "#173177"
            );
            $man_num = array(
                            'value' => $man_num,"color" => "#173177"
            );
            
            $o_data = array(
                            'keyword1' => $f_pname,'keyword2' => $f_price,'keyword3' => $sum,'keyword4' => $endtime,'keyword5' => array(
                                                                                                                                        'value' => '待成团',"color" => "#FF4500"
                            ),'keyword6' => $opentime,'keyword7' => $man_num,'keyword8' => $f_sNo
            );
            
            $AccessToken = $this->getAccessToken($appid, $appsecret);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $AccessToken;
            $r=$this->getModel('Notice')->where(['id'=>['=','1']])->fetchAll('*');
            $template_id = $r[0]->group_pending;
            $data = json_encode(array(
                                    'access_token' => $AccessToken,'touser' => $openid,'template_id' => $template_id,'form_id' => $form_id,'page' => $page,'data' => $o_data
            ));
            
            $da = $this->httpsRequest($url, $data);
            
            echo json_encode($da);
            
            exit();
        }
    }

    public function Send_success($arr, $endtime, $template_id, $pro_name)
    {
        
        
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid; // 小程序唯一标识
            $appsecret = $r[0]->appsecret; // 小程序的 app secret
            
            $AccessToken = $this->getAccessToken($appid, $appsecret);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $AccessToken;
        }
        foreach ($arr as $k => $v) {
            $data = array();
            $data['access_token'] = $AccessToken;
            $data['touser'] = $v->uid;
            $data['template_id'] = $template_id;
            $data['form_id'] = $v->fromid;
            $data['page'] = "pages/order/detail?orderId=$v->id";
            $z_price = $v->z_price . '元';
            $p_price = $v->p_price . '元';
            $minidata = array(
                            'keyword1' => array(
                                                'value' => $pro_name,'color' => "#173177"
                            ),'keyword2' => array(
                                                'value' => $z_price,'color' => "#173177"
                            ),'keyword3' => array(
                                                'value' => $v->sNo,'color' => "#173177"
                            ),'keyword4' => array(
                                                'value' => '拼团成功','color' => "#FF4500"
                            ),'keyword5' => array(
                                                'value' => $p_price,'color' => "#FF4500"
                            ),'keyword6' => array(
                                                'value' => $endtime,'color' => "#173177"
                            )
            );
            $data['data'] = $minidata;
            
            $data = json_encode($data);
            $da = $this->httpsRequest($url, $data);
            $delete_rs=$this->getModel('UserFromid')->delete($v->fromid,'fromid');
        }
    }

    function getAccessToken($appID, $appSerect)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appID . "&secret=" . $appSerect;
        // 时效性7200秒实现
        // 1.当前时间戳
        $currentTime = time();
        // 2.修改文件时间
        $fileName = "accessToken"; // 文件名
        if (is_file($fileName)) {
            $modifyTime = filemtime($fileName);
            if (($currentTime - $modifyTime) < 7200) {
                // 可用, 直接读取文件的内容
                $accessToken = file_get_contents($fileName);
                return $accessToken;
            }
        }
        // 重新发送请求
        $result = $this->httpsRequest($url);
        $jsonArray = json_decode($result, true);
        // 写入文件
        $accessToken = $jsonArray['access_token'];
        file_put_contents($fileName, $accessToken);
        return $accessToken;
    }

    private function wxrefundapi($ordersNo, $refund, $price)
    {
        
        // 通过微信api进行退款流程
        $parma = array(
                    
                    'appid' => 'wx9d12fe23eb053c4f',
                    'mch_id' => '1499256602',
                    'nonce_str' => $this->createNoncestr(),
                    'out_refund_no' => $refund,
                    'out_trade_no' => $ordersNo,
                    'total_fee' => $price,
                    'refund_fee' => $price,
                    'op_user_id' => '1499256602'
        
        );
        
        $parma['sign'] = $this->getSign($parma);
        
        $xmldata = $this->arrayToXml($parma);
        
        $xmlresult = $this->postXmlSSLCurl($xmldata, 'https://api.mch.weixin.qq.com/secapi/pay/refund');
        
        $result = $this->xmlToArray($xmlresult);
        
        return $result;
    }

    protected function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        
        $str = "";
        
        for ($i = 0; $i < $length; $i ++) {
            
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        
        return $str;
    }

    protected function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            
            $Parameters[$k] = $v;
        }
        
        // 签名步骤一：按字典序排序参数
        
        ksort($Parameters);
        
        $String = $this->formatBizQueryParaMap($Parameters, false);
        
        // 签名步骤二：在string后加入KEY
        
        $String = $String . "&key=td153g1d2f321g23ggrd123g12fd1g22";
        
        // 签名步骤三：MD5加密
        
        $String = md5($String);
        
        // 签名步骤四：所有字符转为大写
        
        $result_ = strtoupper($String);
        
        return $result_;
    }

    protected function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        
        ksort($paraMap);
        
        foreach ($paraMap as $k => $v) {
            
            if ($urlencode) {
                
                $v = urlencode($v);
            }
            
            // $buff .= strtolower($k) . "=" . $v . "&";
            
            $buff .= $k . "=" . $v . "&";
        }
        
        $reqPar='';
        
        if (strlen($buff) > 0) {
            
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        
        return $reqPar;
    }

    protected function arrayToXml($arr)
    {
        $xml = "<xml>";
        
        foreach ($arr as $key => $val) 
        {
            
            if (is_numeric($val)) {
                
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        
        $xml .= "</xml>";
        
        return $xml;
    }

    protected function xmlToArray($xml)
    {
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        
        return $array_data;
    }

    private function postXmlSSLCurl($xml, $url, $second = 30)
    {
        $ch = curl_init();
        
        // 超时时间
        
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        
        // 这里设置代理，如果有的话
        
        // curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        
        // curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        
        curl_setopt($ch, CURLOPT_URL, $url);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        
        // 设置header
        
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        
        // 要求结果为字符串且输出到屏幕上
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        
        // 设置证书
        
        // 使用证书：cert 与 key 分别属于两个.pem文件
        
        // 默认格式为PEM，可以注释
        
        $cert = str_replace('lib', 'filter', MO_LIB_DIR) . '/apiclient_cert.pem';
        
        $key = str_replace('lib', 'filter', MO_LIB_DIR) . '/apiclient_key.pem';
        
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        
        // 默认格式为PEM，可以注释
        
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
        
        // post提交方式
        
        curl_setopt($ch, CURLOPT_POST, true);
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        
        $data = curl_exec($ch);
        
        // 返回结果
        
        if ($data) {
            
            curl_close($ch);
            
            return $data;
        } 
        else {
            
            $error = curl_errno($ch);
            
            echo "curl出错，错误码:$error" . "<br>";
            
            curl_close($ch);
            
            return false;
        }
    }

    public function up_out_trade_no (Request $request)
    {
             
        $pagefrom = trim($request->param('pagefrom'));
        $uid = addslashes(trim($request->param('uid')));
        $oid = addslashes(trim($request->param('oid')));
        $form_id = addslashes(trim($request->param('fromid')));
        $pro_id = intval(trim($request->param('pro_id')));
        $man_num = intval(trim($request->param('man_num')));
        $sizeid = intval(trim($request->param('sizeid')));
        $groupid = addslashes(trim($request->param('groupid')));
        $pro_name = addslashes(trim($request->param('ptgoods_name')));
        $price = (float) (trim($request->param('price')));
        $y_price = (float) (trim($request->param('d_price')));
        $name = addslashes(trim($request->param('name')));
        $sheng = intval(trim($request->param('sheng')));
        $shi = intval(trim($request->param('shi')));
        $quyu = intval(trim($request->param('quyu')));
        $address = addslashes(trim($request->param('address')));
        $tel = addslashes(trim($request->param('tel')));
        $lack = intval(trim($request->param('lack')));
        $buy_num = intval(trim($request->param('num')));
        $paytype = addslashes(trim($request->param('paytype')));
        $trade_no = addslashes(trim($request->param('trade_no')));
        $status = intval(trim($request->param('status')));
        $time_over = addslashes(trim($request->param('time_over')));
        $ordstatus = $status == 1 ? 9 : 0;
        
        if ($pagefrom == 'kaituan') {
            $array = array(
                        'uid' => $uid,'form_id' => $form_id,'oid' => $oid,'pro_id' => $pro_id,'sizeid' => $sizeid,'groupid' => $groupid,'man_num' => $man_num,'pro_name' => $pro_name,'price' => $price,'y_price' => $y_price,'name' => $name,'sheng' => $sheng,'shi' => $shi,'quyu' => $quyu,'address' => $address,'tel' => $tel,'lack' => $lack,'buy_num' => $buy_num,'paytype' => $paytype,'trade_no' => $trade_no,'status' => $status,'ordstatus' => $ordstatus,'time_over' => $time_over,'pagefrom' => $pagefrom
            );
        } else {
            $array = array(
                        'uid' => $uid,'form_id' => $form_id,'oid' => $oid,'pro_id' => $pro_id,'sizeid' => $sizeid,'groupid' => $groupid,'man_num' => $man_num,'pro_name' => $pro_name,'price' => $price,'y_price' => $y_price,'name' => $name,'sheng' => $sheng,'shi' => $shi,'quyu' => $quyu,'address' => $address,'tel' => $tel,'lack' => $lack,'buy_num' => $buy_num,'paytype' => $paytype,'trade_no' => $trade_no,'status' => $status,'ordstatus' => $ordstatus,'pagefrom' => $pagefrom
            );
        }
        
        $data = serialize($array);
        
        $rid=$this->getModel('OrderData')->insert(['trade_no'=>$trade_no,'data'=>$data,'addtime'=>nowDate()]);
        
        $yesterday = date("Y-m-d", strtotime("-1 day"));
        $delete_rs=$this->getModel('OrderData')->delete($yesterday,'addtime');
        
        echo json_encode(array(
                                'data' => $array
        ));
        exit();
    }

    public function verification (Request $request)
    {
        
        
        $trade_no = addslashes(trim($request->param('trade_no')));
        $gmsg=$this->getModel('Order')->fetchWhere(['trade_no'=>['=',$trade_no]],'id,sNo,ptcode');
        
        if ($gmsg) {
            echo json_encode(array(
                                    'status' => 1,'data' => $gmsg[0]
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0
            ));
            exit();
        }
    }

}