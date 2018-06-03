<?php
/*-------------------------------------------------------------------
 $  LIST CLASS
---------------------------------------------------------------------*/

// list 处理
class CLASS_DTL_LIST extends CLASS_DTL {

  var $index = 0;    // 当前索引
  var $value_selected;
  var $key_selected;
  var $mask_selected;

  function __construct() { parent::__construct(); }
  
  function CLASS_DTL_LIST() { $this->__construct(); }
  
  function ondata($item_tpl) {
    $this->value_selected = $this->getAttribute('valueSelected');
    $this->key_selected = $this->getAttribute('keySelected');
    $this->mask_selected = $this->getAttribute('maskSelected');
    if($this->is_datatype_sql()) {
      return $this->query_data_tpl($item_tpl);
    } else if($this->is_datatype_data()) {
      $records = $this->query_data();
      $buffer = '';
      foreach($records as $item) {
        $buffer .= $this->item_process($item, $item_tpl);
      }
      return $buffer;
    }
  }

  function item_process($item, $item_tpl) {
    $this->index++;
    $item['index'] = $this->index;
    if($item[$this->key_selected] == $this->value_selected) {
      $item['selected'] = $this->mask_selected;
    }
 
    return parent::item_process($item, $item_tpl);
  }

  function support_viewtype_tree() { return false; }
}

?>
