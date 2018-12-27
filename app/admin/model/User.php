<?php
namespace app\admin\model;

use core\Model;

class User extends Model
{

    public function __construct($options=[],$module='')
    {
        $this->db_table='user';
        $this->db_options=empty($options)?include(APP_PATH.DS.$module.DS.'config.php'):$options;
        parent::__construct($options);
    }


   public function getMember($date='')
   {
       !empty($date)&&$where['Register_data']=['like',$date.'%'];    
       return $this->getCount($where,'id');
       
   }

}

