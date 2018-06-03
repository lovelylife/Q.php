<?php
/*--------------------------------------------------------------------
 $ xml class 
 $ created: 2010-10-31 12:05:46 by LovelyLife
 $ lastModified: 2010-08-24 1:40:55
 $ powered by http://onlyaa.com
----------------------------------------------------------------------*/


if(!class_exists('XML')) {

class xml_php4 {

}

class xml_php5 {
	
}

class XMLNode {
	var $_attrs = null;
	function __construct(){
		$this->_attrs = array();
	}
	function XMLNode(){ $this->__construct(); }
	function attrs($attrName) {
		
	}

	function setAttrs($attrName, $value) {
	
	}
}

#如果DOMDocument不存在则重新定义
class XML {
	var $xmlDoc;
	function __construct(){
		// for templates
		if (version_compare(PHP_VERSION, '5.0.0', '<')) {
			$this->xmlDoc = new xml_php4();
		} else {
			// for templates
			$this->xmlDoc = new xml_php5();
		}
	}
	function XML(){ $this->__construct(); }
	function loadXML($xmlstr) {}
	function __toString($fname) {}
}

}

?>
