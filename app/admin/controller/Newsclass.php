<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Newsclass extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    public function Index(Request $request)
    {
        
        
        // 查询新闻分类表，根据sort顺序排列
        $sql = "select * from lkt_news_class order by sort";
        $r = $db->select($sql);
        $this->assign("list",$r);

        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    public function add(Request $request)
    {
       $request->method()=='post'&&$this->do_add($request);
		

		$cat_name = $request->param('cat_name');

		$sort = $request->param('sort');

		$this->assign('cat_name', $cat_name);

		$this->assign('sort', $sort);

		return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);

	}

	
    private function do_add($request)
    {

		

        

        // 获取分类名称和排序号

        $cat_name = addslashes(trim($request->param('cat_name')));

        $sort = floatval(trim($request->param('sort')));

		//检查分类名称是否重复

        $r=$this->getModel('NewsClass')->get($cat_name,'cat_name ');

		// 如果有数据 并且 数据条数大于0

        if ($r && count($r) > 0) {

            $this->error('新闻分类 {$cat_name} 已经存在，请选用其他名称！','');

            

        }

		//添加分类

		$sql = "insert into lkt_news_class(cat_name,sort,add_date) "

            ."values('$cat_name','$sort',CURRENT_TIMESTAMP)";

		$r = $db->insert($sql);

		if($r ==false) {

			$this->error('未知原因，添加新闻分类失败！','');

			

		} else {

			$this->success('添加新闻分类成功！',$this->module_url."/newsclass");

			

		}

		

		return;

	}

	
    public function del(Request $request)
    {
        
        
        // 获取分类id
        $cat_id = intval($request->param('cat_id'));
        // 根据分类id,删除这条数据
        $sql = "delete from lkt_news_class where cat_id = '$cat_id'";
        $db->delete($sql);

        $this->success('删除成功！',$this->module_url."/newsclass");
        return;
    }

    
    public function modify(Request $request)
    {
       $request->method()=='post'&&$this->do_modify($request);
        

        

        // 接收分类id

        $cat_id = intval($request->param("cat_id"));

        

        // 根据分类id,查询新闻分类表

        $r=$this->getModel('NewsClass')->get($cat_id,'cat_id ');

        if($r){

            $cat_name = $r[0]->cat_name; // 分类名称

            $sort = $r[0]->sort; // 分类排序

        }

        $this->assign('cat_id', $cat_id);

        $this->assign('cat_name', isset($cat_name) ? $cat_name : '');

        $this->assign('sort', isset($sort) ? $sort : '');

		return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);

	}

	
    private function do_modify($request)
    {

		

		

		$cat_id = intval($request->param('cat_id'));

        $cat_name = addslashes(trim($request->param('cat_name')));

        $sort = floatval(trim($request->param('sort')));

        //检查分类名是否重复

        $sql = "select cat_id from lkt_news_class where cat_name = '$cat_name' and cat_id <> '$cat_id'";

        $r = $db->select($sql);

        if ($r) {

            $this->error('新闻分类 {$cat_name} 已经存在，请选用其他名称修改！','');

            

        }

		//更新分类列表

		$sql = "update lkt_news_class " .

			"set cat_name = '$cat_name', sort = '$sort'"

			."where cat_id = '$cat_id'";

		

		$r = $db->update($sql);

		if($r ==false) {

		$this->error('未知原因，修改新闻分类失败！',$this->module_url."/newsclass");

			

		} else {

			$this->success('修改新闻分类成功！',$this->module_url."/newsclass");

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