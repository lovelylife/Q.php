<?php
/*--------------------------------------------------------------------
 $ module: CLASS_DATABASE For Q.php
 $ date:   2015-04-20 00:23:28
 $ author: Q
 $ copyright@wayixia.com
----------------------------------------------------------------------*/

(!defined('DB_MYSQL')) && define('DB_MYSQL', 1);
(!defined('DB_MSSQL')) && define('DB_MSSQL', 2);

// mysql
class CLASS_DB_MYSQL {
  
  var $linker;  // 数据库连接句柄
  private $db_host;  // mysql服务器地址，一般为localhost
  private $db_user;  // 数据库用户名
  private $db_pwd;  // 数据库密码
  private $db_name;  // 连接的数据库
  private $db_lang;
  private $db_prefix;
  private $err;
  
  function __construct($host, $user, $pwd, $dbname, $prefix, $lang) {
    $this->err = "";
    $this->db_host = $host;
    $this->db_user = $user;
    $this->db_pwd  = $pwd;
    $this->db_name = $dbname;
    $this->db_prefix = $prefix;
    $this->db_lang = $lang;
    $this->connect($this->db_host,$this->db_user,$this->db_pwd);
  }
   
   // 构造函数
  function CLASS_DB_MYSQL($host, $user, $pwd, $dbname, $prefix, $lang) {
    $this->__construct($host, $user, $pwd, $dbname, $prefix, $lang); 
  }
  
  function connect($host, $user, $pwd, $extra = array()) {
    $this->linker = mysql_connect($host, $user, $pwd, true);
    
    //处理错误，成功连接则选择数据库
    if(!$this->linker){
      trigger_error("Can\"t use ".$this->db_name." : " . mysql_error(), E_USER_ERROR);
      exit();
    }
    // select database
    $select_db_result = mysql_select_db($this->db_name, $this->linker);
    if(!$select_db_result){
      trigger_error("Can\"t use ".$this->db_name." : " . mysql_error(), E_USER_ERROR);
    } else {
    }
    // set language
    mysql_query("SET NAMES '".$this->db_lang."';", $this->linker);
    // sql mode
    @mysql_query("SET sql_mode='' ;", $this->linker);
  }

  // 组合insert语句(insert into ##__$table(...) values(...);
  static function insertSQL($table, $fields, $isPrefix = true) {
    if(!is_array($fields)) { return -1;  }
    $setFields = array();
    $setValues = array();
    foreach($fields as $name => $value) {
      array_push($setFields, $name);
      array_push($setValues, addslashes($value));
    }
    $setFields = "`".implode("`,`", $setFields)."`";
    $setValues = "'".implode("','", $setValues)."'";
    $prefix = $isPrefix ? "##__" : "";
    $sql = "INSERT INTO `{$prefix}".$table."` (".$setFields.") VALUES(".$setValues.")";

    return $sql;  
  }

  // 组合update set部分的语句
  static function updateSQL($table, $fields) {
    if(!is_array($fields)) { return -1;  }
    $setUpdates = array();
    foreach($fields as $name => $value) {
      array_push($setUpdates, "`".$name."` = '".addslashes($value)."'");
    }
    $sql = "UPDATE `".$table."` set " .implode(",", $setUpdates);
    return $sql;
  }
  
  function setQuery($sql) {
    $this->sql = ereg_replace("##__", $this->db_prefix, $sql);
  }
  
  function get_row($sql) {
    $records = array();
    if(!$this->get_results($sql, $records)) 
      trigger_error($this->get_error());
      
    if(empty($records)) 
      return array();
    else 
        return $records[0];
  }

  function dummy(&$record) {}
    
  function get_results($sql, &$result, $call=array()) {
    if(!is_array($result)) {
      $this->set_error('invalid parameter $$result');
      return false;
    }

    $this->result = $this->execute($sql);
    if(!$this->result) { 
      trigger_error($this->get_error(), E_USER_ERROR);
      return false;
    }
    
    $context = $call['context'];
    $method  = $call['method'];
    if(!is_object($context)) {
      $context = $this;
    }

    
    if(!method_exists($context, $method)) {
      $context = $this;
      $method  = 'dummy';
    }
     
    // check callback
    while($record = $this->fetch_assoc($this->result)) {
      $context->$method($record);
      array_push($result, $record);
    }

    return true;
  }
  
  // 执行无记录集返回的sql语句
  function execute_tpl($sql, &$context, &$tpl, &$out_buffer) {
    $this->result = $this->execute($sql);
    if(!$this->result)   
      return false;

    // check $context
    if(!is_object($context) || !method_exists($context, 'item_process')) {
      $this->set_error(
         'invalid parameter $$context or method "item_process" is not exists', true);
      return false;
    }

    while($record = $this->fetch_assoc($this->result)) {
      $out_buffer .= $context->item_process($record, $tpl);
    }

    return true;
  }
  
  function execute($sql) {
    $this->setQuery($sql);
    return mysql_query($this->sql, $this->linker);
  }

  function query_count($sql) {
      $reg = "/^select\s+(.+?)\s+from/i";
      $sql = preg_replace($reg, "select count(*) as qcount from", $sql, 1);
      $rs = $this->get_row($sql);
      if(!empty($rs)) {
        return $rs['qcount'];
      }

          return 0;
  }
  
  function num_rows() {
    if(!$this->result) {
      return 0;  
    } else {
      return mysql_num_rows($this->result, $this->linker);
    }
  }
  
  function get_fieldsname($table) {
    return $this->doQuery("show columns from {$table}");
  }
  
  function fetch_object($result) {
    return mysql_fetch_object($result);
  }
  
  function fetch_array($result) {
    return mysql_fetch_array($result);
  }
  
  function fetch_assoc($result) {
    return mysql_fetch_assoc($result);  
  }
  
  function get_insert_id() {
    return mysql_insert_id($this->linker);
  }
  
  function affected_rows() {
    return mysql_affected_rows($this->linker);
  }

  function free_result($result) {
    mysql_free_result($result);
  }
  
  function close() {
    if($this->linker) {
      @mysql_close($this->linker);
    }
  }
  
  private function set_error($msg, $interrupt = false) {
    $this->err = $msg;
    if($interrupt) {
      trigger_error($this->err, E_USER_ERROR);
    }
  }

  function get_error() {
    return $this->err."\r\n".mysql_error($this->linker);
  }
}

// 数据库类工厂, 默认为mysql数据库
function createdb($dbtype, $dbparams) {
  switch($dbtype) {
  case 'mssql':
    $classname = 'CLASS_DB_MSSQL';
    break;
  default:
    $classname = 'CLASS_DB_MYSQL';
  }
  
  // 如果类没有定义则
  if(!class_exists($classname)) {
    die('create db['.$classname.'] error!');
    return null;
  }
  
  return(new $classname($dbparams['host'], 
    $dbparams['user'], 
    $dbparams['pwd'], 
    $dbparams['dbname'], 
    $dbparams['prefix'],
    $dbparams['lang']
  ));
}

?>
