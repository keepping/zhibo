<?php

require_once dirname(__FILE__) . '/Core.class.php';
require_once dirname(__FILE__) . '/Connect.class.php';

/**
 * @property array table_name_alias
 */
class Model
{
    /**
     * [$sql 表别名]
     * [$table_alias 表别名]
     * [$field_alias 列别名]
     * [$prefix  表前缀]
     * [$query 请求参数]
     * @var array
     */
    private $table_alias, $field_alias, $prefix, $query = array(
        'table' => '',
        'field' => '',
        'where' => '',
        'order' => '',
        'group' => '',
        'limit' => '',
    );
    /**
     * [$table_name 表名]
     * @var string
     */
    protected $table_name = '';
    /**
     * 模型类目录地址
     * @var boolean
     */
    public static $sql, $lib = false;

    /**
     * 初始化
     * @param [type] $table_name [description]
     */
    public function __construct($table_name)
    {
        $this->prefix     = DB_PREFIX;
        $this->table_name = $table_name;
        $this->resetQuery();
    }

    /**
     * 新建实体类
     * @param  [type] $table [description]
     * @return Model [type]        [description]
     */
    public static function build($table)
    {
        $class_name = $table . 'Model';
        if (self::$lib) {
            $file_name = self::$lib . '/models/' . $class_name . '.class.php';
            if (!class_exists($class_name) && file_exists($file_name)) {
                require_once $file_name;
            }
        }
        return class_exists($class_name) ? new $class_name($table) : new Model($table);
    }

    /**
     * $query请求参数设置
     * @param  string $name 参数名
     * @param  array  $args 参数值
     * @return $this [type] [description]
     */
    public function __call($name, $args)
    {
        if (array_key_exists($name, $this->query)) {
            switch (sizeof($args)) {
                case 0:
                    return $this->query[$name];
                    break;
                case 1:
                    if ($args[0]) {
                        $this->query[$name] = self::stringToArray($args[0]);
                    }
                    break;
                default:
                    $this->query[$name] = $args;
                    break;
            }
            return $this;
        } else {
            switch ($name) {
                case 'count':
                    $args[1] = $args[0];
                    $field   = 1;
                case 'sum':
                case 'min':
                case 'max':
                    if (isset($args[0])) {
                        $field = isset($field) ? $field : $this->getFullField($args[0]);
                        $res   = $this->field(array(array("$name($field) $name")))->selectOne($args[1]);
                        return $res[$name];
                    }
                default:
                    Core::error('function ' . $name . ' is not found!');
                    break;
            }
        }
    }

    /**
     * 获取上一条数据库语句
     * @return string [description]
     */
    public function getLastSql()
    {
        return self::$sql;
    }
    /**
     * 查询
     * @param  string $where [description]
     * @return array         [description]
     */
    public function select($where = '')
    {
        self::$sql = $this->where($where)->getSelectSql();
        $this->resetQuery();
        return Connect::query(self::$sql);
    }

    /**
     * 查询一条记录
     * @param  string $where [description]
     * @return array         [description]
     */
    public function selectOne($where = '')
    {
        self::$sql = $this->where($where)->limit(1)->getSelectSql();
        $this->resetQuery();
        return Connect::query(self::$sql, 0);
    }

    /**
     * 插入
     * @param  array $data [description]
     * @return int          [description]
     */
    public function insert($data)
    {
        self::$sql = $this->getInsertSql($data);
        $this->resetQuery();
        return Connect::exec(self::$sql) ? Connect::lastInsertId() : false;
    }

    /**
     * 更新
     * @param  array  $data  [description]
     * @param  string $where [description]
     * @return int           [description]
     */
    public function update($data, $where = '')
    {
        self::$sql = $this->where($where)->getUpdateSql($data);
        $this->resetQuery();
        return Connect::exec(self::$sql);
    }

    /**
     * 删除
     * @param  string $where [description]
     * @return [type]        [description]
     */
    public function delete($where = '')
    {
        self::$sql = $this->where($where)->getDeleteSql();
        $this->resetQuery();
        return Connect::exec(self::$sql);
    }

    /**
     * 重置请求参数
     * @return void
     */
    public function resetQuery()
    {
        $this->query = array(
            'table' => '',
            'field' => '',
            'where' => '',
            'order' => '',
            'group' => '',
            'limit' => '',
        );
        $this->table_name_alias = array();
        $this->field_alias      = array();
    }

    /**
     * 获取查询sql语句
     * @return string [type]        [description]
     */
    private function getSelectSql()
    {
        $table = $this->getTableSql() ?: $this->getTableName($this->table_name);
        $field = $this->getFieldSql() ?: '*';
        $where = $this->getWhereSql();
        $order = $this->getOrderSql();
        $group = $this->getGroupSql();
        $limit = $this->getLimitSql();
        return "SELECT $field FROM $table$where$group$order$limit;";
    }

    /**
     * 获取插入sql语句
     * @param  array $data [description]
     * @return string [type]       [description]
     */
    private function getInsertSql($data)
    {
        $table  = $this->getTableName($this->table_name);
        $column = '';
        $values = '';
        foreach ($data as $key => $value) {
            $column .= '`' . $key . '`,';
            $values .= '\'' . addslashes($value) . '\',';
        }
        $column = rtrim($column, ',');
        $values = rtrim($values, ',');
        return "INSERT INTO $table ($column) VALUES($values);";
    }

    /**
     * 获取更新sql语句
     * @param  array $data [description]
     * @return string [type]       [description]
     */
    private function getUpdateSql($data)
    {
        $table  = $this->getTableName($this->table_name);
        $values = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $values .= $this->getFieldName($key) . '=' . $value[0] . ',';
            } else {
                $values .= $this->getFieldName($key) . '=\'' . addslashes($value) . '\',';
            }
        }
        $values = rtrim($values, ',');
        $where  = $this->getWhereSql();
        if (empty($where)) {
            Core::error("update where is error");
        }
        return "UPDATE $table SET $values$where;";
    }

    /**
     * 获取删除sql语句
     * @return string [type] [description]
     */
    private function getDeleteSql()
    {
        $table = $this->getTableName($this->table_name);
        $where = $this->getWhereSql();
        if (empty($where)) {
            Core::error("delete where is error");
        }
        return "DELETE FROM $table$where";
    }

    /**
     * 获取表名sql语句
     * @return string [type] [description]
     */
    private function getTableSql()
    {
        $tables = $this->query['table'];
        if ($tables) {
            $this->table_alias = array();
            $sql               = '';
            foreach ($tables as $key => $table) {
                if (is_string($key) && is_array($table)) {
                    $on = false;
                    foreach ($table as $k => $v) {
                        $k = strtoupper(self::mergeSpaces($k));
                        switch ($k) {
                            case '0':
                                $option = $v;
                                break;
                            case 'JOIN':
                            case 'LEFT JOIN':
                            case 'RIGHT JOIN':
                                $option = $k;
                            case '1':
                                $table2 = self::getFullTable(self::mergeSpaces($v));
                                break;
                            case '2':
                            case 'ON':
                                $on = $v;
                                break;
                            default:
                                break;
                        }
                    }
                    $table1 = self::getFullTable(self::mergeSpaces($key));
                    $on     = $on ? 'ON' . $this->getWhereSql($on) : '';
                    $sql .= "$table1 $option $table2$on,";
                } else {
                    $sql .= self::getFullTable(self::mergeSpaces($table)) . ',';
                }
            }
            return rtrim($sql, ',');
        } else {
            return '';
        }
    }

    /**
     * 获取列名sql语句
     * @return string [type] [description]
     */
    private function getFieldSql()
    {
        $fields = $this->query['field'];
        if ($fields) {
            $sql = '';
            foreach ($fields as $field) {
                if ($field) {
                    if (is_array($field)) {
                        $sql .= $field[0] . ',';
                    } else {
                        $sql .= $this->getFullField(self::mergeSpaces($field)) . ',';
                    }
                }
            }
            return rtrim($sql, ',');
        } else {
            return '';
        }
    }

    /**
     * 获取条件sql语句
     * @return string [type] [description]
     */
    private function getWhereSql($wheres = false)
    {
        if ($wheres === false) {
            $wheres = $this->query['where'];
            if ($wheres) {
                return ' WHERE' . $this->getWhereSql($wheres);
            } else {
                return '';
            }
        } else {
            $sql = '';
            foreach ($wheres as $key => $value) {
                if (is_array($value) && !is_string($key)) {
                    $sql_item = '';
                    foreach ($value as $k => $v) {
                        $sql_item .= $this->getWhereItem($k, $v);
                    }
                    $pre = substr($sql_item, 0, strpos($sql_item, ' ') + 1);
                    $sql .= $pre . '( ' . ltrim($sql_item, $pre) . ')';
                } else {
                    $sql .= $this->getWhereItem($key, $value);
                }
            }
            return ltrim($sql, 'AND');
        }
    }

    /**
     * 获取排序sql语句
     * @return string [type] [description]
     */
    private function getOrderSql()
    {
        $orders = $this->query['order'];
        if ($orders) {
            $sql = ' ORDER BY ';
            foreach ($orders as $order) {
                if (is_array($order)) {
                    $sql .= $order[0] . ',';
                } else {
                    $order     = self::stringToArray($order, ' ');
                    $order_sql = '';
                    switch (sizeof($order)) {
                        case 2:
                            $order_sql = ' ' . $order[1];
                        case 1:
                            $order_sql = $this->getFieldName($order[0]) . $order_sql;
                            break;
                        default:
                            throw new Exception("select order is error");
                            break;
                    }
                    $sql .= $order_sql . ',';
                }
            }
            return rtrim($sql, ',');
        } else {
            return '';
        }
    }

    /**
     * 获取分组sql语句
     * @return string [type] [description]
     */
    private function getGroupSql()
    {
        $groups = $this->query['group'];
        if ($groups) {
            $sql = ' GROUP BY ';
            foreach ($groups as $group) {
                if (is_array($group)) {
                    $sql .= $group[0] . ',';
                } else {
                    $sql .= $this->getFieldName($group) . ',';
                }
            }
            return rtrim($sql, ',');
        } else {
            return '';
        }
    }

    /**
     * 获取限制sql语句
     * @return string [type] [description]
     */
    private function getLimitSql()
    {
        $limit = $this->query['limit'];
        if ($limit) {
            $sql = '';
            switch (sizeof($limit)) {
                case 2:
                    $sql = ',' . intval($limit[1]);
                case 1:
                    $sql = intval($limit[0]) . $sql;
                    break;
                default:
                    Core::error("select limit is error");
                    break;
            }
            return ' LIMIT ' . $sql;
        } else {
            return '';
        }
    }

    /**
     * 获取条件单元sql语句
     * @param  string $key   [description]
     * @param  [type] $val   [description]
     * @return string [type] [description]
     */
    private function getWhereItem($key, $val)
    {
        if (is_array($val)) {
            if (sizeof($val) == 1) {
                $val[3] = 1;
                $val[2] = 'AND';
                $val[1] = $val[0];
                $val[0] = '=';
            }
            $option   = self::whereOp($val[0]);
            $value    = $val[1];
            $rule     = isset($val[2]) ? $val[2] : 'AND';
            $is_field = isset($val[3]) ? $val[3] : 0;
            switch ($option) {
                case 'EXP':
                    return $value . ' ';
                    break;
                case 'IN':
                case 'NOT IN':
                    $value = self::stringToArray($value);
                    foreach ($value as $k => $v) {
                        $value[$k] = '\'' . addslashes($v) . '\'';
                    }
                    return $rule . ' ' . $this->getFieldName($key) . ' ' . $option . ' (' . implode(',', $value) . ') ';
                    break;
                case 'BETWEEN':
                    $value = self::stringToArray($value);
                    return $rule . ' (' . $this->getFieldName($key) . ' BETWEEN \'' . addslashes($value[0]) . '\' AND \'' . addslashes($value[1]) . '\') ';
                    break;
                default:
                    return $rule . ' ' . $this->getFieldName($key) . ' ' . $option . ' ' . ($is_field ? $this->getFieldName($value) : ('\'' . addslashes($value) . '\'')) . ' ';
                    break;
            }
        } else {
            return 'AND ' . $this->getFieldName($key) . ' = \'' . addslashes($val) . '\'';
        }
    }

    /**
     * 获取单个完整表名sql语句，包含别名
     * @param  string $table_str [description]
     * @return string [type]     [description]
     */
    private function getFullTable($table_str)
    {
        $table = self::stringToArray($table_str, ' ');
        $sql   = '';
        switch (sizeof($table)) {
            case 3:
                if (strtoupper($table[1]) == 'AS') {
                    $table[1] = $table[2];
                } else {
                    Core::error("select table '$table_str' is error");
                }
            case 2:
                // 表别名
                $sql                 = ' AS `' . $table[1] . '`';
                $this->table_alias[] = $table[1];
            case 1:
                $sql = $this->getTableName($table[0]) . $sql;
                break;
            default:
                Core::error("select table '$table_str' is error");
                break;
        }
        return $sql;
    }

    /**
     * 获取完整表名
     * @param  string $table_str [description]
     * @return string [type]     [description]
     */
    private function getTableName($table_str)
    {
        $table = array_reverse(self::stringToArray($table_str, '.'), 0);
        $sql   = '';
        switch (sizeof($table)) {
            case 2:
                $sql = '`' . $table[1] . '`.';
            case 1:
                $sql .= '`' . $this->prefix . $table[0] . '`';
                break;
            default:
                Core::error("select table '$table_str' is error");
                break;
        }
        return $sql;
    }

    /**
     * 获取单个列名完整语句，包括别名
     * @param  string $field_str [description]
     * @return string [type]     [description]
     */
    private function getFullField($field_str)
    {
        $field = self::stringToArray($field_str, ' ');
        $sql   = '';
        switch (sizeof($field)) {
            case 3:
                if (strtoupper($field[1]) == 'AS') {
                    $field[1] = $field[2];
                } else {
                    Core::error("select field '$field_str' is error");
                }
            case 2:
                // 列别名
                $sql = ' AS `' . $field[1] . '`';
            case 1:
                $sql = $this->getFieldName($field[0]) . $sql;
                break;
            default:
                Core::error("select field '$field_str' is error");
                break;
        }
        return $sql;
    }

    /**
     * 获取完整列名
     * @param  string $field_str [description]
     * @return string [type]     [description]
     */
    private function getFieldName($field_str)
    {
        $field = array_reverse(self::stringToArray($field_str, '.'), 0);
        $sql   = '';
        switch (sizeof($field)) {
            case 3:
                // 数据库名
                $sql .= '`' . $field[2] . '`.';
            case 2:
                // 表名
                if (in_array($field[1], $this->table_alias)) {
                    $sql .= '`' . $field[1] . '`.';
                } else {
                    $sql .= '`' . $this->prefix . $field[1] . '`.';
                }
            case 1:
                // 列名
                $sql .= $field[0] == '*' ? '*' : ('`' . $field[0] . '`');
                break;
            default:
                Core::error("select field '$field_str' is error");
                break;
        }
        return $sql;
    }

    /**
     * 字符串转数组
     * @param  [type] $string    [description]
     * @param  string $delimiter [description]
     * @return array [type]      [description]
     */
    private static function stringToArray($string, $delimiter = ',')
    {
        if (!is_array($string) && !empty($string)) {
            return explode($delimiter, $string);
        }
        return $string;
    }

    /**
     * 去除字符串多余空格
     * @param  string $string [description]
     * @return mixed [type]   [description]
     */
    private static function mergeSpaces($string)
    {
        return preg_replace('/\s(?=\s)/', '\\1', trim($string));
    }
    /**
     * 判断数组是否为键值对
     * @param  [type]  $arr [description]
     * @return boolean      [description]
     */
    private static function isAssoc($arr)
    {
        if (!is_array($arr)) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * 条件参数过滤
     * @param  string $value [description]
     * @return string [type] [description]
     */
    private static function whereOp($value)
    {
        $value = strtoupper($value);
        switch ($value) {
            case 'EQ':
                return '=';
            case 'NEQ':
                return '<>';
            case 'GT':
                return '>';
            case 'EGT':
                return '>=';
            case 'LT':
                return '<';
            case 'ELT':
                return '<=';
            case 'NOTLIKE':
                return 'NOT LIKE';
            default:
                return $value;
                break;
        }
    }
}
