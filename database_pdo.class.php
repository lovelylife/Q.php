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
    $dsn = "mysql:host=$host;dbname=".$this->db_name;
    $init = array(
      array( PDO::ATTR_PERSISTENT => true )
    );
    
    //if( $this->db_lang ) {
      //$init[ PDO::MYSQL_ATTR_INIT_COMMAND ] = "SET NAMES '".$this->db_lang."';";
      //$dsn .= ";charset=".$this->db_lang;
    //}

    $this->linker = new PDO( $dsn, $user, $pwd, $init );
    
    //处理错误，成功连接则选择数据库
    if(!$this->linker){
      trigger_error("Can\"t use ".$this->db_name." : " . mysql_error(), E_USER_ERROR);
      exit();
    }

    $this->linker->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    //if( version_compare(PHP_VERSION, '5.3.6', '<') && !defined('PDO::MYSQL_ATTR_INIT_COMMAND') ){
    //  $this->linker->exec( "SET NAMES '".$this->db_lang."';" );
    //}
    if( $this->db_lang ) {
      $this->linker->exec( "SET NAMES '".$this->db_lang."';" );
    } 
    // set language
    //mysql_query("SET NAMES '".$this->db_lang."';", $this->linker);
    // sql mode
    //@mysqdl_query("SET sql_mode='' ;", $this->linker);
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
    $this->sql = str_replace("##__", $this->db_prefix, $sql);
    return $this->linker->prepare( $this->sql );
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

    $context = $call['context'];
    $method  = $call['method'];
    if(!is_object($context)) {
      $context = $this;
    }

    
    if(!method_exists($context, $method)) {
      $context = $this;
      $method  = 'dummy';
    }
    
    $res = $this->setQuery( $sql );
    $res->execute();

    while( $record = $res->fetch( PDO::FETCH_ASSOC ) ) {
      //print_r( $record );
      $context->$method($record);
      array_push($result, $record);
    }
    //print_r($result);

    return true;
  }
  
  // 执行无记录集返回的sql语句
  function execute_tpl($sql, $context, $tpl, &$out_buffer) {
    // check $context
    if(!is_object($context) || !method_exists($context, 'item_process')) {
      $this->set_error(
         'invalid parameter $$context or method "item_process" is not exists', true);
      return false;
    }

    $res = $this->setQuery( $sql );
    $res->execute();
    while( $record = $res->fetch( PDO::FETCH_ASSOC ) ) {
      $out_buffer .= $context->item_process($record, $tpl);
    }

    return true;
  }
  
  function execute($sql) {
    $res = $this->setQuery($sql);
    return $res->execute();
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
    return $this->linker->lastInsertId();
  }
  
  function affected_rows() {
    return $this->linker->rowCount();
  }

  function free_result($result) {
    mysql_free_result($result);
  }
  
  function close() {
    if($this->linker) {
      //@mysql_close($this->linker);
    }
  }
  
  private function set_error($msg, $interrupt = false) {
    $this->err = $msg;
    if($interrupt) {
      trigger_error($this->err, E_USER_ERROR);
    }
  }

  function get_error() {
    $info = $this->linker->errorInfo();
    return $this->err."\r\n".$this->linker->errorCode() . ", message: " . $info[2];
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
