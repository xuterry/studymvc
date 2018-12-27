<?php
namespace app\admin\model;
use core\Model;
class CombinedPay extends Model
{

    function __construct($options=[],$module='')
    {
        $this->db_table='combined_pay';
        $this->db_options=empty($options)?include(APP_PATH.DS.$module.DS.'config.php'):$options;
        parent::__construct();
    }
}