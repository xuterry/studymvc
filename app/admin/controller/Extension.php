<?php
namespace app\admin\controller;
use core\Session;
use core\Request;

class Extension extends Index
{
    function __construct()
    {
        parent::__construct();
    }
    public function Index(Request $request)
    {
        $m = $request->param('m');      
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : '10';
        // 每页显示多少条数据
        $page = $request->param('page');      
        // 页码
        if ($page) {
            $start = ($page - 1) * $pagesize;
        } else {
            $start = 0;
        }       
        // 查询推广图，根据sort顺序排列      
        $r=$this->getModel('Extension')->order(['add_date'=>'asc'])->paginator($pagesize);
        $pages_show=$r->render();
        // 查询配置表信息
        
        $this->assign("uploadImg",Session::get('uploadImg')?:$this->getConfig()[0]->uploadImg);
        $this->assign("list", $r);
        $this->assign('pages_show', $pages_show);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function add(Request $request)
    {
        $request->method() == 'post' && $this->do_add($request);
        
        // 查询配置表信息
        $this->assign("uploadImg",Session::get('uploadImg')?:$this->getConfig()[0]->uploadImg);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_add($request)
    {
        
        // 接收数据
        $title = trim($request->param('title')); // 名称
        $type = trim($request->param('type')); // 海报类型
        $keyword = trim($request->param('keyword')); // 关键词
        $isdefault = trim($request->param('isdefault')); // 是否默认
        $bg = trim($request->param('bg')); // 背景图片
        $waittext = trim($request->param('waittext')); // 等待语
        $data = trim($request->param('data')); // 排序的数据
        $color = trim($request->param('color')); // 颜色
        $img = $request->param('img');
        
        if (empty($title) || empty($keyword) || empty($waittext)) {
            $this->error('信息未填写完整,请重新添加！', '');
        }
        // 添加数据
        if ($isdefault) {
            $r=$this->getModel('Extension')->saveAll(['isdefault'=>0],['type'=>['=',$type]]);
        }
        $r=$this->getModel('Extension')->insert(['image'=>$img,'name'=>$title,'type'=>$type,'keyword'=>$keyword,'isdefault'=>$isdefault,'bg'=>$bg,'waittext'=>$waittext,'data'=>$data,'color'=>$color,'add_date'=>nowDate()]);
        if ($r == false) {
            $this->error('未知原因，添加失败！', '');
        } else {
            $this->success('添加成功！', $this->module_url . "/extension");
        }
        return;
    }

    public function del(Request $request)
    {
        
        // 接收信息
        $id = intval($request->param('id')); // 轮播图id
                                             // 根据轮播图id，删除轮播图信息
        $delete_rs=$this->getModel('Extension')->delete($id,'id');
        echo 1;
        return;
    }

    public function modify(Request $request)
    {
        $request->method() == 'post' && $this->do_modify($request);
        
        // 接收信息
        $id = intval($request->param("id")); // 推广图id
                                             // 根据推广图id，查询推广图信息
        $r = $this->getModel('Extension')->get($id, 'id');
        $res = [];
        if ($r) {
            $data = json_decode($r[0]->data); // 推广图
            $res = $r[0];
        }
        // $data = json_decode($data);
        // 查询配置表信息
        $this->assign("uploadImg",Session::get('uploadImg')?:$this->getConfig()[0]->uploadImg);
        
        $this->assign("res", $res);
        $this->assign('id', $id);
        $this->assign('data', $data);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        
        // 接收数据
        $title = trim($request->param('title')); // 名称
        $type = trim($request->param('type')); // 海报类型
        $keyword = trim($request->param('keyword')); // 关键词
        $isdefault = trim($request->param('isdefault')); // 是否默认
        $bg = trim($request->param('bg')); // 背景图片
        $waittext = trim($request->param('waittext')); // 等待语
        $data = trim($request->param('data')); // 排序的数据
        $color = trim($request->param('color')); // 颜色
        $img = $request->param('img');
        $oldimg=$request->param('oldimg');
        empty($img)&&$img=$oldimg;
        if (empty($title) || empty($keyword) || empty($waittext)) {
            $this->error('信息未填写完整,请重新添加！', '');
        }
        // 添加数据
        if ($isdefault) {
            $r=$this->getModel('Extension')->saveAll(['isdefault'=>0],['type'=>['=',$type]]);
        }
        
        $id = intval($request->param("id")); // 推广图id
                                             // 更新数据表
        
        $r=$this->getModel('Extension')->saveAll(['image'=>$img,'name'=>$title,'type'=>$type,'keyword'=>$keyword,'isdefault'=>$isdefault,'bg'=>$bg,'waittext'=>$waittext,'data'=>$data,'color'=>$color,'add_date'=>nowDate()],['id'=>['=',$id]]);
        
        if ($r == false) {
            $this->error('未知原因，修改失败！', $this->module_url . "/extension");
        } else {
            $this->success('修改成功！', $this->module_url . "/extension");
        }
        return;
    }

    public function uploadImg(Request $request)
    {
        $request->method() == 'post' && $this->do_uploadImg($request);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }
    public function showImage(Request $request)
    {
        if($request->get('do')=='delete'){
            $id=$request->param('id');
            $filename=check_file(PUBLIC_PATH.DS.$id);
            $rs=-1;
            is_file($filename)&&$rs=unlink($filename);
            if($rs)
                exit('1');
            else 
                exit('-1');
        }
        if($request->get('do')!='local')
            return;
        $page=$request->get('page');
        $pagesize=24;
        $uploadImg=Session::get('uploadImg')?:$this->getConfig()[0]->uploadImg;
        $path=check_file(PUBLIC_PATH.DS.$uploadImg.DS);
        $imgs=scandir($path);
        $items=[];
        foreach($imgs as $v){
            if($v=='..'||$v=='.'||is_dir($path.$v))
                continue;
            $filename=$path.$v;
            $getext=pathinfo($filename,PATHINFO_EXTENSION);
            if($getext=='jpg'||$getext=='jpeg'||$getext=='gif'||$getext=='png'){             
                  $size=round(filesize($filename)/1204,2);
                  $createdate=date('Y-m-d H:i:s',filemtime($filename));
                  $items[]=['url'=>$uploadImg.$v,'title'=>$size.'kb '.$createdate,'name'=>$v,'path'=>$uploadImg];
            }
        }
        $page==0&&$page=1;
        $total=count($items);
        $item=array_slice($items,($page-1)*$pagesize,$pagesize);
        $count=ceil($total/$pagesize);
        $pageshow=$a='';
        for($i=1;$i<=$count;$i++){
            $a.="<a href='javascript:void(0);' page='".$i."' class='btn'>".$i."</a>";
        }
        $data=['message'=>['items'=>
                 $item      
        ],'pageshow'=>$a
        ];
        exit(json_encode($data));
    }

    private function do_uploadImg($request)
    {
        
        // 查询配置表信息
        $r_1 = $this->getConfig();
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        $file=$request->file('file');
        $imgURL = $file['tmp_name'];
        $type = pathinfo($file['name'],PATHINFO_EXTENSION);
        $imgURL_name = time() . mt_rand(1, 1000) . '.'.$type;
        $filename=check_file(PUBLIC_PATH.DS.$uploadImg . $imgURL_name);
        move_uploaded_file($imgURL,$filename);
        $do = in_array($request->param('do'), array(
                                                    'upload'
        )) ? $request->param('do') : 'upload';
        $type = in_array($request->param('type'), array(
                                                        'image','audio'
        )) ? $request->param('type') : 'image';
        $ext = strtolower($type);
        $array = getimagesize($filename);
        $size = getimagesize($filename);
        $info = array(
                    'name' => $file['name'],'ext' => $type,'filename' => $imgURL_name,'attachment' => $imgURL_name,'url' => $img . $imgURL_name,'is_image' => 1,'filesize' => $size
        );
        
        $info['width'] = $size[0];
        $info['height'] = $size[1];
        die(json_encode($info));
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