<?php
namespace app\api\controller;

class Gethot extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function index()
    {
       // $sql = 'select keyword from lkt_hotkeywords';
        $res = $this->getModel('hotkeywords')->fetchAll();
        foreach ($res as $k => $v) {
            $res[$k] = $v->keyword;
        }
        echo json_encode($res);
        exit();
    }
}