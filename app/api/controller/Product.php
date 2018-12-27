<?php
namespace app\api\controller;
use core\Request;

class Product extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function product(Request $request)
    {
       $request->method()=='post'&&$this->do_product($request);

        $this->execute();
    }

    
    private function do_product($request)
    {
            
        $m = addslashes(trim($request->param('m')));
        if($m == 'index'){
            $this->index();
        }else if($m == 'add_cart'){
            $this->add_cart();
        }else if($m == 'listdetail'){
            $this->listdetail();
        }else if($m == 'get_more'){
            $this->get_more();
        }else if($m == 'Settlement'){ // 结算页面
            $this->Settlement();
        }else if($m == 'Shopping'){
            $this->Shopping();
        }else if($m == 'delcart'){
            $this->delcart();
        }else if($m == 'delAll_cart'){
            $this->delAll_cart();
        }else if($m == 'to_Collection'){
            $this->to_Collection();
        }else if($m == 'up_cart'){
            $this->up_cart();
        }else if($m == 'payment'){ // 生产订单
            $this->payment();
        }else if($m == 'up_order'){
            $this->up_order();
        }else if($m == 'comment'){
            $this->comment();
        }else if($m == 't_comment'){
            $this->t_comment();
        }else if($m == 'new_product'){
            $this->new_product();
        }else if($m == 'wallet_pay'){
            $this->wallet_pay();
        }else if($m == 'choujiangjiesuan'){//抽奖创建结算页面
            $this->choujiangjiesuan();
        }else if ($m == 'choujiangpayment') {//抽奖创建订单
              $this->choujiangpayment();
        }else if ($m == 'select_size') {
               //属性选择
              $this->select_size();
        }else if ($m == 'save_formid') {
              //普通商品储存from_id 用于发货 退款等操作信息推送
              $this->save_formid();
        }
        return;
    }
    
    public function index (Request $request){
        
        // 获取产品id
        $id = trim($request->param('pro_id'));
        $openid = trim($request->param('openid'));

        // 根据微信id,查询用户id
        // 获取类别值id，用于区分是抽奖和其他
        $type1 = $request->param('type1');
        $choujiangid = $request->param('choujiangid');
        $wx_id = $request->param('wx_id');

        $t_user_id = $request->param('userid');

        if($type1 == 1){
            $re=$this->getModel('Draw')->where(['id'=>['=',$choujiangid]])->fetchAll('*');
            if($re){
                $price01 = $re[0]->price;
                $type01 =$re[0]->type;
                $start_time = $re[0]->start_time;//活动开始时间
                $end_time =$re[0]->end_time;//活动结束时间
            }else{
                $price01 = '';
                $type01 ='';
                $start_time = '';//活动开始时间
                $end_time ='';//活动结束时间
            }

            $time = date('Y-m-d H:i:s',time());//当前时间
            if($start_time<$time && $end_time < $time){//判断用户进入抽奖详情页面抽奖活动是否结束
                echo json_encode(array('status'=>3,'err'=>'活动已结束!'));
                exit;
            }
        }else{
            $price01 ='';
            $type01 ='';
        }

        $r_1=$this->getConfig();
        if($r_1){
            $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
            $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
            if(strpos($uploadImg,'../') === false){ // 判断字符串是否存在 ../
                $img = $uploadImg_domain . $uploadImg; // 图片路径
            }else{
                // 不存在
                $img = $uploadImg_domain . substr($uploadImg,2); // 图片路径
            }
        }else{
            $img = '';
        }

        $type = 0;
        $collection_id = '';
        $zhekou = '';

        if($openid){
            $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('*');
            if($r){
                $user_id = $r[0]->user_id;
                // 根据用户id、产品id,获取收藏表信息
                $rr=$this->getModel('UserCollection')->where(['user_id'=>['=',$user_id],'p_id'=>['=',$id]])->fetchAll('*');
                if($rr){
                    $type = 1;
                    $collection_id = $rr['0']->id;
                }else{
                    $type = 0;
                    $collection_id = '';
                }
                $time = date("Y-m-d");
                // 根据用户id,在足迹表里插入一条数据
                $rr_collection=$this->getModel('UserFootprint')->where(['user_id'=>['=',$user_id],'p_id'=>['=',$id],'add_time'=>['like',"$time%"]])->fetchAll('*');

                if(empty($rr_collection)){
                    $rrr=$this->getModel('UserFootprint')->insert(['user_id'=>$user_id,'p_id'=>$id,'add_time'=>nowDate()]);
                }
            }
        }
        // 根据产品id,查询产品数据
        $res=$this->getModel('ProductList')->alias('a')->join('configure c','a.id=c.pid','LEFT')->fetchWhere(['a.id'=>['=',$id],'a.status'=>['=','0']],'a.*,c.price,c.yprice,c.attribute,c.img');
        if(!$res){
            echo json_encode(array('status'=>0,'err'=>'该商品已下架！'));
            exit();
        }else{
            $img_arr =  [];
            $r=$this->getModel('ProductImg')->where(['product_id'=>['=',$id]])->fetchAll('product_url,id');
            if($r){
                foreach ($r as $key => $value) {
                    $img_arr[$key] = $img.$value->product_url;
                }
            }else{
                $img_arr['0'] = $img.$res['0']->imgurl;
            }
            $class =  $res['0'] -> product_class;
            $typestr=trim($class,'-');
            $typeArr=explode('-',$typestr);
            //  取数组最后一个元素 并查询分类名称
            $cid = end($typeArr);
            $r_p=$this->getModel('ProductClass')->where(['cid'=>['=',$cid]])->fetchAll('pname');
            $pname = '自营';
            if($r_p){
                $pname = $r_p['0']->pname;
            }
            $product = [];
            $imgurl = $img.$res['0']->img;
            $content = $res['0']->content;

            $newa = substr($uploadImg_domain,0,strrpos($uploadImg_domain,'/'));
            if($newa == 'http:/' || $newa == 'https:/' ){
                $newa = $uploadImg_domain;
            }
            $new_content = preg_replace('/(<img.+?src= ")(.*?)/',"$1$newa$2", $content);

            $freight_id = $res[0]->freight;
            $r_freight=$this->getModel('Freight')->where(['id'=>['=',$freight_id]])->fetchAll('*');
            if($r_freight){
                $freight = unserialize($r_freight[0]->freight); // 属性
                foreach ($freight as $k => $v){
                    foreach ($v as $k1 => $v1){
                        $freight_list[$k]['freight'] = $v['two'];
                        $freight_list[$k]['freight_name'] = $v['name'];
                    }
                }
                $product['freight'] = $freight[0]['two'];
            }else{
                $product['freight'] = 0.00;
            }
            $s_type = explode(',',$res['0']->s_type);
            $xp = 0;
            $rexiao = 0;
            $tuijian = 0;
            foreach ($s_type as $k1 => $v1){
                if($v1 == 1){
                    $xp = 1;
                }else if($v1 == 2){
                    $rexiao = 1;
                }else if($v1 == 3){
                    $tuijian = 1;
                }
            }
            $product['xp'] = $xp;
            $product['rexiao'] = $rexiao;
            $product['tuijian'] = $tuijian;

            $product['id'] = $res['0']->id;
            $product['shop_id'] = $res['0']->id;
            $product['name'] = $res['0']->product_title;
            $product['intro'] = $res['0']->product_title;
            $product['num'] = $res['0']->num;
            $product['price'] = $res['0']->yprice;
            $product['price_yh'] = $res['0']->price;
            $product['price11'] =$price01 ? $price01:'';
            $product['type01'] =$type01 ? $type01:'';
            $product['photo_x'] = $imgurl;
            $product['photo_d'] =$res['0']->img;
            $product['content'] = $new_content;
            $product['pro_number'] = $res['0']->id;
            $product['company'] = '件';
            $product['cat_name'] = $pname;
            $product['brand'] = '来客推';
            $product['img_arr'] = $img_arr;
            $product['choujiangid'] = $choujiangid? '':$choujiangid;
            $product['volume'] = $res['0']->volume;
            $product['is_zhekou'] = $res['0']->is_zhekou;
            if($type1 == 1){
                $product['type111'] = 1;
            }else{
                $product['type111'] = 2;
                $wx_id ='';
            }

            if(!empty($res[0]->brand_id)){
                $b_id =$res[0]->brand_id;
                $r01=$this->getModel('BrandClass')->where(['brand_id'=>['=',$b_id]])->fetchAll('brand_name');
            }
            if(!empty($r01)){
                 $product['brand_name'] = $r01[0]->brand_name;
            }else{
                 $product['brand_name'] = '无';
            }

            $r_c=$this->getModel('Comments')->alias('a')->join('user m','a.uid=m.user_id','LEFT')->fetchWhere(['a.pid'=>['=',$id]],'a.id,a.add_time,a.content,a.CommentType,a.size,m.user_name,m.headimgurl');
            $arr=[];
            if($r_c){
                foreach ($r_c as $key => $value) {
                    $va = (array)$value;
                    $va['time'] = substr($va['add_time'],0,10);
                    //-------------2018-05-03  修改  作用:返回评论图片
                    $comments_id = $va['id'];
                    $comment_res=$this->getModel('CommentsImg')->where(['comments_id'=>['=',$comments_id]])->fetchAll('comments_url');
                    $va['images'] ='';
                    if($comment_res){
                        $va['images'] = $comment_res;
                        $array_c = [];
                        foreach ($comment_res as $kc => $vc) {
                            $url = $vc->comments_url;
                            $array_c[$kc] = array('url' =>$img.$url);
                        }
                        $va['images'] = $array_c;
                    }
                    //-------------2018-07-27  修改
                    $ad_res=$this->getModel('ReplyComments')->where(['cid'=>['=',$comments_id],'uid'=>['=','admin']])->fetchAll('content');
                    if($ad_res){
                        $reply_admin = $ad_res[0]->content;
                    }else{
                        $reply_admin = '';
                    }

                    $va['reply'] = $reply_admin;

                    $obj = (object)$va;
                    $arr[$key] = $obj;
                }
            }

            $commodityAttr = [];
            $r_size=$this->getModel('Configure')->where(['pid'=>['=',$id]])->fetchAll('*');
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
                        if(!in_array($k, $arrayName)){
                            array_push($arrayName, $k);
                            $kkk = $attnum++;
                            $attrList[$kkk] = array('attrName' => $k,'attrType' => '1','id' => md5($k),'attr' => [],'all'=>[]);
                        }
                    }
                }

                foreach ($r_size as $key => $value) {
                    $attribute = unserialize($value->attribute);
                    $attributes = [];
                    $name = '';
                    foreach ($attribute as $k => $v) {
                       $attributes[] = array('attributeId' => md5($k), 'attributeValId' => md5($v));
                       $name .= $v;
                    }

                    $cimgurl = $img.$value->img;

                    $skuBeanList[$key] = array('name' => $name,'imgurl' => $cimgurl,'cid' => $value->id,'price' => $value->price,'count' => $value->num,'attributes' => $attributes);
                    

                    for ($i=0; $i < count($attrList); $i++) {
                        $attr = $attrList[$i]['attr'];
                        $all = $attrList[$i]['all'];
                        foreach ($attribute as $k => $v) {
                            if($attrList[$i]['attrName'] == $k){
                                $attr_array = array('attributeId' => md5($k), 'id' =>md5($v), 'attributeValue' => $v, 'enable' => false, 'select' => false);

                                if(empty($attr)){
                                    array_push($attr, $attr_array);
                                    array_push($all, $v);
                                }else{
                                    if(!in_array($v, $all)){
                                        array_push($attr, $attr_array);
                                        array_push($all, $v);
                                    }
                                }
                            }
                        }
                        $attrList[$i]['all'] =$all;
                        $attrList[$i]['attr'] =$attr;
                    }
                }
            }
            //排序
            asort($array_price);
            asort($array_yprice);
            //获取价格区间并返回
            $qj_price = reset($array_price)==end($array_price)? reset($array_price):reset($array_price).'-'.end($array_price);
            $qj_yprice = reset($array_yprice)==end($array_yprice)? reset($array_yprice):reset($array_yprice).'-'.end($array_yprice);
            //返回JSON             $skuBeanList = []; $attrList = [];
            $share = array('friends' => true, 'friend' => true);
            echo json_encode(array('status'=>1,'pro'=>$product,'qj_price'=>$qj_price,'qj_yprice'=>$qj_yprice,'attrList'=>$attrList,'skuBeanList'=>$skuBeanList,'collection_id'=>$collection_id,'comments'=>$arr,'type'=>$type,'wx_id' =>$wx_id,'share'=>$share,'zhekou'=>$zhekou));
            exit();
        }
    }

    public function save_formid (Request $request){
        
        
        $uid = addslashes(trim($request->param('userid')));
        $formid = addslashes(trim($request->param('from_id')));
        $lifetime = date('Y-m-d H:i:s',time() + 7*24*3600);
        if($formid != 'the formId is a mock one' && $formid != ''){          
            $addres=$this->getModel('UserFromid')->insert(['open_id'=>$uid,'fromid'=>$formid,'lifetime'=>$lifetime]);
            echo json_encode(array('status'=>1,'succ'=>$addres));
        }
    }

    public function add_cart (Request $request){
         
        $Uid = trim($request->param('uid')); //  '微信id',
        $Goods_id = trim($request->param('pid')); //  '产品id',
        $Goods_num = trim($request->param('num')); //  '数量',
        $size_id =trim($request->param('sizeid')); //  '商品属性id',
        $pro_type =trim($request->param('pro_type')); //  '点击类型',
        if(empty($Uid) || empty($Goods_id) || empty($Goods_id) || empty($size_id)){
            echo json_encode(array('status'=>0,'info'=>'添加失败请重新提交!!'));
        }else{
            $r_1=$this->getModel('User')->where(['wx_id'=>['=',$Uid]])->fetchAll('user_id');
            if($r_1){
                $user_id = $r_1[0]->user_id;
            }else{
                $user_id = '';
            }

            $res_k=$this->getModel('Configure')->where(['pid'=>['=',$Goods_id],'num'=>['>','0']])->fetchAll('num');
            if($res_k){
                $num = $res_k[0]->num;
            }else{
                $num = 0;
            }
            if($num >= $Goods_num){
                //查询购物车是否有过改商品，有则修改 无则新增
                $res=$this->getModel('Cart')->where(['Uid'=>['=',$Uid],'Goods_id'=>['=',$Goods_id],'Size_id'=>['=',$size_id]])->fetchAll('Goods_num,id');
                if ($res) {
                    //根据点击的类型进行修改
                    if($pro_type == 'buynow'){
                        $r_u=$this->getModel('Cart')->saveAll(['Goods_num'=>$Goods_num],['Uid'=>['=',$Uid],'Goods_id'=>['=',$Goods_id],'Size_id'=>['=',$size_id]]);
                    }else{
                        $r_u=$this->getModel('Cart')->where(['Uid'=>['=',$Uid],'Goods_id'=>['=',$Goods_id],'Size_id'=>['=',$size_id]])->inc('Goods_num',$Goods_num)->update();
                    }
                    echo json_encode(array('status'=>1,'cart_id'=>$res['0']->id));
                }else{
                    $r=$this->getModel('Cart')->insert(['user_id'=>$user_id,'Uid'=>$Uid,'Goods_id'=>$Goods_id,'Goods_num'=>$Goods_num,'Create_time'=>nowDate(),'Size_id'=>$size_id]);
                    if($r){
                        echo json_encode(array('status'=>1,'cart_id'=>$r));
                    }else{
                        echo json_encode(array('status'=>0,'err'=>'添加失败请重新提交!'));
                    }
                }                  
            }else{
                echo json_encode(array('status'=>0,'err'=>'库存不足！'));
            }
        }
        exit;
    }

    public function listdetail (Request $request){
           
        $id = trim($request->param('cid')); //  '分类ID'
        $paegr = trim($request->param('page')); //  '页面'
        $select = trim($request->param('select')); //  选中的方式 0 默认  1 销量   2价格
        if($select == 0){
             $select = 'a.add_date'; 
        }elseif ($select == 1) {
             $select = 'a.volume'; 
        }else{
             $select = 'c.price'; 
        }

        $sort = trim($request->param('sort')); // 排序方式  1 asc 升序   0 desc 降序
        if($sort){
             $sort = 'asc'; 
        }else{
             $sort = 'desc'; 
        }
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if($r_1){
            $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
            $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
            if(strpos($uploadImg,'../') === false){ // 判断字符串是否存在 ../
                $img = $uploadImg_domain . $uploadImg; // 图片路径
            }else{ // 不存在
                $img = $uploadImg_domain . substr($uploadImg,2); // 图片路径
            }
        }else{
            $img = '';
        }
        if(!$paegr){
            $paegr = 1;
        }
        $start = ($paegr-1)*10;
        $end = $paegr*10;
        $r=$this->getModel('ProductList')->alias('a')->join('configure c','a.id=c.pid','LEFT')->where(['a.product_class'=>['like',"%-$id-%"],'a.status'=>['=','0']])
        ->fetchOrder([$select=>$sort],'a.id,a.product_title,volume,c.price,c.yprice,c.img,a.s_type,c.id AS sizeid',"$start,$end");
        if($r){
            $product = [];
            foreach ($r as $k => $v) {
                $imgurl = $img.$v->img;/* end 保存*/
                $product[$k] = array('id' => $v->id,'name' => $v->product_title,'price' => $v->yprice,'price_yh' => $v->price,'imgurl' => $imgurl,'size'=>$v->sizeid,'volume' => $v->volume,'s_type' => $v->s_type);
            }
            echo json_encode(array('status'=>1,'pro'=>$product));
            exit;
        }else{
            echo json_encode(array('status'=>0,'err'=>'没有了！'));
            exit;
        }
    }

    public function get_more (Request $request){
        
        
        $id = trim($request->param('cid')); //  '分类ID'
        $paegr = trim($request->param('page')); //  '分页显示'
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if($r_1){
            $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
            $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
            if(strpos($uploadImg,'../') === false){ // 判断字符串是否存在 ../
                $img = $uploadImg_domain . $uploadImg; // 图片路径
            }else{ // 不存在
                $img = $uploadImg_domain . substr($uploadImg,2); // 图片路径
            }
        }else{
            $img = '';
        }
        if(!$paegr){
            $paegr = 1;
        }
        $start = ($paegr-1)*10;
        $end = $paegr*10;
        $r=$this->getModel('ProductList')->alias('a')->join('configure c','a.id=c.pid','LEFT')->where(['a.product_class'=>['like',"%-$id-%"],'c.num'=>['>','0']])->fetchOrder(['a.sort'=>'asc'],'a.id,a.product_title,a.volume,c.price,c.yprice,c.img,c.id AS sizeid',"$start,$end");
        if($r){
            $product = [];
            foreach ($r as $k => $v) {
                $imgurl = $img.$v->img;/* end 保存*/
                $product[$k] = array('id' => $v->id,'name' => $v->product_title,'price' => $v->yprice,'size'=>$v->sizeid,'price_yh' => $v->price,'imgurl' => $imgurl,'volume' => $v->volume);
            }
            echo json_encode(array('status'=>1,'pro'=>$product));
            exit;
        }else{
            echo json_encode(array('status'=>0,'pro'=>''));
            exit;
        }
    }

    public function freight($freight,$num,$address)
    {
        $r_1=$this->getModel('Freight')->where(['id'=>['=',$freight]])->fetchAll('*');
        if($r_1){
            $rule = $r_1[0];
            $yunfei = 0;
            if(empty($address)){
                return 0;
            }else{
                $sheng = $address['sheng'];
                $r_2=$this->getModel('AdminCgGroup')->where(['GroupID'=>['=',$sheng]])->fetchAll('G_CName');
                if($r_2){
                    $city = $r_2[0]->G_CName;
                    $rule_1 = $r_1[0]->freight;
                    $rule_2 = unserialize($rule_1);
                    
                    foreach ($rule_2 as $key => $value) {
                        $citys_str = $value['name'];
                        $citys_array=explode(',',$citys_str);
                        $citys_arrays = [];
                        foreach ($citys_array as $k => $v) {
                            $citys_arrays[$v] = $v;
                        }
                        if(array_key_exists($city , $citys_arrays)){
                            if($num > $value['three']){
                                $yunfei += $value['two']; 
                                $yunfei += ($num-$value['three'])*$value['four']; 
                            }else{
                                $yunfei += $value['two']; 
                            }
                        }
                    }
                    return $yunfei;
                }else{
                   return 0;
                }
            }
        }else{
            return 0;
        }
    }

    public function Settlement (Request $request){
        
        
        $cart_id = trim($request->param('cart_id')); //  购物车id
        $uid = trim($request->param('uid')); // 微信id
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if($r_1){
            $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
            $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
            if(strpos($uploadImg,'../') === false){ // 判断字符串是否存在 ../
                $img = $uploadImg_domain . $uploadImg; // 图片路径
            }else{ // 不存在
                $img = $uploadImg_domain . substr($uploadImg,2); // 图片路径
            }
        }else{
            $img = '';
        }

        //地址
        $address = [];
        //计算运费
        $yunfei = 0;
        // 根据微信id,查询用户id
        $r_user=$this->getModel('User')->where(['wx_id'=>['=',$uid]])->fetchAll('user_id,money,consumer_money');
        if($r_user){
            $userid = $r_user['0']->user_id; // 用户id
            $user_money = $r_user['0']->money; // 用户余额
            $user_consumer_money = $r_user['0']->consumer_money; // 用户消费金
        }else{
            $userid = ''; // 用户id
            $user_money = ''; // 用户余额
            $user_consumer_money = ''; // 用户消费金
        }

        // 根据用户id,查询收货地址
        $r_a=$this->getModel('UserAddress')->where(['uid'=>['=',$userid]])->fetchAll('id');
        if(!empty($r_a)){
            $arr['addemt']=0; // 有收货地址
            // 根据用户id、默认地址,查询收货地址信息
            $r_e=$this->getModel('UserAddress')->where(['uid'=>['=',$userid],'is_default'=>['=','1']])->fetchAll('*');
            if(!empty($r_e)){
                $arr['adds'] = (array)$r_e['0']; // 收货地址
            }else{
                // 根据用户id、默认地址,查询收货地址信息
                $aaaid = $r_a[0]->id;
                $r_e=$this->getModel('UserAddress')->where(['id'=>['=',$aaaid]])->fetchAll('*');
                $arr['adds'] = (array)$r_e['0']; // 收货地址 
                $update_rs=$this->getModel('UserAddress')->saveAll(['is_default'=>1],['id'=>['=',$aaaid]]);
            }
            $address = (array)$r_e['0']; // 收货地址
        }else{
            $arr['addemt']=1; // 没有收货地址
            $arr['adds'] = ''; // 收货地址
        }

        $typestr=trim($cart_id,','); // 移除两侧的逗号
        $typeArr=explode(',',$typestr); // 字符串打散为数组
        //  取数组最后一个元素 并查询分类名称
        $zong =0;

        //新增分销分销等级商品不能再次购买
        $status= [];
        $products = [];
        //查询是否是会员卡商品 限制支付方式只能为余额和微信
        $distributor_products = [];
        
        //控制优惠方式
        $discount = true;
        $pstuat = true;

        $usort = 0;

        foreach ($typeArr as $key => $value) {
            // 联合查询返回购物信息
            $r_c=$this->getModel('Cart')->alias('a')->join('product_list m','a.Goods_id=m.id','LEFT')->fetchWhere(['c.num'=>['>','0'],'m.status'=>['=','0'],'a.id'=>['=',$value]],'a.Goods_num,a.Goods_id,a.id,m.product_title,m.volume,c.price,c.attribute,c.img,c.yprice,m.freight,m.product_class');
            if($r_c){
                $product = (array)$r_c['0']; // 转数组
                $attribute = unserialize($product['attribute']);
                $product_id[] = $product['Goods_id'];
                $product_class[] = $product['product_class'];
                $size = '';
                foreach ($attribute as $ka => $va) {
                    $size .= ' '.$va;
                }
                $Goods_id = $product['Goods_id'];
                if(in_array($Goods_id, $products)){
                    $pstuat = false;
                    $status_id = $Goods_id;
                }

                if(array_key_exists($Goods_id, $distributor_products)){ // 检查数组里是否有指定的键名或索引
                    $discount = false;
                    $grade_id = $distributor_products[$Goods_id];
                    if($grade_id){
                        $r_grade=$this->getModel('DistributionGrade')->where(['id'=>['=',$grade_id]])->fetchAll('sort');
                        if($r_grade){
                            $gsort = $r_grade[0]->sort;
                            if($gsort <= $usort){
                                echo json_encode(array('status'=>0,'err'=>'存在无法购买的商品！'));
                                exit;
                                break;
                            }
                        }
                    }
                }
                //计算运费
                $yunfei = $yunfei + $this->freight($product['freight'],$product['Goods_num'],$address);

                $product['photo_x'] = $img.$product['img'];/* 拼接图片链接*/
                $num = $product['Goods_num']; // 产品数量
                $price = $product['price']; // 产品价格
                $product['size'] = $size; // 产品价格
                $zong += $num*$price; // 产品总价
                $res[$key] = $product;
            }else{
                $res[$key] = '';
                $yunfei = 0;
                $zong = 0;
            }
        }

        // 查询自动满减设置
        $r_subtraction=$this->getModel('Subtraction')->where(['id'=>['=','1']])->fetchAll('*');
        $subtraction = [];
        if($r_subtraction){
            $subtraction = unserialize($r_subtraction[0]->subtraction); // 自动满减

            if($r_subtraction[0]->status == 1){
                $man_money = $r_subtraction[0]->man_money; // 满多少包邮
                $region = $r_subtraction[0]->region; // 不包邮地区
                $region_list = explode(',',$region);
                if($man_money <= $zong){ // 当商品总价满足 包邮限制
                    $r_address=$this->getModel('AdminCgGroup')->where(['GroupID'=>['=','']])->fetchAll('G_CName');
                    if($r_address){
                        $G_CName = $r_address[0]->G_CName;
                        if(in_array($G_CName, $region_list)){
                            $arr['freight'] = $yunfei; // 运费
                        }else{
                            $arr['freight'] = 0; // 运费
                        }
                    }else{
                        $arr['freight'] = 0; // 运费
                    }
                }else{ // 当订单总价不满足 包邮限制
                    $arr['freight'] = $yunfei; // 运费
                }
            }else{
                $arr['freight'] = $yunfei; // 运费
            }
        }

        $order_zong = $zong + $yunfei; // 订单总价
        $reduce_name = '';
        $reduce = 0;
        if($subtraction){
            foreach ($subtraction as $kk => $vv){
                foreach ($vv as $kk1 => $vv1){
                    if($order_zong > $kk1){
                        $reduce_name = '满'.$kk1.'减'.$vv1;
                        $reduce = $vv1;
                        break;
                    }
                }
            }
        }
        $arr['name'] = $reduce_name;
        $arr['reduce_money'] = $reduce;

        $order_zong = $order_zong - $reduce;
        if($pstuat){
            $arr['price'] = $zong; // 产品总价
            $arr['pro'] = $res; // 产品信息

            $time = date("Y-m-d H:i:s"); // 当前时间
            //查询消费金参数
            $scoremsg=$this->getModel('Setscore')->fetchOrder(['lever'=>'asc'],'lever,ordernum,scorenum');
            if($scoremsg){
                foreach ($scoremsg as $k => $v) {
                    if($v -> lever < 0){
                        $arr['scorebl'] = $v -> ordernum;
                        unset($scoremsg[$k]);
                    }
                }
                $arr['scorebuy'] = $scoremsg;
            }else{
                $arr['scorebuy'] = '';
            }
            // 根据用户id,查询优惠券状态为 (使用中)
            $r=$this->getModel('Coupon')->where(['user_id'=>['=',$userid],'type'=>['=','1']])->fetchAll('*');
            $r=$r?:'';
            if($r){
                foreach ($r as $k => $v) {
                    $id = $v->id; // 优惠券id
                    // 根据优惠券id,查询订单表(查看优惠券是否绑定)
                    $rr=$this->getModel('Order')->where(['coupon_id'=>['=',$id]])->fetchAll('id');

                    if(empty($rr)){ // 没有数据,表示优惠券没绑定
                        $hid = $v->hid; // 活动id
                        $money = $v->money; // 优惠券金额
                        $rr1=$this->getModel('CouponActivity')->where(['id'=>['=',$hid]])->fetchAll('*');
                        $activity_type = $rr1[0]->activity_type; // 类型
                        $product_class_id = $rr1[0]->product_class_id; // 分类id
                        $product_id1 = $rr1[0]->product_id; // 商品id
                        $z_money = $rr1[0]->z_money; // 满减金额
                        if($activity_type == 1){ // 当活动为注册类型
                            if($money >= $order_zong){
                                // 当优惠券金额比总价格高时,修改优惠券状态为(未使用)
                                $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$id]]);
                                $arr['coupon_id'] = ''; // 付款金额
                                $arr['money'] = ''; // 优惠券金额
                                $arr['coupon_money'] = $order_zong; // 付款金额
                                $arr['user_money'] = $user_money; // 用户余额
                                $arr['discount'] = $discount; // 优惠控制
                                $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                                echo json_encode(array('status'=>1,'arr'=>$arr));
                                exit;
                            }else{
                                $arr['coupon_id'] = $id; // 付款金额
                                $arr['money'] = $v->money; // 优惠券金额
                                $arr['coupon_money'] = $order_zong - $money; // 付款金额
                                $arr['user_money'] = $user_money; // 用户余额
                                $arr['discount'] = $discount; // 优惠控制
                                $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                                echo json_encode(array('status'=>1,'arr'=>$arr));
                                exit;
                            }
                        }else if($activity_type == 3){ // 当活动为满减类型
                            if($order_zong < $z_money){
                                // 当订单总价格不满足满减金额时,修改优惠券状态为(未使用)
                                $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$id]]);
                                $arr['coupon_id'] = ''; // 付款金额
                                $arr['money'] = ''; // 优惠券金额
                                $arr['coupon_money'] = $order_zong; // 付款金额
                                $arr['user_money'] = $user_money; // 用户余额
                                $arr['discount'] = $discount; // 优惠控制
                                $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                                echo json_encode(array('status' => 1, 'arr' => $arr));
                            }else{
                                $arr['coupon_id'] = $id; // 付款金额
                                $arr['money'] = $v->money; // 优惠券金额
                                $arr['coupon_money'] = $order_zong - $money; // 付款金额
                                $arr['user_money'] = $user_money; // 用户余额
                                $arr['discount'] = $discount; // 优惠控制
                                $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                                echo json_encode(array('status'=>1,'arr'=>$arr));
                                exit;
                            }
                        }else{ // 活动类型为节日/活动
                            if($product_class_id == 0){ // 当没设置商品分类
                                if($money >= $order_zong){
                                    // 当优惠券金额比总价格高时,修改优惠券状态为(未使用)
                                    $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$id]]);
                                    $arr['coupon_id'] = ''; // 付款金额
                                    $arr['money'] = ''; // 优惠券金额
                                    $arr['coupon_money'] = $order_zong; // 付款金额
                                    $arr['user_money'] = $user_money; // 用户余额
                                    $arr['discount'] = $discount; // 优惠控制
                                    $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                                    echo json_encode(array('status'=>1,'arr'=>$arr));
                                    exit;
                                }else{
                                    $arr['coupon_id'] = $id; // 付款金额
                                    $arr['money'] = $v->money; // 优惠券金额
                                    $arr['coupon_money'] = $order_zong - $money; // 付款金额
                                    $arr['user_money'] = $user_money; // 用户余额
                                    $arr['discount'] = $discount; // 优惠控制
                                    $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                                    echo json_encode(array('status'=>1,'arr'=>$arr));
                                    exit;
                                }
                            }else{ // 当设置商品分类
                                // 根据活动指定的商品分类查询所有商品的分类
                                $rr_1=$this->getModel('ProductList')->where(['product_class'=>['like',"%$product_class_id%"]])->fetchAll('product_class');
                                if($rr_1){
                                    $calss_status = 1; // 商品属于优惠券指定的分类
                                    foreach ($rr_1 as $k1 => $v1){
                                        $rr_list[$k1] = $v1->product_class;
                                    }
                                    foreach ($product_class as $k2 => $v2){
                                        if(!in_array($v2, $rr_list)){
                                            $calss_status = 0; // 商品不属于优惠券指定的分类
                                            break;
                                        }
                                    }
                                    if($calss_status == 0){ // 当有商品不属于优惠券指定的分类
                                        // 根据优惠券id,修改优惠券状态（未使用）
                                        $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$id]]);
                                        $arr['coupon_id'] = ''; // 付款金额
                                        $arr['money'] = ''; // 优惠券金额
                                        $arr['coupon_money'] = $order_zong; // 付款金额
                                        $arr['user_money'] = $user_money; // 用户余额
                                        $arr['discount'] = $discount; // 优惠控制
                                        $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                                    }else{
                                        $product_status = 1; // 商品属于优惠券指定商品
                                        if ($product_id1 != 0) { // 当优惠券指定了商品
                                            foreach ($product_id as $k3 => $v3){
                                                if ($product_id1 != $v3) {
                                                    $product_status = 0;
                                                    break;
                                                }
                                            }
                                            if($product_status == 0){
                                                // 根据优惠券id,修改优惠券状态（未使用）
                                                $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$id]]);
                                                $arr['coupon_id'] = ''; // 付款金额
                                                $arr['money'] = ''; // 优惠券金额
                                                $arr['coupon_money'] = $order_zong; // 付款金额
                                                $arr['user_money'] = $user_money; // 用户余额
                                                $arr['discount'] = $discount; // 优惠控制
                                                $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                                            }else{
                                                if ($money >= $order_zong) {
                                                    // 当优惠券金额比总价格高时,修改优惠券状态为(未使用)
                                                    $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$id]]);
                                                    $arr['coupon_id'] = ''; // 付款金额
                                                    $arr['money'] = ''; // 优惠券金额
                                                    $arr['coupon_money'] = $order_zong; // 付款金额
                                                    $arr['user_money'] = $user_money; // 用户余额
                                                    $arr['discount'] = $discount; // 优惠控制
                                                    $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                                                } else {
                                                    $arr['coupon_id'] = $id; // 付款金额
                                                    $arr['money'] = $v->money; // 优惠券金额
                                                    $arr['coupon_money'] = $order_zong - $money; // 付款金额
                                                    $arr['user_money'] = $user_money; // 用户余额
                                                    $arr['discount'] = $discount; // 优惠控制
                                                    $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                                                }
                                            }
                                        }else { // 当优惠券没有指定商品
                                            if ($money >= $order_zong) {
                                                // 当优惠券金额比总价格高时,修改优惠券状态为(未使用)
                                                $update_rs=$this->getModel('Coupon')->saveAll(['type'=>0],['id'=>['=',$id]]);
                                                $arr['coupon_id'] = ''; // 付款金额
                                                $arr['money'] = ''; // 优惠券金额
                                                $arr['coupon_money'] = $order_zong; // 付款金额
                                                $arr['user_money'] = $user_money; // 用户余额
                                                $arr['discount'] = $discount; // 优惠控制
                                                $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                                            } else {
                                                $arr['coupon_id'] = $id; // 付款金额
                                                $arr['money'] = $v->money; // 优惠券金额
                                                $arr['coupon_money'] = $order_zong - $money; // 付款金额
                                                $arr['user_money'] = $user_money; // 用户余额
                                                $arr['discount'] = $discount; // 优惠控制
                                                $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }else{ // 有数据
                        $arr['coupon_id'] = ''; // 付款金额
                        $arr['money'] = ''; // 优惠券金额
                        $arr['coupon_money'] = $order_zong; // 付款金额
                        $arr['user_money'] = $user_money; // 用户余额
                        $arr['discount'] = $discount; // 优惠控制
                        $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                    }
                }
                echo json_encode(array('status' => 1, 'arr' => $arr));
                exit;
            }else{
                $arr['money'] = '';
                $arr['coupon_id'] = '';
                $arr['coupon_money'] = $order_zong; // 付款金额
                $arr['user_money'] = $user_money; // 用户余额
                $arr['discount'] = $discount; // 优惠控制
                $arr['user_consumer_money'] = $user_consumer_money; // 用户消费金
                echo json_encode(array('status'=>1,'arr'=>$arr));
                exit;
            }
        }else{
            if($status[$status_id] == 0){
                echo json_encode(array('status'=>0,'err'=>'您有会员套餐未付款订单！'));
                exit;
            }else{
                echo json_encode(array('status'=>0,'err'=>'存在无法购买的商品！'));
                exit;  
            }

        }
    }

    public function Shopping (Request $request){
        
        
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if($r_1){
            $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
            $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
            if(strpos($uploadImg,'../') === false){ // 判断字符串是否存在 ../
                $img = $uploadImg_domain . $uploadImg; // 图片路径
            }else{ // 不存在
                $img = $uploadImg_domain . substr($uploadImg,2); // 图片路径
            }
        }else{
            $img = '';
        }

        $arr = [];
        $uid = trim($request->param('user_id')); //  '分类ID'
        $r_c=$this->getModel('Cart')->alias('a')->join('configure c','a.Size_id=c.id','LEFT')->join('product_list m','a.Goods_id=m.id','LEFT')->fetchWhere(['c.num'=>['>','0'],'a.Uid'=>['=',$uid]],'a.*,c.price,c.attribute,c.img,c.num as pnum,m.product_title,c.id AS sizeid');
        if($r_c){
            foreach ($r_c as $key => $value) {
                $imgurl = $img.$value->img;/* end 保存*/

                $attribute = unserialize($value->attribute);
                $size = '';
                foreach ($attribute as $ka => $va) {
                    $size .= ' '.$va;
                }

                $arr[$key] = array('id' => $value->id,'uid' => $uid,'pnum' => $value->pnum,'sizeid' => $value->sizeid,'pid' => $value->Goods_id,'size' => $size,'price' => $value->price,'num' => $value->Goods_num,'pro_name' => $value->product_title,'imgurl' =>$imgurl);
            }
        }

        echo json_encode(array('status' => 1, 'cart' => $arr));
        exit;
    }

    public function delAll_cart (Request $request){
        
        
        $user_id = addslashes(trim($request->param('user_id')));
        $res=$this->getModel('Cart')->delete($user_id,'Uid');
        if($res){
            echo json_encode(array('status'=>1,'succ'=>'操作成功!'));
            exit;
        }else{
            echo json_encode(array('status'=>0,'err'=>'操作失败!'));
            exit;
        }
    }

    public function delcart (Request $request){
        
        
        $carts = addslashes(trim($request->param('carts')));

        $cartstr=trim($carts,','); // 移除两侧的逗号
        $cartArr=explode(',',$cartstr); // 字符串打散为数组
        //循环删除指定的购物车商品
        foreach ($cartArr as $key => $value) {
            $res=$this->getModel('Cart')->delete($value,'id');
        }

        if($res){
            echo json_encode(array('status'=>1,'succ'=>'操作成功!'));
            exit;
        }else{
            echo json_encode(array('status'=>0,'err'=>'操作失败!'));
            exit;
        }
    }

    public function to_Collection (Request $request){
        
        
        //购物车商品
        $carts = $request->param('carts');
        //用户id
        $userid = addslashes(trim($request->param('user_id')));

        $cartstr=trim($carts,','); // 移除两侧的逗号
        $cartArr=explode(',',$cartstr); // 字符串打散为数组
        //循环移动并删除指定的购物车商品
        foreach ($cartArr as $key => $value) {
            //查询商品id
            $cres=$this->getModel('Cart')->where(['id'=>['=',$value]])->fetchAll('Goods_id');
            if($cres){
                $pid = $cres[0]->Goods_id;
            }else{
                $pid = 0;
            }
            //添加至收藏
            $this->addFavorites($userid,$pid);
            //删除指定购物车id
            $res=$this->getModel('Cart')->delete($value,'id');
        }

        if($res){
            echo json_encode(array('status'=>1,'succ'=>'操作成功!'));
            exit;
        }else{
            echo json_encode(array('status'=>0,'err'=>'操作失败!'));
            exit;
        }
    }

    public function addFavorites($openid,$pid){
        
        
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id');
        if($r){
            $user_id = $r[0]->user_id;
            // 根据用户id,产品id,查询收藏表
            $r=$this->getModel('UserCollection')->where(['user_id'=>['=',$user_id],'p_id'=>['=',$pid]])->fetchAll('*');
            if (!$r) {
                // 在收藏表里添加一条数据
                $r=$this->getModel('UserCollection')->insert(['user_id'=>$user_id,'p_id'=>$pid,'add_time'=>nowDate()]);
            }
        }
    }

    public function up_cart (Request $request){
        
        
        $cart_id = trim($request->param('cart_id'));
        $num = trim($request->param('num'));
        $user_id = trim($request->param('user_id'));

        $r_num=$this->getModel('Cart')->alias('a')->join('configure c','a.Size_id=c.id','LEFT')->fetchWhere(['a.id'=>['=',$cart_id]],'c.num');
        if($r_num){
            $pnum = $r_num[0]->num;
            if($pnum > $num){
                $r_u=$this->getModel('Cart')->saveAll(['Goods_num'=>$num],['id'=>['=',$cart_id],'Uid'=>['=',$user_id]]);
                if($r_u){
                    echo json_encode(array('status'=>1,'succ'=>'操作成功!'));
                    exit;
                }else{
                    echo json_encode(array('status'=>0,'err'=>'操作失败!'));
                    exit;
                }
            }else{
                echo json_encode(array('status'=>0,'err'=>'库存不足!'));
                exit;
            }
        }else{
            echo json_encode(array('status'=>0,'err'=>'网络繁忙!'));
            exit;
        }
    }

    public function wallet_pay (Request $request){
        
        
        $uid = trim($request->param('uid')); // 微信id
        $total = trim($request->param('total')); // 付款余额
        // 根据微信id,查询用户列表(支付密码,钱包余额,用户id)
        $r_user=$this->getModel('User')->where(['wx_id'=>['=',$uid]])->fetchAll('password,money,user_id');
        if($r_user){
            $user_money = $r_user['0']->money; // 用户余额
            $userid = $r_user['0']->user_id; // 用户id

            if($user_money >= $total){
                // 根据微信id,修改用户余额
                if($total > 0){
                    $r=$this->getModel('User')->where(['user_id'=>['=',$userid]])->dec('money',$total)->update();
                    $event = $userid.'使用了'.$total.'元余额';
                    $rr=$this->getModel('Record')->insert(['user_id'=>$userid,'money'=>$total,'oldmoney'=>$user_money,'event'=>$event,'type'=>4]);
                }
                echo json_encode(array('status' => 1, 'succ' => '扣款成功!'));
            }else{
                echo json_encode(array('status' => 0, 'err' => '余额不足！'));
            }
        }else{
            echo json_encode(array('status'=>0,'err'=>'网络繁忙!'));
            exit;
        }
        exit;
    }

    public function payment (Request $request){
        
        $M=$this->getModel('User');
        //开启事务
        $M->starttrans();       
        $cart_id = trim($request->param('cart_id')); // 购物车id
        $uid = trim($request->param('uid')); // 微信id
        $type = trim($request->param('type')); // 用户支付方式
        $coupon_id = trim($request->param('coupon_id')); // 优惠券id
        $r_name =  trim($request->param('name')); // 自动满减金额名称
        $reduce_money =  trim($request->param('reduce_money')); // 自动满减金额
        $allow = trim($request->param('allow')); // 用户使用积分
        $red_packet = trim($request->param('red_packet')); // 用户使用红包
        $total = $request['total']; // 付款金额
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if($r_1){
            $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
            $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
            if(strpos($uploadImg,'../') === false){ // 判断字符串是否存在 ../
                $img = $uploadImg_domain . $uploadImg; // 图片路径
            }else{ // 不存在
                $img = $uploadImg_domain . substr($uploadImg,2); // 图片路径
            }
        }else{
            $img = '';
        }

        if($r_name){
            $coupon_activity_name = $r_name;
        }else{
            $coupon_activity_name = '';
        }
        // 根据微信id,查询用户id
        $r_user=$this->getModel('User')->where(['wx_id'=>['=',$uid]])->fetchAll('user_id,money');
        if($r_user){
            $userid = $r_user['0']->user_id; // 用户id
            $user_money = $r_user['0']->money; // 用户余额
        }else{
            $userid = ''; // 用户id
            $user_money = 0; // 用户余额
        }

        
        if($type == 'wallet_Pay' && $user_money < $total){ // 当余额小于付款金额
            echo json_encode(array('status' => 0, 'err' => '余额不足！'));
            exit;
        }else{
            // 根据用户id、默认地址,查询地址信息
            $r_a=$this->getModel('UserAddress')->where(['uid'=>['=',$userid],'is_default'=>['=','1']])->fetchAll('*');
            if($r_a){
                $name = $r_a['0']->name; // 联系人
                $mobile = $r_a['0']->tel; // 联系电话
                $address = $r_a['0']->address_xq; // 加省市县的详细地址
                $sheng = $r_a['0']->sheng; // 省
                $shi = $r_a['0']->city; // 市
                $xian = $r_a['0']->quyu; // 县
            }else{
                $name = ''; // 联系人
                $mobile = ''; // 联系电话
                $address = ''; // 加省市县的详细地址
                $sheng = ''; // 省
                $shi = ''; // 市
                $xian = ''; // 县
            }

            $z_num = 0;
            $z_price = 0;
            $sNo = $this ->order_number(); // 生成订单号
            // 根据省的id,查询省名称
            $r1=$this->getModel('AdminCgGroup')->where(['GroupID'=>['=',$sheng]])->fetchAll('G_CName');
            if($r1){
                $G_CName = $r1[0]->G_CName; // 省
            }else{
                $G_CName = '';
            }

            $z_freight = 0; // 总运费

            //  拆分购物ID 依次插入数据库
            $typestr=trim($cart_id,',');
            $typeArr=explode(',',$typestr);
            foreach ($typeArr as $key => $value) {
                // 联合查询返回购物信息
                $r_c=$this->getModel('Cart')->alias('a')->join('product_list m','a.Goods_id=m.id','LEFT')->fetchWhere(['a.id'=>['=',$value],'c.num'=>['>','=']],'a.Size_id,a.Goods_num,a.Goods_id,a.id,m.product_title,m.volume,m.freight,c.price,c.attribute,c.img,c.yprice,c.unit');
                if(!empty($r_c)){
                    $product = (array)$r_c['0']; // 转数组
                    $product['photo_x'] = $img.$product['img'];/* 拼接图片链接*/
                    $num = $product['Goods_num']; // 商品数量
                    $z_num += $num; // 商品数量
                    $price = $product['price']; // 商品价格
                    $z_price += $num*$price; // 总价
                    $pid = $product['Goods_id']; // 商品id
                    $product_title = $product['product_title']; // 商品名称
                    $size_id = $product['Size_id']; // 商品Size_id  
                    $unit = $product['unit'];
                    $freight_id = $r_c[0]->freight; // 运费id
                    if(empty($freight_id) || $freight_id == 0){ // 当运费id不存在 或者 为0 时
                        $freight = 0; // 运费为0
                    }else{
                        // 根据运费id,查询运费信息
                        $r2=$this->getModel('Freight')->where(['id'=>['=',$freight_id]])->fetchAll('type,freight');
                        if($r2){
                            $freight_type = $r2[0]->type;
                            $freight_1 = unserialize($r2[0]->freight);
                            $freight_status = 0; // 表示收货地址不存在运费规则里
                            $weight = 1;
                            foreach ($freight_1 as $k2 => $v2){
                                $province_arr = explode(',',$v2['name']); // 省份数组
                                if(in_array($G_CName,$province_arr)){
                                    $one = $v2['one']; // 首件/重
                                    $two = $v2['two']; // 运费
                                    $three = $v2['three']; // 续件/重
                                    $four = $v2['four']; // 续费
                                    $province_name = $G_CName; // 省
                                    $freight_status = 1; // 表示收货地址存在运费规则里
                                    continue;
                                }
                            }
                            if($freight_status == 1){
                                if($freight_type == 0){ // 运费为计件时
                                    if($num > $one){ // 当购买数量大于首件数量时
                                        $Goods_num_1 = $num - $one;
                                        $freight = $two;
                                        $frequency = ceil($Goods_num_1/$three);
                                        $freight = $four * $frequency + $freight; // 运费
                                    }else{ // 当购买数量低于或等于首件数量时
                                        $freight = $two; // 运费
                                    }
                                }else{ // 运费为计重时
                                    $z_weight = $num * $weight;
                                    if($z_weight > $one){ // 当购买数量大于首件数量时
                                        $z_weight_1 = $z_weight - $one;
                                        $freight = $two;
                                        $frequency = ceil($z_weight_1/$three);
                                        $freight = $four * $frequency + $freight; // 运费
                                    }else{ // 当购买数量低于或等于首件数量时
                                        $freight = $two; // 运费
                                    }
                                }
                            }else{
                                $freight = 0; // 运费
                            }
                        }
                    }

                    $z_freight += $freight;

                    //写入配置
                    $attribute = unserialize($product['attribute']);
                    $size = '';
                    foreach ($attribute as $ka => $va) {
                        $size .= $va.' ';
                    }
                    // 循环插入订单附表
                   $beres=$this->getModel('OrderDetails')->insert(['user_id'=>$userid,'p_id'=>$pid,'p_name'=>$product_title,'p_price'=>$price,'num'=>$num,'unit'=>$unit,'r_sNo'=>$sNo,'add_time'=>nowDate(),'r_status'=>0,'size'=>$size,'sid'=>$size_id,'freight'=>$freight]);
                    if($beres < 1){
                        $M->rollback();
                        echo json_encode(array('status' => 0, 'err' => '下单失败,请稍后再试!'));
                        exit;
                    }
                    // 删除对应购物车内容
                    $res_del=$this->getModel('Cart')->delete($value,'id');
                    if($res_del < 1){
                        $M->rollback();
                        echo json_encode(array('status' => 0, 'err' => '下单失败,请稍后再试!'));
                        exit;
                    }
                }else{
                    //回滚删除已经创建的订单
                    $M->rollback();
                    echo json_encode(array('status' => 0, 'err' => '下单失败,请稍后再试!'));
                    exit;
                }
            }
            $spz_price = $z_price; // 商品总价

            // 判断积分使用
            if ($allow >0 && $allow != 'undefined') {
                $z_price = $z_price - $allow;
            }else{
                $allow = 0;
            }
            // 判断红包使用
            if ($red_packet >0 && $red_packet != 'undefined') {
                // $z_price = $z_price - $red_packet;
            }else{
                $red_packet = 0;
            }
            // 判断满减金额
            if ($reduce_money >0 && $reduce_money != 'undefined') {
                $z_price = $z_price - $reduce_money;
            }else{
                $reduce_money = 0;
            }
            //判断优惠券
            if($coupon_id){
                $r_coupon=$this->getModel('Coupon')->where(['id'=>['=',$coupon_id]])->fetchAll('*');
                if($r_coupon){
                    $c_money = $r_coupon[0]->money;
                }else{
                    $c_money = 0;
                }
                $z_price = $z_price - $c_money;
            }else{
                $coupon_id = 0;
                $c_money = 0;
            }

            $z_price = $z_price + $z_freight; // 订单总价

            // 在订单表里添加一条数据
            $r_o=$this->getModel('Order')->insert(['user_id'=>$userid,'name'=>$name,'mobile'=>$mobile,'num'=>$z_num,'z_price'=>$z_price,'sNo'=>$sNo,'sheng'=>$sheng,'shi'=>$shi,'xian'=>$xian,'address'=>$address,'remark'=>'','pay'=>$type,'add_time'=>nowDate(),'status'=>0,'coupon_id'=>$coupon_id,'consumer_money'=>$allow,'coupon_activity_name'=>$coupon_activity_name,'spz_price'=>$spz_price,'reduce_price'=>$reduce_money,'coupon_price'=>$c_money,'red_packet'=>$red_packet,'source'=>1]);
            if($r_o > 0){
                if($allow){

                    $update_rs=$this->getModel('User')->where(['user_id'=>['=',$userid]])->dec('consumer_money',$allow)->update();

                    $event = $userid.'抵用'.$allow.'元消费金';
                    //类型 1:转入(收入) 2:提现 3:管理佣金 4:使用消费金 5收入消费金 6 系统扣款
                    $insert_rs=$this->getModel('DistributionRecord')->insert(['user_id'=>$userid,'from_id'=>$userid,'money'=>$allow,'sNo'=>$sNo,'level'=>0,'event'=>$event,'type'=>4,'add_date'=>nowDate()]);
                }
                //返回
                $M->commit();
                $arr = array('pay_type' => $type,'sNo' => $sNo,'coupon_money' => $z_price,'coupon_id' => $coupon_id,'order_id' => $r_o);
                echo json_encode(array('status' => 1, 'arr' => $arr));
                exit;
            }else{
                //回滚删除已经创建的订单
                $M->rollback();
                echo json_encode(array('status' => 0, 'err' => '下单失败,请稍后再试!'));
                exit;
            }
        }
    }

    private function order_number(){
        return date('Ymd',time()).time().rand(10,99);//18位
    }

    public function up_order (Request $request){
        
        
        $coupon_id = trim($request->param('coupon_id')); // 优惠券id
        $allow = trim($request->param('allow')); // 用户使用消费金
        $coupon_money = trim($request->param('coupon_money')); // 付款金额
        $order_id = trim($request->param('order_id')); // 订单号
        $user_id = trim($request->param('user_id')); // 微信id
        $d_yuan = trim($request->param('d_yuan')); // 抵扣余额
        $trade_no = trim($request->param('trade_no')); // 微信支付单号
        $pay =  trim($request->param('pay'));
        // 根据微信id,查询用户id
        $r_user=$this->getModel('User')->where(['wx_id'=>['=',$user_id]])->fetchAll('user_id,money');
        if($r_user){
            $userid = $r_user['0']->user_id; // 用户id
            $user_money =  $r_user['0']->money; // 用户余额

            if($d_yuan){
                // 使用组合支付的时候 lkt_combined_pay
                $r=$this->getModel('User')->where(['user_id'=>['=',$userid]])->dec('money',$d_yuan)->update();
                $weixin_pay = $coupon_money - $d_yuan;
                //写入日志
                $rr=$this->getModel('CombinedPay')->insert(['weixin_pay'=>$weixin_pay,'balance_pay'=>$d_yuan,'total'=>$coupon_money,'order_id'=>$order_id,'add_time'=>nowDate(),'user_id'=>$user_id]);
                // 根据修改支付方式
                $r_combined=$this->getModel('Order')->saveAll(['pay'=>combined_Pay],['sNo'=>['=',$order_id],'user_id'=>['=',$userid]]);

                //微信支付记录-写入日志
                $event = $userid.'使用组合支付了'.$coupon_money.'元--订单号:'.$order_id;
                $rr=$this->getModel('Record')->insert(['user_id'=>$userid,'money'=>$coupon_money,'oldmoney'=>$d_yuan,'event'=>$event,'type'=>4]);
            }

            if($trade_no){
                //微信支付记录-写入日志
                $event = $userid.'使用微信支付了'.$coupon_money.'元--订单号:'.$order_id;
                $rr=$this->getModel('Record')->insert(['user_id'=>$userid,'money'=>$coupon_money,'oldmoney'=>$d_yuan,'event'=>$event,'type'=>4]);
            }

            if($coupon_money <= 0 && $allow > 0){
                // 根据订单号、用户id,修改订单状态(未发货)
                $r_u=$this->getModel('Order')->saveAll(['status'=>1,'pay'=>consumer_pay,'trade_no'=>$trade_no],['sNo'=>['=',$order_id],'user_id'=>['=',$userid]]);
            }else{
                // 根据订单号、用户id,修改订单状态(未发货)
             $rpay=$pay?:0;
                $r_u=$this->getModel('Order')->saveAll(['status'=>1,'pay'=>$rpay,'trade_no'=>$trade_no],['sNo'=>['=',$order_id],'user_id'=>['=',$userid]]);
            }

            if($allow && $coupon_money > 0){
                // 使用组合支付的时候 lkt_combined_pay 消费金情况
                if($pay == 'wallet_Pay'){
                    $zpay = 'balance_pay';
                }else{
                    $zpay = 'weixin_pay';
                }
                //写入日志
                $total = $allow + $coupon_money;
                $rr=$this->getModel('CombinedPay')->insert(['$zpay'=>$coupon_money,'consumer_pay'=>$allow,'total'=>$total,'order_id'=>$order_id,'add_time'=>nowDate(),'user_id'=>$user_id]);
                // 根据修改支付方式
                $r_combined=$this->getModel('Order')->saveAll(['pay'=>combined_Pay],['sNo'=>['=',$order_id],'user_id'=>['=',$userid]]);

                //微信支付记录-写入日志
                $event = $userid.'使用组合支付了'.$total.'元--订单号:'.$order_id;
                $rr=$this->getModel('Record')->insert(['user_id'=>$userid,'money'=>$coupon_money,'oldmoney'=>$d_yuan,'event'=>$event,'type'=>4]);
            }

            // 根据用户id、优惠券id,修改优惠券状态(已使用)
            $update_rs=$this->getModel('Coupon')->saveAll(['type'=>2],['user_id'=>['=',$userid],'id'=>['=',$coupon_id]]);
            
            // 根据订单号,查询商品id、商品名称、商品数量
            $r_o=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$order_id]])->fetchAll('p_id,num,p_name,sid');
            // 根据订单号,修改订单详情状态(未发货)
            $r_d=$this->getModel('OrderDetails')->saveAll(['r_status'=>1],['r_sNo'=>['=',$order_id]]);
            // 修改产品数据库数量
            $pname = '';
            foreach ($r_o as $key => $value) {
                $pid = $value->p_id; // 商品id
                $num = $value->num; // 商品数量
                $p_name = $value->p_name; // 商品名称
                $sid = $value->sid; // 商品属性id
                $pname .= $p_name;
                // 根据商品id,修改商品数量
                $r_p=$this->getModel('Configure')->where(['id'=>['=',$sid]])->dec('num',$num)->update(); 
                // 根据商品id,修改卖出去的销量
                $r_x=$this->getModel('ProductList')->where(['id'=>['=',$pid]])->inc('volume',$num)->dec('num',$num)->update(); 
            }

            if($r_u){
                // 根据订单号,查询订单id、订单金额
                $r_id=$this->getModel('Order')->where(['sNo'=>['=',$order_id]])->fetchAll('*');
                if($r_id){
                    $id = $r_id['0']->id; // 订单id
                }else{
                    $id = 0;
                }
                $time =date("Y-m-d h:i:s",time()); // 当前时间
                $ds =  false;

                echo json_encode(array('status'=>1,'succ'=>'操作成功!','sNo' => $order_id,'coupon_money' => $coupon_money,'id' => $id,'pname'=>$pname,'time'=>$time,'qu'=>$ds));
                exit;
            }else{
                echo json_encode(array('status'=>0,'err'=>'操作失败!'));
                exit;
            }
        }else{
            echo json_encode(array('status'=>0,'err'=>'操作失败!'));
            exit;
        }
    }

    public function comment (Request $request){
        
        
        $order_id = trim($request->param('order_id')); // 订单号
        $user_id = trim($request->param('user_id')); // 微信id
        $pid = trim($request->param('pid')); // 商品id
        $attribute_id = trim($request->param('attribute_id')); // 属性id
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if($r_1){
            $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
            $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
            if(strpos($uploadImg,'../') === false){ // 判断字符串是否存在 ../
                $img = $uploadImg_domain . $uploadImg; // 图片路径
            }else{ // 不存在
                $img = $uploadImg_domain . substr($uploadImg,2); // 图片路径
            }
        }else{
            $img = '';
        }

        $r_user=$this->getModel('User')->where(['wx_id'=>['=',$user_id]])->fetchAll('user_id');

        if($r_user){
            if($pid && $attribute_id){ 
                $r_o=$this->getModel('OrderDetails')->alias('a')->join('configure m','a.sid=m.id','LEFT')->fetchWhere(['a.r_sNo'=>['=',$order_id],'a.p_id'=>['=',$pid],'a.sid'=>['=',$attribute_id],'a.r_status'=>['=','3']],'a.p_id as commodityId,m.img,a.size,a.sid');               
            }else{
                $r_o=$this->getModel('OrderDetails')->alias('a')->join('configure m','a.sid=m.id','LEFT')
                ->fetchWhere("a.r_sNo ='$order_id' and (a.r_status = 3 or a.r_status = 1 or a.r_status = -1)",'a.p_id as commodityId,m.img,a.size,a.sid');               
            }
            
            if($r_o){
                foreach ($r_o as $key => $value) {
                    $arr = (array)$value;
                    $imgurl = $arr['img'];/* end 保存*/
                    $arr['commodityIcon']=$img.$imgurl;
                    $obj = (object)$arr;
                    $res[$key] = $obj;
                }
                echo json_encode(array('status'=>1,'commentList'=>$res));
                exit;
            }
        }
    }

    public function t_comment (Request $request){
        $M=$this->getModel('Order');
        $M->starttrans();
        
        $type = trim($request->param('type'));
        if($type == 'file'){
            //处理评论图片
            $id = trim($request->param('id'));//评论ID
            // 查询配置表信息
            $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
            if($r){
                $uploadImg = $r[0]->uploadImg;
            }

            $imgURL=($_FILES['imgFile']['tmp_name']);
            $type = str_replace('image/', '.', $_FILES['imgFile']['type']);
            $imgURL_name=time().mt_rand(1,1000).$type;
            move_uploaded_file($imgURL,$uploadImg.$imgURL_name);
            $res=$this->getModel('CommentsImg')->insert(['comments_url'=>$imgURL_name,'comments_id'=>$id,'add_time'=>nowDate()]);
            
            if($res){
                $M->commit();
                echo json_encode(array('status'=>1,'err'=>'修改成功','sql'=>$sql));
                exit;
            }else{
                $M->rollback();
                echo json_encode(array('status'=>0,'err'=>'修改失败'));
                exit;
            }
        }else{
            //接收评论JSON数据  
            $json = json_decode (file_get_contents('php://input'));
            $comments = $json->comments;
            $r_d = 0;
            $oid = '';

            // 查询配置表信息
            $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
            if($r)
                $uploadImg = $r[0]->uploadImg;
            //敏感词表
            $badword=include('badword.php');
        
            foreach ($comments as $key => $value){
                $oid =  $value->orderId; // 订单号
                $uid =  $value->userid; // 微信id
                $pid =  $value->commodityId; // 商品id
                $images =  $value->images; // 商品id
                $size =  $value->size; // 属性名称
                $attribute_id =  $value->attribute_id; // 属性id
                $content =  $value->content; // 评论内容
                $badword1 = array_combine($badword,array_fill(0,count($badword),'*'));

                $content = preg_replace ( "/\s(?=\s)/","\\1", $this->strtr_array($content, $badword1));

                //特殊字符处理
                $content = htmlentities($content);

                $CommentType =  $value->score; // 评论类型

                $r_name=$this->getModel('User')->where(['wx_id'=>['=',$uid]])->fetchAll('user_id');
                if($r_name){
                    $user_id = $r_name[0]->user_id;
                }else{
                    $user_id = '';
                }

                $arr = array();
                if($content != '' || count($images) != 0){
                    $r_c=$this->getModel('Comments')->where(['oid'=>['=',$oid],'pid'=>['=',$pid],'attribute_id'=>['=',$attribute_id]])->fetchAll('oid');
                    if(empty($r_c['0'])){
                        $lcid=$this->getModel('Comments')->insert(['oid'=>$oid,'uid'=>$user_id,'pid'=>$pid,'attribute_id'=>$attribute_id,'size'=>$size,'content'=>$content,'CommentType'=>$CommentType,'add_time'=>nowDate()]);
                        $cid[$value->pingid] = $lcid;
                        if($lcid > 0){
                            $r_d=$this->getModel('OrderDetails')->saveAll(['r_status'=>5],['r_sNo'=>['=',$oid],'sid'=>['=',$attribute_id]]);

                            $rr=$this->getModel('OrderDetails')->where(['r_sNo'=>['=',$oid]])->fetchAll('r_status');
                            if($rr){
                                foreach($rr as $k => $v){
                                    $r_status[] = $v->r_status;
                                }
                                $arr = array_count_values($r_status);
                                if($arr[5] == count($rr)){
                                    $update_rs=$this->getModel('Order')->saveAll(['status'=>5],['sNo'=>['=',$oid]]);
                                }
                            }
                        }else{
                            echo json_encode(array('status'=>0,'err'=>'修改失败'));
                            exit;
                        }
                    }else{
                        $M->rollback();
                        echo json_encode(array('status'=>0,'err'=>'亲!评论过了1'));
                        exit;
                    }
                }else{
                    $M->rollback();
                    echo json_encode(array('status'=>0,'err'=>'修改失败'));
                    exit;
                }
            }

            $M->commit();
            echo json_encode(array('status'=>1,'succ'=>'评论成功!','arrid'=>$cid));
            exit; 
        }
    }

    function strtr_array($str,$replace_arr) {
        $maxlen = 0;$minlen = 1024*128;
        if (empty($replace_arr)) return $str;
        foreach($replace_arr as $k => $v) {
            $len = strlen($k);
            if ($len < 1) continue;
            if ($len > $maxlen) $maxlen = $len;
            if ($len < $minlen) $minlen = $len;
        }
        $len = strlen($str);
        $pos = 0;$result = '';
        while ($pos < $len) {
            if ($pos + $maxlen > $len) $maxlen = $len - $pos; 
            $found = false;$key = '';
            for($i = 0;$i<$maxlen;++$i) $key .= $str[$i+$pos]; 
            for($i = $maxlen;$i >= $minlen;--$i) {
                $key1 = substr($key, 0, $i);
                if (isset($replace_arr[$key1])) {
                    $result .= $replace_arr[$key1];
                    $pos += $i;
                    $found = true;
                    break;
                }
            }
            if(!$found) $result .= $str[$pos++];
        }
        return $result;
    }

    public function new_product (Request $request){

        $tid = trim($request->param('tid')); //  '分类ID'
        $paegr = trim($request->param('page')); //  '页面'

        $select = trim($request->param('select')); //  选中的方式 0 默认  1 销量   2价格
        if($select == 0){
             $select = 'a.add_date'; 
        }elseif ($select == 1) {
             $select = 'a.volume'; 
        }else{
             $select = 'price'; 
        }

        $sort = trim($request->param('sort')); // 排序方式  1 asc 升序   0 desc 降序
        if($sort){
             $sort = 'asc'; 
        }else{
             $sort = 'desc'; 
        }
      $img=$this->getUploadImg(1);
        if(!$paegr){
            $paegr = 1;
        }
        $start = ($paegr-1)*10;
        $end = 10;

        $r=$this->getModel('ProductList')->alias('a')->join('configure c','a.id=c.pid','RIGHT')
        ->where(['a.s_type'=>['like',"%$tid%"],'a.status'=>['=','0'],'a.num'=>['>','0']])
        ->where("a.recycle=0")
        ->order([$select=>$sort])
        ->fetchGroup('c.pid','a.id,a.product_title,a.imgurl,a.volume,min(c.price) as price,c.yprice,c.img,a.s_type,c.id AS sizeid',"$start,$end");
        if($r){
            $product = [];
            foreach ($r as $k => $v) {
                $imgurl = $img.$v->imgurl;/* end 保存*/
                $pid = $v->id;
                $r_ttt=$this->getModel('Configure')->where(['pid'=>['=',$pid]])->fetchOrder(['price'=>'asc'],'price,yprice');
                $price =$r_ttt[0]->yprice;
                $price_yh =$r_ttt[0]->price;
                $product[$k] = array('id' => $v->id,'name' => $v->product_title ,'price' => $price,'price_yh' => $price_yh,'imgurl' => $imgurl,'volume' => $v->volume,'s_type' => $v->s_type);
            }
            echo json_encode(array('status'=>1,'pro'=>$product));
            exit;
        }else{
            echo json_encode(array('status'=>0,'err'=>'没有了！'));
            exit;
        }
    }

    public function choujiangjiesuan (Request $request){
        
        
        $productId = trim($request->param('productId')); //  购物车id
        $uid = trim($request->param('uid')); // 微信id
        $choujiangid = trim($request->param('choujiangid')); //  活动id
        $size =trim($request->param('size')); //型号
        $wx_id = $request->param('size');//分享人ID
        // 根据微信id,查询用户id
        $r_user=$this->getModel('User')->where(['wx_id'=>['=',$uid]])->fetchAll('user_id,money');
        if($r_user){
            $userid = $r_user['0']->user_id; // 用户id
            $user_money = $r_user['0']->money; // 用户余额
        }else{
            $userid = '';
            $user_money = 0;
        }
        // 根据用户id,查询收货地址
        $r_a=$this->getModel('UserAddress')->where(['uid'=>['=',$userid]])->fetchAll('id');
        if($r_a){
            $arr['addemt']=0; // 有收货地址
            // 根据用户id、默认地址,查询收货地址信息
            $r_e=$this->getModel('UserAddress')->where(['uid'=>['=',$userid],'is_default'=>['=','1']])->fetchAll('*');
            $arr['adds'] = (array)$r_e['0']; // 收货地址
        }else{
            $arr['addemt']=1; // 没有收货地址
            $arr['adds'] = ''; // 收货地址
        }
        $re=$this->getModel('Draw')->where(['id'=>['=',$choujiangid]])->fetchAll('price');

        $r_d=$this->getModel('Configure')->where(['id'=>['=','']])->fetchAll('*');
        $size1 = '';
        if($r_d){
            $attribute = unserialize($r_d[0]->attribute);
            foreach ($attribute as $ka => $va) {
                $size1 .= $va.' ';
            }
        }

        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if($r_1){
            $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
            $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
            if(strpos($uploadImg,'../') === false){ // 判断字符串是否存在 ../
                $img = $uploadImg_domain . $uploadImg; // 图片路径
            }else{ // 不存在
                $img = $uploadImg_domain . substr($uploadImg,2); // 图片路径
            }
        }else{
            $img = '';
        }
        $product = array();
        $r_c=$this->getModel('Draw')->alias('a')->join('product_list b','a.draw_brandid=b.id','inner')->fetchWhere(['a.id'=>['=',$choujiangid]],'*');
        // 联合查询返回购物信息
        if(!empty($r_c)){
            $product = (array)$r_c['0']; // 转数组
            $product['photo_x'] = $img.$product['imgurl'];/* 拼接图片链接*/
            $num =1; // 商品数量
            $pid = $product['draw_brandid']; // 商品id
            $product_title = $product['product_title']; // 商品名称
            $size1 = $size1?$size1:'默认';
        }

        if($re){
            $arr['price'] = $re[0]->price; // 产品总价
        }else{
            $arr['price'] = ' '; // 产品总价
        }
        $arr['adds']['photo_x']=$product['photo_x']?$product['photo_x']:'';
        $arr['adds']['num']=$num;
        $arr['adds']['pid']=$pid;
        $arr['adds']['product_title']=$product_title;
        $arr['adds']['choujiangid'] = $choujiangid; // 付款金额
        $arr['adds']['user_money'] = $user_money; // 用户余额
        $arr['adds']['size1'] = $size1; //型号
        $arr['size'] = $size; //型号
        echo json_encode(array('status'=>1,'arr'=>$arr));
        exit;
    }

    public function choujiangpayment (Request $request){
        
        
        // print_r($request);die;
        $choujiangid = trim($request->param('choujiangid')); // 抽奖id
        $uid = trim($request->param('uid')); // 微信id
        $and = trim($request->param('remark')); // 用户备注
        $type = trim($request->param('type')); // 用户支付方式
        $size = trim($request->param('size'));
        $total = $request['total']; // 付款金额
        $role = $request->param('role');//分享订单ID
        if(!empty($role) && $role !='undefined'){//通过分享ID查询该团成员总数与设定拼团人数
            $r04=$this->getModel('Draw')->where(['id'=>['=',$choujiangid]])->fetchAll('num,spelling_number,collage_number');
            if($r04){
                $num1 = $r04[0]->num;//每个团所需人数
                $spelling_number = $r04[0]->spelling_number;//可抽中奖次数（默认为1）
                $collage_number = $r04[0]->collage_number;//最少开奖团数（默认为1）
            }else{
                $num1 = 0;
            }

            $r05=$this->getModel('DrawUser')->where(['draw_id'=>['=',$choujiangid],'role'=>['=',$role]])->fetchAll('count(id) as aa');
            if($r05){
                $bb = $r05[0]->aa;
            }else{
                $bb = 0;
            }
            if($bb>=$num1){
                $role = 0;
            }
        }

        // 根据微信id,查询用户id
        $r_user=$this->getModel('User')->where(['wx_id'=>['=',$uid]])->fetchAll('user_id,money');
        $userid = $r_user['0']->user_id; // 用户id
        $user_money = $r_user['0']->money; // 用户余额
        $role=$role?:0;

        if($type == 'balance_Pay' && $user_money < $total){ // 当余额小于付款金额
            echo json_encode(array('status' => 0, 'err' => '余额不足！'));
            exit;
        }else{
            $remark = preg_replace('/[ ]/', '', $and);
            // 根据用户id、默认地址,查询地址信息
            $r_a=$this->getModel('UserAddress')->where(['uid'=>['=',$userid],'is_default'=>['=','1']])->fetchAll('*');
            if($r_a){
                $name = $r_a['0']->name; // 联系人
                $mobile = $r_a['0']->tel; // 联系电话
                $address = $r_a['0']->address_xq; // 加省市县的详细地址
                $sheng = $r_a['0']->sheng; // 省
                $shi = $r_a['0']->city; // 市
                $xian = $r_a['0']->quyu; // 县
            }else{
                $name = ''; // 联系人
                $mobile = ''; // 联系电话
                $address = ''; // 加省市县的详细地址
                $sheng = ''; // 省
                $shi = ''; // 市
                $xian = ''; // 县
            }

            $z_num = 0;
            $z_price = 0;

            $sNo = $this ->order_number(); // 生成订单号
            $size_id = $size;//商品Size_id
            $r_d=$this->getModel('Configure')->where(['id'=>['=','']])->fetchAll('*');
            $size = '';
            if($r_d){
                $attribute = unserialize($r_d[0]->attribute);
                foreach ($attribute as $ka => $va) {
                    $size .= $va.' ';
                }
            }

            $r_c=$this->getModel('Draw')->alias('a')->join('product_list b','a.draw_brandid=b.id','inner')->fetchWhere(['a.id'=>['=',$choujiangid]],'*');

            // 联合查询返回购物信息
            if(!empty($r_c)){

                $product = (array)$r_c['0']; // 转数组
                $product['photo_x'] = 'http://'.$_SERVER['HTTP_HOST'].$product['imgurl'];/* 拼接图片链接*/
                $num =1; // 商品数量
                $z_num += $num; // 商品数量
                $price = $total; // 商品价格
                $z_price += $num*$price; // 总价
                $pid = $product['draw_brandid']; // 商品id
                $product_title = $product['product_title']; // 商品名称
                $size = $size?$size:'默认';

                // 循环插入订单附表
                $r_d=$this->getModel('OrderDetails')->insert(['user_id'=>$userid,'p_id'=>$pid,'p_name'=>$product_title,'p_price'=>$price,'num'=>$num,'unit'=>'件','r_sNo'=>$sNo,'add_time'=>nowDate(),'r_status'=>0,'size'=>$size,'sid'=>$size_id]);
            }else{
                echo json_encode(array('status' => 0, 'err' => '请勿重复下单！'));
                exit;
            }
            // 插入抽奖与用户关联表
            $r_r=$this->getModel('DrawUser')->insert(['draw_id'=>$choujiangid,'user_id'=>$userid,'time'=>nowDate(),'role'=>$role],1);
            // 在订单表里添加一条数据
            $r_o=$this->getModel('Order')->insert(['user_id'=>$userid,'name'=>$name,'mobile'=>$mobile,'num'=>$z_num,'z_price'=>$z_price,'sNo'=>$sNo,'sheng'=>$sheng,'shi'=>$shi,'xian'=>$xian,'address'=>$address,'remark'=>$remark,'pay'=>$type,'add_time'=>nowDate(),'status'=>0,'coupon_id'=>0,'allow'=>0,'drawid'=>$r_r]);
            if( $role == 0){
              //把抽奖与用户关联的
                $r06=$this->getModel('DrawUser')->saveAll(['role'=>$r_r],['id'=>['=',$r_r]]);
            }
            if($r_d > 0 && $r_o > 0){
                //返回
                $arr = array('pay_type' => $type,'sNo' => $sNo,'coupon_money' => $total,'coupon_id' => 0,'order_id' => $r_o , 'type1' => 11);
                if(!empty($bb)){
                    if($bb>=$num1){
                        echo json_encode(array('status' => 1, 'arr' => $arr, 'err' => '该团已满，生成新团！'));
                        exit;
                        
                    }else{
                        echo json_encode(array('status' => 1, 'arr' => $arr, 'err' => ''));
                        exit;
                    }
                }else{
                    echo json_encode(array('status' => 1, 'arr' => $arr, 'err' => ''));
                        exit;
                }
            }else{
                echo json_encode(array('status' => 0, 'err' => '请勿重复下单2！'));
                exit;
            }
        }
    }

}