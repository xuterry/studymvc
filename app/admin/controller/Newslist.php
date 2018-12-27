<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Newslist extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    public function Index(Request $request)
    {
        
        
        $cat_id = addslashes(trim($request->param('cat_id'))); // 分类名称
        $news_title = addslashes(trim($request->param('news_title'))); // 新闻标题

        $r=$this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        // 查询新闻分类
        $rr=$this->getModel('NewsClass')->fetchAll();

        $condition = ' 1=1 ';
        if($cat_id != ''){
            
            $condition .= " and news_class = '$cat_id' ";
        }
        
        if($news_title != ''){
            $condition .= " and news_title = '$news_title' ";
        }
        // 根据新闻类别id等于新闻分类id,查询新闻列表(新闻id、新闻类别、新闻标题、新闻排序、添加时间、分享次数),新闻分类列表(分类id,分类名称)
        $sql = 'select a.id,a.news_class,a.news_title,a.sort,a.add_date,a.share_num,m.cat_id AS mid,m.cat_name '.'from lkt_news_list'." AS a LEFT JOIN ".' lkt_news_class'." AS m  ON a.news_class = m.cat_id "." where $condition"." order by sort ";
        $r = $db->select($sql);

        $this->assign("uploadImg",$uploadImg);
        $this->assign("news_title",$news_title);
        $this->assign("class",$rr);
        $this->assign("list",$r);

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function add(Request $request)
    {
       $request->method()=='post'&&$this->do_add($request);
        

        

        //获取新闻类别

        $sql = "select cat_id,cat_name from lkt_news_class ";

        $r = $db->select($sql);

        $this->assign("ctype",$r);

		return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);

	}

	
    private function do_add($request)
    {

		

		

        // 接收数据 

        $news_class = addslashes(trim($request->param('news_class'))); // 新闻类别

        $news_title = addslashes(trim($request->param('news_title'))); // 新闻标题

        $sort = floatval(trim($request->param('sort'))); // 排序

        $content = addslashes(trim($request->param('content'))); // 新闻内容

        $author = addslashes(trim($request->param('author'))); // 作者

        $imgurl = addslashes(trim($request->param('imgurl'))); // 新闻图片

        $t_link = addslashes(trim($request->param('t_link'))); // 推广二维码图片

        if($imgurl){

            $imgurl = preg_replace('/.*\//','',$imgurl);

        }

        if($t_link){

            $t_link = preg_replace('/.*\//','',$t_link);

        }

        // 发布新闻

        $sql = "insert into lkt_news_list(news_class,news_title,author,imgurl,sort,content,t_link,add_date) " .

            "values('$news_class','$news_title','$author','$imgurl','$sort','$content','$t_link',CURRENT_TIMESTAMP)";

        $r = $db->insert($sql);

        if($r ==false){

            $this->error('未知原因，新闻发布失败！','');

            

        }else{

            $this->success('新闻发布成功！',$this->module_url."/newslist");

            

        }

	    return;

	}

	
    public function amount(Request $request)
    {
       $request->method()=='post'&&$this->do_amount($request);

        
        
        // 接收信息
        $id = intval($request->param('id'));
        // 根据新闻id，查询新闻信息
        $r=$this->getModel('NewsList')->get($id,'id');
        $title = $r[0]->news_title; // 新闻标题
        $total_amount = $r[0]->total_amount; // 红包金额
        $total_num = $r[0]->total_num; // 红包数量
        $wishing = $r[0]->wishing; // 祝福语

        $this->assign("id",$id);
        $this->assign("title",$title);
        $this->assign("total_amount",$total_amount);
        $this->assign("total_num",$total_num);
        $this->assign("wishing",$wishing);
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    private function do_amount($request)
    {
        
        
        // 接收参数
        $id = intval($request->param('id')); // 新闻id
        $total_amount = addslashes(trim($request->param('total_amount'))); // 红包金额
        $total_num = addslashes(trim($request->param('total_num'))); // 红包数量
        $wishing = addslashes(trim($request->param('wishing'))); // 祝福语
        // 判断金额是否为空 或 判断金额是否为0
        if($total_amount == '' || $total_amount == 0){
            $this->error('红包金额不能为0！','');
            
        }
        // 判断数量是否为空
        if($total_num == ''){
            $this->error('红包数量不能为空！','');
            
        }
        // 判断金额和数量是否为数字
        if(is_numeric($total_amount) == false || is_numeric($total_num) == false){
            $this->error('金额或数量不为数字！','');
            
        }
        // 根据新闻id，修改新闻列表信息
        $sql = "update lkt_news_list " . "set total_amount = '$total_amount',total_num = '$total_num',wishing = '$wishing'" ."where id = '$id'";
        $r = $db->update($sql);
        if($r ==false){
            $this->error('未知原因，红包设置失败！','');
            
        }else{
            $this->success('红包设置成功！',$this->module_url."/newslist");
            
        }
        return;
    }

    
    public function del(Request $request)
    {
        
        
        // 接收信息
        $id = intval($request->param('id')); // 新闻id
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 原图片路径带名称
        $uploadImg = substr($uploadImg,0,strripos($uploadImg, '/')) . '/'; // 图片路径
        // 根据新闻id,查询新闻
        $r=$this->getModel('NewsList')->get($id,'id');
        $imgurl = $r[0]->imgurl;
        $t_link = $r[0]->t_link;
        //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$imgurl));
        //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$t_link));
        // 根据新闻id，删除新闻信息
        $sql = "delete from lkt_news_list where id = '$id'";
        $db->delete($sql);
        $this->success('删除成功！',$this->module_url."/newslist");
        return;
    }

    
    public function modify(Request $request)
    {
       $request->method()=='post'&&$this->do_modify($request);
        

        

        // 接收信息

        $id = intval($request->param("id")); // 新闻id

        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置

        // 根据新闻id，查询新闻新闻信息

        $r=$this->getModel('NewsList')->get($id,'id');

        if($r){

            $news_title = $r[0]->news_title; // 新闻标题

            $news_class = $r[0]->news_class ; // 新闻类别

            $author = $r[0]->author ; // 作者

            $sort = $r[0]->sort; // 排序

            $t_link = $r[0]->t_link; // 推广链接

            $imgurl = $r[0]->imgurl; // 新闻图片

            $content = $r[0]->content; // 新闻内容

            

        }

        //绑定新闻分类

        

        $sql = "select * from lkt_news_class order by sort";

        $rs = $db->select($sql);

        

        $this->assign("ctypes",$rs);

        $this->assign('id', $id);

        $this->assign('news_title', isset($news_title) ? $news_title : '');

        $this->assign("news_class",$news_class);

        $this->assign('author', isset($author) ? $author : '');

        $this->assign('sort', isset($sort) ? $sort : '');

		$this->assign('t_link', $t_link);

        $this->assign('imgurl', $imgurl);

        $this->assign('content', isset($content) ? $content : '');

        $this->assign('uploadImg', $uploadImg);

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);

	}

	
    private function do_modify($request)
    {

		

		

        // 接收信息

		$id = intval($request->param('id')); // 新闻id

        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置

        $news_title = addslashes(trim($request->param('news_title'))); // 新闻标题

        $news_class= addslashes($request->param('news_class')); // 新闻分类

        $author = addslashes(trim($request->param('author'))); // 作者

        $sort = floatval(trim($request->param('sort'))); // 排序

        $imgurl = addslashes($request->param('imgurl')); // 新闻图片

        $t_link = addslashes($request->param('t_link')); // 推广链接

        $oldpic1 = addslashes($request->param('oldpic1')); // 原新闻图片

        $oldpic2 = addslashes($request->param('oldpic2')); // 原推广链接

        $content = addslashes($request->param('content')); // 新闻内容

     

        if($imgurl){

            $imgurl = preg_replace('/.*\//','',$imgurl);

            if($imgurl != $oldpic1){

                //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$oldpic1));

            }

        }else{

            $imgurl = $oldpic1;

        }

        if($t_link){

            $t_link = preg_replace('/.*\//','',$t_link);

            if($t_link != $oldpic2){

                //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$oldpic2));

            }

        }else{

            $t_link = $oldpic2;

        }

        

        // 检查新闻标题是否重复

        $sql = "select 1 from lkt_news_list where news_title = '$news_title' and id <> '$id'";

        $r = $db->select($sql);

        if ($r && count($r) > 0) {

            $this->error('{$news_title} 已经存在，请选用其他标题进行修改！','');

            

        }

		//更新数据表

		$sql = "update lkt_news_list " .

			"set news_title = '$news_title',news_class = '$news_class', sort = '$sort', imgurl = '$imgurl' , content = '$content', t_link = '$t_link' , author = '$author'"

			." where id = '$id'";

		$r = $db->update($sql);

		if($r ==false) {

		$this->error('未知原因，新闻修改失败！',$this->module_url."/newslist");

			

		} else {

			$this->success('新闻修改成功！',$this->module_url."/newslist");

		}

		return;

	}

	
    public function view(Request $request)
    {

        
        
        // 接收信息
        $id = intval($request->param("id"));
        // 根据新闻id，查询新闻标题
        $sql ="select news_title a from lkt_news_list where id = $id";
        $r = $db->select($sql);
        $news_title = $r[0]->a;
        // 根据新闻id，查询分享列表
        $sql1 = "select * from lkt_share where Article_id = $id";
        $rr = $db->select($sql1);
        // 根据新闻id，查询总条数
        $sql2 = "select count(id) c from lkt_share where Article_id = $id";
        $rrr = $db->select($sql2);
        $total = $rrr[0]->c;
        if($total == ''){
            $total = 0;
        }

        $this->assign("news_title",$news_title);
        $this->assign("total",$total);
        $this->assign("list",$rr);

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
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