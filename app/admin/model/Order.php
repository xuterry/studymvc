<?php
namespace app\admin\model;

use core\Model;

class Order extends Model
{

    public function __construct($options=[],$module='')
    {
        $this->db_table='order';
        $this->db_options=empty($options)?include(APP_PATH.DS.$module.DS.'config.php'):$options;
        parent::__construct($options);
    }
   public function getNum($status=0)
   {
       return $this->getCount("status = ".$status,'num');
   }
   public function getBalance($date='')
   {
       $where=['status'=>['>',0],'status'=>['<>',4]];
       !empty($date)&&$where['add_time']=['like',$date.'%'];
       return $this->db_query->where($where)->sum('z_price');
   }
   public function getOrders($date='')
   {
       $where=["status"=>['>',0]];
       !empty($date)&&$where['add_time']=['like',$date.'%'];    
       return $this->getCount($where,'id');
       
   }

}

