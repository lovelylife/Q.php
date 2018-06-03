<?php

/*----------------------------------------------------
 $ 名称: apploader.class.php
 $ 功能: 加载应用程序类
 $ 参数:
     $str      预处理的字符串
     $force    强制转换
 $ 返回值: 处理结果
------------------------------------------------------*/

class CLASS_APPLOADER {
  function __construct($app_root) {
    global $S_AJAX_MODE;
    $app_root = str_ireplace('\\', '/', $app_root);
    
    if(!file_exists($app_root.'/install.lock')){
      die("<html><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/> 
<meta http-equiv=\"Content-Language\" content=\"utf-8\"/> <body>对不起，第一次使用该程序需要安装，请点击<a href=\"install/?app={$appName}\">安装</a></body></html>");
    }

    // 调用接口参数
    $args = array();
    $args['path']     = get_path($app_root.'/');
    $args['root']     = $app_root;
    $args['host']     = (is_ssl()?"https://":"http://").$_SERVER["HTTP_HOST"];
    $args['module']   = $_GET['mod'];
    $args['action']   = empty($_GET['action'])?"index":$_GET['action'];
    $args['inajax']   = $S_AJAX_MODE;

    // 初始化应用程序实例
    $app_file = $app_root.'/app.class.php';
    if(!file_exists($app_file))  {
      trigger_error('application not found.', E_USER_ERROR);
    }
        
    // 应用程序文件
    require($app_file);
  
    // 检测有效模块，如果不存在使用默认模块处理
    $valid_module = $args['module'];
    if(empty($valid_module)) {
      $valid_module = 'default';
      $args['module'] = $valid_module;
    }

    // 创建应用程序目录结构
    $paths = $this->checkAppRequired($app_root, $args);
    //$args = array_merge($args, $paths);
    $args['settings'] = &$paths;
    if(!class_exists('APPLICATION')) {
      trigger_error('class `APPLICATION` is not found.', E_USER_ERROR);
    } 
    // 创建应用程序实例
    $app=new APPLICATION;
    $app->appMain($args);
  }
    
  function CLASS_APPLOADER ($appName){
     $this->__construct($appName);
  }
    
  function checkAppRequired($appRoot, &$args) {
    $appRequiredFile = $appRoot.'/app.required.php';
    if(!file_exists($appRequiredFile)) {
      trigger_error("fatal error, file \"app.required.php\" is not found.", E_USER_ERROR);
    }
        
    $paths = require($appRequiredFile);
    foreach($paths as $key => $value) {
      if(!key_exists($key, $args) && preg_match('/_dir$/i', $key)) {
        $dirName = $appRoot.$value;
        if(!is_dir($dirName)) {
          createfolders($dirName, true);
        }
      }
    }

    return $paths;
  }
}

?>
