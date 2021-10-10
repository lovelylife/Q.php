<?php

/*--------------------------------------------------------------------
 $ module: MODULE FRAMEWORK For Q.PHP
 $ date:   2009-11-10 21:23:28
 $ author: LovelyLife
 $ last modified: 2012-04-24 12:47:47
 $ copyright www.qlibs.com
----------------------------------------------------------------------*/

class CLASS_MODULE {
  private $app_;
  public  $request;    
  private $response;      
  private $errmsgs;

  // 构造函数
  function __construct() {
    $this->request = $_POST; 
    $this->response = null;    
    $this->errmsgs = array();
  }
     
  // 构造函数
  //function CLASS_MODULE() { $this->__construct(); }
  
  // Ajax模式入口
  function __doajax($app, $action) {
    $this->app_ = $app;	  
    header('Content-Type: text/html; charset=utf-8'); 
    // 应答包
    $this->response = new CLASS_AJAX_PACKAGE();
    $this->response->set_header(0);        // 正确应答包
    $this->doAjax($action);
    
    echo $this->response->__toString();
  }
    
  // 非Ajax模式入口
  function __domain($app, $action) {
    $this->app_ = $app;
    $this->doMain($action);
  }
    
  // 多态接口，用于处理普通表单的提交的请求
  function doMain($action) {
    trigger_error('ui mode: action "'.$action.'" is not supported .', E_USER_ERROR);
  }

  // 默认提示
  function doAjax($action) {
    $this->errmsg('ajax mode: action "'.$action.'" is not supported. ');
  }
 
  // Ajax Header Set
  function AjaxHeader($header) {
    if(!$this->IsAjax()) {
      trigger_error('not ajax mode', E_USER_ERROR);
    }        
    $this->response->set_header($header);            
  }
    
  // Ajax Data Set       
  function AjaxData($data) {
    if(!$this->IsAjax()) {
      trigger_error('not ajax mode', E_USER_ERROR);
    }
    $this->response->set_data($data);          
  }
    
  // Ajax Extra Set         
  function AjaxExtra($extra) {
    if(!$this->IsAjax()) {
      trigger_error('not ajax mode', E_USER_ERROR);
    }  
    $this->response->set_extra($extra);
  }

  // 错误消息处理
  function errmsg($str) {
    $this->errmsgs[] = $str;
    if($this->IsAjax()) {
      $this->AjaxHeader(-1);
      $this->AjaxData($this->errmsgs);
    }
  }

  function App() {
    return $this->app_;
  }

  function Config($cfg_name) {
    return $this->app_->Config($cfg_name);
  }

  function IsAjax() {
    return $this->app_->InAjax();
  }
}

class CLASS_QRPCMODULE 
  extends CLASS_MODULE
{
  function __construct() {
    parent::__construct(); 
  }

  function doAjax($action) {
    $method=$action;
    $args = $_POST["data"];
    if(!is_array($args)) {
      $args = array();
    }
    if(is_callable(array($this, $method))) {
      return call_user_func_array(array($this, $method), $args);
    } else {
      return parent::doAjax($action);
    } 
  }
}

/**
  * 优化客户端请求包，去除了{header: "", data: "", extra:""}包结构
  */
class CLASS_QRPCMODULE2 
  extends CLASS_MODULE
{
  function __construct() {
    parent::__construct(); 
  }

  function doAjax($action) {
    $method=$action;
    if(is_callable(array($this, $method))) {
      return call_user_func_array(array($this, $method), $_POST);
    } else {
      return parent::doAjax($action);
    } 
  }
}



class CLASS_QUIMODULE
  extends CLASS_MODULE 
{
  function __construct() { parent::__construct(); }

  function doMain($action) {
    $method=$action;
    $args = $_GET;
    if(is_callable(array($this, $method))) {
      return call_user_func_array(array($this, $method), $args);
    } else {
      return parent::doMain($action);
    } 
  }
}

?>
