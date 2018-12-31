<?php
namespace app\api\controller;
use core\Request;

class Index extends Api
{

    function __construct()
    {  
        parent::__construct();
    }
    public function index (Request $request)
    {
         
        // 查询系统参数
        $r_1=$this->getConfig();
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置 
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        $title = $r_1[0]->company;
        $logo = $img . $r_1[0]->logo;
        // 查询轮播图,根据排序、轮播图id顺序排列
        $r=$this->getModel('Banner')->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
        $banner = array();
        foreach ($r as $k => $v) {
            $result = array();
            $result['id'] = $v->id; // 轮播图id
            $result['image'] = $img . $v->image; // 图片
            $result['url'] = $v->url; // 链接
            $banner[] = $result;
            unset($result); // 销毁指定变量
        }
        
        $shou = [];
        $r_t=$this->getModel('IndexPage')->fetchOrder(['sort'=>'desc'],'*');
        
        foreach ($r_t as $k => $v) {
            if ($v->type == 'img') {
                $imgurl = $img . $v->image;
                $shou[$k] = array(
                                'id' => $v->id,'url' => $v->url,'imgurl' => $imgurl
                );
            } else {
                $ttcid = $v->url;
                //$sql_cs = "select a.id,a.product_title,a.volume,min(c.price) as price,c.yprice,a.imgurl,c.name from lkt_product_list AS a right JOIN lkt_configure AS c ON a.id = c.pid 
//where a.product_class like '%-$ttcid-%' and a.status = 0 and a.num >0 group by c.pid  order by a.sort desc limit 0,10";
                $r_cs = $this->getModel('productList')->alias('a')->join("configure c","a.id=c.pid",'right')
                ->where("a.product_class like '%-".$ttcid."-%' and a.status = 0 and a.num >0")
                ->where(['a.recycle'=>['=','0']])
                ->group('c.pid')->order(["a.sort"=>"desc"])->limit("0,10")
                ->field("c.pid,a.id,a.product_title,a.volume,min(c.price) as price,c.yprice,a.imgurl,c.name")
                ->select();
               // dump($r_cs,$ttcid);
                $cproduct = [];
                if ($r_cs) {
                    foreach ($r_cs as $keyc => $valuec) {
                        $valuec->imgurl = $img . $valuec->imgurl;
                        $cproduct[$keyc] = $valuec;
                    }
                    $shou[$k] = $cproduct;
                }
            }
        } 
        ;
        $r_t=$this->getModel('ProductList')->alias('a')
        ->join('configure c','a.id=c.pid','RIGHT')
        ->where(['a.distributor_id'=>['>','0'],'a.status'=>['=','0'],'a.num'=>['>','0'],'a.recycle'=>['=','0']])
        ->order(['a.sort'=>'desc'])
        ->fetchGroup('c.pid','c.pid,a.id,a.product_title,a.volume,min(c.price) as price,c.yprice,a.imgurl,c.name,a.distributor_id',"0,20");
        if ($r) {
            $sort = $r[0]->sort;
        } else {
            $sort = 0;
        }
        // 查询用户等级判断是否升级
        $distribus = [];
        // 列出等级关系
        $distributor = [];
        
        // 查询商品并分类显示返回JSON至小程序
        ;
        $r_c=$this->getModel('ProductClass')->where(['sid'=>['=','0'],'recycle'=>['=','0']])->fetchOrder(['sort'=>'desc'],'cid,pname');
        $twoList = [];
        foreach ($r_c as $key => $value) {
            $r_e=$this->getModel('ProductClass')->where(['sid'=>['=',$value->cid],'recycle'=>['=','0']])->fetchOrder(['sort'=>'desc'],'cid,pname,img',"0,10");
            $icons = [];
            if ($r_e) {
                foreach ($r_e as $ke => $ve) {
                    $imgurl = $img . $ve->img;
                    $icons[$ke] = array(
                                        'id' => $ve->cid,'name' => $ve->pname,'img' => $imgurl
                    );
                }
            } else {
                $icons = [];
            }
            
            $ttcid = $value->cid;
            
           // $sql_s = "select a.id,a.product_title,a.volume,min(c.price) as price,c.yprice,a.imgurl,c.name from lkt_product_list AS a RIGHT JOIN lkt_configure AS c ON a.id = c.pid where a.product_class like '%-$ttcid-%' and a.status = 0 and a.num >0 group by c.pid  order by a.sort desc limit 0,10";
            $r_s = $this->getModel('productList')->alias('a')->join("configure c","a.id=c.pid",'right')
            ->where("a.product_class like '%-".$ttcid."-%' and a.status = 0 and a.num >0")
            ->where(['a.recycle'=>['=','0']])
            ->group('c.pid')->order(["a.sort"=>"desc"])->limit("0,10")
            ->field("c.pid,a.id,a.product_title,a.volume,min(c.price) as price,c.yprice,a.imgurl,c.name")
            ->select();
            $product = [];
            
            $r_s = empty($r_s) ? [] : ($r_s ? $r_s : []);
            
            foreach ($r_s as $k => $v) {
                $imgurl = $img . $v->imgurl;
                $pid = $v->id;
                $price = $v->yprice;
                $price_yh = $v->price;
                $product[$k] = array(
                                    'id' => $v->id,'name' => $v->product_title,'price' => $price,'price_yh' => $price_yh,'imgurl' => $imgurl,'volume' => $v->volume
                );
            }
            $twoList['0'] = array(
                                'id' => '0','name' => '首页','count' => 1,'twodata' => $shou,'distributor' => $distributor
            );
            $twoList[$key + 1] = array(
                                    'id' => $value->cid,'name' => $value->pname,'count' => 1,'twodata' => $product,'icons' => $icons
            );
        }
        $r=$this->getModel('BackgroundColor')->where(['status'=>['=','1']])->fetchAll();
        if ($r) {
            $bgcolor = $r[0]->color;
        } else {
            $bgcolor = '#FF6347';
        }
        
        // 查询插件表里,状态为启用的插件
        $plug=$this->getModel('PlugIns')->where(['status'=>['=','1'],'type'=>['=','0'],'software_id'=>['=','3']])->fetchAll('*');
        if ($plug) {
            foreach ($plug as $k => $v) {
                $v->image = $img . $v->image;
                if ($v->name == '钱包') {
                    unset($plug[$k]);
                }
                if ($v->name == '积分') {
                    unset($plug[$k]);
                }
                
                if (strpos($v->name, '红包') !== false) {
                    if (!empty($rfhb)) {
                        unset($plug[$k]);
                    }
                }
            }
        }
        $pmd = [];
        $notice = [];        
        $res_notice=$this->getModel('SetNotice')->fetchOrder(['time'=>'desc'],'id,name');
        if ($res_notice) {
            foreach ($res_notice as $key => $value) {
                $notice[$key] = array(
                                    'url' => $value->id,'title' => $value->name
                );
            }
        }
       //dump($twoList[0]['twodata'][1]);
        echo json_encode(array(
                                'banner' => $banner,'notice' => $notice,'djname' => '','twoList' => $twoList,'bgcolor' => $bgcolor,'plug' => $plug,'title' => $title,'logo' => $logo,'list' => $pmd
        ),JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function get_more (Request $request)
    {
        
        
        $paegr = trim($request->param('page')); // '显示位置'
        $index = trim($request->param('index')); // '分类ID'
                                                        
        // 查询系统参数
        $r_1=$this->getConfig();
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
        $start = 10 * $paegr;
        $end = 10;
        // 查询商品并分类显示返回JSON至小程序
        if (! $index) {
            echo json_encode(array(
                                    'prolist' => [],'status' => 0
            ));
            exit();
        } else {
            // 查询商品并分类显示返回JSON至小程序
           // $sql_t = "select a.id,a.product_title,a.volume,min(c.price) as price,c.yprice,a.imgurl,c.name from lkt_product_list AS a RIGHT JOIN lkt_configure AS c ON a.id = c.pid where a.num >0 and a.status = 0 and a.product_class like '%-$index-%' group by c.pid  order by a.sort desc limit $start,$end";
            $r_s = $this->getModel('productList')->alias('a')->join("configure c","a.id=c.pid",'right')
            ->where("a.product_class like '%-".$index."-%' and a.status = 0 and a.num >0")
            ->group('c.pid')->order(["a.sort"=>"desc"])->limit("$start,$end")
            ->field("a.id,a.product_title,a.volume,min(c.price) as price,c.yprice,a.imgurl,c.name")
            ->select();
            $product = [];
            $product = [];
            if ($r_s) {
                foreach ($r_s as $k => $v) {
                    $imgurl = $img . $v->imgurl; /* end 保存 */
                    $pid = $v->id;
                    $price = $v->yprice;
                    $price_yh = $v->price;
                    $product[$k] = array(
                                        'id' => $v->id,'name' => $v->product_title,'price' => $price,'price_yh' => $price_yh,'imgurl' => $imgurl,'volume' => $v->volume
                    );
                }
                echo json_encode(array(
                                        'prolist' => $product,'status' => 1
                ));
                exit();
            } else {
                echo json_encode(array(
                                        'prolist' => $product,'status' => 0
                ));
                exit();
            }
        }
    }

    public function draw (Request $request)
    {
               
        $r02=$this->getModel('PlugIns')->where(['id'=>['=','4']])->fetchAll('status');
        $type = $r02[0]->status;
        $banner = '';
        
        if ($type == 1) {
            // 参加抽奖商品
            $datatime = date("Y-m-d H:m:s", time());
            $r01 = $this->getModel('draw')->alias('a')->join("product_list b","a.draw_brandid=b.id")
            ->where("b.num >0 and a.start_time <= '" . $datatime . "' and a.end_time >= '" . $datatime . "'")
          ->field("b.id,b.product_title,b.volume,b.imgurl,a.draw_brandid,a.start_time,a.end_time,a.price as price11")->select();
            foreach ($r01 as $key => $value) {
                $draw_brandid = $value->id;
                $r01[$key]->imgurl=$this->getUploadImg(1).$value->imgurl;           
                $r002=$this->getModel('Configure')->where(['num'=>['>','0'],'pid'=>['=',$draw_brandid]])->fetchAll('yprice');
                // var_dump($r01,$value,$sql01,$r002,$sql002);
                $r01[$key]->yprice = $r002[0]->yprice;
            }
        } else {
            $r01 = "1";
        }
        echo json_encode(array(
                                'r01' => $r01,'banner' => $banner,'status' => 1
        ));
        exit();
    }

}