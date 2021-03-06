<?php
namespace core;
use core\db\Query;
use core\db\Connection;
/**
/xtw
2018
model类
*/
class Model implements \ArrayAccess
{
    protected $data=[];
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
     * 连接实例
     */
    static $conn_instance=[];
    
    /**
     *  数据库连接
     * @var Connection
     */
    protected $db_conn;
    /**
     * 数据库query
     * var Query
     */
    public $db_query;
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
            $this->db_options=array_merge(Config::get('database'),$this->db_options);
            $this->db_options=array_merge($this->db_options,$options);
        }
        if(empty($this->db_table))
        $this->db_table=isset($this->db_options['table'])?$this->db_options['table']:'';
        if(empty($this->db_table))
            throw new \Exception('empty db table');
        try{
          $key=md5(serialize($this->db_options));
          if(isset(self::$conn_instance[$key]))   
              $this->db_conn=self::$conn_instance[$key];
          else{
             $this->db_conn=Db::connect($this->db_options);
             self::$conn_instance[$key]=$this->db_conn;
          }
          $this->db_query=$this->db_conn->name($this->db_table);
        }catch(\Exception $e){
          throw $e;
        }
    }
    public function conn()
    {
        return $this->db_conn;
    }
    /**
     * 根据id删除操作
     */
    public function delete($id=0,$primary='')
    {
      //$id=intval($id);
      empty($primary)&&$primary=$this->getFields(2);
      if(empty($primary))
          throw new \Exception('not primary key');
        $rs=$this->db_query->where($primary,'=',$id)->delete();
        return $rs;
    }
    /**
     * 根据条件删除操作
     */
    public function deleteWhere($where=[])
    {
        if(empty($where))
            throw new \Exception('not where condition');
        return $this->db_query->where($where)->delete();
    }
    /**
     * 根据id获取内容
     */
    public function get($id=0,$primary='',$field='')
    {
        empty($primary)&&$primary=$this->getFields(2);
        return empty($field)?$this->db_query->where($primary,'=',$id)->select():$this->db_query->field($field)->where($primary,'=',$id)->select();
    }
    /**
     * 获取一条数据
     * @param array $where
     * @param string $field
     * @return \PDOStatement|boolean|\core\Collection|string
     */
    public function one($where=[],$field='')
    {
        return empty($where)?$this->db_query->field($field)->limit(1)->select():$this->db_query->where($where)->field($field)->limit(1)->select();
    }
    /**
     * 获取字段 type 0返回详细信息 1返回字段名 2返回主键
     * @param number $filter
     * @return array
     */
    protected  function getFields($type=0)
    {
        $fields=$this->db_conn->getFields($this->db_options['prefix'].$this->db_table);
        if($type==1)
            return array_keys($fields);
        elseif($type==2){
            foreach($fields as $field=>$val){
                if($val['primary']===true){
                    return $field;
                }
            }
            return null;
        }
        return $this->db_conn->getFields($this->db_options['prefix'].$this->db_table);
    }
    /**
     * 获取记录
     * @param array $fields
     * @param number $limit
     */
    public function fetchAll($fields='*',$limit=0)
    {
        if(empty($limit))
           return $this->db_query->field($fields)->select();
        else
           return $this->db_query->field($fields)->limit($limit)->select();
    }
    /**
     * 根据条件获取记录
     * @param array $fields
     * @param array where
     */
    public function fetchWhere($where=[],$fields='*',$limit='')
    {
        if(empty($where))
            return empty($limit)?$this->db_query->field($fields)->select():$this->db_query->field($fields)->limit($limit)->select();
        else
            return empty($limit)?$this->db_query->field($fields)->where($where)->select():
            $this->db_query->field($fields)->where($where)->limit($limit)->select();
    }
    /**
     * 获取排序结果
     */
    public function fetchOrder($order=[],$fields='',$limit=0)
    {
        $query=$this->db_query;
        !empty($fields)&&$query=$query->field($fields);
        $sortby='';
       if(is_array($order)&&isset($order[1])){
           $sortby=$order[1];
           unset($order[1]);
       }
        $query=$query->order($order,$sortby);
        if(!empty($limit))
            $query=$query->limit($limit);
        return $query->select();
    }
    /**
     * 获取分组排序结果
     */
    public function fetchGroup($group='',$fields='',$limit=0)
    {
        $query=$this->db_query;
        !empty($fields)&&$query=$query->field($fields);
        $query=$query->group($group);
        if(!empty($limit))
            $query=$query->limit($limit);
       return $query->select();
    }
    /**
     * 获取query
     * @param  $fun
     * @param  $args
     * @return Query|mixed;
     */
    function __call($fun,$args)
    {
        if(method_exists($this->db_query, $fun)){
        $result=call_user_func_array([$this->db_query,$fun],$args);
        if($result instanceof Query){
            $this->db_query=$result;
            return $this;
        }else{
            return $result;
        }
        }
        throw new  \Exception(__CLASS__.'::'.$fun.' method not exist');
    }
    /**
     * 重新选择数据表
     * @param string $tablename
     */
    public function setDbTable($tablename='')
    {
        if(empty($tablename))
            return;
        $this->db_query=$this->db_conn->name($tablename);
        return $this;
    }
    public function select()
    {
        return $this->db_query->select();
    }
    /**
     * 保存数据
     */
    public function save($data=[],$id=0,$primary='')
    {
        empty($primary)&&$primary=$this->getFields(2);
        return $this->db_query->where($primary,'=',$id)->update($data);
    }
    /**
     * 更新数据
     * @param array $data
     */
    public function saveAll($data=[],$where=[])
    {
        return empty($where)?$this->db_query->update($data):$this->db_query->where($where)->update($data);
    }
   /**
    * 计数
    */
    function getCount($where=[],$name='*')
    {
        return empty($where)?$this->db_query->count($name):$this->db_query->field($name)->where($where)->count($name);
    }
    /**
     * 插入新数据
     */
    public function insert($data=[],$returnId=true)
    {
        return $this->db_query->insert($data,false,$returnId);
    }
    /**
     * 关联模型查询
     */
    public function relationQuery($model,$foreignKey,$localKey='',$type='children')
    {
        empty($localKey)&&$localKey=$this->getFields(2);
        if($model instanceof Model)
            $model=$model->db_table;
        if(empty($localKey))
            throw new \Exception('primary key no exists');
       if($type=='children'){
            $this->db_query=$this->db_query->join($model.' Alias',$this->db_query->getTable().".".$localKey."=Alias.".$foreignKey,'left');
        }
        if($type=='parent'){
            $this->db_query=$this->db_query->join($model.' Alias',$this->db_table->getTable().".".$localKey."=Alias.".$foreignKey,'right');
        }
        return $this;
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
    public function where($field,$op=null,$condition=null)
    {
        $this->db_query=$this->db_query->where($field,$op,$condition);
        return $this;
    }
    function offsetGet($name)
    {
        if(isset($this->data[$name]))
            return $this->data[$name];
    }
    function offsetSet($name,$value)
    {
        if(isset($this->data[$name]))
            $this->data[$name]=$value;
    }
   function offsetExists($offset)
   {
       return isset($this->data[$offset]);
   }
   function offsetUnset($offset){
       unset($this->data[$offset]);
   }
   public function __get($name)
   {
       if(isset($this->data[$name]))
           return $this->data[$name];
       
   }
   public function __set($name,$value)
   {
       $this->data[$name]=$value;
   }
    /**
     * 分页
     */
    public function paginator($page_num=10,$config=[],$simple=null)
    {
        return $this->db_query->paginate($page_num,$simple,$config);
    }
    /**
     * 关闭连接，释放资源
     */
    protected function resetConn()
    {
        $this->db_conn->free();
        $this->db_conn->close();
    }
    public function free()
    {
        $this->db_conn->free();
    }
    /**
     * 关闭连接，释放资源
     */
    function __destruct()
    {
        $this->resetConn();
        unset($this->db_conn,$this->db_query);
        foreach(self::$conn_instance as $k=>$inst){
            $inst->free();
            $inst->close();
        }
       // self::$conn_instance=[];
    }
}