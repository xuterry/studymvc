<?php
namespace app\admin\controller;

use core\Request;

class Article extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        $r = $this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        
        $r=$this->getModel('Article')->fetchOrder(['sort'=>'asc'],'*');
        $this->assign("list", $r);
        $this->assign("uploadImg", $uploadImg);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function add(Request $request)
    {
        $request->method() == 'post' && $this->do_add($request);
        
        // 获取文章类别
                
        $r = $this->getModel('newsClass')->fetchAll('cat_id,cat_name');       
        $this->assign("ctype", $r);       
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_add($request)
    {
        
        // 接收数据
        $Article_title = addslashes(trim($request->param('Article_title'))); // 文章标题
        
        $Article_prompt = addslashes(trim($request->param('Article_prompt'))); // 文章副标题
        
        $sort = floatval(trim($request->param('sort'))); // 排序
        
        $imgurl = addslashes($request->param('imgurl')); // 文章图片
        
        $content = $this->trimContent(addslashes(trim($request->param('content'))),''); // 产品内容
        
        if ($imgurl) {
            
            $imgurl = preg_replace('/.*\//', '', $imgurl);
        }
        
        // 发布文章
        
        $r=$this->getModel('Article')->insert(['Article_title'=>$Article_title,'Article_prompt'=>$Article_prompt,'Article_imgurl'=>$imgurl,'sort'=>$sort,'content'=>$content,'add_date'=>nowDate()]);
        
        if ($r == false) {
            
            $this->error('未知原因，文章发布失败！', '');
        } else {
            
            $this->success('文章发布成功！', $this->module_url . "/Article");
        }
        
        return;
    }

    public function amount(Request $request)
    {
        $request->method() == 'post' && $this->do_amount($request);
        
        // 接收信息
        $id = intval($request->param('id'));
        // 根据新闻id，查询新闻信息
        $r = $this->getModel('Article')->get($id, 'Article_id ');
        $title = $r[0]->Article_title; // 文章标题
        $total_amount = $r[0]->total_amount; // 红包金额
        $total_num = $r[0]->total_num; // 红包数量
        $wishing = $r[0]->wishing; // 祝福语
        
        $this->assign("id", $id);
        $this->assign("title", $title);
        $this->assign("total_amount", $total_amount);
        $this->assign("total_num", $total_num);
        $this->assign("wishing", $wishing);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_amount($request)
    {      
        // 接收参数
        $id = intval($request->param('id')); // 文章id
        $total_amount = addslashes(trim($request->param('total_amount'))); // 红包金额
        $total_num = addslashes(trim($request->param('total_num'))); // 红包数量
        $wishing = addslashes(trim($request->param('wishing'))); // 祝福语
        // 判断金额是否为空 或 判断金额是否为0
        if ($total_amount == '' || $total_amount == 0) {
            $this->error('红包金额不能为0！', '');
        }
        // 判断数量是否为空
        if ($total_num == '') {
            $this->error('红包数量不能为空！', '');
        }
        // 判断金额和数量是否为数字
        if (is_numeric($total_amount) == false || is_numeric($total_num) == false) {
            $this->error('金额或数量不为数字！', '');
        }
        // 根据文章id，修改新闻列表信息
        $r=$this->getModel('Article')->saveAll(['total_amount'=>$total_amount,'total_num'=>$total_num,'wishing'=>$wishing],['Article_id'=>['=',$id]]);
        if ($r == false) {
            $this->error('未知原因，红包设置失败！', '');
        } else {
            $this->success('红包设置成功！', $this->module_url . "/Article");
        }
        exit;
    }

    public function del(Request $request)
    {
        // 接收信息
        $id = intval($request->param('id')); // 新闻id
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 原图片路径带名称
        $uploadImg = substr($uploadImg, 0, strripos($uploadImg, '/')) . '/'; // 图片路径
                                                                           // 根据文章id,查询文章
        $r = $this->getModel('Article')->get($id, 'Article_id ');
        $Article_imgurl = $r[0]->Article_imgurl;
        // @unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$Article_imgurl));
        // 根据文章id，删除新闻信息
        $delete_rs=$this->getModel('Article')->delete($id,'Article_id');
        $this->success('删除成功！', $this->module_url . "/Article");
        return;
    }
    public function modify(Request $request)
    {
        $request->method() == 'post' && $this->do_modify($request);
        
        // 接收信息      
        $id = intval($request->param("id")); // 文章id    
        $uploadImg = addslashes($request->param('uploadImg')); // 图片上传位置                                                              
        // 根据文章id，查询文章文章信息       
        $r = $this->getModel('Article')->get($id, 'Article_id ');       
        if ($r) {           
            $Article_title = $r[0]->Article_title; // 文章标题            
            $Article_prompt = $r[0]->Article_prompt; // 文章标题        
            $sort = $r[0]->sort; // 排序          
            $content = $r[0]->content; // 文章内容Article_imgurl        
            $Article_imgurl = $r[0]->Article_imgurl; // 文章图片
        }      
        $this->assign('id', $id);     
        $this->assign('Article_title', $Article_title);     
        $this->assign('Article_prompt', $Article_prompt);   
        $this->assign('sort', isset($sort) ? $sort : '');        
        $this->assign('Article_imgurl', $Article_imgurl);       
        $this->assign('content', $content);     
        $this->assign('uploadImg', $uploadImg);       
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        
        // 接收信息
        $id = intval($request->param('id'));
        
        $uploadImg = addslashes($request->param('uploadImg')); // 图片上传位置
        
        $Article_title = trim($request->param('Article_title')); // 文章标题
        
        $Article_prompt = trim($request->param('Article_prompt')); // 文章副标题
        
        $sort = floatval(trim($request->param('sort'))); // 排序
        
        $imgurl = addslashes($request->param('imgurl')); // 文章新图片
        
        $oldpic = addslashes($request->param('oldpic')); // 文章原图片
        
        $content = $this->trimContent(addslashes(trim($request->param('content'))),''); // 产品内容
        
        if ($imgurl) {
            
            $imgurl = preg_replace('/.*\//', '', $imgurl);
            
            if ($imgurl != $oldpic) {
                
                // @unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$oldpic));
            }
        } else {
            
            $imgurl = $oldpic; // 文章图片
        }
        
        // 检查文章标题是否重复
        
        $r=$this->getModel('Article')->where(['Article_title'=>['=',$Article_title],'Article_id'=>['<>',$id]])->fetchAll('1');
        
        if ($r && count($r) > 0) {
            
            $this->error('{$Article_title} 已经存在，请选用其他标题进行修改！', '');
        }
        
        // 更新数据表
        
        $r=$this->getModel('Article')->saveAll(['Article_title'=>$Article_title,'Article_prompt'=>$Article_prompt,'sort'=>$sort,'Article_imgurl'=>$imgurl,'content'=>$content],['Article_id'=>['=',$id]]);
        
        if ($r == false) {
            
            $this->error('未知原因，文章修改失败！', $this->module_url . "/Article");
        } else {
            
            $this->success('文章修改成功！', $this->module_url . "/Article");
        }
        
        return;
    }

    public function view(Request $request)
    {
        
        // 接收信息
        $id = intval($request->param("id"));
        
        // 根据新闻id，查询新闻标题
        $r=$this->getModel('Article')->where(['Article_id'=>['=',$id]])->fetchAll('Article_title as a');
        $Article_title = $r[0]->a;
        // 根据新闻id，查询分享列表
        $rr = $this->getModel('Share')->get($id, 'Article_id ');
        
        // 根据新闻id，查询总条数
        $rrr = $this->getModel('share')->getCount("Article_id = ".$id,'id');
        $total = $rrr;
        if ($total == '') {
            $total = 0;
        }      
        $this->assign("Article_title", $Article_title);
        $this->assign("list", $rr);
        $this->assign("total", $total);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
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