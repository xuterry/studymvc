<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Test extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    public function Index(Request $request)
    {
       $request->method()=='post'&&$this->do_Index($request);
			
		
        
		$sql_dp = "select id,type  from test";
		$r = $db->select($sql_dp);

		$data =  json_encode($r);
var_dump($data);
		$this->assign('data',$data);

		return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
	}

	
    private function do_Index($request)
    {

			
		
        
		$sql_dp = "select id,type  from test";
		$r = $db->select($sql_dp);

		$data =  json_encode($r);
var_dump($data);

		return $data;
		
	}

	
    public function test(Request $request)
    {

			
		
        
		$sql_dp = "select *  from test";
		$r = $db->select($sql_dp);

		$data =  json_encode($r);
var_dump($data);
		$this->assign('data',$data);

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