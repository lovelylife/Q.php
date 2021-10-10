<?php
/*--------------------------------------------------------------------
 $ module: Q.php
 $ date:   2010-4-6 18:24:26
 $ author: LovelyLife 
 $ last modified: 2012-07-21 12:36:22
 $ 
 全局宏：
 _QDOCUMENT_ROOT        - 虚拟主机根目录
 _QROOT        - Q.PHP框架的物理目录
----------------------------------------------------------------------*/

// 宏定义
define('_Q', 1);
define('_QSESSION', 'q.php.session');
define('_QDEBUG', true);  // 开发环境， debug开启，将错误抛出

// 设置默认时区 PRC
date_default_timezone_set('PRC');
// 获得脚本执行时间
$_start = microtime(true);

if(!_QDEBUG) {
  error_reporting(E_ALL || ~E_NOTICE || ~E_DEPRECATED);
}

// 处理某些的虚拟主机script_filename 和 __FILE__所在根目录不是同一个目录
if(!defined('_QDOCUMENT_ROOT')) {
  $bind_root = str_replace('\\', '/', 
    substr($_SERVER["SCRIPT_FILENAME"], 0, 0-strlen($_SERVER['PHP_SELF'])));
  define('_QDOCUMENT_ROOT', $bind_root);
}

// 框架的根目录
define('_QROOT', str_replace('\\', '/', dirname(__file__)));

function err_handler( $errno , $errstr , $errfile , $errline) {
  if($errno != E_NOTICE && $errno != E_DEPRECATED && $errno != E_WARNING) {
    echo "<pre><h1>Q.PHP Exception:<h1>";
    echo "<h3>Message: </h3>".$errstr."\n";
    if(_QDEBUG)
      echo print_stack_trace();
    echo "</pre>\r\n";
    exit(1);
  }    
}

function print_stack_trace() {
  $array = debug_backtrace();
  unset($array[0]);
  $html = "<h3>Stack: </h3>";
  $count = 0;
  foreach($array as $row) {
    if($count > 0) {
      $html .=str_ireplace(_QROOT, '', str_replace('\\', '/', $row['file'])) .":".$row['line'].", ".$row['function']."\n";
    }
    $count++;
  }
  
  return $html;
}

// bind error handler
set_error_handler("err_handler", E_ALL);


// 禁用数据库和文件中的引号转义 
if( version_compare(PHP_VERSION, '5.3.0', '<')){
  @set_magic_quotes_runtime(0);
}

define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());

// 导入系统常用函数库
require(_QROOT."/function.php");

// register_global setting
if(ini_get('register_globals')) {
  foreach($_SESSION as $key=>$value) {
    if(isset($GLOBALS[$key])) unset($GLOBALS[$key]);
  }
}

// 清除不需要的全局变量，保存系统使用的变量
$variables_whitelist = array (
  'GLOBALS',
  '_SERVER',
  '_GET',
  '_POST',
  '_REQUEST',
  '_FILES',
  '_ENV',
  '_COOKIE',
  '_SESSION',
  'error_handler',
  '_start',
  'variables_whitelist',
  'key',
);

foreach (get_defined_vars() as $key => $value) {
    if (! in_array($key, $variables_whitelist)) {
        unset($$key);
    }
}

unset($key, $value, $variables_whitelist);

// 如果单引号转义没有开启，则自动加上'\'处理
if(!MAGIC_QUOTES_GPC) {
  //foreach($_GET as $_key => $_value) 
  //  $_GET[$_key] = daddslashes($_value);
  
  $_GET = daddslashes( $_GET );
  //foreach($_POST as $_key => $_value) 
  //  $_POST[$_key] = daddslashes($_value);
  $_POST = daddslashes( $_POST );
  
  //foreach($_COOKIE as $_key => $_value) 
  //  $_COOKIE[$_key] = daddslashes($_value);
  $_COOKIE[ $_key ] = daddslashes( $_COOKIE );
}


// Ajax 模式
$S_AJAX_MODE = (isset($_GET['inajax']) && (strtolower($_GET['inajax']) == "true"));

// Ajax 处理
if($S_AJAX_MODE) {
  require(_QROOT.'/ajax.lib.class.php');
  $data = $_POST['postdata'];
  // 去掉单引号转义，否则json_decode无法工作
  $data = stripcslashes($data);
  $data = urldecode($data);
  if(!empty($data)) {
    $_POST = json_decode($data, true);
    if(is_null($_POST)) {
      $_POST = array();
      trigger_error('Invalid ajax package!\ndata:\n\n'.$data, E_USER_ERROR);
    }
  }
}

// for templates
require(_QROOT."/algory.class.php");
require(_QROOT."/page.class.php");
require(_QROOT."/configfile.class.php");
require(_QROOT.'/dtl.class.php');
require(_QROOT.'/templates.class.php');
// application frame work
require(_QROOT.'/module.class.php');
require(_QROOT.'/application.class.php');     
require(_QROOT.'/command.class.php');

?>
