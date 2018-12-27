<?php
namespace app\api\controller;
use core\Request;

class Plugs extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function pay (Request $request)
    {
        echo "string";
    }

}