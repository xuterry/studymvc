<?php
namespace app\admin\controller;

use core\Request;

use app\admin\model\SoftwareJifen;
use app\admin\model\IndexPage;
use app\admin\model\Config;
use app\admin\model\ProductClass;

class Software extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        
        $r = $this->getModel('config')->get(1,'id');
        
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        
        $upload_file = $r[0]->upload_file; // 文件上传位置
                                                 
        // 查询插件表
                
        $r = $this->getModel('software')->fetchAll();
        
        $array1 = array(
                        '2' => '商业版','1' => '运营版','0' => '开源版'
        );
        
        $array2 = array(
                        '0' => '小程序','1' => 'APP','2' => '后台'
        );
        
        $software = [];
        
        foreach ($r as $key => $value) {
            
            foreach ($array2 as $k => $v) {
                
                if ($value->type == $k) {
                    
                    $value->type = $array2[$k];
                }
                
                if ($value->software_version == $k) {
                    
                    $value->software_version = $array1[$k];
                }
                
                $software[$key] = $value;
            }
        }
        
        $this->assign("uploadImg", $uploadImg);
        
        $this->assign("upload_file", $upload_file);
        
        $this->assign("list", $software);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function add(Request $request)
    {
        $request->method()=='post'&&$this->do_add($request);
        $software=$this->getModel('software');
        $r =$software->where(['type'=>['=','2'],'software_version'=>['=','2']])->fetchOrder(['id','desc']);
        $ops = '';
        if ($r) {
            foreach ($r as $key => $value) {
                $edition = $value->edition;
                $ops .= " <option value='$edition'>$edition</option>";
            }
        }
        
        $this->assign("ops", $ops);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }
    private function selectType($request)
    {
        $select_type = addslashes(trim($request->param('type')));
        $select_version = addslashes(trim($request->paramr('select_version')));
        $r=$this->getModel('software')->where(['type' =>['=',$select_type],'software_version'=>['=',$select_version]])->orderfetchOrder(['id','desc']);
        $ops = '';
        if($r){
            foreach ($r as $key => $value) {
                $edition = $value->edition;
                $ops .= " <option value='$edition'>$edition</option>";
            }
        }
        $ops .= '<option value="">无</option>';
        echo $ops;
        exit;
    }
    private function do_add($request)
    {
        $m = addslashes(trim($request->param('m')));     
        if ($m) {           
            $this->selectType($request);          
            exit();
        }       
        // 接收数据       
        $name = addslashes(trim($request->param('name'))); // 软件名称      
        $image = addslashes(trim($request->param('image'))); // 软件图标        
        $type = addslashes(trim($request->param('software_type'))); // 软件类型       
        $edition = addslashes(trim($request->param('edition'))); // 软件版本       
        $edition_text = addslashes(trim($request->param('edition_text'))); // 版本介绍        
        $old_version = addslashes(trim($request->param('old_version'))); // 老版本      
        $software_version = $request->param('software_version');
        $sw=$this->getModel('software');
        if ($name) {        
            // 根据软件名称查询软件表                       
            $r=$sw->where(['type' =>['=',$type],'name'=>['=',$name]])->orderfetchOrder(['id','desc']);         
            if ($r) {             
                $id = $r[0]->id; // 软件id           
                if ($edition == $r[0]->edition) {              
                    $this->error('版本" . $edition . "已存在!','');exit;
                } else if ($edition < $r[0]->edition) {
                    
                    $this->error('版本" . $edition . "低于最新版本!','');exit();
                } 
            } 
        } else {        
            $this->error('软件名称不能为空!','');exit;
        }
        
        if ($image) {
            
            $image = preg_replace('/.*\//', '', $image);
        } else {
            
            $this->error('首页插件图标不能为空！','');exit;
        }
        $file=$request->file('edition_url');
        $msg='';
        if ($file['name'] == "") {           
            $this->error('请上传软件包！','');exit();
        } else {
            if($this->validate([$file],"requires",$msg)){          
            $rr = $this->getConfig();        
            $upload_file = $rr[0]->upload_file; // 文件上传位置                                          
            // 将临时文件复制到upload_image目录下
            $edition_url = $file['tmp_name'];  
            $edition_url_name = substr($file['name'], 0, strpos($file['name'], '.')) . time() . '.' . $edition . (strrchr($file['name'], '.'));
            
            if ($type == 2) {
                
                $edition_url_name = 'lkt_update_' . $edition . '.zip';
            }
            
            move_uploaded_file($edition_url, check_file(PUBLIC_PATH.DS.$upload_file.DS.$edition_url_name));
        }else 
            $this->error($msg,'');
        }
        
        // 添加软件
        
        $data= $this->parseSql("(name,image,type,edition,software_version,edition_url,edition_text,add_time) " . 
        "values('$name','$image','$type','$edition','$software_version','$edition_url_name','$edition_text',".nowDate(),'insert');
        
        $r = $sw->insert($data);
        
        if ($r == false) {
            
            $this->error('未知原因，添加失败！','');
        } else {
            
            if (! empty($old_version)) {
                $download_url = check_file(PUBLIC_PATH.DS.$rr[0]->uploadImg_domain . '/' . substr($upload_file, 3) . '/' . $edition_url_name);
                $data= $this->parseSql("(`type`,`software_version`, `name`, `version`, `sid`, `old_version`, `download_url`, `version_abstract`, `release_time`)VALUES ('$type','$software_version','$name','$edition','$r','$old_version','$download_url','$edition_text',".nowDate(),'insert');
                $rs = $this->getModel('edition')->insert($data);
            }
            
            $this->success('添加成功！',$this->module_url."/software");
        }
      
        exit();
    }

    public function del(Request $request)
    {
        
        // 接收信息
        $id = intval($request->param('id')); // 插件id
        
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片路径
        
        $upload_file = addslashes(trim($request->param('upload_file'))); // 图片路径
        if(empty($upload_file)){
            $config_rs=$this->getModel('config')->get('1','id');
            if($config_rs){
            $upload_file=$config_rs[0]->upload_file;
            $uploadImg=$config_rs[0]->uploadImg;
            }
        }
        // 根据插件id,查询插件
        
        $sw= $this->getModel('software');
        if(!empty($id)){
        $r = $sw->get($id,'id');      
        $image = $r[0]->image;
                
        $edition_url = $r[0]->edition_url;
        
        @unlink(check_file(PUBLIC_PATH.$uploadImg . $image));
        
        @unlink(check_file(PUBLIC_PATH.$upload_file . '/' . $r[0]->edition_url));
        
        // 根据轮播图id，删除轮播图信息
        if($sw->delete($id,'id')){
          exit('1');
        }
        }
        exit('-1');
    }

    public function jifen(Request $request)
    {
        $request->method()=='post'&&$this->do_jifen($request);
        $r = (new SoftwareJifen($this->db_config))->get(1,'id');
        empty($r)&&$r=1;
        $this->assign("r", $r);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_jifen($request)
    {
        $jifennum = $request->param('jifennum');
        $switch = $request->param('switch');
        $rule = trim($request->param('rule'));
        if ($jifennum >= 0) {
            $jifen=new SoftwareJifen($this->db_config);
             $r = $jifen->get(1,'id');
            if (! $r) {             
                $r = $jifen->insert(['jifennum'=>$jifennum,'switch'=>$switch,'rule'=>$rule]);
                if ($r > 0) {
                    $this->success('添加成功！',$this->module_url."/software/jifen");
                }
            } else {
                $data = $this->parseSql("jifennum = '$jifennum', switch = '$switch',rule='$rule'");
                $r = $jifen->save($data,1,'id');
                if ($r > 0) {
                    $this->success('修改成功',$this->module_url."/software/jifen");
                }else 
                    $this->error('更新失败，请检查数据',$this->module_url.'/software/jifen');
            }
        } elseif ($jifennum < 0) {
            $this->error('请正确输入积分值','');
        } else {
            // print_r(4);die;
            $this->error('积分值不能为空!','');
        }
        exit;
    }

    public function modify(Request $request)
    {
        $request->method()=='post'&&$this->do_modify($request);
        // 接收信息
        $id = intval($request->param("id")); // 插件id
        
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
                                                                     
        // 根据插件id，查询插件信息
        $software=$this->getModel('software');    
        $r = $software->get($id,'id');     
        if ($r) {      
            $name = $r[0]->name; // 软件名称
            
            $image = $r[0]->image; // 软件图标
            
            $type = $r[0]->type; // 类型
            
            $edition = $r[0]->edition; // 版本号
            
            $edition_url = $r[0]->edition_url; // 版本名称
            
            $edition_text = $r[0]->edition_text; // 版本名称
            
            $software_version = $r[0]->software_version;
        }else{
            $this->error('id 错误','');
        }
        
        
        $r = $software->where(['type'=>['=',$type],'software_version'=>['=',$software_version]])->fetchOrder(['id','desc']);
        
        $ops = '';
        
        if ($r) {
            
            foreach ($r as $key => $value) {
                
                $vedition = $value->edition;
                
                if ($vedition == $edition) {
                    
                    $ops .= " <option selected='selected' value='$vedition'>$vedition</option>";
                } else {
                    
                    $ops .= " <option  value='$vedition'>$vedition</option>";
                }
            }
        }
        
        $this->assign("ops", $ops);
        
        $this->assign('software_version', $software_version);
        
        $this->assign('id', $id);
        
        $this->assign('uploadImg', $uploadImg);
        
        $this->assign('name', $name);
        
        $this->assign("image", $image);
        
        $this->assign("type", $type);
        
        $this->assign('edition', $edition);
        
        $this->assign('edition_url', $edition_url);
        
        $this->assign('edition_text', $edition_text);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        
        // 接收信息
        $id = intval($request->param('id'));
        
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        
        $upload_file = addslashes(trim($request->param('upload_file'))); // 文件上传位置
        
        $name = addslashes(trim($request->param('name'))); // 软件名称
         
        $image = addslashes($request->param('image')); // 软件图片
        
        $oldpic = addslashes($request->param('oldpic')); // 原软件图片
        
        $type = addslashes($request->param('type')); // 软件类型
        
        $edition = addslashes($request->param('edition')); // 版本号
        
        $edition_text = addslashes($request->param('edition_text')); // 版本号
        $software=$this->getModel('software');    
        if ($name) {
            // 根据软件名称查询软件表                        
            $r = $software->where(['name'=>['=',$name,'type'=>['=',$type],'id'=>['<>',$id]]])->fetchOrder(['id','desc']);
            
            if ($r) {           
                $id = $r[0]->id; // 软件id                
                if ($edition == $r[0]->edition) {                  
                    $this->error('版本'. $edition . '已存在!',$this->module_url."/software");exit;
                } else if ($edition < $r[0]->edition) {                
                    $this->error('版本" . $edition . "低于最新版本!',$this->module_url."/software");
                    exit;
                } 
            } 
        } else {
            
            $this->error('软件名称不能为空!',$this->module_url."/software");
        }
        
        if ($image) {
            
            $image = preg_replace('/.*\//', '', $image);
            
            if ($image != $oldpic) {
                
               // @unlink($uploadImg . $oldpic);
            }
        } else {
            
            $image = $oldpic;
        }
        $file=$request->file('edition_url');
        $r = $software->one("id = ".$id,'edition_url');  
        if (empty($file['name'])) {                      
            $edition_url_name = $r[0]->edition_url;
        } else {
                        
            $rr = $this->getModel('config')->get(1,'id');       
            $upload_file = $rr[0]->upload_file; // 文件上传位置
                                                
            // 将临时文件复制到upload_image目录下
            $msg='';
            if($this->validate([$file],"requires|fileType:zip",$msg)){            
            $edition_url_name = pathinfo($file['name'],PATHINFO_BASENAME) . time() . '.' . $edition . ".zip";
            move_uploaded_file($file['tmp_name'], check_file(PUBLIC_PATH.DS.$upload_file.DS.$edition_url_name));
                               
            @unlink(check_file(PUBLIC_PATH.DS.$upload_file . DS . $r->edition_url));
            }
            else{
                $this->error($msg,'');
            }
        }
        
        // 更新数据表
        
        $data=$this->parseSql("name = '$name',edition_text='$edition_text',image = '$image',type = '$type',edition = '$edition', edition_url = '$edition_url_name',add_time ='".nowDate()."'");       
        $r = $software->save($data,$id,'id');   
        if ($r == false) {
            
            $this->error('未知原因，修改失败！',$this->module_url."/software");
        } else {
            
            $this->success('修改成功！',$this->module_url."/software");
        }
        
        exit();
    }

    public function pageadd(Request $request)
    {        
        $request->method()=='post'&&$this->do_pageadd($request);
        $r = $this->getModel('config')->get(1,'id');
        
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
                                       
        // 产品显示选择
        $products = $this->getModel('ProductList')->where("recycle = 0")->fetchOrder("sort,id","id,product_title,sort,add_date");
        
        // 查询分类表，根据sort顺序排列
        $pclass=$this->getModel('ProductClass');
        $rr = $pclass->where('sid','=',0)->where("recycle=0")->fetchOrder(['sort','desc']);
        $list = [];
        foreach ($rr as $key => $value) {
            $value->str='';
            array_push($list, $value);
            $list = $this->category($pclass,$list, $value->cid, $key);
        }
        
        // 获取文章信息
        $article =$this->getModel('article')->fetchAll('Article_id,Article_prompt,Article_title');
        $this->assign('article', $article);
        $this->assign("list", $list);
        $this->assign('products', $products);
        $this->assign("uploadImg", $uploadImg);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_pageadd($request)
    {
        
        // 接收数据
        $image = addslashes($request->param('image')); // 图
        $url = addslashes(trim($request->param('url'))); // 链接
        $sort = floatval(trim($request->param('sort'))); // 排序
        $type = trim($request->param('type')); // 类型
        $product_class = trim($request->param('product_class')); // 分类
        if ($image) {
            $image = preg_replace('/.*\//', '', $image);
        }
        if (! $sort) {
            $this->error('排序号不能为空！',$this->module_url."/software/pageadd");
        }
        $indexpage=$this->getModel('indexPage');
        if ($type == 'img') {
            if (! $image) {
                $this->error('图片不能为空！',$this->module_url."/software/pageadd");
            }
            $data = $this->parseSql("(type,image,url,sort,add_date) values('$type','$image','$url','$sort',".nowDate().")",'insert');
            
        } else {
            $data = $this->parseSql("(type,url,sort,add_date) values('$type','$product_class','$sort',".nowDate().")",'insert');
        }
        $r = $indexpage->insert($data);
        if ($r == false) {
            $this->error('未知原因，添加失败！','');
        } else {
            $this->success('添加成功！',$this->module_url."/software/pageindex");
        }     
        exit;
    }
    private function category($pclass,$list,$cid,$k,$num = 0)
    {
        $num++;
        // 查询分类表，根据sort顺序排列
        $rr = $pclass->where('sid','=',$cid)->fetchOrder('sort,cid');
        foreach ($rr as $key => $value) {
            $str = '|——';
            for ($i=0; $i < $num; $i++) {
                $str .= '——————';
            }
            $value->str = $str;
            array_push($list, $value);
            $rs = $pclass->where('sid','=',$value->cid)->fetchOrder('sort,cid');
            if($rs){
                $list = $this->category($pclass,$list,$value->cid,$k,$num+1);
            }
        }    
        return $list;
    }
    public function pagedel(Request $request)
    {
        
        // 接收信息
        $id = intval($request->param('id')); // 轮播图id
        $yimage = addslashes(trim($request->param('yimage'))); // 原图片路径带名称
        $uploadImg = substr($yimage, 0, strripos($yimage, '/')) . '/'; // 图片路径
       // @unlink($uploadImg . $image);
        // 根据轮播图id，删除轮播图信息
        $this->getModel('indexPage')->delete($id,'id');
        $this->success('删除成功！',$this->module_url."/software/pageindex");
        exit;
    }

    public function pageindex(Request $request)
    {     
        $r =(new Config($this->db_config))->get(1,'id');      
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
                                       
        // 查询轮播图表，根据sort顺序排列
        
        $r = (new IndexPage($this->db_config))->fetchOrder(['sort']);
        foreach ($r as $k => $v) {
            if ($v->type == 'img') {
                $v->image = $uploadImg . $v->image;
            } else {
                $cid = $v->url;
                $v->name='';
                $cr = (new ProductClass($this->db_config))->get($cid,'cid');
                if ($cr) {
                    $v->name = $cr[0]->pname; // 分类名称
                }
            }
        }
        
        $this->assign("list", $r);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function pagemodify(Request $request)
    {
        $request->method()=='post'&&$this->do_pagemodify($request);
        // 接收信息
        $id = intval($request->param("id")); // 轮播图id
        
        $yimage = addslashes(trim($request->param('yimage'))); // 原图片路径带名称
        
        $uploadImg = substr($yimage, 0, strripos($yimage, '/')) . '/'; // 图片路径
                                                                     
        // 根据轮播图id，查询轮播图信息
                
        $r = $this->getModel('indexPage')->get($id,'id');
        
        if ($r) {
            
            $image = $r[0]->image; // 轮播图
            
            $url = $r[0]->url; // 链接
            
            $sort = $r[0]->sort; // 排序
            
            $type = $r[0]->type;
        }
        
      $url=empty($url)?'#':$url;
        
        // 产品显示选择
        $products = $this->getModel('ProductList')->where("recycle=0")->fetchOrder("sort,id","id,product_title,sort,add_date");
             
        // 查询分类表，根据sort顺序排列
        $pclass=$this->getModel('ProductClass');
        $rr = $pclass->where('sid=0 and recycle=0')->fetchOrder(['sort','desc']);
        $list = [];
        foreach ($rr as $key => $value) {
            array_push($list, $value);
            $list = $this->category($pclass,$list, $value->cid, $key);
        }
        
        // 获取文章信息
        $article =$this->getModel('article')->fetchAll('Article_id,Article_prompt,Article_title');      
        
        $this->assign('article', $article);
        $this->assign("list", $list);
        $this->assign('products', $products);
        $this->assign("uploadImg", $uploadImg);
        $this->assign("image", isset($image)?$image:'');
        $this->assign('id', $id);
        $this->assign('url', $url);
        $this->assign('sort', isset($sort)?$sort:0);
        $this->assign('type', isset($type)?$type:0);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_pagemodify($request)
    {
        
        // 接收信息
        $id = intval($request->param('id'));
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        $image = addslashes(trim($request->param('image'))); // 轮播图
        $oldpic = addslashes(trim($request->param('oldpic'))); // 原轮播图
        $url = addslashes(trim($request->param('url'))); // 链接
        $sort = floatval(trim($request->param('sort'))); // 排序
        $type = trim($request->param('type')); // 类型
        $product_class = trim($request->param('product_class')); // 分类
        if ($image) {
            $image = preg_replace('/.*\//', '', $image);
            if ($image != $oldpic) {
                @unlink($uploadImg . $oldpic);
            }
        } else {
            $image = $oldpic;
        }
        if ($type == 'img') {
            $data = $this->parseSql("image = '$image',url = '$url', sort = '$sort',type = '$type'");
        } else {
            $data = $this->parseSql("url = '$product_class', sort = '$sort',type = '$type'");
        }
        // 更新数据表
        
        $r = $this->getModel('IndexPage')->save($data,$id,'id');
        if ($r == false) {
            $this->error('未知原因，修改失败！',$this->module_url."/software/pageindex");
        } else {
            
            $this->success('修改成功！',$this->module_url."/software/pageindex");
        }
        exit;
    }

    public function program(Request $request)
    {
        
        $r = $this->getModel('software')->where('type','=','0')->fetchOrder(['id','desc']);
        
        $list = [];
        
        if ($r) {
            
            $list = $r;
        }
        
        
        $r = $this->getConfig();
        
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        
        $upload_file = $r[0]->upload_file . '/'; // 文件上传位置
           
        $uploadImg_domain = $r[0]->uploadImg_domain; // 图片上传域名
        
        
        if (strpos($upload_file, '../') === false) { // 判断字符串是否存在 ../
            
            $zip = $uploadImg_domain . $upload_file; // 图片路径
        } else { // 不存在
            
            $zip = $uploadImg_domain . substr($upload_file, 2); // 图片路径
        }
        
        $this->assign("uploadImg", $uploadImg);
        
        $this->assign("down_file", $zip);
        
        $this->assign("list", $list);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }


    public function whether(Request $request)
    {
        
        // 接收信息
        $id = intval($request->param('id')); // 插件id
        empty($id)&&exit();                              
        // 根据插件id,查询查询状态
        $plug=$this->getModel('plugIns');
        $r =$plug ->one("id = ".$id,'type');
        
        if ($r&&$r[0]->type == 1) {
                        
           $plug->save(['type'=>0],$id,'id');
            
            $this->success('禁用成功！',$this->module_url."/plug_ins");
            
            exit;
        } else {
            
            $plug->save(['type'=>1],$id,'id');
                    
            $this->success('启用成功！',$this->module_url."/plug_ins");
            
            exit;
        }
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