<?php
namespace app\admin\model;
use core\Model;
class NewsList extends Model
{

    function __construct($options=[],$module='')
    {
        $this->db_table='news_list';
        $this->db_options=empty($options)?include(APP_PATH.DS.$module.DS.'config.php'):$options;
        parent::__construct();
    }
}