<?php
namespace app\admin\model;

use core\Model;

class Record extends Model
{

    public function __construct($options=[],$module='')
    {
        $this->db_table='record';
        $this->db_options=empty($options)?include(APP_PATH.DS.$module.DS.'config.php'):$options;
        //dump($this->db_options);exit();//
        parent::__construct($this->db_options);
    }
    //获取访问人数
    public function getAccess($date='')
    {
        $query=$this->db_query->where('type','=',0);
        !empty($date)&&$query->where("add_date like '$date%'");
        return $query->group('user_id')->count('id');
    }
    public function record($admin_name,$event,$type)
    {
        $event = $admin_name . $event;
        $this->insert(['admin_name'=>$admin_name,'event'=>$event,'type'=>$type]);
    }
}

