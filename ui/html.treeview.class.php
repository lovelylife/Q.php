<?php
/*--------------------------------------------------------------------
 $ module: jtree controls library of CMS@Home System
 $ date:   2009-4-23 10:43:20
 $ author: LovelyLife
----------------------------------------------------------------------*/


class CLASS_DTL_TREEVIEW extends CLASS_DTL {
	function __construct($node) {
		parent::__construct($node);
	}
	
	function CLASS_DTL_TREEVIEW($node) { $this->__construct($node);	}
	
	function Create($node) {
		static $_simpletree_count = 0;
		$jparams = array();
		$_simpletree_count = $_simpletree_count + 1;
		$jtreeid = ($node->attrs("id") != "") ? $node->attrs("id") : $_simpletree_count;
		
		
		$jstore  = sprintf("jstore_%s", $jtreeid);
		$jsimpletree = sprintf("jsimpletree_%s", $jtreeid);
		
		$jparams["render"] = $node->attrs("render");
		$jparams["text"] = $node->attrs("text");
		$jparams["root"] = $node->attrs("root");
		$jparams["key"] = $node->attrs("key");
		$jparams["parentKey"] = $node->attrs("parentKey");
		$jparams["data"] = $this->getJsonData($node);
		
		// events
		//if($node->attrs("onRowClick") != "") {
		//	$jparams["row_onclick"] = $node->attrs("onRowClick");
		//}
		
		
		$this->html .= "<script language=\"javascript\"><!--\n";
		$this->html .= "var {$jstore} = ".json_encode($jparams).";\n";
		$this->html .= "var {$jsimpletree} = null;\n";
		$this->html .= "Q.Ready(function() {\n";
		$this->html .= "{$jsimpletree} = new __simpleTreeL(Q.$('frame_left'), \"站点根目录\", true); \n";
		$this->html .= "{$jsimpletree}.render({$jstore});\n";
		$this->html .="});\n--></script>";
	}
	
	function getJsonData($node) {
		$db = CLASS_APPLICATION::theApp()->db();
		// print($node->attrs("cache"));

		if($node->attrs("cache") != "") {
			$rs = cache_query($node->attrs("cache"));
		} else {
				
			$sql = $node->attrs("sql");
			if($sql=="") {
				$this->html .= "sql 没有指定!"; 
			} else {
				$rs = $db->doQuery($sql, true);
			}
		}
		return $rs;
		//return json_encode($mysql = CLASS_MYSQL::getInstance()->doQuery($sql));
	}

	function __toString() {
		return $this->html;
	}
};

?>