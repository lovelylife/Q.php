<?php
/*--------------------------------------------------------------------
 $ module: grid form library of CMS@Home System
 $ date:   2009-4-23 10:43:20
 $ author: LovelyLife
----------------------------------------------------------------------*/

class CLASS_DTL_TABLE extends CLASS_DTL {

  function __construct() {  parent::__construct(); }
  function CLASS_DTL_TABLE() {  $this->__construct(); }
  
  function onheader() {
    $attrs = $this->contact_attrs( 
      array("type", "name","id","style","class","border","cellspacing","cellpadding")
    );

    return "<".$this->getNodeName().$attrs.">";
  }

  function ondata($item_tpl) {
    //echo $item_tpl;
    if($this->is_viewtype_tree()) {
      $records = $this->query_data();
      //print_r( $records );
      return $this->query_tree_data_tpl($records, $item_tpl);
    } else {
      if($this->is_datatype_sql()) {
        return $this->query_data_tpl($item_tpl);
      } else if($this->is_datatype_data()) {
        $records = $this->query_data();
      	$buffer = '';
        foreach($records as $i => $item) {
          $buffer .= $this->item_process($item, $item_tpl);
        }
        return $buffer;
      }
    }  
  }

  function onpager($tpl) {
    return $this->getPager()->__toString();
  }

  function onfooter() {
    return "</".$this->getNodeName().">";
  }

  
  function tree_item_process( $item, $context, &$out ) {
    //print_r( $item );
    // get sub items
    $subitems = $context["subitems"];
    $tableid = $this->id;
    $item["rowid"] = $tableid."_row_";
    $expandid = $tableid."_expand_".$context["itemid"];
  
    $rowclick = "row_expand(\"{$tableid}\", this)";
    //$str = $context["hasChild"]?"yes":"no";
    $expand = $context["hasChild"]?"top":"-31px";
    $expandwidth = (11*($context["depth"]*3+1));
    $rowhtml = $context["tpl"];

    $rp = "";
    if($context["itemid"] == $context["valueSelected"]) {
      $rp = "class=\"row_focus\"";
    }
    //print($rp);
    $rowhtml = preg_replace("/\[class\/?\]/", $rp, $rowhtml);
  
    // print(getLines("--", $context["depth"]).$item["category_name"]."[".$str."]<br>");
    //$display = "display:".((count($subitems) > 0 ) "" : "none");
    $rowhtml = preg_replace("/\[expand\/?\]/i", "<DIV id='{$expandid}' class=\"table_expand\" subitems='".implode(",", $subitems)."' style='width: {$expandwidth}px; {$display};' onclick='{$rowclick};'></DIV>&nbsp;", $rowhtml);

    $out .= CLASS_DTL::item_process($item, $rowhtml, $context);
    
    return true;
  }

  function support_viewtype_tree() { return true; }
}

?>
