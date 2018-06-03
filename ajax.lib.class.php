<?php
/*--------------------------------------------------------------------
 $ module: ajax package struct libraries of CMS@Home System
 $ function: ajax 通信协议包结构
 $ date:   2009-4-23 10:43:20
 $ author: LovelyLife
----------------------------------------------------------------------*/

 if (!function_exists('json_encode')) {
  require_once (_QROOT."/JSON.class.php");
  function json_encode($object) {
    $value = new Services_JSON();
    return $value->encode($object); 
  }
}

if (!function_exists('json_decode')) {
  //note:empty ,such as array() will not converted
  function arrayToObject($data) 
  {
      if(!is_array($data) || empty($data)) return $data;
      
      $object = new stdClass();
      if (is_array($data) && count($data) > 0) {
        foreach ($data as $name=>$value) {
           //$name = strtolower(trim($name));
          // if (!empty($name)) {
              $object->$name = arrayToObject($value);
          // }
        }
      }
      return $object;
  }
  //note:empty object ,such as array(),new stdClass() will not converted
  function objectToArray( $object )
  {
      if( !is_object( $object ) 
      && !is_array( $object ) 
      || empty($object) 
      || $object==new stdClass()
    )
      {
          return $object;
      }
      if( is_object( $object ) )  {
          $object = get_object_vars( $object );
      }
      return array_map( 'objectToArray', $object );
  }

  require_once (_QROOT."/JSON.class.php");
  
  function json_decode($json_string) {
    $value = new Services_JSON();
    return objectToArray($value->decode($json_string));
  }
}

class CLASS_AJAX_PACKAGE {
  var $json;
  function __construct() {
    $this->json = array(
      "header" => "",
      "data"   => "",
      "extra"   => ""
    );
  }
  
  function CLASS_AJAX_PACKAGE() { $this->__construct(); }
  function set_header($dt){ $this->json["header"] = $dt; }
  function set_data($dt) { $this->json["data"] = $dt; }
  function set_extra($dt) { $this->json["extra"] = $dt; }
  function __toString() { return json_encode($this->json); }
}

?>
