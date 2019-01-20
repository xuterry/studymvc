<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Product extends Index
{
    protected $product_list;
    function __construct()
    {
        parent::__construct(); 
        $this->product_list=$this->getModel('ProductList');
    }
    public function Index(Request $request)
    {
         $product_class = addslashes(trim($request->param('cid'))); // 分类名称
        $brand_id = addslashes(trim($request->param('brand_id'))); // 品牌id
        $status = addslashes(trim($request->param('status'))); // 上下架
        $s_type = addslashes(trim($request->param('s_type'))); // 类型
        $product_title = addslashes(trim($request->param('product_title'))); // 标题

        $pageto = $request -> param('pageto');
        // 导出
        $pagesize = $request -> param('pagesize');
        $pagesize = $pagesize ? $pagesize:10;
        // 每页显示多少条数据
        $page = $request -> param('page');

        // 页码
        if($page){
            $start = ($page-1)*$pagesize;
        }else{
            $start = 0;
        }
        $rr=$this->getModel('ProductClass')->where(['recycle'=>['=','0'],'sid'=>['=','0']])->fetchAll('cid,pname');
        $res = '';
        foreach ($rr as $key => $value) {
            $c = '-'.$value->cid.'-';
            //判断所属类别 添加默认标签
            if ($product_class == $c) {
              $res .= '<option selected="selected" value="'.$c.'">'.$value->pname.'</option>';
            }else{
              $res .= '<option  value="'.$c.'">'.$value->pname.'</option>';
            }
            //循环第一层
            $r_e=$this->getModel('ProductClass')->where(['recycle'=>['=','0'],'sid'=>['=',$value->cid]])->fetchAll('cid,pname');
            if($r_e){
          $hx = '-----';
          foreach ($r_e as $ke => $ve){
            $cone = $c . $ve->cid.'-';
            //判断所属类别 添加默认标签
            if ($product_class == $cone) {
              $res .= '<option selected="selected" value="'.$cone.'">'.$hx.$ve->pname.'</option>';
            }else{
              $res .= '<option  value="'.$cone.'">'.$hx.$ve->pname.'</option>';
            }
            //循环第二层
            $r_t=$this->getModel('ProductClass')->where(['recycle'=>['=','0'],'sid'=>['=',$ve->cid]])->fetchAll('cid,pname');
            if($r_t){
              $hxe = $hx.'-----';
              foreach ($r_t as $k => $v){
                $ctow = $cone . $v->cid.'-'; 
                //判断所属类别 添加默认标签
                if ($product_class == $ctow) {
                  $res .= '<option selected="selected" value="'.$ctow.'">'.$hxe.$v->pname.'</option>';
                }else{
                  $res .= '<option  value="'.$ctow.'">'.$hxe.$v->pname.'</option>';
                }
              }
            }
          }
        }
        }
        $rr1=$this->getModel('BrandClass')->where(['recycle'=>['=','0']])->fetchAll('*');
        $rew = '';
        foreach ($rr1 as $key => $value) {
            if ($brand_id == $value->brand_id) {
                $rew .= "<option selected='selected' value='" . $value->brand_id . "'>$value->brand_name</option>";
            } else {
                $rew .= "<option value='" . $value->brand_id . "'>$value->brand_name</option>";
            }
        }
        $rr=$this->getModel('ProductConfig')->get('1','id');

        if($rr){
            $config = unserialize($rr[0]->config);
            $min_inventory = $config['min_inventory'];
        }else{
            $min_inventory = 0;
        }

        $condition = ' recycle = 0 ';
        if($product_class != 0){
            $condition .= " and a.product_class like '%$product_class%' ";
        }
        if ($brand_id != 0) {
            $condition .= " and brand_id like '$brand_id' ";
        }
        if ($status != 0) {
            if($status == 1){
                $condition .= " and status like 1 ";
            }else if($status == 2){
                $condition .= " and status like 0 ";
            }
        }
        if ($s_type != 0) {
            $condition .= " and s_type like '%$s_type%' ";
        }
        if($product_title != ''){
            $condition .= " and a.product_title like '%$product_title%' ";
        }
        //$sql = "select * from lkt_product_list as a where $condition" . " order by status asc,a.add_date desc,a.sort desc limit $start,$pagesize ";
        $r = $this->product_list->alias('a')->where($condition)->order(['status'=>'asc','a.add_date'=>'desc','a.sort'=>'desc'])->paginator($pagesize,$this->getUrlConfig($request->url));
      //  dump($this->product_list->relationQuery('product_img','product_id','id')->delete(2,"lkt_product_list.id"));exit();
        $pageshow=$r->render();
        $list = [];
        $status_num = 0;
        foreach ($r as $key => $value) {
            $pid =  $value -> id;
            $class =  $value -> product_class;
            $num =  $value -> num;
            $value -> s_type = explode(',',$value -> s_type);
            $typestr=trim($class,'-');
            $typeArr=explode('-',$typestr);
            //  取数组最后一个元素 并查询分类名称
            $cid = end($typeArr);
            $r_p=$this->getModel('ProductClass')->where(['cid'=>['=',$cid]])->fetchAll('pname');
            if($r_p){
              $pname = $r_p['0']->pname;
            }else{
              $pname = '顶级';
            }
            if($num == 0 && $value->status == 0){ // 当库存为0 并且商品还为上架状态
                // 根据商品id，修改商品状态（下架）
                $update_rs=$this->product_list->saveAll(['status'=>1],['id'=>['=',$pid]]);
                $status_num += 1;
                // 根据商品id，把商品下的属性全部下架
                $update_rs=$this->getModel('Configure')->saveAll(['status'=>4],['pid'=>['=',$pid]]);
            }
            $r_s=$this->getModel('Configure')->where(['pid'=>['=',$pid]])->fetchAll('id,num,unit,price');
            if($r_s){
                $price = [];
                $unit = $r_s[0]->unit;
                foreach ($r_s as $k1 => $v1){
                    $price[$k1] = $v1->price;
                    $configure_id = $v1->id;
                    if($v1->num <= $min_inventory && $v1->num > 0){
                        $update_rs=$this->getModel('Configure')->saveAll(['status'=>3],['id'=>['=',$configure_id]]);
                    }else if($v1->num == 0){
                        $update_rs=$this->getModel('Configure')->saveAll(['status'=>4],['id'=>['=',$configure_id]]);
                    }
                }
                $min = min($price);
                $present_price = $min;
            }else{
                $unit = '';
                $present_price = '';
            }
            $value->unit = $unit;
            $value->price = $present_price;
            foreach ($value as $k => $v) {
              $arr[$k] = $v;
            }
            $arr['pname'] = $pname;

            $list[$key] = (object)$arr;
        }
        if($status_num > 0){
            $this->getDefaultView();
        }
        foreach ($list as $key01 => $value01) {
            if(!empty($value01->brand_id)){
                $r01=$this->getModel('BrandClass')->where(['brand_id'=>['=',$value01->brand_id]])->fetchAll('brand_name');
                if($r01){
                    $list[$key01]->brand_name = $r01[0]->brand_name;
                }
            }
        } 

        $r=$this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置

        $this->assign("uploadImg", $uploadImg);
        $this->assign("product_title", $product_title);
        $this->assign("class", $res);
        $this->assign("rew", $rew);
        $this->assign("s_type", $s_type);
        $this->assign("status", $status);
        $this->assign("list", $list);
        $this->assign("min_inventory", $min_inventory);
        $this->assign('pages_show', $pageshow);
        $this->assign('pagesize', $pagesize);

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function add(Request $request)
    {
       $request->method()=='post'&&$this->do_add($request);
       dump($_COOKIE);
        /*** 报错不清除输入内容 ***/
        $product_number = addslashes(trim($request->cookie('product_number'))); // 产品编号
        $product_title =  $request->cookie('product_title'); // 产品标题
        $brand_id1 = addslashes(trim($request->cookie('brand_class'))); // 品牌
        $product_class=$request->cookie('product_class');
        $subtitle = $request->cookie('subtitle'); // 小标题
        $scan = addslashes(trim($request->cookie('scan'))); // 条形码
        $attribute = $request->cookie('attribute'); // 属性
        $keyword =$request->cookie('keyword'); // 关键词
        $wei产品内容ght = addslashes(trim($request->cookie('weight'))); // 重量
        $s_type = explode(",",$request->cookie('s_type')); // 类型
        $distributor_id = trim($request->cookie('distributor_id')); //关联的分销层级id
        $is_distribution = trim($request->cookie('is_distribution')); //是否开启分销
        $is_zhekou = trim($request->cookie('is_zhekou')); //是否开启会员商品折扣
        $volume = trim($request->cookie('volume')); //拟定销量
        $sort = floatval(trim($request->cookie('sort'))); // 排序
        $image = addslashes(trim($request->cookie('image'))); // 产品图片
        $oldpic = addslashes(trim($request->cookie('oldpic'))); // 产品图片
        $freight1 = $request->cookie('freight'); // 运费
        $weight=$request->cookie('weight');
        $content = addslashes(trim($request->cookie('content'))); // 
        $attribute=$request->cookie('attribute');

        if(!$s_type){
            $s_type = [];
        }
        if($image == ''){
            $image = $oldpic;
        }

        if($attribute ){
            $attribute1 = json_decode($attribute);
            dump($attribute1);
            $attribute2 = [];
            $attribute_val = [];
            foreach ($attribute1 as $k => $v){
                $attribute_key = array_keys((array)$v); // 属性表格第一栏
                $attribute_key1 = array_keys((array)$v); // 属性表格第一栏
                $attribute_val[] = array_values((array)$v); // 属性表格
                $attribute_num = $k + 1;
                $attribute2[] = (array)$v;
            }
            $attribute3 = json_encode($attribute2);

            for ($i=0;$i<6;$i++){
                array_pop($attribute_key1); // 循环去掉数组后面6个元素
            }
            $rew = '';
            foreach ($attribute_key1 as $key1 => $val1){
                $key_num = $key1;
                $rew .= "<div style='margin: 5px auto;' class='attribute_".($key1+1)." option' id='cattribute_".($key1+1)."' >";
                $rew .= "<input type='text' name='attribute_name' id='attribute_name_".($key1+1)."' placeholder='属性名称' value='".$val1."' class='input-text' readonly='readonly' style=' width:50%;background-color: #EEEEEE;' />" .
                    " - " .
                    "<input type='text' name='attribute_value' id='attribute_value_".($key1+1)."' placeholder='值' value='' class='input-text' style='width:45%' />";
                $rew .= "</div>";
            }
            $num_k = count($attribute_key1) + 1;
            $rew .= "<div style='margin: 5px auto;display:none;' class='attribute_".$num_k." option' id='cattribute_".$num_k."' >" .
                "<input type='text' name='attribute_name' id='attribute_name_".$num_k."' placeholder='属性名称' value='' class='input-text' readonly='readonly' style=' width:50%;background-color: #EEEEEE;'  onblur='leave();'/>" .
                " - " .
                "<input type='text' name='attribute_value' id='attribute_value_".$num_k."' placeholder='值' value='' class='input-text' style='width:45%' onblur='leave();'/>" .
                "</div>";
        }

        $distributors1 = '';

        /*** 报错不清除输入内容 结束 ***/

        $r=$this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置

        //获取产品类别
        $r=$this->getModel('ProductClass')->where(['sid'=>['=','0']])->fetchAll('cid,pname');
        $res = '';
        $sid=0;
        if(!empty($product_class)){
            $sids=array_filter(explode("-",$product_class));
            $sid=end($sids);
        }
        foreach ($r as $key => $value) {
            $c = '-'.$value->cid.'-';
            $checked=$value->cid==$sid?'selected':'';
            $res .= '<option  value="-'.$value->cid.'-" '.$checked.'>'.$value->pname.'</option>';
            //循环第一层
            $r_e=$this->getModel('ProductClass')->where(['sid'=>['=',$value->cid]])->fetchAll('cid,pname');
            if($r_e){
                $hx = '-----';
                foreach ($r_e as $ke => $ve){
                    $cone = $c . $ve->cid.'-';
                    $res .= '<option  value="'.$cone.'">'.$hx.$ve->pname.'</option>';
                    //循环第二层
                    $r_t=$this->getModel('ProductClass')->where(['sid'=>['=',$ve->cid]])->fetchAll('cid,pname');
                    if($r_t){
                        $hxe = $hx.'-----';
                        foreach ($r_t as $k => $v){
                            $ctow = $cone . $v->cid.'-';
                            $checked=$value->cid==$sid?'selected':'';
                           
                            $res .= '<option  value="'.$ctow.'" '.$checked.'>'.$hxe.$v->pname.'</option>';
                        }
                    }
                }
            }
        }
        // 品牌
        $r01=$this->getModel('BrandClass')->where(['status'=>['=','0']])->where("recycle=0")->fetchAll('brand_id ,brand_name');
        $brand = [];
        $brand_num = 0;
        if($r01){
            if($brand_id1){
                $r011=$this->getModel('BrandClass')->where(['brand_id'=>['=',$brand_id1]])->fetchAll('brand_id ,brand_name');
                $brand[$brand_num] = (object)array('brand_id'=> $r011[0]->brand_id,'brand_name'=> $r011[0]->brand_name);
                $brand_num++;
                $brand[$brand_num] = (object)array('brand_id'=>0,'brand_name'=>'请选择品牌');
            }else{
                $brand[$brand_num] = (object)array('brand_id'=>0,'brand_name'=>'请选择品牌');
            }

            foreach ($r01 as $k2 =>$v2){
                $brand_num++;
                $brand[$brand_num] = (object)array('brand_id'=> $v2->brand_id,'brand_name'=> $v2->brand_name);
            }
        }

        $distributors = [];
        $distributors_num = 0;

        // 运费
        $rr=$this->getModel('Freight')->fetchOrder(['add_time'=>'desc'],'id,name');
        $freight = [];
        $freight_num = 0;
        if($rr){
            if($freight1){
                $rr1=$this->getModel('Freight')->where(['id'=>['=',$freight1]])->fetchAll('id,name');
                $freight[$freight_num] = (object)array('id'=> $rr1[0]->id,'name'=> $rr1[0]->name);
                $freight_num++;
                $freight[$freight_num] = (object)array('id'=>0,'name'=>'默认模板');
            }else{
                $freight[$freight_num] = (object)array('id'=>0,'name'=>'默认模板');
            }
            foreach ($rr as $k1 => $v1){
                $freight_num++;
                $freight[$freight_num] = (object)array('id'=> $v1->id,'name'=> $v1->name);
            }
        }
        $this->assign("distributors",$distributors);

        $this->assign("uploadImg",$uploadImg);
        $this->assign("ctype",$res);
        $this->assign("brand",$brand);
        $this->assign("freight",$rr);

        $this->assign('attribute', isset($attribute3) ? $attribute3 : '');
        $this->assign('attribute_num', isset($attribute_num) ? $attribute_num : '');
        $this->assign('attribute_key', isset($attribute_key) ? $attribute_key : '');
        $this->assign('attribute_val', isset($attribute_val) ? $attribute_val : '');
        $this->assign('rew', isset($rew) ? $rew : '');

        $this->assign('product_number', isset($product_number) ? $product_number : '');
        $this->assign('product_title', isset($product_title) ? $product_title : '');
        $this->assign('subtitle', isset($subtitle) ? $subtitle : '');
        $this->assign('scan', isset($scan) ? $scan : '');
        $this->assign('s_type', isset($s_type) ? $s_type : '');
        $this->assign('keyword', isset($keyword) ? $keyword : '');
        $this->assign('weight', isset($weight) ? $weight : '');
        $this->assign('is_distribution', isset($is_distribution) ? $is_distribution : '0');
        $this->assign('is_zhekou', isset($is_zhekou) ? $is_zhekou : '0');

        $this->assign('sort', $sort ? $sort : '100');
        $this->assign('image', isset($image) ? $image : '');
        $this->assign('freight', isset($freight) ? $freight : '');
        empty($freight1)&&$freight1=0;
        $this->assign('freight1',$freight1);

        $this->assign('content', isset($content) ? $content : '');
        $this->assign('volume', $volume ? $volume : '0');
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    private function do_add($request)
    {       
      ///  dump($request->post('is_distribution'));exit();
        // 接收数据
        $attribute = $request->param('attribute'); // 属性
        $config=$this->getConfig();
        $uploadImg = $config[0]->uploadImg; // 图片路径
        $domain=$config[0]->uploadImg_domain;
        $product_number = addslashes(trim($request->param('product_number'))); // 产品编号
        $product_title = addslashes(trim($request->param('product_title'))); // 产品标题
        $subtitle = addslashes(trim($request->param('subtitle'))); // 小标题
        $scan = addslashes(trim($request->param('scan'))); // 条形码
        $product_class = addslashes(trim($request->param('product_class'))); // 产品类别
        $brand_id = addslashes(trim($request->param('brand_class'))); // 品牌
        $keyword = addslashes(trim($request->param('keyword'))); // 关键词
        $weight = addslashes(trim($request->param('weight'))); // 重量
        $s_type = $request->param('s_type'); // 显示类型
        $sort = floatval(trim($request->param('sort'))); // 排序
        $content = $this->trimContent(addslashes(trim($request->param('content'))),$domain); // 产品内容
        $image = addslashes(trim($request->param('image'))); // 产品图片
        $oldpic = addslashes(trim($request->param('oldpic'))); // 产品图片
        $distributor_id = trim($request->param('distributor_id')); //关联的分销层级id
        $is_distribution = trim($request->param('is_distribution')); //是否开启分销
        $is_zhekou = trim($request->param('is_zhekou')); //是否开启会员商品折扣
        $volume = trim($request->param('volume')); //拟定销量
        $freight = $request->param('freight'); // 运费

        $arr = json_decode($attribute,true);
        if($product_title == ''){
            $this->error('产品名称不能为空！','');
            
        }else{
            $r=$this->product_list->fetchAll('id,product_title');
            if($r){
                foreach ($r as $k => $v){
                    if($product_title == $v->product_title){
                        $this->error('产品名称重复！','');                    
                    }
                }
            }
        }
        if($scan == ''){
            $this->error('条形码不能为空！','');
            
        }else{
            $r=$this->product_list->where(['scan'=>['=',$scan]])->fetchAll('id');
            if($r){
                $this->error('条形码重复！','');
                
            }
        }
        if($product_class == '0'){
            $this->error('请选择产品类别！','');
            
        }
        if($brand_id == '0'){
            $this->error('请选择品牌！','');
            
        }
        if($keyword == ''){
            $this->error('请填写关键词！','');
            
        }
        if($weight == ''){
            $this->error('请填写商品重量！','');
            
        }else{
            if(is_numeric($weight)){
                if($weight < 0){
                    $this->error('重量不能为负数！','');
                    
                }else{
                    $weight = number_format($weight,2);
                }
            }else{
                $this->error('请填写数字！','');
                
            }
        }
        $z_num = 0;
        if(count($arr) == 0){
            $this->error('请填写属性！','');
            
        }else{
            foreach ($arr as $ke => $va){
                $z_num = $z_num+$va['数量'];
            }
        }

        if(count($s_type) == 0){
            $type = 0;
        }else{
            $type = implode(",", $s_type);
        }
        if($sort == ''){
            $this->error('排序不能没空！','');
            
        }

        if($image){
            $image = preg_replace('/.*\//','',$image); // 产品主图
        }else{
            if($oldpic){
                $image = preg_replace('/.*\//','',$oldpic);
            }else{
                $this->error('产品主图不能没空！','');
                
            }
        }
        // 发布产品
        $id1=$this->product_list->insert(['product_number'=>$product_number,'product_title'=>$product_title,'subtitle'=>$subtitle,'scan'=>$scan,'product_class'=>$product_class,'brand_id'=>$brand_id,'keyword'=>$keyword,'weight'=>$weight,'imgurl'=>$image,'sort'=>$sort,'content'=>$content,'num'=>$z_num,'s_type'=>$type,'add_date'=>nowDate(),'volume'=>$volume,'freight'=>$freight],1); // 得到添加数据的id

            $files=$request->file('imgurls');
            $file=$files['tmp_name'];
            $maxsize=1*1024*1024;//1m大小
            $imagetype='jpg,gif,png,jpeg';
            $uploaded=[];
            if($file[0]){
                $msg='';
                foreach($file as $key => $val){
                    $error='';
                    if($this->validate([['type'=>$files['type'][$key],'size'=>$files['size'][$key]]],"requires|fileType:".$imagetype."|fileSize:".$maxsize,$error)){
                    $img_type = $files["type"][$key];
                    if($img_type == "image/png"){
                        $img_type = ".png";
                    }elseif ($img_type == "image/jpeg") {
                        $img_type = ".jpg";
                    }else{
                        $img_type = ".gif";
                    }
                    $imgURL_name = time().mt_rand(1,100).$img_type;
                    //重命名结束
                    $newfile=check_file(PUBLIC_PATH.DS.$uploadImg.$imgURL_name);
                    $info = move_uploaded_file($val,$newfile);
                    if($info){
                        //循环遍历插入
                        $id2=$this->getModel('ProductImg')->insert(['product_url'=>$imgURL_name,'product_id'=>$id1,'add_date'=>nowDate()],1);
                        $uploaded[$id2]=$newfile;
                    }
                    }else
                        $msg.=$error;
                }
            }
            $r_num = 0;
            $c_num = 0;
            foreach ($arr as $ke => $va){
                $costprice = $va['成本价'];
                $yprice = $va['原价'];
                $price = $va['现价'];
                $num = $va['数量'];
                $unit = $va['单位'];
                $img = trim(strrchr($va['图片'], '/'),'/');
                for ( $i = 0;$i < 6;$i++){
                    array_pop($va);
                }
                $attribute_1 = $va;
                $attribute = serialize($attribute_1);

                $r_attribute=$this->getModel('Configure')->insert(['costprice'=>$costprice,'yprice'=>$yprice,'price'=>$price,'img'=>$img,'pid'=>$id1,'num'=>$num,'unit'=>$unit,'attribute'=>$attribute]);

                $c_num += $num;
                if($r_attribute > 0){
                    $r_num = $r_num + 1;
                }
            }
            
            if($r_num == count($arr)){
                if($c_num < 1){
                    $r_update=$this->product_list->saveAll(['status'=>1],['id'=>['=',$id1]]);
                }
                $this->success('产品发布成功！',$this->module_url."/product");
                
            }else{
                $del_rs=$this->product_list->delete($id1,'id');
                $del_rs=$this->getModel('ProductImg')->delete($id1,'product_id');
                $del_rs=$this->getModel('ProductAttribute')->delete($id1,'pid');
                foreach($uploaded as $id=>$imgfile){
                    unlink($imgfile);
                }
                $this->error('商品属性添加错误！',$this->module_url."/product");              
             }
            exit();
    }

    private function do_bargain($request)
    {
        $id = intval($request->param("id")); // 商品id
        $sx_id = intval($request->param("sx_id")); // 属性id
        $bargain_price = addslashes(trim($request->param('bargain_price'))); // 砍价初始值
        
        $r=$this->getModel('Configure')->where(['pid'=>['=',$id],'id'=>['=',$sx_id]])->fetchAll('status');
        if($r){
        $status = $r[0]->status;
        
        $rr=$this->getModel('Configure')->saveAll(['bargain_price'=>$bargain_price,'status'=>1],['pid'=>['=',$id],'id'=>['=',$sx_id]]);
        if($rr&&$status>1)
            $this->success('修改成功');
        elseif($rr&&$status==0)
           $this->success('开启成功');
        }
           $this->error('操作失败');
    }
    public function bargain(Request $request)
    {
        $request->method()=='post'&&$this->do_bargain($request);
        $id = intval($request->param("id")); // 商品id
        $sx_id = intval($request->param("sx_id")); // 属性id
        $status = intval($request->param("status")); // 状态
        $bargain_price = intval($request->param("bargain_price")); // 砍价价格

        $r_1=$this->getModel('BargainConfig')->get('1','id');
        $can_num = $r_1[0]->can_num; // 能砍的次数
        $parameter = $r_1[0]->parameter; // 每次砍价的参数
        $r=$this->getModel('Configure')->where(['pid'=>['=',$id],'id'=>['=',$sx_id]])->fetchAll('price,status');
        if($r)
        $price = $r[0]->price;
        else 
            $price=0;
        $this->assign("can_num",$can_num);
        $this->assign("parameter",$parameter);
        $this->assign("price",$price);

        if($status == 1){
            if($bargain_price){
                $product_title = addslashes(trim($request->param('product_title'))); // 产品标题
                $this->assign('id', $id);
                $this->assign('sx_id', $sx_id);
                $this->assign("product_title",$product_title);
                $this->assign("bargain_price",$bargain_price);
                return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
            }else{
                $update_rs=$this->getModel('Configure')->saveAll(['bargain_price'=>0,'status'=>0],['pid'=>['=',$id],'id'=>['=',$sx_id]]); 
                $this->error('砍价功能关闭!','');
                return;
            }
        }else{
            $product_title = addslashes(trim($request->param('product_title'))); // 产品标题

            $this->assign('id', $id);
            $this->assign('sx_id', $sx_id);
            $this->assign("product_title",$product_title);
            return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
        }
    }

    public function delimg(Request $request)
    {    
        // 接收信息
        $id = intval($request->param('id')); // 轮播图id
        $pid=intval($request->get('pid'));
        empty($id)&&exit;
        $file=$request->get('file');
        $filename=check_file(PUBLIC_PATH.DS.$file);
        if($this->getModel('productImg')->delete($id,'id')){
           if(is_file($filename)){
            unlink($filename);           
            }
         $files=$this->getModel('productImg')->fetchWhere("product_id ='".$pid."'","id,product_url");
         $data=[];
         $path=pathinfo($file,PATHINFO_DIRNAME);
         if($files){
             foreach($files as $v)
             $data[]=['id'=>$v->id,'file'=>str_replace("//","/",$path.'/'.$v->product_url)];
         }
         exit(json_encode(['data'=>$data]));
        }
        exit(json_encode(['data'=>'-1']));
    }
    public function del(Request $request)
    {            
        $admin_id = Session::get('admin_id');
        // 接收信息
        $id = $request->param('id'); // 产品id

        $num = 0;
        $id = rtrim($id, ','); // 去掉最后一个逗号
        $id = explode(',',$id); // 变成数组
        empty($id[0])&&exit(json_encode(['status'=>0,'info'=>'失败']));
        foreach ($id as $k => $v){
            $del_rs=$this->getModel('Cart')->delete($v,'Goods_id');

            $del_rs=$this->getModel('UserFootprint')->delete($v,'p_id');

            $del_rs=$this->getModel('UserCollection')->delete($v,'p_id');
            // 根据产品id，删除产品信息
            $update_rs=$this->product_list->saveAll(['recycle'=>1,'status'=>1],['id'=>['=',$v]]); 

           // $update_rs=$this->getModel('Configure')->saveAll(['recycle'=>1],['pid'=>['=',$v]]);
            $this->getModel('Configure')->delete($v,'pid');
            $update_rs=$this->getModel('ProductImg')->delete($v,'product_id');
            $update_rs=$this->getModel('ProductImg')->saveAll(['recycle'=>1],['product_id'=>['=',$v]]);

            $this->recordAdmin($admin_id,' 删除商品id为 '.$v.' 的信息',3);
        }

        $res = array('status' => '1','info'=>'成功！');
        echo json_encode($res);
        return;
    }

    
    public function modify(Request $request)
    {
       $request->method()=='post'&&$this->do_modify($request);          
        // 接收信息
        $id = intval($request->param("id")); // 产品id
        empty($id)&&exit;
        $r=$this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        // 根据产品id，查询产品产品信息
        $r=$this->product_list->get($id,'id');

        if($r){
            $product_number = $r[0]->product_number; // 产品编号
            $product_title = $r[0]->product_title; // 产品标题
            $subtitle = $r[0]->subtitle; // 副标题
            $scan = $r[0]->scan; // 条形码
            $product_class = $r[0]->product_class ; // 产品类别
            $sort = $r[0]->sort; // 排序
            $brand_class = $r[0]->brand_id ; // 产品品牌
            $keyword = $r[0]->keyword ; // 关键词
            $weight = $r[0]->weight ; // 重量
            $content = $r[0]->content; // 产品内容
            $num = $r[0]->num; //数量
            $imgurl = $r[0]->imgurl; //图片
            $s_type = $r[0]->s_type;
            $distributor_id = $r[0]->distributor_id;//分销层级id
            $is_distribution = $r[0]->is_distribution;//是否开启分销
            $volume = $r[0]->volume;//volume拟定销量
            $freight_id = $r[0]->freight;
            $is_zhekou = $r[0]->is_zhekou;
            $recycle=$r[0]->recycle;
        }
        if($recycle)
            $this->error('商品已经回收',$this->module_url.'/product');
        $arr = explode(',',$s_type);

        if (!empty($brand_class)) {
            $r01=$this->getModel('BrandClass')->where(['brand_id'=>['=',$brand_class]])->fetchAll('brand_id,brand_name');
            $brand_name = $r01[0]->brand_name ; // 产品品牌
        }

        if($freight_id != 0){
            $r_freight=$this->getModel('Freight')->where(['id'=>['=',$freight_id]])->fetchAll('id,name');
            $freight_name = $r_freight[0]->name ; // 运费规则
            $freight_list = "<option selected='selected' value='{$freight_id}'>{$freight_name}</option>";
            $freight_list .= "<option value='0'>默认模板</option>";
        }else{
            $freight_list = "<option selected='selected' value='0'>默认模板</option>";
            $r_freight=$this->getModel('Freight')->fetchOrder(['id'=>'asc'],'id,name');
            if($r_freight){
                foreach ($r_freight as $k => $v){
                    $freight_id = $v->id ; // 运费规则id
                    $freight_name = $v->name ; // 运费规则
                    $freight_list .= "<option value='{$freight_id}'>{$freight_name}</option>";
                }
            }
        }

        //绑定产品分类
        $r=$this->getModel('ProductClass')->where("recycle=0")->where(['sid'=>['=','0']])->where("recycle=0")->fetchAll('cid,pname');
        $res = '';
        foreach ($r as $key => $value) {
            $c = '-'.$value->cid.'-';
            //判断所属类别 添加默认标签
            if ($product_class == $c) {
                $res .= '<option selected="selected" value="'.$c.'">'.$value->pname.'</option>';
            }else{
                $res .= '<option  value="'.$c.'">'.$value->pname.'</option>';
            }
            //循环第一层
            $r_e=$this->getModel('ProductClass')->where("recycle=0")->where(['sid'=>['=',$value->cid]])->fetchAll('cid,pname');
            if($r_e){
                $hx = '-----';
                foreach ($r_e as $ke => $ve){
                    $cone = $c . $ve->cid.'-';
                    //判断所属类别 添加默认标签
                    if ($product_class == $cone) {
                        $res .= '<option selected="selected" value="'.$cone.'">'.$hx.$ve->pname.'</option>';
                    }else{
                        $res .= '<option  value="'.$cone.'">'.$hx.$ve->pname.'</option>';
                    }
                    //循环第二层
                    $r_t=$this->getModel('ProductClass')->where("recycle=0")->where(['sid'=>['=',$ve->cid]])->fetchAll('cid,pname');
                    if($r_t){
                        $hxe = $hx.'-----';
                        foreach ($r_t as $k => $v){
                            $ctow = $cone . $v->cid.'-';
                            //判断所属类别 添加默认标签
                            if ($product_class == $ctow) {
                                $res .= '<option selected="selected" value="'.$ctow.'">'.$hxe.$v->pname.'</option>';
                            }else{
                                $res .= '<option  value="'.$ctow.'">'.$hxe.$v->pname.'</option>';
                            }
                        }
                    }
                }
            }
        }

        //产品分类
        $r02=$this->getModel('BrandClass')->where("recycle=0")->where(['status'=>['=','0']])->fetchAll('brand_id ,brand_name');
        $imgurls = $this->getModel('ProductImg')->where("recycle=0")->get($id,'product_id');

        //查询规格数据
        $res_size = $this->getModel('Configure')->where("recycle=0")->get($id,'pid');
        if($res_size){
            $attribute = [];
            $attribute_1 = [];
            foreach ($res_size as $k => $v){
                $attribute_1 = unserialize($v->attribute); // 属性
                $attribute_1['rid']=$v->id;
                $attribute_1['成本价'] = $v->costprice;
                $attribute_1['原价'] = $v->yprice;
                $attribute_1['现价'] = $v->price;
                $attribute_1['数量'] = $v->num;
                $attribute_1['单位'] = $v->unit;
                $attribute_1['图片'] = $uploadImg . $v->img;
                $attribute[] = $attribute_1;
            }
        }
        empty($attribute)&&$this->error('attribute error',$this->module_url.'/product');
        $attribute1 = json_encode($attribute);
        $unit_arr = $attribute[0];
        $unit = $unit_arr['单位']; // 取数组最后一个元素
        unset($unit_arr['rid']);
        $attribute_key = array_keys($unit_arr); // 属性表格第一栏
        $attribute_key1 = array_keys($unit_arr); // 填写属性
        for ($i=0;$i<6;$i++){
            array_pop($attribute_key1); // 循环去掉数组后面6个元素
        }
        if($unit == ''){
            $rer = "<option value='个'>个</option>";
        }else{
            $rer = "<option value='$unit'>" .
                $unit.
                "</option>";
        }
        $rew = '';
        foreach ($attribute_key1 as $key1 => $val1){
                $rew .= "<div style='margin: 5px auto;' class='attribute_".($key1)." option' id='cattribute_".($key1)."' >";
                $rew .= "<input type='text' name='attribute_name' id='attribute_name_".($key1)."' placeholder='属性名称' value='".$val1."' class='input-text' readonly='readonly' style=' width:50%;background-color: #EEEEEE;' />" .
                    " - " .
                    "<input type='text' name='attribute_value' id='attribute_value_".($key1)."' placeholder='值' value='' class='input-text' style='width:45%' />";
                $rew .= "</div>";
            
        }
        $num_k = count($attribute_key1);
        $rew .= "<div style='margin: 5px auto;display:none;' class='attribute_".$num_k." option' id='cattribute_".$num_k."' >" .
            "<input type='text' name='attribute_name' id='attribute_name_".$num_k."' placeholder='属性名称' value='' class='input-text' readonly='readonly' style=' width:50%;background-color: #EEEEEE;'  onblur='leave();'/>" .
            " - " .
            "<input type='text' name='attribute_value' id='attribute_value_".$num_k."' placeholder='值' value='' class='input-text' style='width:45%' onblur='leave();'/>" .
            "</div>";
        $attribute_key2 = json_encode($attribute_key1,JSON_UNESCAPED_UNICODE);
        $attribute_val = [];
        foreach ($attribute as $k1 => $v1){
            $attribute_val[] = array_values($v1); // 属性表格
        }
       // $r022222=$this->getModel('DistributionGrade')->where(['is_ordinary'=>['=','0']])->fetchAll('id,sets');
        $distributors = [];
        $distributors_opt = '';

        $this->assign("is_distribution",$is_distribution);
        $this->assign("distributors_opt",$distributors_opt);
        $this->assign("volume",$volume);
        $this->assign("uploadImg",$uploadImg);
        $this->assign("attribute",$attribute);
        $this->assign("attribute1",$attribute1);
        $this->assign("rer",$rer);
        $this->assign("rew",$rew);
        $this->assign("attribute_key",$attribute_key);
        $this->assign("attribute_key2",$attribute_key2);
        $this->assign("attribute_val",$attribute_val);
        $this->assign('s_type', $arr);  //s_type
        $this->assign("ctypes",$res);
        $this->assign('id', $id);
        $this->assign('r02', $r02);//所有品牌
        $this->assign("product_class",$product_class);
        $this->assign('product_number', isset($product_number) ? $product_number : '');
        $this->assign('product_title', isset($product_title) ? $product_title : '');
        $this->assign('subtitle', isset($subtitle) ? $subtitle : '');
        $this->assign('scan', isset($scan) ? $scan : '');
        $this->assign('brand_name', isset($brand_name) ? $brand_name : '');//品牌名称
        $this->assign('sort', isset($sort) ? $sort : '');
        $this->assign('keyword', isset($keyword) ? $keyword : '');
        $this->assign('weight', isset($weight) ? $weight : '');
        $this->assign('content', isset($content) ? $content : '');
        $this->assign('num', isset($num) ? $num : '');
        $this->assign('imgurl', isset($imgurl) ? $imgurl : '');
        $this->assign('imgurls', isset($imgurls) ? $imgurls : '');
        $this->assign('imageurl_len',isset($imgurls)?sizeof($imgurls):0);
        $this->assign('freight_list', $freight_list);// 运费
        $this->assign("is_zhekou",$is_zhekou);
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    private function do_modify($request)
    {         
        $id = intval($request->param("id")); // 产品id
        $config=$this->getConfig();
        $uploadImg = $config[0]->uploadImg; // 图片路径
        $domain=$config[0]->uploadImg_domain;
        $attribute = $request->param('attribute'); // 属性
        $product_number = addslashes(trim($request->param('product_number'))); // 产品编号
        $product_title = addslashes(trim($request->param('product_title'))); // 产品标题
        $product_class = addslashes(trim($request->param('product_class'))); // 产品类别
        $subtitle = addslashes(trim($request->param('subtitle'))); // 产品副标题
        $scan = addslashes(trim($request->param('scan'))); // 条形码

        $brand_id = addslashes(trim($request->param('brand_class'))); // 品牌
        $keyword = addslashes(trim($request->param('keyword'))); // 关键词
        $weight = addslashes(trim($request->param('weight'))); // 关键词
        $s_type = $request->param('s_type'); // 显示类型
        $sort = floatval(trim($request->param('sort'))); // 排序
        $content = $this->trimContent(addslashes(trim($request->param('content'))),$domain); // 产品内容
        $image = addslashes(trim($request->param('image'))); // 产品图片
        $img_oldpic = addslashes(trim($request->param('img_oldpic'))); // 产品图片
        $imageurl_len=$request->post('imageurl_len');
        $arr = json_decode($attribute,true);
        $volume = trim($request->param('volume')); //拟定销量
        $freight = $request->param('freight'); // 运费

        if($product_title == ''){
            $this->error('产品名称不能为空！',$this->module_url."/product/modify?id=".$id."&uploadImg=".$uploadImg);
            
        }else{
            $r=$this->product_list->where(['id'=>['<>',$id],'product_title'=>['=',$product_title]])->fetchAll('product_title');
            if($r){
                $this->error('{$product_title} 已经存在，请选用其他标题进行修改！',$this->module_url."/product/modify?id=".$id."&uploadImg=".$uploadImg);
                
            }
        }
        if($scan == ''){
            $this->error('条形码不能为空！','');
            
        }else{
            $r=$this->product_list->where(['scan'=>['=',$scan],'id'=>['<>',$id]])->fetchAll('id');
            if($r){
                $this->error('条形码重复！','');
                
            }
        }
        if(empty($product_class)){
            $this->error('产品类别不能为空！','');
            
        }
        if($brand_id == ''){
            $this->error('请选择品牌！','');
            
        }
        if($keyword == ''){
            $this->error('请填写关键词！','');
            
        }
        if($weight == ''){
            $this->error('请填写商品重量！','');
            
        }else{
            if(is_numeric($weight)){
                if($weight < 0){
                    $this->error('重量不能为负数！','');               
                }else{
                    $weight = number_format($weight,2);
                }
            }else{
                $this->error('请填写数字！','');            
            }
        }
        $z_num = 0;
        if(count($arr) == 0){
            $this->error('请填写属性！','');
            
        }else{
            foreach ($arr as $ke => $va){
                $z_num = $z_num+$va['数量'];
            }
        }

        if(count($s_type) == 0){
            $type = 0;
        }else{
            $type = implode(",", $s_type);
        }
        if($sort == ''){
            $this->error('排序不能没空！',$this->module_url."/product/modify?id=".$id."&uploadImg=".$uploadImg);
            
        }
        if($image){
            $image = preg_replace('/.*\//','',$image);
            if($image != $img_oldpic){
                //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$img_oldpic));
            }
        }else{
            $image = $img_oldpic;
        }

        //五张轮播图
        $files=$request->file('imgurls');
        
        $file=$files['tmp_name'];
        $maxsize=1*1024*1024;//1m大小
        $imagetype='jpg,gif,png,jpeg';
        $uploaded=[];
        if($file[0]){
            $msg='';
            if((sizeof($file)+$imageurl_len)>5)
                $this->error('图片已经超过5张！',$this->module_url."/product/modify?id=".$id."&uploadImg=".$uploadImg);
                foreach($file as $key => $val){
                $error='';
                if($this->validate([['type'=>$files['type'][$key],'size'=>$files['size'][$key]]],"requires|fileType:".$imagetype."|fileSize:".$maxsize,$error)){
                    $img_type = $files["type"][$key];
                    if($img_type == "image/png"){
                        $img_type = ".png";
                    }elseif ($img_type == "image/jpeg") {
                        $img_type = ".jpg";
                    }else{
                        $img_type = ".gif";
                    }
                    $imgURL_name = time().mt_rand(1,100).$img_type;
                    //重命名结束
                    $newfile=check_file(PUBLIC_PATH.DS.$uploadImg.$imgURL_name);
                    $info = move_uploaded_file($val,$newfile);
                    if($info){
                        //循环遍历插入
                        $id2=$this->getModel('ProductImg')->insert(['product_url'=>$imgURL_name,'product_id'=>$id,'add_date'=>nowDate()],1);
                        $uploaded[$id2]=$newfile;
                    }
                }else
                    $msg.=$error;
            }
        }
        // 根据产品id，查询原来的数据
        $r_arr=$this->product_list->get($id,'id');
        // 根据产品id,修改产品信息
        $r_update=$this->product_list->saveAll(['product_number'=>$product_number,'product_title'=>$product_title,'scan'=>$scan,'product_class'=>$product_class,'brand_id'=>$brand_id,'keyword'=>$keyword,'weight'=>$weight,'s_type'=>$type,'num'=>$z_num,'sort'=>$sort,'content'=>$content,'imgurl'=>$image,'subtitle'=>$subtitle,'volume'=>$volume,'freight'=>$freight],['id'=>['=',$id]]);

        if($r_update ==false ){
            $rew1 = 0; // 修改失败
        }else{
            $rew1 = 1; // 修改成功
        }
        // 根据产品id,查询属性列表
        $r_zarr=$this->getModel('Configure')->where(['pid'=>['=',$id]])->fetchAll('id');
        $rid = [];
        foreach ($r_zarr as $ke1 =>$v1){
            $rid[$v1->id] = $v1->id;
        }
        $r_count = count($r_zarr) - count($arr); // 原来的属性个数 - 现在的属性个数

        $r_num = 0;
        $c_num = 0;
        foreach ($arr as $ke => $va){
            if(!empty($va['rid'])){
                $r_id = $va['rid'];
                $r_id1 = $va['rid'];
            }else{
                $r_id = '';
                $r_id1 = '';
            }
            $costprice = $va['成本价'];
            $yprice = $va['原价'];
            $price = $va['现价'];
            $num = $va['数量'];
            $c_num += $num;
            $unit = $va['单位'];
            $img = trim(strrchr($va['图片'], '/'),'/');
            for ( $i = 0;$i < 6;$i++){
                array_pop($va); // 
            }
            if(isset($va['rid']))
                unset($va['rid']);
            $attribute_1 = $va;
            $attribute = serialize($attribute_1);
            if(in_array($r_id,$rid)){
                $r_attribute=$this->getModel('Configure')->saveAll(['costprice'=>$costprice,'yprice'=>$yprice,'price'=>$price,'img'=>$img,'num'=>$num,'unit'=>$unit,'attribute'=>$attribute],['id'=>['=',$r_id]]);
                unset($rid[$r_id]);
            }else{
                $r_attribute=$this->getModel('Configure')->insert(['costprice'=>$costprice,'yprice'=>$yprice,'price'=>$price,'img'=>$img,'pid'=>$id,'num'=>$num,'unit'=>$unit,'attribute'=>$attribute]);
            }

            if($r_attribute > 0){
                $r_num = $r_num + 1;
            }
        }
        if($rid){
            foreach ($rid as $ke2 => $va2){
                $r_del =$this->getModel('Configure')->delete($va2,'id');
            }
        }

        if($r_num != count($arr)){
            $rew2 = 0;
        }else{
            $rew2 = 1;
        }

        if($rew1 == 1 || $rew2 == 1||count($uploaded)>0){
            if($c_num < 1){
                $data=["status"=>'1'];
            }else{
                $data = ['status'=>'0'];
            }
            $r_update = $this->getModel('ProductList')->save($data,$id,'id');
            /*
            $getallimgs=$this->getModel('ProductImg')->get($id,'product_id');
            //删除旧的图片
            foreach($getallimgs as $v){
                if(!in_array($v->id,array_keys($uploaded))){
                    $filename=check_file(PUBLIC_PATH.DS.$uploadImg.DS.$v->product_url);
                    is_file($filename)&&unlink($filename);
                    $this->getModel('ProductImg')->delete($v->id,'id');                   
                }
            }
            */
            $this->success('产品修改成功！',$this->module_url."/product");
        }else{
            foreach ($r_arr as $k_arr => $v_arr){
                $data= ['product_title'=>$v_arr->product_title,'product_class'=>$v_arr->product_class,'brand_id'=>$v_arr->brand_id,
'keyword'=>$v_arr->keyword,'s_type'=>$v_arr->s_type,'num'=>$v_arr->num,'sort'=>$v_arr->sort,
                    'content'=>$v_arr->content,'imgurl'=>$v_arr->imgurl];
            }
            $r_y = $this->getModel('ProductList')->save($data,$id,'id');
            foreach($uploaded as $id=>$imgfile){
                unlink($imgfile);
                $this->getModel('ProductImg')->delete($id,'id');
            }
            $this->error('未知原因，产品修改失败！',$this->module_url."/product?id=".$id."&uploadImg=".$uploadImg);           
        }
        return;
    }

    
    public function num(Request $request)
    {
                
        $status = addslashes(trim($request->param('status'))); // 状态
        $product_title = addslashes(trim($request->param('product_title'))); // 标题

        $pageto = $request -> param('pageto');
        // 导出
        $pagesize = $request -> param('pagesize');
        $pagesize = $pagesize ? $pagesize:'10';
        // 每页显示多少条数据
        $page = $request -> param('page');

        // 页码
        if($page){
            $start = ($page-1)*$pagesize;
        }else{
            $start = 0;
        }
        $r1=$this->getModel('ProductConfig')->get('1','id');
        if($r1){
            $config = unserialize($r1[0]->config);
            $min_inventory = $config['min_inventory'];
        }else{
            $min_inventory = 0;
        }
        $condition = " a.recycle = 0 and c.num <= ".$min_inventory;
        
        if($product_title != ''){
            $condition .= " and a.product_title like %$product_title%";
        }

        $sql = "select a.id from lkt_product_list AS a  LEFT JOIN lkt_configure AS c ON a.id = c.pid where $condition and a.recycle = 0 and c.num <= '$min_inventory' order by a.sort,c.id ";

        $sql = "select a.id,a.product_title,a.imgurl,a.sort,a.add_date,a.status,c.id as attribute_id,c.price,c.num,c.unit,c.img,c.attribute from lkt_product_list AS a  LEFT JOIN lkt_configure AS c ON a.id = c.pid where $condition and a.recycle = 0 and c.num <= '$min_inventory' order by a.sort,c.id limit $start,$pagesize ";
        $r = $this->product_list->alias('a')->field("a.id,a.product_title,a.imgurl,a.sort,a.add_date,a.status,c.id as attribute_id,c.price,c.num,c.unit,c.img,c.attribute")
        ->where($condition)->join("configure c","a.id=c.pid","left")->order('a.sort,c.id')->paginator($pagesize);
        $list = [];
        if($r) {
            $res = array();
            foreach ($r as $key => $value) {
                $rew = '[';
                $attribute_2 = unserialize($value->attribute); // 属性
                if(!empty($attribute_2)){
                foreach ($attribute_2 as $k => $v){
                    $rew .= ' ' . $v . ' ';
                }
                }
                $rew .= ']';
                $value->rew = $rew;
                $list[$key] = $value;
            }
        }

        $url = $this->module_url."/product/num?product_title=".urlencode($product_title)."&pagesize=".urlencode($pagesize);
//        $pages_show = $pager->multipage($url,ceil($total/$pagesize),$page, $para = '');
        $pages_show = $r->render();
        
        $uploadImg=Session::get('uploadImg');
        if(empty($uploadImg)){
        $r=$this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        }

        $this->assign("uploadImg",$uploadImg);
        $this->assign("product_title",$product_title);
        $this->assign("list",$list);
        $this->assign('pages_show', $pages_show);
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function operation(Request $request)
    {               
        $admin_id = Session::get('admin_id');
        // 接收信息
        $id = $request->param('id'); // 产品id
        $type = $request->param('type');
        $id = rtrim($id, ','); // 去掉最后一个逗号
        $id = array_filter(explode(',',$id)); // 变成数组
        $count=0;
        try{
        foreach ($id as $k => $v){
            $r=$this->product_list->where(['id'=>['=',$v]])->fetchAll('s_type');
            $s_type1 = $r[0]->s_type;
            $s_type = explode(',',$s_type1);
            if($type == 1 || $type == 2){
                if($type == 1){
                    $status = 0;
                }else{
                    $status = 1;
                }
               $data=['status'=>$status];
                $this->recordAdmin($admin_id,' 修改商品id为 '.$v.' 的状态 ',2);
            }else if($type >= 3) {
                if ($type == 3) {
                    $add_type = 1; // 新品
                    if(in_array($add_type,$s_type)){ // 存在
                        $data=['s_type'=>$s_type1];
                    }else{ // 不存在
                        $s_type2 = implode(',',array_merge($s_type, (array)$add_type));
                        $data=['s_type'=>$s_type2];
                    }
                } else if ($type == 4) {
                    $del_type = 1; // 取消新品
                    if(in_array($del_type,$s_type)){ // 存在
                        foreach ($s_type as $key=>$value){
                            if ($value == $del_type){
                                unset($s_type[$key]);
                            }
                        }
                        $s_type3 = implode(',',$s_type);
                        $data=['s_type'=>$s_type3];
                    }
                } else if ($type == 5) {
                    $add_type = 2; // 热销
                    if(in_array($add_type,$s_type)){ // 存在
                        $data=['s_type'=>$s_type1];
                    }else{ // 不存在
                        $s_type2 = implode(',',array_merge($s_type, (array)$add_type));
                        $data=['s_type'=>$s_type2];
                    }
                } else if ($type == 6) {
                    $del_type = 2; // 取消热销
                    if(in_array($del_type,$s_type)){ // 存在
                        foreach ($s_type as $key=>$value){
                            if ($value == $del_type){
                                unset($s_type[$key]);
                            }
                        }
                        $s_type3 = implode(',',$s_type);
                        $data=['s_type'=>$s_type3];
                    }
                } else if ($type == 7) {
                    $add_type = 3; // 推荐
                    if(in_array($add_type,$s_type)){ // 存在
                        $data=['s_type'=>$s_type1];
                    }else{ // 不存在
                        $s_type2 = implode(',',array_merge($s_type, (array)$add_type));
                        $data=['s_type'=>$s_type2];
                    }
                } else if ($type == 8) {
                    $del_type = 3; // 取消推荐
                    if(in_array($del_type,$s_type)){ // 存在
                        foreach ($s_type as $key=>$value){
                            if ($value == $del_type){
                                unset($s_type[$key]);
                            }
                        }
                        $s_type3 = implode(',',$s_type);
                        $data=['s_type'=>$s_type3];
                    }
                }
                $this->recordAdmin($admin_id,' 修改商品id为 '.$v.' 的类型 ',2);
            }
            if($this->product_list->save($data,$v,'id'))
                $count++;
        }
        }catch(\Exception $e){
            dump($e);
            exit(json_encode(['status'=>0,'info'=>$e->getMessage().' '.$e->getLine()]));
        }
       if($count){
        $res = array('status' => '1','info'=>'操作成功！');
        echo json_encode($res);
       }else 
           exit(json_encode(['status'=>0,'info'=>'失败']));
          
    }

    private function do_see($request)
    {
        $id=intval($request->post('id'));
        $attribute_id=intval($request->post('attribute_id'));
        $num=intval($request->post('num'));
        if(empty($id)){
           exit('-1');
        }
        if($this->getModel('Configure')->save(['num'=>$num],$attribute_id,'id')){
            $pnum=$this->product_list->get($id,'id','num')[0]->num+$num;
           if($this->product_list->save(['num'=>$pnum],$id,'id'))  
             exit('1');
           else 
               exit('-1');
        }
    }
    public function see(Request $request)
    {
         $request->method()=='post'&&$this->do_see($request); 
        $id = addslashes(trim($request->param('id'))); // 产品id
        $product_title = addslashes(trim($request->param('product_title'))); // 标题
        $url = addslashes(trim($request->param('url'))); // 路径

        ($uploadImg=Session::get('uploadImg'))||$uploadImg=$request->param('uploadImg');
        if(empty($uploadImg)){
        $r=$this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        }

        $r=$this->getModel('Configure')->get($id,'pid');
        if($r){
            foreach ($r as $k =>$v){
                $attribute_3['rid'] = $v->id;
                $attribute_2 = unserialize($v->attribute); // 属性
                $attribute = array_merge ($attribute_3,$attribute_2);
                $attribute['成本价'] = $v->costprice;
                $attribute['原价'] = $v->yprice;
                $attribute['现价'] = $v->price;
                $attribute['数量'] = $v->num;
                $attribute['单位'] = $v->unit;
                $attribute['图片'] = $v->img;

                $attribute_key = array_keys($attribute); // 属性表格第一栏
                $arr[] =  $attribute;
            }
        }
        $this->assign("uploadImg",$uploadImg);
        $this->assign("id",$id);
        $this->assign("product_title",$product_title);
        $this->assign("url",$url);
        $this->assign("attribute_key",$attribute_key);
        $this->assign("attribute_value",$arr);

      return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function shelves(Request $request)
    {
        
        
        $admin_id = Session::get('admin_id');

        $id = intval($request->param("id")); // 商品id
        $url = $request->param("url"); // 路径

        $r=$this->product_list->where(['id'=>['=',$id]])->fetchAll('status');
        if($r[0]->status == 0){
            $rr=$this->product_list->saveAll(['status'=>1],['id'=>['=',$id]]);
            if($rr > 0){
                $this->recordAdmin($admin_id,' 商品id为 '.$id.' 下架成功',3);

                $this->success('下架成功！',$this->module_url."/product/$url");
                return;
            }else{
                $this->recordAdmin($admin_id,' 商品id为 '.$id.' 下架失败',3);

                $this->error('下架失败！',$this->module_url."/product/$url");
                return;
            }
        }else{
            $rr=$this->product_list->saveAll(['status'=>0],['id'=>['=',$id]]);
            if($rr > 0){
                $this->recordAdmin($admin_id,' 商品id为 '.$id.' 上架成功',3);

                $this->success('上架成功！',$this->module_url."/product/$url");
                return;
            }else{
                $this->recordAdmin($admin_id,' 商品id为 '.$id.' 上架失败',3);

                $this->error('上架失败！',$this->module_url."/product/$url");
                return;
            }
        }
    }

    public function recycle(Request $requst)
    {
        $id=$requst->param('id');
        if(!empty($id)){
            $update_rs=$this->product_list->saveAll(['recycle'=>0,'status'=>1],['id'=>['=',$id]]);
            
            $update_rs=$this->getModel('Configure')->saveAll(['recycle'=>0],['pid'=>['=',$id]]);
            
            $update_rs=$this->getModel('ProductImg')->saveAll(['recycle'=>0],['product_id'=>['=',$id]]); 
        }
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