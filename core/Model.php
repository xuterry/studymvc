<?php
namespace core;
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
     * 初始数据库连接
     * @param array $options
     * @throws \Exception
     * @throws Exception
     */
    function __construct($options=[])
    {
        $this->db_options=empty($options)?Config::get('database'):array_merge($this->options,$options);
        $this->db_table=isset($options['table'])?$options['table']:'';
        if(empty($this->db_table))
            throw new \Exception('empty db table');
        try{
          $this->db_conn=Db::connect($this->db_options)->table($this->db_table);
        }catch(\Exception $e){
          throw $e;
        }
    }
    /**
     * 重新选择数据表
     * @param string $tablename
     */
    public function setDbTable($tablename='')
    {
        if(empty($tablename))
            return;
        $this->db_conn=$this->db_conn->table($this->db_table);        
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
}