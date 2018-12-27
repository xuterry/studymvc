<?php
namespace app\admin\model;

use core\Model;

class Config extends Model
{

    public function __construct($options=[],$module='')
    {
        $this->db_table='config';  
        $this->db_options=empty($options)?include(APP_PATH.DS.$module.DS.'config.php'):$options;
        parent::__construct($options);
    }
    public function fetch()
    {
        return $this->where('id','=',1)->fetchAll();
    }
}

