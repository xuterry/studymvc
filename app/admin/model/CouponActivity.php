<?php
namespace app\admin\model;
use core\Model;
class CouponActivity extends Model
{

    function __construct($options=[],$module='')
    {
        $this->db_table='coupon_activity';
        $this->db_options=empty($options)?include(APP_PATH.DS.$module.DS.'config.php'):$options;
        parent::__construct();
    }
}