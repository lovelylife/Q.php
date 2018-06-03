<?php
/*--------------------------------------------------------------------
 $ name: calendar controls library of CMS@Home System
 $ date:   2009-4-23 10:43:20
 $ author: LovelyLife
 $ last modify: 2010-12-11
----------------------------------------------------------------------*/

class CLASS_DTL_CALENDAR extends CLASS_DTL {
	function __construct($node) {
		parent::__construct($node);
	}
	
	function CLASS_DTL_CALENDAR($node) { $this->__construct($node);	}
	
	function Create($node) {

		//$D=new Calendar;
		
		//$this->html = $D->OUT();
		return $this->html;
	}
};

?>