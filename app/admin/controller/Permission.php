<?php
namespace app\admin\controller;
use core\Request;
use core\Session;

class Permission extends Index
{

    function __construct()
    {
        parent::__construct();
    }
    public function Index(Request $request)
    {
		
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