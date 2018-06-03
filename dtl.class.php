<?php
/*--------------------------------------------------------------------
 $ module: CORE STARDARD TEMPLATE LABLE FRAMEWORK For CMS@Home System
 $ date:   2009-3-29 21:23:28
 $ author: LovelyLife
 $ last modified: 2013-03-9 22:06:58
 $ copyright www.onlyaa.com
----------------------------------------------------------------------*/


define('REGEX_PARSE_ATTRS', "/\s+(\w+)=\"([^>\"]+)\"/i");

class CLASS_DTL {
  
  // html output
  private $pager_;
  private $data_type_;  // 0 - sql, 1 - dict
  private $data_value_;
  private $app_;
  private $t_;
  private $db_name_;
  private $db_;
  // DOM 
  private $node_name_;
  private $attributes_;
  private $tpl_data_;
  private $page_is_overflow;
  // constuct
  function __construct() {  $this->t_ = null;  }

  // php5 construct
  function CLASS_DTL() { $this->__construct(); }

  // entry
  function parse($tag_xml) {
    $this->tag_xml_ = $tag_xml;
    $ret = preg_match_all(
      '/<html:(\w+)([^>]*)>(.*?)<\/html:\1>/is', 
      $tag_xml, 
      $matches
    );

    if(!$ret) 
      return false;

    $this->node_name_ = $matches[1][0];
    $this->attributes_  = $this->parse_attrs($matches[2][0]);
    $this->tpl_data_    = $matches[3][0];
    $this->db_name_ = $this->getAttribute('db');

    return true;
  }

  // construct tag
  function construct_tag($matches) {
    $onname = 'on'.$matches[2];
    if(!method_exists($this, $onname)) {
      return '[!Q.PHP: "&lt;'.$matches[1].':'.$matches[2].'&gt;" not supported]';
    }
    return $this->$onname($matches[3]);
  }

  // init_data_source
  function initDataSource() {
    if($this->support_data_source()) {
      $result = $this->getDataSource();
      $this->data_type_ = $result['type'];
      $this->data_value_ = $result['value'];
    }
  }

  // virtual method implements in sub class
  function onheader() { return ''; }
  function onfooter() { return ''; }

  function query_data_tpl($tpl) {
    $out_buffer = '';
    if($this->is_datatype_sql() && (!$this->is_viewtype_tree())) {
      $sql = $this->getPageSQL( $this->data_value_ );
      if(!$this->db()->execute_tpl($sql, $this, $tpl, $out_buffer)) 
        $this->error($this->db()->get_error());
    }
    return $out_buffer;
  }

  function query_tree_data_tpl($records, $tpl) {
    if(!$this->is_viewtype_tree())
      $this->error("not support viewtype tree.");
    
    $root_value = $this->getAttribute("rootValue");
    if(empty($root_value)) 
      $root_value = 0;

    $config = array(
      "arrData" => $records,
      "parentKey" => $this->getAttribute("parentKey"),
      "key" => $this->getAttribute("key"),
      "root" => $root_value,
      "context" => array(
        "valueselected" => $this->getAttribute("valueSelected"),
        "valuefield" => $this->getAttribute("valuefield"), 
        "textfield" => $this->getAttribute("textfield"), 
        "tpl"   => trim($tpl),
      ),
    );

    $algory = new CLASS_ALGORY($config, $this);
    return $algory->__toString();
  }

  function query_data() {
    $theApp = $this->getApp();
    $records = array();
    if($this->is_datatype_sql()) {
      $sql = $this->getPageSQL($this->data_value_);
      if(!$this->db()->get_results($sql, $records)) 
        trigger_error($this->db()->get_error(), E_USER_ERROR);
    } else if($this->is_datatype_dict()) {
      $theApp->query_dictionary($this->data_value_, $records);
    } else if($this->is_datatype_data()) {
      $records = $this->t_->get_data($this->data_value_);
    }

    return $records;
  }
  
  // 解析标签内的属性，传入的tag不能包含子标签，否则解析可能不正确
  static function parse_attrs($tag) {
    $attrs = array();
    preg_match_all(REGEX_PARSE_ATTRS, $tag, $matches);
    $names = $matches[1];
    $values = $matches[2];
    $len = count($names);
    for($i=0; $i < $len; $i++) {
      $attrs[$names[$i]] = $values[$i];
    }
    return $attrs;
  }
  
  // contact the attributes to string
  function contact_attrs($attrs) {
    $str = "";
    foreach($attrs as $v) {
      if($this->getAttribute($v) != "")
        $str .= " ".$v."=\"".$this->getAttribute($v)."\"";
    }
    return $str;
  }
  
  // record 处理
  function item_process($item, $tpl) {
  // 解析变量标签[field:varname[attrs='']/]
    $t = $this; 
    return preg_replace_callback(
      '/\[field:(\w+)([^\/\]]*)\s*\/?\]/is', 
      function($matches) use( $t, $item ) {
        return $t->parse_item( $item[$matches[1]], $matches[2] );
      },
      $tpl );
  //return preg_replace(
  //  '/\[field:(\w+)([^\/\]]*)\s*\/?\]/ie', 
  //  '$this->parse_item($item["\\1"],"\\2")',
  //  $tpl);       
  }

  function tree_item_process($item, $context, &$out ) {
    echo "dtl::tree_item_process is not called.";
    return '';
  }
  
  function parse_item($value, $attrs_str) {
    //print_r($attrs);
    $attrs = CLASS_DTL::parse_attrs($attrs_str);

    // 处理字典
    if(isset($attrs["dict"])) {
      $dict = null;
      //trigger_error("err", E_USER_ERROR);
      if($this->getApp()->query_dictionary($attrs["dict"], $dict)) {
        foreach($dict as $item) {
          if($item['value'] == $value) {
            $value = $item['text'];
          }
        }
      }
    }
    
    // 处理函数
    if(isset($attrs["func"])) {
      // get func name
      $func = str_ireplace("@this", addslashes( $value ), stripcslashes( $attrs["func"]));
      //$func = str_ireplace("@this", $value, $attrs["func"] );
      $nPos = strpos($func, '(', 0);
      if($nPos >= 0) {
        $funcname = trim(substr($func, 0, $nPos));
        if(function_exists($funcname)) {
          if(isdebug()) {
            eval("\$value = ".$func.";");  
          } else {
            @eval("\$value = ".$func.";");
          }
        } else {
          if(isdebug()) {
            echo "function {$funcname} not exists<br>";
          }
        }
      }
    }
        
    if(empty($value) && !empty($attrs['default'])) {
      $value = $attrs['default'];
    }
    
    // printf("%s\n", $value);
    return $value;
  }

  function is_viewtype_tree() { 
  return ($this->support_viewtype_tree() 
    && ($this->getAttribute('viewtype') == "tree"));
  }

  function is_datatype_sql() { return ($this->data_type_ == "sql"); }
  function is_datatype_dict() { return ($this->data_type_ == "dict"); }
  function is_datatype_data() { return ($this->data_type_ == "data"); }

  // can overrided by subclass
  function support_data_source() { return true; }
  function support_viewtype_tree() { return true; }

  // default data source method, you can override
  function getDataSource() {
    if(!$this->support_data_source()) 
      $this->error("not support data source attribute.");

    $return_code = array();
    if($this->getAttribute("datasrc") == '') 
      return $return_code;
  
    // get data source * high priority
    // $str = "sql://asdfasd dasdda'sdff";
    $result = preg_match_all(
      '/^(sql|dict|share|data):\/\/\s*(.*)\s*/i', 
      $this->getAttribute("datasrc"), 
      $matches
    );

    if($result <= 0 ) 
      $this->error("invalid data source:".$this->getAttribute("datasrc"));
    
    $return_code['type'] = $type = $matches[1][0];
    $return_code['value'] = $value = $matches[2][0];
    
    if($type == 'sql' || $type=='share') {
      $sql = $value;

      if($data_type == 'share') 
        $sql = $this->db()->share($data_value);

      $return_code['value'] = $sql;
    }

    return $return_code;
  }

  function getPager() 
  {
    if( !$this->pager_ ) {
      $page_size = intval( $this->getAttribute( "pagesize" ), 10 );
      $sql = $this->data_value_;
      if($page_size > 0) {
        $totalsize = $this->db()->query_count($sql);
        //echo "totalsize:\n".$totalsize."  ". ( $page_size * (intval( $_GET['p'], 10 ) - 1)  );
        $this->page_is_overflow = ( $totalsize < ( $page_size * (intval( $_GET['p'], 10 ) - 1) ) );
        $cfg = array("totalsize" => $totalsize,"pagesize" => $page_size, 'pagekey' => 'p' );
        if($this->getAttribute("html") == true) {
          $cfg["html"] = true;
          $cfg["tpl"] = $this->getAttribute("tpl");
          $cfg["first"] = $this->getAttribute("first");
        }
        
        $cfg["page"] = $this->getAttribute("page");

        // 实例化分页类,初始化构造函数中的总条目数和每页显示条目数
        $this->pager_ = new CLASS_PAGE($cfg);
      } 
    }
 
    return $this->pager_;
  }

  function getPageSQL($sql) 
  {
    if( $this->getPager() ) {
      $page_size = $this->getPager()->getPageSize();
      $sql .= " limit ";
      if( !$this->page_is_overflow ) {
        $sql .= ($this->getPager()->GetCurrentPage()-1)*$page_size;
      } else {
        $sql .=  ( intval( $_GET['p'], 10 )-1 )*$page_size;
      }
      $sql .= ",{$page_size};";
    } else {      
      $size = intval($this->getAttribute('size'), 10);
      if($size > 0) {
        $sql .= " limit 0, {$size};";
      }
    }
    return $sql;
  }

  function getApp() {  return $this->t_->getApp(); }

  function db() {
    if(!$this->db_) {
      $this->db_ = $this->getApp()->db($this->db_name_);
      if(!$this->db_) {
        $this->db_ = $this->getApp()->db();
      }
    }

    return $this->db_;
  }

  // error 
  function error($message) {
    trigger_error(
      htmlspecialchars(
        "error occurred:\n\n$message in tag [".$this->getNodeName()."]"
      ), 
      E_USER_ERROR
    );
  }

  // out put html
  function __toString() 
  {
    $tpl = $this->t_->complie_php_vars($this->tpl_data_);
    $this->initDataSource();    
    $out_html_ = $this->onheader();  
    // 解析t:标签,例如 <t:data>tpl</t:data>
    $out_html_ .= preg_replace_callback(
      '/<(t):(\w+)>(.+?)<\/\1:\2>/is',
      array($this, 'construct_tag'),
      $tpl
    );    
    $out_html_ .= $this->onfooter();

    return $out_html_;
  }

  function setTemplate($t) {
    $this->t_ = $t;
  }

  function getTemplate() {
    return $this->_t;
  }


  // Unserialize
  function unserialize($tag_config) {
    if(is_array($tag_config)) {
      $this->node_name_ = $tag_config['nodeName_'];
      $this->attributes_ = $tag_config['attrs_'];
      $this->tpl_data_ = $tag_config['tpl_'];
      return true;
    }

    $this->error('invalid tag config:\n'.$tag_config);
    return false;
  }

  // Serialize
  function serialize() {
    return array(
      'nodeName_' => $this->node_name_,
      'attrs_' => $this->attributes_,
      'tpl_' => $this->tpl_data_, 
    );
  }

  // getNodeName
  function getNodeName() {
    return $this->node_name_;
  }

  // 读取标签属性
  function getAttribute($attrName) {
    $s = $this->attributes_[$attrName];
    if(is_string($s)) {
      return $this->t_->complie_php_vars($s);
    }

    return $s;
  }
    
    //设置标签属性
  function setAttribute($attrName, $value) {
    $this->attributes_[$attrName] = $value;
  }
}

?>
