<?php
namespace core\db;

use PDO;
use PDOStatement;
use core\Db;
/**
 *  Class Connection
 * @method Query table(string $table) 指定数据表（含前缀）
 * @method Query name(string $name) 指定数据表（不含前缀）
 *
 */
abstract class Connection
{

    protected $PDOStatement;

    protected $queryStr = '';

    protected $error = '';

    protected $links = [];
    
    /** @var PDO 当前连接ID */
    protected $linkID;

    protected $linkRead;

    protected $linkWrite;

    protected $fetchType = PDO::FETCH_ASSOC;

    protected $attrCase = PDO::CASE_LOWER;

    protected static $event = [];

    protected $builder;

    protected $params = [
                            PDO::ATTR_CASE => PDO::CASE_NATURAL,PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,PDO::ATTR_STRINGIFY_FETCHES => false,PDO::ATTR_EMULATE_PREPARES => false
    ];

    // 绑定参数
    protected $bind = [];

    protected $config = [
                            // 数据库类型
                            'type' => '',
                            // 服务器地址
                            'hostname' => '',
                            // 数据库名
                            'database' => '',
                            // 用户名
                            'username' => '',
                            // 密码
                            'password' => '',
                            // 端口
                            'hostport' => '',
                            // 连接dsn
                            'dsn' => '',
                            // 数据库连接参数
                            'params' => [],
                            // 数据库编码默认采用utf8
                            'charset' => 'utf8',
                            // 数据库表前缀
                            'prefix' => '',
                            // 数据库调试模式
                            'debug' => false,
                            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
                            'deploy' => 0,
                            // 数据库读写是否分离 主从式有效
                            'rw_separate' => false,
                            // 读写分离后 主服务器数量
                            'master_num' => 1,
                            // 指定从服务器序号
                            'slave_no' => '',
                            // 模型写入后自动读取主服务器
                            'read_master' => false,
                            // 是否严格检查字段是否存在
                            'fields_strict' => true,
                            // 数据返回类型
                            'result_type' => PDO::FETCH_ASSOC,
                            // 数据集返回类型
                            'resultset_type' => 'array',
                            // 自动写入时间戳字段
                            'auto_timestamp' => false,
                            // 时间字段取出后的默认时间格式
                            'datetime_format' => 'Y-m-d H:i:s',
                            // 是否需要进行SQL性能分析
                            'sql_explain' => false,
                            // Builder类
                            'builder' => '',
                            // Query类
                            'query' => '\\core\\db\\Query',
                            // 是否需要断线重连
                            'break_reconnect' => false
    ];

    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        !empty($this->config['result_type'])&&$this->fetchType=$this->config['result_type'];       
    }

    /**
     * 解析pdo连接的dsn信息
     * 
     * @access protected
     * @param array $config
     *            连接信息
     * @return string
     */
    abstract protected function parseDsn($config);

    /**
     * 取得数据表的字段信息
     * 
     * @access public
     * @param string $tableName            
     * @return array
     */
    abstract public function getFields($tableName);

    /**
     * 取得数据库的表信息
     * 
     * @access public
     * @param string $dbName            
     * @return array
     */
    abstract public function getTables($dbName);

    /**
     * SQL性能分析
     * 
     * @access protected
     * @param string $sql            
     * @return array
     */
    abstract protected function getExplain($sql);

    protected function getQuery()
    {
        return new $this->config['query']($this);
    }

    public function getBuilder()
    {
        return empty($this->builder) ? ($this->getConfig('builder') ?: 'core\\db\\' . strtolower($this->getConfig('type') ). '\\Builder') : $this->builder;
    }

    public function __call($method, $args)
    {
        return call_user_func_array([
                                        $this->getQuery(),$method
        ], $args);
    }

    public function getConfig($config = '')
    {
        return empty($config) ? $this->config : $this->config[$config];
    }

    public function fieldCase($info)
    {
        if ($this->attrCase == PDO::CASE_NATURAL)
            return $info;
        return $this->attrCase == PDO::CASE_LOWER ? array_change_key_case($info) : array_change_key_case($info, CASE_UPPER);
    }

    public function setConfig($name, $value = '')
    {
        if (is_array($name)) {
            $this->config = array_merge($this->config, $name);
        } else
            $this->config[$name] = $value;
    }

    public function connect($config = [], $linkNum = 0, $autoConnection = false)
    {
         if (! isset($this->links[$linkNum])) {
            $config = empty($config) ? $this->config : $config;
            $params = isset($config['params']) && is_array($config['params']) ? ($config['params'] + $this->params) : $this->params;
            $this->attrCase = $params[PDO::ATTR_CASE];
            ! isset($config['dsn']) ?: $config['dsn'] = $this->parseDsn($config);
            try {
                // var_dump($config);exit();
                $this->links[$linkNum] = new PDO($config['dsn'], $config['username'], $config['password'], $params);
            } catch (\Exception $e) {
                if ($autoConnection)
                    return $this->connect($autoConnection, $linkNum);
                else
                    throw $e;
            }
        }
        return $this->links[$linkNum];
    }

    public function free()
    {
        $this->PDOStatement = null;
    }

    public function getPdo()
    {
        return ! $this->linkID ? false : $this->linkID;
    }

    /**
     * PDO query查询
     */
    public function query($sql, $bind = [], $master = false, $pdo = false)
    {
        $this->initConnect($master);
        if (! $this->linkID)
            return false;
        $this->queryStr = $sql;
        $bind ? $this->bind = $bind : '';
        
        try {
            if(!empty($this->PDOStatement))
                $this->free();
            if(empty($this->PDOStatement))
                $this->PDOStatement = $this->linkID->prepare($sql);
            $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), [
                                                                            'call','exec'
            ]);
            if ($procedure) {
                $this->bindParam($bind);
            } else {
                $this->bindValue($bind);
            }
           // echo $sql.'<br>';var_dump($this->PDOStatement);
            $this->PDOStatement->execute();
            return $this->getResult($pdo, $procedure);
        } catch (\Exception $e) {
            if ($this->isBreak($e))
                return $this->close->query($sql, $bind, $master, $pdo);
            throw new \Exception($e->getMessage() . $this->getLastsql());
        } catch (\Throwable $e) {
            if ($this->isBreak($e))
                return $this->close()->query($sql, $bind, $master, $pdo);
            throw $e;
        }
    }

    /**
     * pdo execute
     */
    public function execute($sql, $bind = [],Query $query=null)
    {
        $this->initConnect(true);
        if (! $this->linkID)
            return false;
        $this->queryStr = $sql;
        $bind ? $this->bind = $bind : '';
        try {
            if(!empty($this->PDOStatement)&&$this->PDOStatement!=$sql)
                $this->free();
            if(empty($this->PDOStatement))
                $this->PDOStatement = $this->linkID->prepare($sql);
            $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), [
                                                                            'call','exec'
            ]);
            if ($procedure) {
                $this->bindParam($bind);
            } else {
                $this->bindValue($bind);
            }
            
            $this->PDOStatement->execute();
            
            if ($query && !empty($this->config['deploy']) && !empty($this->config['read_master'])) {
                $query->readMaster();
            }
            
            return $this->PDOStatement->rowCount();
        } catch (\Exception $e) {
            if ($this->isBreak($e))
                return $this->close->excute($sql, $bind,$query);
            throw new \Exception($e->getMessage() . $this->getLastsql());
        } catch (\Throwable $e) {
            if ($this->isBreak($e))
                return $this->close()->execute($sql, $bind,$query);
            throw $e;
        }
    }
    public function getRealSql($sql, array $bind = [])
    {
        if (is_array($sql)) {
            $sql = implode(';', $sql);
        }
        
        foreach ($bind as $key => $val) {
            $value = is_array($val) ? $val[0] : $val;
            $type  = is_array($val) ? $val[1] : PDO::PARAM_STR;
            if (PDO::PARAM_STR == $type) {
                $value = $this->quote($value);
            } elseif (PDO::PARAM_INT == $type) {
                $value = (float) $value;
            }
            // 判断占位符
            $sql = is_numeric($key) ?
            substr_replace($sql, $value, strpos($sql, '?'), 1) :
            str_replace(
                [':' . $key . ')', ':' . $key . ',', ':' . $key . ' ', ':' . $key . PHP_EOL],
                [$value . ')', $value . ',', $value . ' ', $value . PHP_EOL],
                $sql . ' ');
        }
        return rtrim($sql);
    }
    
    protected function bindValue(array $bind = [])
    {
        foreach ($bind as $key => $val) {
            // 占位符
            $param = is_numeric($key) ? $key + 1 : ':' . $key;
            if (is_array($val)) {
                if (PDO::PARAM_INT == $val[1] && '' === $val[0]) {
                    $val[0] = 0;
                }
                $result = $this->PDOStatement->bindValue($param, $val[0], $val[1]);
            } else {
                $result = $this->PDOStatement->bindValue($param, $val);
            }
            if (!$result) {
                throw new \Exception(
                    "Error occurred  when binding parameters '{$param}'"
                );
            }
        }
    }
    
    protected function bindParam($bind)
    {
        foreach ($bind as $key => $val) {
            $param = is_numeric($key) ? $key + 1 : ':' . $key;
            if (is_array($val)) {
                array_unshift($val, $param);
                $result = call_user_func_array([$this->PDOStatement, 'bindParam'], $val);
            } else {
                $result = $this->PDOStatement->bindValue($param, $val);
            }
            if (!$result) {
                $param = array_shift($val);
                throw new \Exception(
                    "Error occurred  when binding parameters '{$param}'"
                );
            }
        }
    }
    
    
    protected function getResult($pdo = false, $procedure = false)
    {
        if ($pdo) {
            // 返回PDOStatement对象处理
            return $this->PDOStatement;
        }
        if ($procedure) {
            // 存储过程返回结果
            return $this->procedure();
        }
        $result        = $this->PDOStatement->fetchAll($this->fetchType);
        $this->numRows = count($result);
        return $result;
    }
    
    protected function procedure()
    {
        $item = [];
        do {
            $result = $this->getResult();
            if ($result) {
                $item[] = $result;
            }
        } while ($this->PDOStatement->nextRowset());
        $this->numRows = count($item);
        return $item;
    }
    
    public function close()
    {
        $this->linkID    = null;
        $this->linkWrite = null;
        $this->linkRead  = null;
        $this->links     = [];
        return $this;
    }
    
    protected function isBreak($e)
    {
        if (!$this->config['break_reconnect']) {
            return false;
        }
        
        $info = [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'failed with errno',
        ];
        
        $error = $e->getMessage();
        
        foreach ($info as $msg) {
            if (false !== stripos($error, $msg)) {
                return true;
            }
        }
        return false;
    }
    public function getLastSql()
    {
        return $this->getRealSql($this->queryStr, $this->bind);
    }
    
    public function getLastInsID($sequence = null)
    {
        return $this->linkID->lastInsertId($sequence);
    }
    
    public function getNumRows()
    {
        return $this->numRows;
    }
    protected function initConnect($master = true)
    {
        if (!empty($this->config['deploy'])) {
            // 采用分布式数据库
            if ($master || $this->transTimes) {
                if (!$this->linkWrite) {
                    $this->linkWrite = $this->multiConnect(true);
                }
                $this->linkID = $this->linkWrite;
            } else {
                if (!$this->linkRead) {
                    $this->linkRead = $this->multiConnect(false);
                }
                $this->linkID = $this->linkRead;
            }
        } elseif (!$this->linkID) {
            // 默认单数据库
            $this->linkID = $this->connect();
        }
    }
    
    public function __destruct()
    {
        // 释放查询
        //echo $this->queryStr;
        if ($this->PDOStatement) {
            $this->free();
        }
        // 关闭连接
        $this->close();
    }
}