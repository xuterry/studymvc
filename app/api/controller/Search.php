<?php
namespace app\api\controller;
use core\Request;
class Search extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function index (Request $request)
    {
             
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
        
        // 查询商品并分类显示返回JSON至小程序
        $r_c=$this->getModel('ProductClass')
        ->where("recycle=0")       
        ->where(['sid'=>['=','0']])->fetchOrder(['sort'=>'desc'],'cid,pname,img,bg');
        $twoList = [];
        $abc = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $st = 0;
        $icons = [];
        if ($r_c) {
            foreach ($r_c as $key => $value) {
                $r_e=$this->getModel('ProductClass')
                ->where("recycle=0")
                ->where(['sid'=>['=',$value->cid]])->fetchOrder(['sort'=>'asc'],'cid,pname,img');
                $son = [];
                if ($r_e) {
                    foreach ($r_e as $ke => $ve) {
                        $imgurl = $img . $ve->img;
                        $son[$ke] = array(
                                        'child_id' => $ve->cid,'name' => $ve->pname,'picture' => $imgurl
                        );
                    }
                    $type = true;
                } else {
                    $type = false;
                }
                if ($value->bg) {
                    $cimgurl = $img . $value->bg;
                } else {
                    $cimgurl = '';
                }
                
                $icons[$key] = array(
                                    'cate_id' => $value->cid,'cate_name' => $value->pname,'ishaveChild' => $type,'children' => $son,'cimgurl' => $cimgurl
                );
            }
        }
        
        $res=$this->getModel('Hotkeywords')->fetchAll('keyword');
        if ($res) {
            foreach ($res as $k => $v) {
                $res[$k] = $v->keyword;
            }
        }
        
        echo json_encode(array(
                                'status' => 1,'List' => $icons,'hot' => $res
        ));
        exit();
    }

    public function search (Request $request)
    {
            
        $keyword = trim($request->param('keyword')); // 关键词
        $num = trim($request->param('num')); // '次数'
        $select = trim($request->param('select')); // 选中的方式 0 默认 1 销量 2价格
        $sort = trim($request->param('sort')); // 排序方式 1 asc 升序 0 desc 降序
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
        
        if ($select == 0) {
            $select = 'a.add_date';
        } elseif ($select == 1) {
            $select = 'a.volume';
        } else {
            $select = 'price';
        }
        if ($sort) {
            $sort = ' asc ';
        } else {
            $sort = ' desc ';
        }
        
        // 查出所有产品分类
        $res=$this->getModel('ProductClass')->fetchAll('pname');
        if ($res) {
            foreach ($res as $key => $value) {
                $res[] = $value->pname;
            }
        }
        
        // 判断如果关键词是产品分类名称，如果是则查出该类里所有商品
        if (in_array($keyword, $res)) {
            $type = 0;
            $keyword = addslashes($keyword);
            $a=$this->getModel('ProductClass')->where(['pname'=>['=',$keyword]])->fetchAll('cid');
            if (! empty($a)) {
                $cid = $a['0']->cid; // 分类id
            }
            $start = 10 * ($num - 1);
            $end = 10;
            $data=$this->getModel('ProductList')->alias('a')->join('configure c','a.id=c.pid','RIGHT')
            ->where(['a.product_class'=>['like',"%$cid%"],'a.status'=>['=','0']])
            ->where("recycle=0")           
            ->order([$select=>$sort])
            ->fetchGroup('c.pid','a.id,product_title,a.volume,a.s_type,c.id as cid,c.yprice,c.img,c.name,c.color,min(c.price) as price',"$start,$end");
        } else { // 如果不是商品分类名称，则直接搜产品
            $type = 1;
            $keyword = addslashes($keyword);
            $data=$this->getModel('ProductList')->alias('a')->join('configure c','a.id=c.pid','RIGHT')
            ->where(['a.product_title'=>['like',"%$keyword%"],'a.status'=>['=','0']]) 
            ->where("recycle=0")         
            ->order([$select=>$sort])->fetchGroup('c.pid','a.id,a.product_title,a.product_class,a.volume,a.s_type,c.id as cid,c.yprice,c.img,c.name,c.color,min(c.price) as price');
        }
        if (! empty($data)) {
            $product = array();
            foreach ($data as $k => $v) {
                $imgurl = $img . $v->img; /* end 保存 */
                $names = ' ' . $v->name . $v->color;
                if ($type == 1) {
                    $cid = $v->product_class;
                }
                if ($v->name == $v->color || $v->name == '默认') {
                    $names = '';
                }
                $product[$k] = array(
                                    'id' => $v->id,'name' => $v->product_title . $names,'price' => $v->yprice,'price_yh' => $v->price,'imgurl' => $imgurl,'size' => $v->cid,'volume' => $v->volume,'s_type' => $v->s_type
                );
            }
            echo json_encode(array(
                                    'list' => $product,'cid' => $cid,'code' => 1,'type' => $type
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '没有更多商品了！'
            ));
            exit();
        }
    }

    public function listdetail (Request $request)
    {
        
        
        $id = trim($request->param('cid')); // '分类ID'
        $paegr = trim($request->param('page')); // '页面'
        $select = trim($request->param('select')); // 选中的方式 0 默认 1 销量 2价格
        if ($select == 0) {
            $select = 'a.add_date';
        } elseif ($select == 1) {
            $select = 'a.volume';
        } else {
            $select = 'price';
        }
        
        $sort = trim($request->param('sort')); // 排序方式 1 asc 升序 0 desc 降序
        if ($sort) {
            $sort = ' asc ';
        } else {
            $sort = ' desc ';
        }
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
        
        if (! $paegr) {
            $paegr = 1;
        }
        $start = ($paegr - 1) * 10;
        $end = 10;
        $bg = '';
        $r_c=$this->getModel('ProductClass')->where(['cid'=>['=',$id]])->fetchAll('bg');
        if ($r_c) {
            $bg = $img . $r_c[0]->bg;
        }
        
        $r=$this->getModel('ProductList')->alias('a')->join('configure c','a.id=c.pid','RIGHT')
        ->where(['a.product_class'=>['like',"%$id%"],'c.num'=>['>','0'],'a.status'=>['=','0']])
        ->where("recycle=0")      
        ->order([$select=>$sort])
        ->fetchGroup('c.pid','a.id,a.product_title,volume,min(c.price) as price,c.yprice,c.img,c.name,c.color,c.size,a.s_type,c.id AS sizeid',"$start,$end");
        
        if ($r) {
            $product = [];
            foreach ($r as $k => $v) {
                $imgurl = $img . $v->img; /* end 保存 */
                $names = ' ' . $v->name . $v->color;
                if ($v->name == $v->color || $v->name == '默认') {
                    $names = '';
                }
                $product[$k] = array(
                                    'id' => $v->id,'name' => $v->product_title . $names,'price' => $v->yprice,'price_yh' => $v->price,'imgurl' => $imgurl,'size' => $v->sizeid,'volume' => $v->volume,'s_type' => $v->s_type
                );
            }
            echo json_encode(array(
                                    'status' => 1,'pro' => $product,'bg' => $bg
            ));
            exit();
        } else {
            echo json_encode(array(
                                    'status' => 0,'err' => '没有了！'
            ));
            exit();
        }
    }

}