<?php

define('QPHP_SESSION_TTL', 24*30*3600); //SESSION 生存时长

class QPHP_session {
  static $db;
  function __construct()
  {
  }

  static function initialize($db) {
    self::$db = $db;
    /** clear current session */
    session_destroy();
    //不使用 GET/POST 变量方式
    ini_set('session.use_trans_sid',0);
    //设置垃圾回收最大生存时间
    ini_set('session.gc_maxlifetime',QPHP_SESSION_TTL);
    //使用 COOKIE 保存 SESSION ID 的方式
    ini_set('session.use_cookies',1);
    ini_set('session.cookie_path','/');
    //多主机共享保存 SESSION ID 的 COOKIE,因为我是本地服务器测试所以设置$domain=''
    ini_set('session.cookie_domain', '.wayixia.com');
    ini_set('session.cookie_lifetime', 3600*24*15 );
    //将 session.save_handler 设置为 user，而不是默认的 files
    //session_module_name('user');

    //定义 SESSION 各项操作所对应的方法名
    if( !session_set_save_handler(
      array('QPHP_session','open'),
      array('QPHP_session','close'),
      array('QPHP_session','read'),
      array('QPHP_session','write'),
      array('QPHP_session','destroy'),
      array('QPHP_session','gc')
    ) ) 
    {
      print("set save handler failed");
    }
    session_name('_wa_sid');
    session_start();
  }

  static function setcookie($name, $value, $expired) {
    setcookie($name, $value, $expired, "/", ".wayixia.com", false);
  }

  static function open($save_path, $session_name) {
    return true;
  }

  static function close() {
    if(self::$db)
      self::$db->close();

    return true; 
  }

  static function read($session_id) {
    //echo 'QPHP_session -> read '.$session_id."\r\n";
    $sql = 'SELECT `data` FROM `ch_sessions` WHERE `id`=\''. $session_id . '\' AND `expired_time`>=' . time();
    $row = self::$db->get_row($sql);
    if(empty($row))
      return '';
    
    return $row['data'];
  }

  static function write($session_id, $data) {
    //echo 'QPHP_session -> write '.$session_id.', data: '.$data."\n\r";
    $fields = array(
      'id' => $session_id,
      'expired_time' => (time() + QPHP_SESSION_TTL),
      'data' => $data
    );
    $sql = self::$db->insertSQL('sessions', $fields)." on duplicate key update data=VALUES(data), expired_time=VALUES(expired_time);";
    if(!self::$db->execute($sql)) {
      //print_r(self::$db);
      trigger_error(self::$db->get_error(), E_USER_ERROR);
    }
  }

  static function destroy($session_id) {
    $sql = "delete from ch_sessions where id='$session_id'";
    if(self::$db->execute($sql)) {      
      return true;
    }
    //echo self::$db->get_error();
    return false;
  }

  static function gc($maxlifetime) {
    return true;
  }
}

// if defind config.session.php then use self define
$session_config = dirname(__FILE__).'/config.session.php';
if(file_exists($session_config)) {
  $cfg = require($session_config);
  $db = createdb($cfg['type'], $cfg);
  QPHP_session::initialize($db);
} 

?>
