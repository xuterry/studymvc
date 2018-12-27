<?php
namespace app\admin\model;
use core\Model;
class ProductList extends Model
{

    function __construct($options=[],$module='')
    {
        $this->db_table='product_list';
        $this->db_options=empty($options)?include(APP_PATH.DS.$module.DS.'config.php'):$options;
        parent::__construct();
    }
}