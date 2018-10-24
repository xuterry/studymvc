<?php
namespace core\cache;
use core\cache\Driver;
/**
sqlite 缓存
*/
class Sqlite extends Driver
{
    protected $options = [
        'db'         =>CACHE_PATH.DS. 'sqlite.db',
        'table'      =>'cache',
        'prefix'     => '',
        'expire'     => 0,
    ];
    function __construct($options=[])
    {
        if(!extension_loaded('sqlite3'))
            throw new \Exception('can not load sqlite3');
        $this->options=array_merge($this->options,$options);
        $class='Sqlite3';
        $this->handler=new $class($this->options['db']);
        $this->selectTable($this->options['table']);
    }
    /**
    * 清除缓存
    * @param string $tag
    */
     public  function clear($tag='')
     {
         if ($tag) {
             $name = $this->handler->escapeString($tag);
             $sql  = 'DELETE FROM ' . $this->options['table'] . ' WHERE tag=\'' . $name . '\'';
             $this->handler->query($sql);
             return true;
         }
         $sql = 'DELETE FROM ' . $this->options['table'];
         $this->handler->query($sql);
         return true;
     }
    
    /**
     * 根据name删除缓存
     * @param string $name
     */
     public function del($name='')
     {
         $name = $this->getPreKey($name);
         $sql  = 'DELETE FROM ' . $this->options['table'] . ' WHERE var=\'' . $name . '\'';
         $this->handler->query($sql);
         return true;
     }
    
    /**
     * 获取缓存
     * @param string $name
     * @param string $default
     */
     public function get($name='',$default=false)
     {
         $name   = $this->getPreKey($name);
         $sql    = 'SELECT value FROM ' . $this->options['table'] . ' WHERE var=\'' . $name . '\' AND (expire=0 OR expire >' . $_SERVER['REQUEST_TIME'] . ') LIMIT 1';
         $content = $this->handler->querySingle($sql);
         if ($content) {
            // $content=$this->handler->escapeString($content);
             if (function_exists('gzcompress')) {
                 //启用数据压缩
             //   $content = gzuncompress($content);
             }
             return unserialize($content);
         }
         return $default;
     }
    
    /**
     * 判断是否存在缓存
     */
    public function has($name='')
    {
        $name   = $this->getPreKey($name);
        $sql    = 'SELECT value FROM ' . $this->options['table'] . ' WHERE var=\'' . $name . '\' AND (expire=0 OR expire >' . $_SERVER['REQUEST_TIME'] . ') LIMIT 1';
        $result = $this->handler->exec($sql);
        return $result->fetch_num_rows();
    }
    /**
     * 设置缓存
     * @param ring $name
     * @param mix $value
     * @param number $expire
     */
     public function set($name,$value,$expire=null)
     {
         
         $name  = $this->getPreKey($name);         
         $value = serialize($value); 
         $value=$this->handler->escapeString($value);
         if (is_null($expire)) {
             $expire = $this->options['expire'];
         }
         if ($expire instanceof \DateTime) {
             $expire = $expire->getTimestamp();
         } else {
             $expire = (0 == $expire) ? 0 : (time() + $expire); //缓存有效期为0表示永久缓存
         }
         if (function_exists('gzcompress')) {
             //数据压缩
            // $value = gzcompress($value, 5);
         }       
         if ($this->tag) {
             $tag       = $this->tag;
             $this->tag = null;
         } else {
             $tag = '';
         }
       //  $value='1';
         $sql = 'REPLACE INTO ' . $this->options['table'] . ' (var, value, expire, tag) VALUES (\'' . $name . '\', \'' . $value . '\', \'' . $expire . '\', \'' . $tag . '\')';
       //  exit($sql);
         if ($this->handler->query($sql)) {
             return true;
         }
         return false;
     }
    protected function selectTable()
    {
        $sql= "CREATE TABLE IF NOT EXISTS ".$this->options['table']."  (var TEXT,   value TEXT,  expire TEXT, tag TEXT);";       
        $rs=$this->handler->query($sql);
    }
    public function getPreKey($name)
    {
        return $this->options['prefix'].$this->handler->escapeString($name);
    }
    function __destruct()
    {
        $this->handler->close();
    }
}