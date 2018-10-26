<?php
namespace core\db;

use PDO;
use core\Db;
use core\Loader;
use core\db\Connection;
use core\Cache;
use core\Config;
use core\Collection;

/**
 * 查询类,参考tp5
 */
class Query
{

    protected $builder;

    protected $connection;

    protected $model;

    protected $table;

    protected $name;

    protected $pk;

    protected $prefix;

    protected $options = [];

    protected $bind = [];

    protected static $info = [];

    private static $readMaster;

    public function __construct(Connection $connection = null, $model = null)
    {
        $this->connection = $connection ?: Db::connect([], true);
        $this->prefix = $this->getConfig('prefix');
        $this->model = $model;
        $this->setBuilder();
    }

    function __call($method, $args)
    {
        if (strtolower(substr($method, 0, 5)) == 'getby') {
            $name = Loader::parseName(substr($name, 5));
            return $this->where([
                                    $name => $args[0]
            ])->find();
        } elseif (strtolower(substr($method, 0, 10)) == 'getfieldby') {
            $name = Loader::parseName(substr($method, 10));
            return $this->where([
                                    $name => $args[0]
            ])->value($args[1]);
        } else
            throw new \Exception('method not exist ' . __CLASS__ . '->' . $method);
    }

    function getConnection()
    {
        return $this->connection;
    }

    public function connect($config)
    {
        $this->connection = Db::connect($config);
        $this->setBuilder();
        $this->prefix = $this->connection->getConfig('prefix');
        return $this;
    }

    function setBuilder()
    {
        $class = $this->connection->getBuilder();
        $this->builder = new $class($this->connection, $this);
    }

    function getModel()
    {
        return $this->model;
    }

    function readMaster($allTable = false)
    {
        $table = $allTable ? '*' : (isset($this->options['table']) ? $this->options['table'] : $this->getTable());
        self::$readMaster[$table] = true;
        return $this;
    }

    function getBuilder()
    {
        return $this->builder;
    }

    function name($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public function getTable($name = '')
    {
        if ($name || empty($this->table)) {
            $name = $name ?: $this->name;
            $tablename = $this->prefix;
            if ($name)
                $tablename .= Loader::parseName($name);
        } else
            $tablename = $this->table;
        return $tablename;
    }

    public function parseSqlTable($sql)
    {
        if (false !== strpos($sql, '__')) {
            $prefix = $this->prefix;
            $sql = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                return $prefix . strtolower($match[1]);
            }, $sql);
        }
        return $sql;
    }

    public function query($sql, $bind = [], $master = false, $class = false)
    {
        return $this->connection->query($sql, $bind, $master, $class);
    }

    public function execute($sql, $bind = [])
    {
        return $this->connection->execute($sql, $bind);
    }

    public function getLastInsId($sequence = null)
    {
        return $this->connection->getLastInsID($sequence);
    }

    public function getLastSql()
    {
        return $this->connection->getLastSql();
    }

    public function commit()
    {
        $this->connection->commit();
    }

    public function getConfig($name = '')
    {
        return $this->connection->getConfig($name);
    }

    public function value($field, $default = null, $force = false)
    {
        $result = false;
        if (empty($this->option['fetch_sql']) && ! empty($this->options['cache'])) {
            $cache = $this->options['cache'];
            $this->options['table'] = empty($this->options['table']) ? $this->getTable() : '';
            $key = $this->getCacheKeyName($cache['key'], $field);
            $result = Cache::get($key);
        }
        if ($result === false) {
            if (isset($this->options['field']))
                unset($this->options['field']);
            $pdo = $this->field($field)
                ->limit(1)
                ->getPdo();
            if (is_string($pdo))
                return $pdo;
            $result = $pdo->fetchColumn();
            if ($force)
                $result += 0;
            if (isset($cache))
                $this->cacheData($key, $result, $cache);
        } else
            $this->options = [];
        return $result !== false ?: $default;
    }

    protected function getCacheKeyName($key, $field)
    {
        return is_string($key) ? $key : md5($this->connection->getConfig('database') . '.' . $field . serialize($this->options) . serialize($this->bind));
    }

    public function column($field, $key = '')
    {
        $result = false;
        if (empty($this->option['fetch_sql']) && ! empty($this->options['cache'])) {
            $cache = $this->options['cache'];
            $this->options['table'] = empty($this->options['table']) ? $this->getTable() : '';
            $cachekey = $this->getCacheKeyName($cache['key'], $field);
            $result = Cache::get($cachekey);
        }
        if ($result === false) {
            if (isset($this->options['field']))
                unset($this->options['field']);
            if (is_null($field))
                $field = '*';
            elseif ($key && $field != '*')
                $field = $key . ',' . $field;
            $pdo = $this->field($field)->getPdo();
            if (is_string($pdo))
                return $pdo;
            if ($pdo->columnCount())
                $result = $pdo->fetchAll(PDO::FETCH_COLUMN);
            else {
                $result_set = $pdo->fetchAll(PDO::FETCH_ASSOC);
                if ($result_set) {
                    $fields = array_keys($result_set[0]);
                    $count = count($fields);
                    $key1 = array_shift($fields);
                    $key2 = $field ? array_shift($fields) : '';
                    $key = $key ?: $key1;
                    if (strpos($key, '.'))
                        list ($alias, $key) = explode(".", $key);
                    foreach ($result_set as $v) {
                        if ($count > 2)
                            $result[$v[$key]] = $v;
                        elseif ($count == 2)
                            $result[$v[$key]] = $v[$key2];
                        elseif ($count == 1)
                            $result[$v[$key]] = $v[$key1];
                    }
                } else
                    $result = [];
            }
            if (isset($cache) && isset($cachekey))
                $this->cacheData($cachekey, $result, $cache);
        } else
            $this->options = [];
        return $result;
    }
    public function count($field='')
    {
        if(isset($this->options['group'])){
            $options=$this->getOptions();
            $subsql=$this->options($options)->field('count('.$field.')')->bind($this->bind)->buildSql();
            return $this->table([$subsql=>'_group_count_'])->value('COUNT(*) AS c_count',0,true);
        }
        return $this->value('count('.$field.') as c_count',0,true);
    }
    
    public function sum($field)
    {
        return $this->value('sum('.$field.') as c_sum',0,true);
    }
    public function min($field, $force = true)
    {
        return $this->value('MIN(' . $field . ') AS c_min', 0, $force);
    }
    public function max($field, $force = true)
    {
        return $this->value('MAX(' . $field . ') AS c_max', 0, $force);
    }
    
    public function avg($field)
    {
        return $this->value('AVG(' . $field . ') AS c_avg', 0, true);
    }
    
    public function setField($field='',$value='')
    {
        return is_array($field)?$field:[$field=>$value];
    }
    
    public function join($join,$condition=null,$type='INNER')
    {
        if(empty($condition)){
            foreach($join as $key=>$value){
                if(is_array($value)&&count($value)>2){
                    $this->join($value[0],$value[1],isset($value[2])?$value[2]:$type);
                }
            }
        }else{
            $table=$this->getJoinTable($join);
            $this->options['join'][]=[$table,strtolower($type), $condition];
        }
        return $this;
    }
    /**
     * 获取Join表名及别名 支持
     * ['prefix_table或者子查询'=>'alias'] 'prefix_table alias' 'table alias'
     * @access public
     * @param array|string $join
     * @return array|string
     */
    protected function getJoinTable($join, &$alias = null)
    {
        // 传入的表名为数组
        if (is_array($join)) {
            $table = $join;
            $alias = array_shift($join);
        } else {
            $join = trim($join);
            if (false !== strpos($join, '(')) {
                // 使用子查询
                $table = $join;
            } else {
                $prefix = $this->prefix;
                if (strpos($join, ' ')) {
                    // 使用别名
                    list($table, $alias) = explode(' ', $join);
                } else {
                    $table = $join;
                    if (false === strpos($join, '.') && 0 !== strpos($join, '__')) {
                        $alias = $join;
                    }
                }
                if ($prefix && false === strpos($table, '.') && 0 !== strpos($table, $prefix) && 0 !== strpos($table, '__')) {
                    $table = $this->getTable($table);
                }
            }
            if (isset($alias) && $table != $alias) {
                $table = [$table => $alias];
            }
        }
        return $table;
    }
    
    public function union($union, $all = false)
    {
        $this->options['union']['type'] = $all ? 'UNION ALL' : 'UNION';
        
        if (is_array($union)) {
            $this->options['union'] = array_merge($this->options['union'], $union);
        } else {
            $this->options['union'][] = $union;
        }
        return $this;
    }
    
    public function field($field,$except=false,$table_name='',$prefix='',$alias='')
    {
        if(empty($field))
            return $this;
        if(is_string($field)){
            if (preg_match('/[\<\'\"\(]/', $field)) {
                return $this->fieldRaw($field);
            }
            $field = array_map('trim', explode(',', $field));
        }
        if (true === $field) {
            // 获取全部字段
            $fields = $this->getTableInfo($table_name ?: (isset($this->options['table']) ? $this->options['table'] : ''), 'fields');
            $field  = $fields ?: ['*'];
        } elseif ($except) {
            // 字段排除
            $fields = $this->getTableInfo($table_name ?: (isset($this->options['table']) ? $this->options['table'] : ''), 'fields');
            $field  = $fields ? array_diff($fields, $field) : $field;
        }
        if ($table_name) {
            // 添加统一的前缀
            $prefix = $prefix ?: $table_name;
            foreach ($field as $key => $val) {
                if (is_numeric($key)) {
                    $val = $prefix . '.' . $val . ($alias ? ' AS ' . $alias . $val : '');
                }
                $field[$key] = $val;
            }
        }
        
        if (isset($this->options['field'])) {
            $field = array_merge((array) $this->options['field'], $field);
        }
        $this->options['field'] = array_unique($field);
        return $this;
    }
    public function fieldRaw($field, array $bind = [])
    {
        $this->options['field'][] = $this->raw($field);
        if ($bind) {
            $this->bind($bind);
        }
        return $this;
    }
    public function data($field, $value = null)
    {
        if (is_array($field)) {
            $this->options['data'] = isset($this->options['data']) ? array_merge($this->options['data'], $field) : $field;
        } else {
            $this->options['data'][$field] = $value;
        }
        return $this;
    }
    
    public function exp($field, $value)
    {
        $this->data($field, $this->raw($value));
        return $this;
    }
    
    public function raw($value)
    {
        return new Expression($value);
    }
    
    public function view($join, $field = true, $on = null, $type = 'INNER')
    {
        $this->options['view'] = true;
        if (is_array($join) && key($join) === 0) {
            foreach ($join as $key => $val) {
                $this->view($val[0], $val[1], isset($val[2]) ? $val[2] : null, isset($val[3]) ? $val[3] : 'INNER');
            }
        } else {
            $fields = [];
            $table  = $this->getJoinTable($join, $alias);
            
            if (true === $field) {
                $fields = $alias . '.*';
            } else {
                if (is_string($field)) {
                    $field = explode(',', $field);
                }
                foreach ($field as $key => $val) {
                    if (is_numeric($key)) {
                        $fields[]                   = $alias . '.' . $val;
                        $this->options['map'][$val] = $alias . '.' . $val;
                    } else {
                        if (preg_match('/[,=\.\'\"\(\s]/', $key)) {
                            $name = $key;
                        } else {
                            $name = $alias . '.' . $key;
                        }
                        $fields[$name]              = $val;
                        $this->options['map'][$val] = $name;
                    }
                }
            }
            $this->field($fields);
            if ($on) {
                $this->join($table, $on, $type);
            } else {
                $this->table($table);
            }
        }
        return $this;
    }
    
    public function where($field, $op = null, $condition = null)
    {
        $param = func_get_args();
        array_shift($param);
        $this->parseWhereExp('AND', $field, $op, $condition, $param);
        return $this;
    }
    
    public function whereOr($field, $op = null, $condition = null)
    {
        $param = func_get_args();
        array_shift($param);
        $this->parseWhereExp('OR', $field, $op, $condition, $param);
        return $this;
    }
    
    public function whereXor($field, $op = null, $condition = null)
    {
        $param = func_get_args();
        array_shift($param);
        $this->parseWhereExp('XOR', $field, $op, $condition, $param);
        return $this;
    }
    
    public function whereRaw($where, $bind = [], $logic = 'AND')
    {
        $this->options['where'][$logic][] = $this->raw($where);
        
        if ($bind) {
            $this->bind($bind);
        }
        
        return $this;
    }
    
    public function whereOrRaw($where, $bind = [])
    {
        return $this->whereRaw($where, $bind, 'OR');
    }
    
    public function whereNull($field, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'null', null, [], true);
        return $this;
    }
    
    public function whereNotNull($field, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'notnull', null, [], true);
        return $this;
    }
    public function whereExists($condition, $logic = 'AND')
    {
        $this->options['where'][strtoupper($logic)][] = ['exists', $condition];
        return $this;
    }
    
    public function whereNotExists($condition, $logic = 'AND')
    {
        $this->options['where'][strtoupper($logic)][] = ['not exists', $condition];
        return $this;
    }
    
    public function whereIn($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'in', $condition, [], true);
        return $this;
    }
    
    public function whereNotIn($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'not in', $condition, [], true);
        return $this;
    }
    
    public function whereLike($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'like', $condition, [], true);
        return $this;
    }
    
    public function whereNotLike($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'not like', $condition, [], true);
        return $this;
    }
    
    public function whereBetween($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'between', $condition, [], true);
        return $this;
    }
    
    public function whereNotBetween($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'not between', $condition, [], true);
        return $this;
    }
    
    public function whereExp($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'exp', $this->raw($condition), [], true);
        return $this;
    }
    
    public function useSoftDelete($field, $condition = null)
    {
        if ($field) {
            $this->options['soft_delete'] = [$field, $condition ?: ['null', '']];
        }
        return $this;
    }
    
    protected function parseWhereExp($logic, $field, $op, $condition, $param = [], $strict = false)
    {
        $logic = strtoupper($logic);
        if ($field instanceof \Closure) {
            $this->options['where'][$logic][] = is_string($op) ? [$op, $field] : $field;
            return;
        }
        
        if (is_string($field) && !empty($this->options['via']) && !strpos($field, '.')) {
            $field = $this->options['via'] . '.' . $field;
        }
        
        if ($field instanceof Expression) {
            return $this->whereRaw($field, is_array($op) ? $op : []);
        } elseif ($strict) {
            // 使用严格模式查询
            $where[$field] = [$op, $condition];
            
            // 记录一个字段多次查询条件
            $this->options['multi'][$logic][$field][] = $where[$field];
        } elseif (is_string($field) && preg_match('/[,=\>\<\'\"\(\s]/', $field)) {
            $where[] = ['exp', $this->raw($field)];
            if (is_array($op)) {
                // 参数绑定
                $this->bind($op);
            }
        } elseif (is_null($op) && is_null($condition)) {
            if (is_array($field)) {
                // 数组批量查询
                $where = $field;
                foreach ($where as $k => $val) {
                    $this->options['multi'][$logic][$k][] = $val;
                }
            } elseif ($field && is_string($field)) {
                // 字符串查询
                $where[$field]                            = ['null', ''];
                $this->options['multi'][$logic][$field][] = $where[$field];
            }
        } elseif (is_array($op)) {
            $where[$field] = $param;
        } elseif (in_array(strtolower($op), ['null', 'notnull', 'not null'])) {
            // null查询
            $where[$field] = [$op, ''];
            
            $this->options['multi'][$logic][$field][] = $where[$field];
        } elseif (is_null($condition)) {
            // 字段相等查询
            $where[$field] = ['eq', $op];
            
            $this->options['multi'][$logic][$field][] = $where[$field];
        } else {
            if ('exp' == strtolower($op)) {
                $where[$field] = ['exp', $this->raw($condition)];
                // 参数绑定
                if (isset($param[2]) && is_array($param[2])) {
                    $this->bind($param[2]);
                }
            } else {
                $where[$field] = [$op, $condition];
            }
            // 记录一个字段多次查询条件
            $this->options['multi'][$logic][$field][] = $where[$field];
        }
        
        if (!empty($where)) {
            if (!isset($this->options['where'][$logic])) {
                $this->options['where'][$logic] = [];
            }
            if (is_string($field) && $this->checkMultiField($field, $logic)) {
                $where[$field] = $this->options['multi'][$logic][$field];
            } elseif (is_array($field)) {
                foreach ($field as $key => $val) {
                    if ($this->checkMultiField($key, $logic)) {
                        $where[$key] = $this->options['multi'][$logic][$key];
                    }
                }
            }
            $this->options['where'][$logic] = array_merge($this->options['where'][$logic], $where);
        }
    }
    private function checkMultiField($field, $logic)
    {
        return isset($this->options['multi'][$logic][$field]) && count($this->options['multi'][$logic][$field]) > 1;
    }
    public function removeWhereField($field, $logic = 'AND')
    {
        $logic = strtoupper($logic);
        if (isset($this->options['where'][$logic][$field])) {
            unset($this->options['where'][$logic][$field]);
            unset($this->options['multi'][$logic][$field]);
        }
        return $this;
    }
    
    public function removeOption($option = true)
    {
        if (true === $option) {
            $this->options = [];
        } elseif (is_string($option) && isset($this->options[$option])) {
            unset($this->options[$option]);
        }
        return $this;
    }
    
    public function limit($offset, $length = null)
    {
        if (is_null($length) && strpos($offset, ',')) {
            list($offset, $length) = explode(',', $offset);
        }
        $this->options['limit'] = intval($offset) . ($length ? ',' . intval($length) : '');
        return $this;
    }
    public function page($page, $listRows = null)
    {
        if (is_null($listRows) && strpos($page, ',')) {
            list($page, $listRows) = explode(',', $page);
        }
        $this->options['page'] = [intval($page), intval($listRows)];
        return $this;
    }
    
    public function paginate($listRows = null, $simple = false, $config = [])
    {
        if (is_int($simple)) {
            $total  = $simple;
            $simple = false;
        }
        if (is_array($listRows)) {
            $config   = array_merge(Config::get('paginate'), $listRows);
            $listRows = $config['list_rows'];
        } else {
            $config   = array_merge(Config::get('paginate'), $config);
            $listRows = $listRows ?: $config['list_rows'];
        }
        
        /** @var Paginator $class */
        $class = false !== strpos($config['type'], '\\') ? $config['type'] : '\\core\\Paginator';
        $page  = isset($config['page']) ? (int) $config['page'] : call_user_func([
            $class,
            'getCurrentPage',
        ], $config['var_page']);
        
        $page = $page < 1 ? 1 : $page;
        
        $config['path'] = isset($config['path']) ? $config['path'] : call_user_func([$class, 'getCurrentPath']);
        
        if (!isset($total) && !$simple) {
            $options = $this->getOptions();
            
            unset($this->options['order'], $this->options['limit'], $this->options['page'], $this->options['field']);
            
            $bind    = $this->bind;
            $total   = $this->count();
            $results = $this->options($options)->bind($bind)->page($page, $listRows)->select();
        } elseif ($simple) {
            $results = $this->limit(($page - 1) * $listRows, $listRows + 1)->select();
            $total   = null;
        } else {
            $results = $this->page($page, $listRows)->select();
        }
        return $class::make($results, $listRows, $page, $total, $simple, $config);
    }
    
    public function table($table)
    {
        if (is_string($table)) {
            if (strpos($table, ')')) {
                // 子查询
            } elseif (strpos($table, ',')) {
                $tables = explode(',', $table);
                $table  = [];
                foreach ($tables as $item) {
                    list($item, $alias) = explode(' ', trim($item));
                    if ($alias) {
                        $this->alias([$item => $alias]);
                        $table[$item] = $alias;
                    } else {
                        $table[] = $item;
                    }
                }
            } elseif (strpos($table, ' ')) {
                list($table, $alias) = explode(' ', $table);
                
                $table = [$table => $alias];
                $this->alias($table);
            }
        } else {
            $tables = $table;
            $table  = [];
            foreach ($tables as $key => $val) {
                if (is_numeric($key)) {
                    $table[] = $val;
                } else {
                    $this->alias([$key => $val]);
                    $table[$key] = $val;
                }
            }
        }
        $this->options['table'] = $table;
        return $this;
    }
    
    public function using($using)
    {
        $this->options['using'] = $using;
        return $this;
    }
    
    public function order($field, $order = null)
    {
        if (empty($field)) {
            return $this;
        } elseif ($field instanceof Expression) {
            $this->options['order'][] = $field;
            return $this;
        }
        
        if (is_string($field)) {
            if (!empty($this->options['via'])) {
                $field = $this->options['via'] . '.' . $field;
            }
            if (strpos($field, ',')) {
                $field = array_map('trim', explode(',', $field));
            } else {
                $field = empty($order) ? $field : [$field => $order];
            }
        } elseif (!empty($this->options['via'])) {
            foreach ($field as $key => $val) {
                if (is_numeric($key)) {
                    $field[$key] = $this->options['via'] . '.' . $val;
                } else {
                    $field[$this->options['via'] . '.' . $key] = $val;
                    unset($field[$key]);
                }
            }
        }
        if (!isset($this->options['order'])) {
            $this->options['order'] = [];
        }
        if (is_array($field)) {
            $this->options['order'] = array_merge($this->options['order'], $field);
        } else {
            $this->options['order'][] = $field;
        }
        
        return $this;
    }
    
    public function orderRaw($field, array $bind = [])
    {
        $this->options['order'][] = $this->raw($field);
        
        if ($bind) {
            $this->bind($bind);
        }
        
        return $this;
    }
    
    public function cache($key = true, $expire = null, $tag = null)
    {
        // 增加快捷调用方式 cache(10) 等同于 cache(true, 10)
        if ($key instanceof \DateTime || (is_numeric($key) && is_null($expire))) {
            $expire = $key;
            $key    = true;
        }
        
        if (false !== $key) {
            $this->options['cache'] = ['key' => $key, 'expire' => $expire, 'tag' => $tag];
        }
        return $this;
    }
    
    public function group($group)
    {
        $this->options['group'] = $group;
        return $this;
    }
    
    public function having($having)
    {
        $this->options['having'] = $having;
        return $this;
    }
    
    public function lock($lock = false)
    {
        $this->options['lock']   = $lock;
        $this->options['master'] = true;
        return $this;
    }
    
    public function distinct($distinct)
    {
        $this->options['distinct'] = $distinct;
        return $this;
    }
    public function alias($alias)
    {
        if (is_array($alias)) {
            foreach ($alias as $key => $val) {
                if (false !== strpos($key, '__')) {
                    $table = $this->parseSqlTable($key);
                } else {
                    $table = $key;
                }
                $this->options['alias'][$table] = $val;
            }
        } else {
            if (isset($this->options['table'])) {
                $table = is_array($this->options['table']) ? key($this->options['table']) : $this->options['table'];
                if (false !== strpos($table, '__')) {
                    $table = $this->parseSqlTable($table);
                }
            } else {
                $table = $this->getTable();
            }
            
            $this->options['alias'][$table] = $alias;
        }
        return $this;
    }
  
    public function force($force)
    {
        $this->options['force'] = $force;
        return $this;
    }
    
    public function comment($comment)
    {
        $this->options['comment'] = $comment;
        return $this;
    }
    
    public function fetchSql($fetch = true)
    {
        $this->options['fetch_sql'] = $fetch;
        return $this;
    }
    
    public function fetchPdo($pdo = true)
    {
        $this->options['fetch_pdo'] = $pdo;
        return $this;
    }
    
    public function master()
    {
        $this->options['master'] = true;
        return $this;
    }
    public function strict($strict = true)
    {
        $this->options['strict'] = $strict;
        return $this;
    }
    public function failException($fail = true)
    {
        $this->options['fail'] = $fail;
        return $this;
    }
    
    public function sequence($sequence = null)
    {
        $this->options['sequence'] = $sequence;
        return $this;
    }
    
    public function pk($pk)
    {
        $this->pk = $pk;
        return $this;
    }
    
    public function whereTime($field, $op, $range = null)
    {
        if (is_null($range)) {
            if (is_array($op)) {
                $range = $op;
            } else {
                // 使用日期表达式
                switch (strtolower($op)) {
                    case 'today':
                    case 'd':
                        $range = ['today', 'tomorrow'];
                        break;
                    case 'week':
                    case 'w':
                        $range = ['this week 00:00:00', 'next week 00:00:00'];
                        break;
                    case 'month':
                    case 'm':
                        $range = ['first Day of this month 00:00:00', 'first Day of next month 00:00:00'];
                        break;
                    case 'year':
                    case 'y':
                        $range = ['this year 1/1', 'next year 1/1'];
                        break;
                    case 'yesterday':
                        $range = ['yesterday', 'today'];
                        break;
                    case 'last week':
                        $range = ['last week 00:00:00', 'this week 00:00:00'];
                        break;
                    case 'last month':
                        $range = ['first Day of last month 00:00:00', 'first Day of this month 00:00:00'];
                        break;
                    case 'last year':
                        $range = ['last year 1/1', 'this year 1/1'];
                        break;
                    default:
                        $range = $op;
                }
            }
            $op = is_array($range) ? 'between' : '>';
        }
        $this->where($field, strtolower($op) . ' time', $range);

        return $this;
    }
    
    public function getTableInfo($tableName = '', $fetch = '')
    {
        if (!$tableName) {
            $tableName = $this->getTable();
        }
        if (is_array($tableName)) {
            $tableName = key($tableName) ?: current($tableName);
        }
        
        if (strpos($tableName, ',')) {
            // 多表不获取字段信息
            return false;
        } else {
            $tableName = $this->parseSqlTable($tableName);
        }
        
        // 修正子查询作为表名的问题
        if (strpos($tableName, ')')) {
            return [];
        }
        list($guid) = explode(' ', $tableName);
        $db         = $this->getConfig('database');
        if (!isset(self::$info[$db . '.' . $guid])) {
            if (!strpos($guid, '.')) {
                $schema = $db . '.' . $guid;
            } else {
                $schema = $guid;
            }
            // 读取缓存

            if (is_file(RUNTIME_PATH . 'schema/' . $schema . '.php')) {
                $info = include RUNTIME_PATH . 'schema/' . $schema . '.php';
            } else {
                $info = $this->connection->getFields($guid);
            }
            $fields = array_keys($info);
            $bind   = $type   = [];
            foreach ($info as $key => $val) {
                // 记录字段类型
                $type[$key] = $val['type'];
                $bind[$key] = $this->getFieldBindType($val['type']);
                if (!empty($val['primary'])) {
                    $pk[] = $key;
                }
            }
            if (isset($pk)) {
                // 设置主键
                $pk = count($pk) > 1 ? $pk : $pk[0];
            } else {
                $pk = null;
            }
            self::$info[$db . '.' . $guid] = ['fields' => $fields, 'type' => $type, 'bind' => $bind, 'pk' => $pk];
        }
        return $fetch ? self::$info[$db . '.' . $guid][$fetch] : self::$info[$db . '.' . $guid];
    }
    public function getPk($options = '')
    {
        if (!empty($this->pk)) {
            $pk = $this->pk;
        } else {
            $pk = $this->getTableInfo(is_array($options) ? $options['table'] : $options, 'pk');
        }
        return $pk;
    }
    public function getTableFields($table = '')
    {
        return $this->getTableInfo($table ?: $this->getOptions('table'), 'fields');
    }
    
    // 获取当前数据表字段类型
    public function getFieldsType($table = '')
    {
        return $this->getTableInfo($table ?: $this->getOptions('table'), 'type');
    }
    
    // 获取当前数据表绑定信息
    public function getFieldsBind($table = '')
    {
        $types = $this->getFieldsType($table);
        $bind  = [];
        if ($types) {
            foreach ($types as $key => $type) {
                $bind[$key] = $this->getFieldBindType($type);
            }
        }
        return $bind;
    }
    
    protected function getFieldBindType($type)
    {
        if (0 === strpos($type, 'set') || 0 === strpos($type, 'enum')) {
            $bind = PDO::PARAM_STR;
        } elseif (preg_match('/(int|double|float|decimal|real|numeric|serial|bit)/is', $type)) {
            $bind = PDO::PARAM_INT;
        } elseif (preg_match('/bool/is', $type)) {
            $bind = PDO::PARAM_BOOL;
        } else {
            $bind = PDO::PARAM_STR;
        }
        return $bind;
    }
    
    public function bind($key, $value = false, $type = PDO::PARAM_STR)
    {
        if (is_array($key)) {
            $this->bind = array_merge($this->bind, $key);
        } else {
            $this->bind[$key] = [$value, $type];
        }
        return $this;
    }
    
    public function isBind($key)
    {
        return isset($this->bind[$key]);
    }
    
    protected function options(array $options)
    {
        $this->options = $options;
        return $this;
    }
    
    public function getOptions($name = '')
    {
        if ('' === $name) {
            return $this->options;
        } else {
            return isset($this->options[$name]) ? $this->options[$name] : null;
        }
    }
    
    public function with($with)
    {
        if (empty($with)) {
            return $this;
        }
        
        if (is_string($with)) {
            $with = explode(',', $with);
        }
        
        $first = true;
        
        /** @var Model $class */
        $class = $this->model;
        foreach ($with as $key => $relation) {
            $subRelation = '';
            $closure     = false;
            if ($relation instanceof \Closure) {
                // 支持闭包查询过滤关联条件
                $closure    = $relation;
                $relation   = $key;
                $with[$key] = $key;
            } elseif (is_array($relation)) {
                $subRelation = $relation;
                $relation    = $key;
            } elseif (is_string($relation) && strpos($relation, '.')) {
                $with[$key]                   = $relation;
                list($relation, $subRelation) = explode('.', $relation, 2);
            }
            
            /** @var Relation $model */
            $relation = Loader::parseName($relation, 1, false);
            $model    = $class->$relation();
              if ($closure) {
                $with[$key] = $closure;
            }
        }
        $this->via();
        if (isset($this->options['with'])) {
            $this->options['with'] = array_merge($this->options['with'], $with);
        } else {
            $this->options['with'] = $with;
        }
        return $this;
    }
    
    public function withCount($relation, $subQuery = true)
    {
        if (!$subQuery) {
            $this->options['with_count'] = $relation;
        } else {
            $relations = is_string($relation) ? explode(',', $relation) : $relation;
            if (!isset($this->options['field'])) {
                $this->field('*');
            }
            foreach ($relations as $key => $relation) {
                $closure = false;
                if ($relation instanceof \Closure) {
                    $closure  = $relation;
                    $relation = $key;
                }
                $relation = Loader::parseName($relation, 1, false);
                $count    = '(' . $this->model->$relation()->getRelationCountQuery($closure) . ')';
                $this->field([$count => Loader::parseName($relation) . '_count']);
            }
        }
        return $this;
    }
    
    public function withField($field)
    {
        $this->options['with_field'] = $field;
        return $this;
    }
    
    public function via($via = '')
    {
        $this->options['via'] = $via;
        return $this;
    }
    
    public function relation($relation)
    {
        if (empty($relation)) {
            return $this;
        }
        if (is_string($relation)) {
            $relation = explode(',', $relation);
        }
        if (isset($this->options['relation'])) {
            $this->options['relation'] = array_merge($this->options['relation'], $relation);
        } else {
            $this->options['relation'] = $relation;
        }
        return $this;
    }
    
    
    protected function parsePkWhere($data, &$options)
    {
        $pk = $this->getPk($options);
        // 获取当前数据表
        $table = is_array($options['table']) ? key($options['table']) : $options['table'];
        if (!empty($options['alias'][$table])) {
            $alias = $options['alias'][$table];
        }
        if (is_string($pk)) {
            $key = isset($alias) ? $alias . '.' . $pk : $pk;
            // 根据主键查询
            if (is_array($data)) {
                $where[$key] = isset($data[$pk]) ? $data[$pk] : ['in', $data];
            } else {
                $where[$key] = strpos($data, ',') ? ['IN', $data] : $data;
            }
        } elseif (is_array($pk) && is_array($data) && !empty($data)) {
            // 根据复合主键查询
            foreach ($pk as $key) {
                if (isset($data[$key])) {
                    $attr         = isset($alias) ? $alias . '.' . $key : $key;
                    $where[$attr] = $data[$key];
                } else {
                    throw new \Exception('miss complex primary data');
                }
            }
        }
        
        if (!empty($where)) {
            if (isset($options['where']['AND'])) {
                $options['where']['AND'] = array_merge($options['where']['AND'], $where);
            } else {
                $options['where']['AND'] = $where;
            }
        }
        return;
    }
    
    
    public function insert(array $data = [], $replace = false, $getLastInsID = false, $sequence = null)
    {
        // 分析查询表达式
        $options = $this->parseExpress();
        $data    = array_merge($options['data'], $data);
        // 生成SQL语句
        $sql = $this->builder->insert($data, $options, $replace);
        // 获取参数绑定
        $bind = $this->getBind();
        if ($options['fetch_sql']) {
            // 获取实际执行的SQL语句
            return $this->connection->getRealSql($sql, $bind);
        }
        
        // 执行操作
        $result = 0 === $sql ? 0 : $this->execute($sql, $bind, $this);
        if ($result) {
            $sequence  = $sequence ?: (isset($options['sequence']) ? $options['sequence'] : null);
            $lastInsId = $this->getLastInsID($sequence);
            if ($lastInsId) {
                $pk = $this->getPk($options);
                if (is_string($pk)) {
                    $data[$pk] = $lastInsId;
                }
            }
            $options['data'] = $data;
            $this->trigger('after_insert', $options);
            
            if ($getLastInsID) {
                return $lastInsId;
            }
        }
        return $result;
    }
    
    
    public function insertGetId(array $data, $replace = false, $sequence = null)
    {
        return $this->insert($data, $replace, true, $sequence);
    }
    
    public function insertAll(array $dataSet, $replace = false, $limit = null)
    {
        // 分析查询表达式
        $options = $this->parseExpress();
        if (!is_array(reset($dataSet))) {
            return false;
        }
        
        // 生成SQL语句
        if (is_null($limit)) {
            $sql = $this->builder->insertAll($dataSet, $options, $replace);
        } else {
            $array = array_chunk($dataSet, $limit, true);
            foreach ($array as $item) {
                $sql[] = $this->builder->insertAll($item, $options, $replace);
            }
        }
        
        // 获取参数绑定
        $bind = $this->getBind();
        if ($options['fetch_sql']) {
            // 获取实际执行的SQL语句
            return $this->connection->getRealSql($sql, $bind);
        } elseif (is_array($sql)) {
            // 执行操作
            return $this->batchQuery($sql, $bind, $this);
        } else {
            // 执行操作
            return $this->execute($sql, $bind, $this);
        }
    }
    
    /**
     * 通过Select方式插入记录
     * @access public
     * @param string $fields 要插入的数据表字段名
     * @param string $table  要插入的数据表名
     * @return integer|string
     * @throws PDOException
     */
    public function selectInsert($fields, $table)
    {
        // 分析查询表达式
        $options = $this->parseExpress();
        // 生成SQL语句
        $table = $this->parseSqlTable($table);
        $sql   = $this->builder->selectInsert($fields, $table, $options);
        // 获取参数绑定
        $bind = $this->getBind();
        if ($options['fetch_sql']) {
            // 获取实际执行的SQL语句
            return $this->connection->getRealSql($sql, $bind);
        } else {
            // 执行操作
            return $this->execute($sql, $bind, $this);
        }
    }
    public function update(array $data = [])
    {
        $options = $this->parseExpress();
        $data    = array_merge($options['data'], $data);
        $pk      = $this->getPk($options);
        if (isset($options['cache']) && is_string($options['cache']['key'])) {
            $key = $options['cache']['key'];
        }
        
        if (empty($options['where'])) {
            // 如果存在主键数据 则自动作为更新条件
            if (is_string($pk) && isset($data[$pk])) {
                $where[$pk] = $data[$pk];
                if (!isset($key)) {
                    $key = 'think:' . $options['table'] . '|' . $data[$pk];
                }
                unset($data[$pk]);
            } elseif (is_array($pk)) {
                // 增加复合主键支持
                foreach ($pk as $field) {
                    if (isset($data[$field])) {
                        $where[$field] = $data[$field];
                    } else {
                        // 如果缺少复合主键数据则不执行
                        throw new \Exception('miss complex primary data');
                    }
                    unset($data[$field]);
                }
            }
            if (!isset($where)) {
                // 如果没有任何更新条件则不执行
                throw new \Exception('miss update condition');
            } else {
                $options['where']['AND'] = $where;
            }
        } elseif (!isset($key) && is_string($pk) && isset($options['where']['AND'][$pk])) {
            $key = $this->getCacheKey($options['where']['AND'][$pk], $options, $this->bind);
        }
        
        // 生成UPDATE SQL语句
        $sql = $this->builder->update($data, $options);
        // 获取参数绑定
        $bind = $this->getBind();
        if ($options['fetch_sql']) {
            // 获取实际执行的SQL语句
            return $this->connection->getRealSql($sql, $bind);
        } else {
            // 检测缓存
            if (isset($key) && Cache::get($key)) {
                // 删除缓存
                Cache::rm($key);
            } elseif (!empty($options['cache']['tag'])) {
                Cache::clear($options['cache']['tag']);
            }
            // 执行操作
            $result = '' == $sql ? 0 : $this->execute($sql, $bind, $this);
            if ($result) {
                if (is_string($pk) && isset($where[$pk])) {
                    $data[$pk] = $where[$pk];
                } elseif (is_string($pk) && isset($key) && strpos($key, '|')) {
                    list($a, $val) = explode('|', $key);
                    $data[$pk]     = $val;
                }
                $options['data'] = $data;
                $this->trigger('after_update', $options);
            }
            return $result;
        }
    }
    
    public function getPdo()
    {
        // 分析查询表达式
        $options = $this->parseExpress();
        // 生成查询SQL
        $sql = $this->builder->select($options);
        // 获取参数绑定
        $bind = $this->getBind();
        if ($options['fetch_sql']) {
            // 获取实际执行的SQL语句
            return $this->connection->getRealSql($sql, $bind);
        }
        // 执行查询操作
        return $this->query($sql, $bind, $options['master'], true);
    }
    public function select($data = null)
    {
        if ($data instanceof Query) {
            return $data->select();
        } elseif ($data instanceof \Closure) {
            call_user_func_array($data, [ & $this]);
            $data = null;
        }
        // 分析查询表达式
        $options = $this->parseExpress();
        
        if (false === $data) {
            // 用于子查询 不查询只返回SQL
            $options['fetch_sql'] = true;
        } elseif (!is_null($data)) {
            // 主键条件分析
            $this->parsePkWhere($data, $options);
        }
        
        $resultSet = false;
        if (empty($options['fetch_sql']) && !empty($options['cache'])) {
            // 判断查询缓存
            $cache = $options['cache'];
            unset($options['cache']);
            $key       = is_string($cache['key']) ? $cache['key'] : md5($this->connection->getConfig('database') . '.' . serialize($options) . serialize($this->bind));
            $resultSet = Cache::get($key);
        }
        if (false === $resultSet) {
            // 生成查询SQL
            $sql = $this->builder->select($options);
            // 获取参数绑定
            $bind = $this->getBind();
            if ($options['fetch_sql']) {
                // 获取实际执行的SQL语句
                return $this->connection->getRealSql($sql, $bind);
            }
            
            $options['data'] = $data;
            if (($resultSet = $this->trigger('before_select', $options))==true) {
            } else {
                // 执行查询操作
                //exit($sql);
                $resultSet = $this->query($sql, $bind, $options['master'], $options['fetch_pdo']);
                //  dump($resultSet);exit();
                if ($resultSet instanceof \PDOStatement) {
                    // 返回PDOStatement对象
                    return $resultSet;
                }
            }
            
            if (isset($cache) && false !== $resultSet) {
                // 缓存数据集
                $this->cacheData($key, $resultSet, $cache);
            }
        }
        
        // 数据列表读取后的处理
        if (!empty($this->model)) {
            // 生成模型对象
            if (count($resultSet) > 0) {
                foreach ($resultSet as $key => $result) {
                    /** @var Model $model */
                    $model = $this->model->newInstance($result);
                    $model->isUpdate(true);
                    
                    // 关联查询
                    if (!empty($options['relation'])) {
                        $model->relationQuery($options['relation']);
                    }
                    // 关联统计
                    if (!empty($options['with_count'])) {
                        $model->relationCount($model, $options['with_count']);
                    }
                    $resultSet[$key] = $model;
                }
                if (!empty($options['with'])) {
                    // 预载入
                    $model->eagerlyResultSet($resultSet, $options['with']);
                }
                // 模型数据集转换
                $resultSet = $model->toCollection($resultSet);
            } else {
                $resultSet = $this->model->toCollection($resultSet);
            }
        } elseif ('collection' == $this->connection->getConfig('resultset_type')) {
            // 返回Collection对象
            $resultSet = new Collection($resultSet);
        }
        // 返回结果处理
        if (!empty($options['fail']) && count($resultSet) == 0) {
            $this->throwNotFound($options);
        }
        return $resultSet;
    }
    protected function cacheData($key, $data, $config = [])
    {
        if (isset($config['tag'])) {
            Cache::tag($config['tag'])->set($key, $data, $config['expire']);
        } else {
            Cache::set($key, $data, $config['expire']);
        }
    }
    
    protected function getCacheKey($value, $options, $bind = [])
    {
        if (is_scalar($value)) {
            $data = $value;
        } elseif (is_array($value) && is_string($value[0]) && 'eq' == strtolower($value[0])) {
            $data = $value[1];
        }
        $prefix = $this->connection->getConfig('database') . '.';
        
        if (isset($data)) {
            return 'think:' . $prefix . (is_array($options['table']) ? key($options['table']) : $options['table']) . '|' . $data;
        }
        
        try {
            return md5($prefix . serialize($options) . serialize($bind));
        } catch (\Exception $e) {
            throw new \Exception('closure not support cache(true)');
        }
    }
    
    public function find($data = null)
    {
        if ($data instanceof Query) {
            return $data->find();
        } elseif ($data instanceof \Closure) {
            call_user_func_array($data, [ & $this]);
            $data = null;
        }
        // 分析查询表达式
        $options = $this->parseExpress();
        $pk      = $this->getPk($options);
        if (!is_null($data)) {
            // AR模式分析主键条件
            $this->parsePkWhere($data, $options);
        } elseif (!empty($options['cache']) && true === $options['cache']['key'] && is_string($pk) && isset($options['where']['AND'][$pk])) {
            $key = $this->getCacheKey($options['where']['AND'][$pk], $options, $this->bind);
        }
        
        $options['limit'] = 1;
        $result           = false;
        if (empty($options['fetch_sql']) && !empty($options['cache'])) {
            // 判断查询缓存
            $cache = $options['cache'];
            if (true === $cache['key'] && !is_null($data) && !is_array($data)) {
                $key = 'think:' . $this->connection->getConfig('database') . '.' . (is_array($options['table']) ? key($options['table']) : $options['table']) . '|' . $data;
            } elseif (is_string($cache['key'])) {
                $key = $cache['key'];
            } elseif (!isset($key)) {
                $key = md5($this->connection->getConfig('database') . '.' . serialize($options) . serialize($this->bind));
            }
            $result = Cache::get($key);
        }
        if (false === $result) {
            // 生成查询SQL
            $sql = $this->builder->select($options);
            // 获取参数绑定
            $bind = $this->getBind();
            if ($options['fetch_sql']) {
                // 获取实际执行的SQL语句
                return $this->connection->getRealSql($sql, $bind);
            }
            if (is_string($pk)) {
                if (!is_array($data)) {
                    if (isset($key) && strpos($key, '|')) {
                        list($a, $val) = explode('|', $key);
                        $item[$pk]     = $val;
                    } else {
                        $item[$pk] = $data;
                    }
                    $data = $item;
                }
            }
            $options['data'] = $data;
            // 事件回调
            if ($result = $this->trigger('before_find', $options)) {
            } else {
                // 执行查询
                $resultSet = $this->query($sql, $bind, $options['master'], $options['fetch_pdo']);
                
                if ($resultSet instanceof \PDOStatement) {
                    // 返回PDOStatement对象
                    return $resultSet;
                }
                $result = isset($resultSet[0]) ? $resultSet[0] : null;
            }
            
            if (isset($cache) && $result) {
                // 缓存数据
                $this->cacheData($key, $result, $cache);
            }
        }
        
        // 数据处理
        if (!empty($result)) {
            if (!empty($this->model)) {
                // 返回模型对象
                $result = $this->model->newInstance($result);
                $result->isUpdate(true, isset($options['where']['AND']) ? $options['where']['AND'] : null);
                // 关联查询
                if (!empty($options['relation'])) {
                    $result->relationQuery($options['relation']);
                }
                // 预载入查询
                if (!empty($options['with'])) {
                    $result->eagerlyResult($result, $options['with']);
                }
                // 关联统计
                if (!empty($options['with_count'])) {
                    $result->relationCount($result, $options['with_count']);
                }
            }
        } elseif (!empty($options['fail'])) {
            $this->throwNotFound($options);
        }
        return $result;
    }
    
    protected function throwNotFound($options = [])
    {
        if (!empty($this->model)) {
            $class = get_class($this->model);
            throw new \Exception('model data Not Found:' . $class);
        } else {
            $table = is_array($options['table']) ? key($options['table']) : $options['table'];
            throw new \Exception('table data not Found:' . $table);
        }
    }
    
    public function selectOrFail($data = null)
    {
        return $this->failException(true)->select($data);
    }
    
    public function chunk($count, $callback, $column = null, $order = 'asc')
    {
        $options = $this->getOptions();
        if (empty($options['table'])) {
            $options['table'] = $this->getTable();
        }
        $column = $column ?: $this->getPk($options);
        
        if (isset($options['order'])) {
            unset($options['order']);
        }
        $bind = $this->bind;
        if (is_array($column)) {
            $times = 1;
            $query = $this->options($options)->page($times, $count);
        } else {
            if (strpos($column, '.')) {
                list($alias, $key) = explode('.', $column);
            } else {
                $key = $column;
            }
            $query = $this->options($options)->limit($count);
        }
        $resultSet = $query->order($column, $order)->select();
        
        while (count($resultSet) > 0) {
            if ($resultSet instanceof Collection) {
                $resultSet = $resultSet->all();
            }
            
            if (false === call_user_func($callback, $resultSet)) {
                return false;
            }
            
            if (is_array($column)) {
                $times++;
                $query = $this->options($options)->page($times, $count);
            } else {
                $end    = end($resultSet);
                $lastId = is_array($end) ? $end[$key] : $end->getData($key);
                $query  = $this->options($options)
                ->limit($count)
                ->where($column, 'asc' == strtolower($order) ? '>' : '<', $lastId);
            }
            
            $resultSet = $query->bind($bind)->order($column, $order)->select();
        }
        
        return true;
    }
    
    public function getBind()
    {
        $bind       = $this->bind;
        $this->bind = [];
        return $bind;
    }
    
    public function buildSql($sub = true)
    {
        return $sub ? '( ' . $this->select(false) . ' )' : $this->select(false);
    }
    
    public function delete($data = null)
    {
        // 分析查询表达式
        $options = $this->parseExpress();
        $pk      = $this->getPk($options);
        if (isset($options['cache']) && is_string($options['cache']['key'])) {
            $key = $options['cache']['key'];
        }
        
        if (!is_null($data) && true !== $data) {
            if (!isset($key) && !is_array($data)) {
                // 缓存标识
                $key = 'think:' . $options['table'] . '|' . $data;
            }
            // AR模式分析主键条件
            $this->parsePkWhere($data, $options);
        } elseif (!isset($key) && is_string($pk) && isset($options['where']['AND'][$pk])) {
            $key = $this->getCacheKey($options['where']['AND'][$pk], $options, $this->bind);
        }
        
        if (true !== $data && empty($options['where'])) {
            // 如果条件为空 不进行删除操作 除非设置 1=1
            throw new \Exception('delete without condition');
        }
        // 生成删除SQL语句
        $sql = $this->builder->delete($options);
        // 获取参数绑定
        $bind = $this->getBind();
        if ($options['fetch_sql']) {
            // 获取实际执行的SQL语句
            return $this->connection->getRealSql($sql, $bind);
        }
        
        // 检测缓存
        if (isset($key) && Cache::get($key)) {
            // 删除缓存
            Cache::rm($key);
        } elseif (!empty($options['cache']['tag'])) {
            Cache::clear($options['cache']['tag']);
        }
        // 执行操作
        $result = $this->execute($sql, $bind, $this);
        if ($result) {
            if (!is_array($data) && is_string($pk) && isset($key) && strpos($key, '|')) {
                list($a, $val) = explode('|', $key);
                $item[$pk]     = $val;
                $data          = $item;
            }
            $options['data'] = $data;
            $this->trigger('after_delete', $options);
        }
        return $result;
    }
    
    /**
     * 分析表达式（可用于查询或者写入操作）
     * @access protected
     * @return array
     */
    protected function parseExpress()
    {
        $options = $this->options;
        
        // 获取数据表
        if (empty($options['table'])) {
            $options['table'] = $this->getTable();
        }
        
        if (!isset($options['where'])) {
            $options['where'] = [];
        } elseif (isset($options['view'])) {
            // 视图查询条件处理
            foreach (['AND', 'OR'] as $logic) {
                if (isset($options['where'][$logic])) {
                    foreach ($options['where'][$logic] as $key => $val) {
                        if (array_key_exists($key, $options['map'])) {
                            $options['where'][$logic][$options['map'][$key]] = $val;
                            unset($options['where'][$logic][$key]);
                        }
                    }
                }
            }
            
            if (isset($options['order'])) {
                // 视图查询排序处理
                if (is_string($options['order'])) {
                    $options['order'] = explode(',', $options['order']);
                }
                foreach ($options['order'] as $key => $val) {
                    if (is_numeric($key)) {
                        if (strpos($val, ' ')) {
                            list($field, $sort) = explode(' ', $val);
                            if (array_key_exists($field, $options['map'])) {
                                $options['order'][$options['map'][$field]] = $sort;
                                unset($options['order'][$key]);
                            }
                        } elseif (array_key_exists($val, $options['map'])) {
                            $options['order'][$options['map'][$val]] = 'asc';
                            unset($options['order'][$key]);
                        }
                    } elseif (array_key_exists($key, $options['map'])) {
                        $options['order'][$options['map'][$key]] = $val;
                        unset($options['order'][$key]);
                    }
                }
            }
        }
        
        if (!isset($options['field'])) {
            $options['field'] = '*';
        }
        
        if (!isset($options['data'])) {
            $options['data'] = [];
        }
        
        if (!isset($options['strict'])) {
            $options['strict'] = $this->getConfig('fields_strict');
        }
        
        foreach (['master', 'lock', 'fetch_pdo', 'fetch_sql', 'distinct'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = false;
            }
        }
        
        if (isset(static::$readMaster['*']) || (is_string($options['table']) && isset(static::$readMaster[$options['table']]))) {
            $options['master'] = true;
        }
        
        foreach (['join', 'union', 'group', 'having', 'limit', 'order', 'force', 'comment'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = '';
            }
        }
        
        if (isset($options['page'])) {
            // 根据页数计算limit
            list($page, $listRows) = $options['page'];
            $page                  = $page > 0 ? $page : 1;
            $listRows              = $listRows > 0 ? $listRows : (is_numeric($options['limit']) ? $options['limit'] : 20);
            $offset                = $listRows * ($page - 1);
            $options['limit']      = $offset . ',' . $listRows;
        }
        
        $this->options = [];
        return $options;
    }
    
    /**
     * 注册回调方法
     * @access public
     * @param string   $event    事件名
     * @param callable $callback 回调方法
     * @return void
     */
    public static function event($event, $callback)
    {
        self::$event[$event] = $callback;
    }
    
    /**
     * 触发事件
     * @access protected
     * @param string $event   事件名
     * @param mixed  $params  额外参数
     * @return bool
     */
    protected function trigger($event, $params = [])
    {
        $result = false;
        if (isset(self::$event[$event])) {
            $callback = self::$event[$event];
            $result   = call_user_func_array($callback, [$params, $this]);
        }
        return $result;
    }
    
}
    