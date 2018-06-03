<?php
/*--------------------------------------------------------------------
 $ class: base algorithm for onlyaa.com
 $ date:   2009-7-8 23:00:25
 $ version 1.0
 $ author: LovelyLife
 
 $ callback define
 function cb([in]$item, [in]&$context, &$out) {
  // do any other action
 }
 
----------------------------------------------------------------------*/

class CLASS_ALGORY {
  
  var $arrData;  // 记录集 二维数组
  var $parentKey;  // 父键名
  var $key;    // 键名
  var $rootValue; // 默认根值，（是顶级的父级）
  var $context;  // 递归中需要的上下文
  var $out;    // 数据输出通道
  
  function __construct($config, &$caller_context) {
    //print("\n".get_class($this)."::__construct.\n");
    $this->out = "";
    $this->parentKey = $config["parentKey"];
    $this->key = $config["key"];
    $this->rootValue = $config["root"];
    $this->context = $config["context"];
    $this->arrData = $config["arrData"];

    $rootItems = $this->getItems($this->rootValue);  // 获得所有根节点记录
    $this->doTraverse($rootItems, 0, $caller_context );
  }
  
  function CLASS_ALGORY($data, &$caller_context) { 
    $this->__construct($data, $caller_context); 
  }
  
  // 递归处理分类节点(遍历算法)
  function doTraverse($items, $depth, $caller_context ) {
    //print("\ndoTraverse was called.\n");
    $len = count($items);
    if($len == 0) {  return; }
    foreach($items as $item) {
      
      // get sub items
      $childNodes = $this->getItems($item[$this->key]);
      
      // set attribute id
      $this->context["itemid"] = $item[$this->key];
      
      // set attribute depth
      $this->context["depth"] = $depth;

      // set attribute hasChild
      $this->context["hasChild"] = (count($childNodes)>0);
      
      // collect sub item information
      $this->context["subitems"] = array();
      foreach($childNodes as $rd) {
        array_push($this->context["subitems"], $rd[$this->key]);
      }
      
      
      // $callback
      if(!$caller_context->tree_item_process($item, $this->context, $this->out)) 
        return false;
    
      // traverse sub items
      $this->doTraverse($childNodes, ($depth+1), $caller_context );
    }
    return true;
  }
  
  function getItems($parentKey) {
    $itemsArray = array();
    foreach($this->arrData as $item) {
      if($item[$this->parentKey] == $parentKey) { 
        array_push($itemsArray, $item); 
      }
    }
    return $itemsArray;
  }
  
  function __toString() { return $this->out; }
}

?>
