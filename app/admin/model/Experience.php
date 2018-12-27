<?php
namespace app\admin\model;
use core\Model;
class Experience extends Model
{

    function __construct($options=[],$module='')
    {
        $this->db_table='experience';
        $this->db_options=empty($options)?include(APP_PATH.DS.$module.DS.'config.php'):$options;
        parent::__construct();
    }
}