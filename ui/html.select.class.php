<?php
/*--------------------------------------------------------------------
 $ module: select form controls library of CMS@Home System
 $ date:   2009-4-23 10:43:20
 $ author: LovelyLife
----------------------------------------------------------------------*/
function cpx($char, $n) {
  $s = '';
  for($i=0; $i < $n; $i++) {
    $s .= $char;
  }
  return $s;
}


class CLASS_DTL_SELECT extends CLASS_DTL {
  var $index = 0;    // 当前索引
  var $value_selected;
  var $key_selected;
  var $mask_selected;

  function __construct() { parent::__construct(); }
  function CLASS_DTL_SELECT() { $this->__construct();  }
  
  function onheader() {
    $attrs = $this->contact_attrs(
      array("id","name","style","class","size", "value", "onchange")
    );
    return "<".$this->getNodeName().$attrs.">";
  }

  function ondata($item_tpl) {
    $this->value_selected = $this->getAttribute('valueSelected');
    $this->key_selected = $this->getAttribute('keySelected');
    $this->mask_selected = $this->getAttribute('maskSelected');
    
    if(empty($this->mask_selected)) 
      $this->mask_selected = "selected";
    
    if(empty($this->key_selected)) 
      $this->key_selected = "value";
    
    if($this->is_datatype_sql()) {
      if($this->is_viewtype_tree()) {
        $records = $this->query_data();
        $rootValue = $this->getAttribute("rootValue");
        if(empty($rootValue)) 
          $rootValue = 0;

        $config = array(
          "parentKey" => $this->getAttribute("parentKey"),
          "key" => $this->getAttribute("key"),
          "root" => $rootValue,
          "context" => array(
            "valueselected" => $value_selected,
            "valuefield" => $this->getAttribute("valuefield"), 
            "textfield" => $this->getAttribute("textfield"), 
            "tpl"       => trim($item_tpl),
          ),
          "arrData" => $records,
        );

        $algory = new CLASS_ALGORY($config, $this);
        return $algory->__toString();
      } else {
        return $this->query_data_tpl($item_tpl);
      }
    } else {
      $buffer = '';
      $records = $this->query_data();
      foreach($records as $item) {
        $buffer .= $this->item_process($item, $item_tpl);
      }
      //print($buffer);
      return $buffer;
    }
  }

  function onfooter() {
    return "</".$this->getNodeName().">";
  }

  function item_process($item, $item_tpl) {
    $this->index++;
    $item['index'] = $this->index;
    if($item[$this->key_selected] == $this->value_selected) {
      $item['selected'] = $this->mask_selected;
    }
    //print_r($item);
    return parent::item_process($item, $item_tpl);
  }
  
  function tree_item_process($item, $context, &$out) {
    $selinfo = "";
    $valuefield = $context["valuefield"];
    $textfield = $context["textfield"];

    if($item[$this->getAttribute('key')] == $this->value_selected) {
      //$item['selected'] = $this->mask_selected;
      $selinfo = $this->mask_selected;
    }
    #$tplData1 = preg_replace(
    #  "/\[property:selected\s?\/?\]/i", $selinfo, $tplData);
    if($context["tpl"] == "") {
      $out .= "<option value=\"".$item[$valuefield]."\" ".$selinfo.">".cpx("--", $context["depth"])."".$item[$textfield]."</option>";  
    } else {
      $delivertpldata = preg_replace("/\[field:deliver\s?\/?\]/i", cpx("--", $context["depth"]), $context["tpl"]); 
      $delivertpldata = preg_replace("/\[property:selected\s?\/?\]/i", $selinfo, $delivertpldata);
      $out .= $this->item_process($item, $delivertpldata );
    }
    
    return true;
  }
}

?>
