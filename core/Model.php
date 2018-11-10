<?php
namespace core;
use core\db\Query;
use core\db\Connection;
/**
/xtw
2018
model类
*/
class Model
{
    /**
     * 数据库配置
     * @var array
     */
    protected $db_options=[];
    /**
     * 数据表
     * @var string
     */
    protected $db_table='';
    /**
     *  数据库连接
     * @var Connection
     */
    protected $db_conn;
    /**
     * 数据库query
     * var Query
     */
    protected $db_query;
    /**
     * 初始数据库连接
     * @param array $options
     * @throws \Exception
     * @throws Exception
     */
    function __construct($options=[])
    {
        if(is_string($options)){
            $this->db_options=Config::get('database');
            $this->db_options['table']=$options;
        }else{
        $this->db_options=empty($options)?Config::get('database'):array_merge($this->db_options,$options);
        }
        if(empty($this->db_table))
        $this->db_table=isset($this->db_options['table'])?$this->db_options['table']:'';
        if(empty($this->db_table))
            throw new \Exception('empty db table');
        try{
          $this->db_conn=Db::connect($this->db_options);
          $this->db_query=$this->db_conn->name($this->db_table);
        }catch(\Exception $e){
          throw $e;
        }
    }
    /**
     * 根据id删除操作
     */
    public function delete($id)
    {
      $primary=$this->getFields(2);
      if(empty($primary))
          throw new \Exception('not primary key');
        $rs=$this->db_query->where($primary,'=',$id)->delete();
        return $rs;
    }
    /**
     * 根据id获取内容
     */
    public function get($id=0,$primary='')
    {
        empty($primary)&&$primary=$this->getFields(2);
        return $this->db_query->where($primary,'=',$id)->select();
    }
    /**
     * 获取字段 type 0返回详细信息 1返回字段名 2返回主键
     * @param number $filter
     * @return array
     */
    protected  function getFields($type=0)
    {
        $fields=$this->db_conn->getFields(Config::get('database')['prefix'].$this->db_table);
        if($type==1)
            return array_keys($fields);
        elseif($type==2){
            foreach($fields as $table=>$val){
                if($val['primary']===true){
                    return $table;
                }
            }
            return null;
        }
        return $this->db_conn->getFields(Config::get('database')['prefix'].$this->db_table);
    }
    /**
     * 获取记录
     * @param array $fields
     * @param number $limit
     */
    public function fetchAll($fields=[],$limit=30)
    {
        empty($fields)&&$fields=$this->getFields(1);
        return $this->db_query->field($fields)->limit($limit)->select();
    }
    /**
     * 重新选择数据表
     * @param string $tablename
     */
    public function setDbTable($tablename='')
    {
        if(empty($tablename))
            return;
        $this->db_query=$this->db_conn->name($this->db_table);
    }
    /**
     * 重新配置数据库连接
     * @param array $configs
     */
    public function setDbConn($configs=[])
    {
        $this->db_options=array_merge($this->db_options,$configs);
        if(empty($this->db_options))
            return;
        $this->resetConn();
        $this->db_conn=Db::connect($this->db_options);
    }
    /**
     * 关闭连接，释放资源
     */
    protected function resetConn()
    {
        $this->db_conn->free();
        $this->db_conn->close();
    }
    /**
     * 关闭连接，释放资源
     */
    function __destruct()
    {
        $this->resetConn();
        unset($this->db_conn,$this->db_query);
    }
}