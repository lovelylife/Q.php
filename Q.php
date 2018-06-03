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

// 加载Q.PHP内核
require(_QROOT.'/core.php');

?>
