<?php
namespace app\admin\model;
use core\Model;
class Software extends Model
{

    function __construct($options=[],$module='')
    {
        $this->db_table='software';
        $this->db_options=empty($options)?include(APP_PATH.DS.$module.DS.'config.php'):$options;
        parent::__construct();
    }
}