<?php
/*--------------------------------------------------------------------
 $ module: jtable controls library of CMS@Home System
 $ date:   2009-4-23 10:43:20
 $ author: LovelyLife
 $ last modify: 2010-09-08
----------------------------------------------------------------------*/


class CLASS_DTL_JTABLE extends CLASS_DTL {
  private $jtable_id_ = 0;
  private $jstore_;
  private $jobject_;
  private $jtable_;
  private $jcontainer_;
  private $jtable_params_ = array();
  private $jtable_cols = array();
  var $srctype;
  var $CONST_SRCTYPE = null;
  function __construct() {
    $this->CONST_SRCTYPE = array("xml", "sql");
    $srctype = strtolower($this->getAttribute('srctype'));
    if(in_array($srctype, $this->CONST_SRCTYPE)) {
      $this->srctype = $srctype;
    } else {
      $this->srctype = NULL;  // 默认数据类型
    }

    static $_jtable_count = 0;
    $jtable_id_ = $_jtable_count + 1;
    $_jtable_count++;
    parent::__construct();
  }
  
  function CLASS_DTL_JTABLE() { $this->__construct();  }
  
  function onheader() {
    $str_temp = $this->getAttribute("id");
    if(!empty($str_temp)) {
      $this->jtable_id_ = $str_temp;
    }

    $this->jstore_  = sprintf("jstore_%s",  $this->jtable_id_);
    $this->jtable_  = sprintf("jtable_%s",  $this->jtable_id_);
    $this->jobject_ = sprintf("jobject_%s", $this->jtable_id_);
    $this->jcontainer_ = sprintf("jtable_container_%s", $this->jtable_id_);
    $this->jtable_params_["container"] = $this->jcontainer_;

    if($this->getAttribute("style") != "") {
      $style = " style=\"".$this->getAttribute("style")."\"";
    }
    
    $str_output = sprintf("<div id=\"%s\"%s></div>\n", 
        $this->jcontainer_, 
        $style);
    
    if($this->getAttribute("title") != "") {
      $this->jtable_params_["title"] = $this->getAttribute("title");
    }
    
    if($this->getAttribute("wstyle") != "") {
      $this->jtable_params_["wstyle"] = $this->getAttribute("wstyle");
    }
    
    if($this->getAttribute("checkboxname") != "") {
      $this->jtable_params_["checkbox_name"] = $this->getAttribute("checkboxname");
    }
      
    if($this->getAttribute("checkboxfieldname") != "") {
      $this->jtable_params_["checkbox_fieldname"] = $this->getAttribute("checkboxfieldname");
    }

    if($this->getAttribute("rowHeight") != "") {
      $this->jtable_params_["row_height"] = $this->getAttribute("rowHeight");
    }
      
    // events
    if($this->getAttribute("onRowClick") != "") {
      $this->jtable_params_["row_onclick"] = $this->getAttribute("onRowClick");
    }
    
    if($this->getAttribute("onRowMouseOver") != "") {
      $this->jtable_params_["row_onmouseover"] = $this->getAttribute("onRowMouseOver");
    } else {
      //$this->jtable_params_["row_onmouseover"] = "row_onmouseover";
    }
    
    if($this->getAttribute("onRowMouseOut") != "") {
      $this->jtable_params_["row_onmouseout"] = $this->getAttribute("onRowMouseOut");
    }  else {
      //$this->jtable_params_["row_onmouseout"] = "row_onmouseout";
    }
    
    if($this->getAttribute("onRowInsert") != "") {
      $this->jtable_params_["row_oninsert"] = $this->getAttribute("onRowInsert");
    }
    $jobject= $this->jobject_;
    $str_output .= "<script>\n<!--\n";

    $str_output .= "Q.Ready(function() { \n"
      ."var {$jobject}=".json_encode($this->jtable_params_).";\n";
    return $str_output;
  }

  function oncols($tpl) {
    $html_content = "<cols>".$tpl."</cols>";
    $document = new DOMDocument();
    $r = $document->loadXML($html_content);
    $cols = $document->getElementsByTagName("item");
    // 遍历子节点
    $len = $cols->length;
    // 清空映射转换表数据
    for($i = 0; $i < $len; $i++) {
      $col = $cols->item($i);
      /* { 
        name: 'action', title: '操作', 
        width: 150,  align: 'center', 
        renderer: renderer_action, 
        isHTML: true 
      } */

      $width = intval($col->getAttribute("width"), 10);
      if((is_nan($width)) || ($width < 10)) {
        $width = 150;
      }

      $th = array(
        "name" => $col->getAttribute("fieldname"),
        "title"=> $col->nodeValue, 
        "width"=> $width,  
        "align"=> $col->getAttribute("align"),
        "isHTML"=> true
      );
      
      // 映射转换表
      $dicname = null;
      $func = null;
      if($col->getAttribute("func")) {
        $func = $col->getAttribute("func");
      }
      
      if($col->getAttribute("dict")) {
        $dicname = $col->getAttribute("dict");
      }
      //if($dicname || $func) {
        //__db_push_map($col->getAttribute("fieldname"), $dicname, $func);
      //}
      if($col->getAttribute("renderer") != "") {
        $th["renderer"] = $col->getAttribute("renderer");
      }
      // 映射转换表结束      
      array_push($this->jtable_cols, $th);
    }

    //$this->jtable_params_["columns"] = $this->jtable_cols;
    $jobject= $this->jobject_;
    return "{$jobject}['columns']=".json_encode($this->jtable_cols).";";
  }

  function ondata($tpl) {
    $str_output = '';
    $data = $this->getJsonData();
    $data = ($data == "null")?"[]":$data;
    $jobject= $this->jobject_;
    $str_output .= 
      "{$jobject}['jstore']=new jstore({$data});\n";

    return $str_output;
  }
  
  function onfooter() {
    $jtable = $this->jtable_;
    $jobject= $this->jobject_;
    $str_output .=
      "if(!window.{$jtable}) { "
      ."window.{$jtable}=new jtable({$jobject}); \n"
      ."} else { alert('{$jtable} is conflict.'); } \n"
      ."})\n"
      ."\n--></script>";

    return $str_output;
  }

  function getJsonData() {
    $arr = array(
      "loadtype"=>"json", 
      "datasource" => $this->query_data()
    );

    return json_encode($arr);
    /*
    $db = $this->theApp()->db();
    // print($this->attrs("cache"));

    if($this->getAttribute("cache") != "") {
      $rs = cache_query($this->getAttribute("cache"));
    } else {
        
      $sql = $this->attrs("sql");
      if($sql=="") {
        $this->html .= "sql 没有指定!"; 
      } else {
        $rs = $db->doQuery($sql, true);
      }
    }
    return $rs;
    //return json_encode($mysql = CLASS_MYSQL::getInstance()->doQuery($sql));
    */
  }
};

?>
