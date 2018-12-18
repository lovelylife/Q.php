<?php
/*--------------------------------------------------------------------
 $ module: page.class.php
 $ license: version 1.0
 $ create: 2009-7-17 15:21:12
 $ author: LovelyLife <master#onlyaa.com>
 $ copyright :Copyright 2006 onlyaa.com
----------------------------------------------------------------------*/

class page {
  var $total_size;
  var $page_size;
  var $page_count;
  var $current_page;

  function __construct($total_size, $page_size, $current_page) {
    $this->total_size = $total_size;
    $this->page_size  = $page_size;
    
    if($this->page_size < 1) 
      $this->page_size = 10;
    
    // 记录数
    $this->page_count = ceil($this->total_size / $this->page_size);
    $this->page_count = max($this->page_count, 1);  // 修正页数为1的bug

    // 当前访问页
    $current_page = max(1, $current_page);
    $this->current_page = min($this->page_count, $current_page);
  }

  function page($total_size, $page_size, $current_page) {
    $this->__construct($total_size, $page_size, $current_page);
  }

  function first() { return 1; }
  function prev()  { return max(1, $this->current_page - 1);  }
  function next()  { return min($this->current_page + 1, $this->page_count);  }
  function last()  { return $this->page_count;  }
  function size()  { return $this->page_size; }
  function count() { return $this->page_count; }
  function current() { return $this->current_page; }
  function set($p) {
    $cur = intval($p, 10);
    $cur = max(1, $cur);
    $this->current_page = min($this->page_count, $cur);
  }
}

// 分页模板
class CLASS_PAGE {
  var $html;
  var $url_template;
  var $visible_size;    // 显示长度
  var $table;        // 用来区分多表分页访问
  var $page_key;        // 地址中的查询字段
  var $current;      // 当前访问页
  var $total_size;      // 记录总数
  var $page_size;      // 分页大小
  var $page_count;      // 页数
  var $first;
  function __construct($cfg) {
    $this->html = "";
    $this->url = "";
    $this->first = 1;
    $this->visible_size = 10;
    $this->page_key = "page";
    if(!empty($cfg['pagekey'])) {
      $this->page_key = $cfg['pagekey'];
    }
    // 默认分页大小为 10
    $this->page_size = intval($cfg["pagesize"], 10);
    if($this->page_size < 1) 
      $this->page_size = 10;
    
    // 记录数
    $this->total_size = $cfg["totalsize"];
    $this->page_count = ceil($this->total_size / $this->page_size);
    $this->page_count = max($this->page_count, 1);  // 修正页数为1的bug
    // 当前访问页
    
    if($cfg["html"] == true) {
      if(!array_key_exists($this->page_key, $cfg)) {
        $cur = intval($_GET[$this->page_key]);
      } else {
        $cur = intval($cfg[$this->page_key]);
      }
      
      $cur = max(1, $cur);
      $this->current = min($this->page_count, $cur);
      $this->first = $cfg["first"];
      if(empty($this->first)) {
        $this->first = 1;
      }
      $this->url_template = $cfg["tpl"];
    } else {
      if(!isset($_GET[$this->page_key])) {
        $this->current = 1;
      } else {
        $cur = intval($_GET[$this->page_key]);
        $cur = max(1, $cur);
        $this->current = min($this->page_count, $cur);
      }
      $this->_baseURL();
    }
    //var_dump($this);
  }
  
  function CLASS_PAGE($cfg) { $this->__construct($cfg); }
  
  function GetCurrentPage() {
    return $this->current;
  }
  
  function _first(){  return 1; }
  
  function _prev() {  return max(1, $this->current - 1);  }
  
  function _next() {  return min($this->current + 1, $this->page_count);  }
  
  function _last() {  return $this->page_count;  }

  function __toString() {
    $mode = 1;
    switch($mode) {
      case 2:
        break;
      case 3:
        break;
      default:

        $this->defaultview();
    }
    //echo $this->html;
    return $this->html;
  }
  
  function defaultview() {
    $cur = $this->current;
    $pcount = $this->page_count;
    if($cur > 1) {
      $this->html = "<a href=\"".$this->_goto($this->_prev())."\" class=\"first\"><font size=2> << </font></a>";
    }
    
    $buf = "";  // 分页缓冲区
    $start = $cur - (floor($this->visible_size/2));
    $start = max(1, $start);
    
    $t = $cur + floor($this->visible_size/2) - 1; // last visible page id
    $end = min($pcount, $t);  // 显示分页长度
    
    for($i=$start; $i <= $end; $i++) {
      if($i == $cur) {
        $buf .= '<span class="current">'.$i.'</span>'; 
        continue; 
      } else { 
        $buf .= '<a href="'.$this->_goto($i).'">'.$i.'</a> ';
      }
    }
    
    $this->html .= $buf;
    // print("<br>cur:{$cur}<br>");
    if($cur < $pcount) {
      $this->html .= "<a href=\"".$this->_goto($this->_next())."\"><font size=2> >> </font></a>";  
    }
  }
  
  function _goto($pageid) {
    if($pageid > 1) {
      $url = str_ireplace("{pid}", $pageid, $this->url_template);
    } else {
      $url = $this->first;
    }
    
    return $url;
  }

  function _baseURL() {
    $url = $_SERVER["REQUEST_URI"];
    $pos = strpos($url, "?");
    // 没有其他的参数
    if($pos == -1) {
      $this->url_template=$url."?".$this->page_key."=";
      return;
    }
    print_r($this); 
    // 存在其他参数
    $params = preg_split("/&/", $_SERVER["QUERY_STRING"]);
    print_r($params);
    $params2 = array();
    foreach($params as $value) {
      $l = preg_split("/=/", $value);
      if($l[0] != $this->page_key) {
        array_push($params2, $value);
      }
    }
    
    $this->url_template = $_SERVER["SCRIPT_NAME"]."?".implode($params2, "&")."&".$this->page_key."={pid}";
    $this->first = str_ireplace("{pid}", 1, $this->url_template);
  }
    
  function setPage($page) {
    $cur = intval($page, 10);
    $cur = max(1, $cur);
    $this->current = min($this->page_count, $cur);
  }

  function getPageSize() {
    return $this->page_size;
  }

  function getSQLPage() {
      $sql = " limit ";
      $sql .= ($this->GetCurrentPage()-1)*$this->getPageSize();
      $sql .= ",".$this->getPageSize().";";
      return $sql;
  }
}

?>
