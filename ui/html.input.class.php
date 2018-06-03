<?php
/*--------------------------------------------------------------------
 $ module: input form controls library of CMS@Home System
 $ date:   2009-4-23 10:43:20
 $ author: LovelyLife
----------------------------------------------------------------------*/

class CLASS_DTL_INPUT extends CLASS_DTL {
  
  function __construct() {
    parent::__construct();
  }
  
  function CLASS_DTL_INPUT() { $this->__construct();  }
  
  function onheader() {
    return "";
  }

  function onfooter() {
    return "";
  }

  function ondata($item_tpl) {
    $type = strtolower($this->getAttribute('type'));
    if(in_array($type, array('radio','checkbox'))) {
      return $this->getitems($type);
    }
    return $item_tpl;
  }
  
  function getitems($type) {
    $valueSelected = $this->getAttribute('valueSelected');
    $valueSelected = preg_replace('/(\s*,\s*)/i', ',', $valueSelected);
    $valueSelected = str_split(',', $valueSelected);
    
    $records = $this->query_data();    
    $len = count($records);
    $acceptAttrs = array('style', 'class', 'value');
    $tagName = $this->getNodeName();
    $name = $this->getAttribute('name');
    $buf = '';
    foreach($records as $item) {
      $buf .= '<'. $tagName;
      $buf .= CLASS_DTL::contact_attrs($acceptAttrs);
      $buf .= ' type="'.$type.'"';
      $buf .= ' name="'.$name.'"';
      $buf .= ' value="'.$item['value'].'"';
      
      if(in_array($item['value'], $valueSelected)) {
        $buf .= ' checked'; 
      }
      $buf .= '>&nbsp;'.$item['text'].'&nbsp;&nbsp;&nbsp;';
    }

    return $buf;    
  }
}

?>
