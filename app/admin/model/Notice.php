<?php
namespace app\admin\model;

use core\Model;

class Notice extends Model
{
    public function __construct($options=[],$module='')
    {
        $this->db_table='notice';
        $this->db_options=empty($options)?include(APP_PATH.DS.$module.DS.'config.php'):$options;
        //dump($this->db_options);exit();//
        parent::__construct($this->db_options);
    }
}

