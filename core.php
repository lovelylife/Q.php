<?php
/*--------------------------------------------------------------------
 $ file: core.php
 $ date:   2009-4-5 18:24:26
 $ author: LovelyLife 
 $ bug fixed:
 
 [2011-07-24]:
 1. 解决json_decode无法处理单引号转义的json数据
----------------------------------------------------------------------*/

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

// application loadder
require(_QROOT.'/apploader.class.php');
?>
