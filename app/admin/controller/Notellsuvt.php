<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Notellsuvt extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    public function Index(Request $request)
    {
        
        
        $sql = "select m.name,n.* from (select s.id,u.name from lkt_system_message as s left join lkt_admin as u on s.senderid=u.id) as m left join (select r.title,r.content,r.time,r.id,a.user_name  as r_name ,a.headimgurl from lkt_system_message as r left join lkt_user as a on r.recipientid=a.id) as n on m.id=n.id ORDER BY  time desc";
        $r = $db->select($sql);
		$this->assign("re",$r);

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function del(Request $request)
    {
        
        
        // 接收信息
        $id = $request->param('id'); // id
        $recip = explode(',',$id);//字符串转一维数组
        $cor = count($recip);
        foreach ($recip as $key => $value) {
           $sql = "delete from lkt_system_message where id = '$value'";
            $res = $db->delete($sql);
        }
        if($res > 0){
            echo json_encode(array('code' => 1,'msg' => '删除成功!'));exit;
        }
        // $this->success('删除成功！',$this->module_url."/notellsuvt");
        // return;
    }

    
    public function view(Request $request)
    {
       $request->method()=='post'&&$this->do_view($request);
        

        

        // 接收信息

        $id = intval($request->param("id")); // 文章id

        // 根据文章id，查询文章文章信息

        $r=$this->getModel('User')->get($id,'id');

        

        $this->assign('user', $r);

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);

	}

	
    private function do_view($request)
    {

		

		

        // 接收信息

		$id = intval($request->param('id'));

        $sort = floatval(trim($request->param('sort')));

        $content = addslashes($request->param('content'));

        $Article_title = trim($request->param('Article_title'));

        //判断是否重新上传过图片 -》 将临时文件复制到upload_image目录下 

        $imgURL=($_FILES['imgurl']['tmp_name']);

		if($imgURL){

			$imgURL_name=($_FILES['imgurl']['name']);

        	move_uploaded_file($imgURL,"../upfile/$imgURL_name");

			$imgURL_name = ', Article_imgurl =  \'' . $imgURL_name . '\'';

		}else{

			$imgURL_name = '';

		}

        // 检查文章标题是否重复

        $sql = "select 1 from lkt_article where Article_title = '$Article_title' and Article_id <> '$id'";

        $r = $db->select($sql);

        if ($r && count($r) > 0) {

            $this->error('{$Article_title} 已经存在，请选用其他标题进行修改！','');

            

        }

		//更新数据表

		$sql = "update lkt_article " .

			"set Article_title = '$Article_title', sort = '$sort' $imgURL_name, content = '$content' "

			."where Article_id = '$id'";

		$r = $db->update($sql);

		

		if($r ==false) {

		$this->error('未知原因，文章修改失败！',$this->module_url."/Article");

			

		} else {

			$this->success('文章修改成功！',$this->module_url."/Article");

		}

		return;

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