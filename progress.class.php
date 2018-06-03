<?php
/*--------------------------------------------------------------------
 $ module: task class libraries of CMS@Home System
 $ function: 任务类
 $ date:   2009-4-23 10:43:20
 $ author: LovelyLife
----------------------------------------------------------------------*/

// task id name
(!defined('_taskid')) && define('_taskid', '_taskid');

// 任务队列
class CLASS_TASKMGR  {
	
	var $tasks;
	var $errmsg;
	
	function __construct() {
		$this->tasks = array();
		$this->errmsg = '';
	}
	
	function CLASS_TASKMGR() {
		$this->__construct();
	}
	
	function append($task) {
		array_push($this->tasks, $task);
	}
	
	function remove() {
		return array_shift($this->tasks);
	}

	function& getAlltasks() {
		return $this->tasks;
	}

	function& getTask($tkid) {
		return $this->tasks[$tkid];
	}
}

// 任务进度条
class CLASS_PROGRESS extends CLASS_TASKMGR  {

	var $tpl_vars_;	// 模板引擎
	
	function __construct() {
		$this->tpl_vars_ = array();
		parent::__construct();
	}
	
	function CLASS_PROGRESS() {
		$this->__construct();
	}
	
	//! 执行任务
	function doTask($start, &$num, $context) 
	{  
		$retCode = 0;
		$topNum = $this->doing($start, $num, $context);
		if($topNum < -1) { $retCode = $topNum;  return $retCode; }
		
		if($topNum > ($start + $num)) {
			$retCode = -1;
			$this->showProgress(((($start + $num)*100)/$topNum));
		} else {
			$this->showProgress(100);
		}
		return $retCode;
	}
	
	function doing($start, &$num, $context){	return 0; }
	
	//! 处理任务
	function Process() {
		
		$taskid = ($_GET["taskid"]) ? intval($_GET["taskid"]) : 0;
		$start = ($_GET["start"])?intval($_GET["start"]) : 0;
		$num = ($_GET["num"])?intval($_GET["num"]) : 1;
		$tlen = count($this->getAlltasks());
		// print_r($this->get_tasks());
		$task = null;
		if($taskid < $tlen ) {
			$task = $this->getTask($taskid);
			$this->updateCompleted(($taskid+1)."/".$tlen);		
		} else {
			$this->showProgress(100);
			$this->setTaskTitle("任务结束");
			$this->updateCompleted($taskid."/".$tlen);
			$this->updateMessage("操作完成!");
			return;
		}
		
		// 执行任务
		$context["task"] = $task; 
		$result = $this->doTask($start, $num, $context);
		if( $result== 0) {
			// complete on tesk
			$taskid = $taskid + 1;
			$start = 0;
		} else if($result == -1){
			// continue execute task
			$start = $start+$num;
		} else {
			// for error
			die("this is an error occured(errCode: {$result}).");
		}

		// check the querystring
		$qstr = "?taskid={$taskid}&start={$start}&num={$num}";
		foreach($_GET as $key => $value) {
			if(in_array($key, array("taskid","start","num"))) {
				continue;
			}
			
			$qstr .= "&".$key."=".$value;
		}
		$url = $phpselfname.$qstr;
		unset($qstr);
		$this->push("redirect", $this->redirect($url));
	}
	
	function setTaskTitle($title) {
		$this->push("title", $title);
	}
	
	function updateCompleted($completed) {
		
		$this->push("completed", $completed);
	}
	
	function updateMessage($msg) {
		$this->push("currentaction", $msg);
	}
	
	function showProgress($p) {
		if($p >= 100) { $p = 100; }
		if($p < 0) { $p = 0; }
		$ProgressWidth = 300;
		$this->push("color", ($p > 60)?"#FFF":"#000");
		$this->push("progresswidth", $ProgressWidth);
		$this->push("currentwidth", ($ProgressWidth/100)*$p);
		$this->push("progress", sprintf("%.1f", $p));
	}
	
	function push($key, $val) {
		$this->tpl_vars_[$key] = $val;
	}
	
	function& getVars() {
		return $this->tpl_vars_;
	}

	function redirect($url) {
		$buffer = <<<STR
<script>
		function redirect() {window.location.replace('{$url}');}
		setTimeout('redirect();', 2000);
		</script>\n
		<br><a href="{$url}">如果您的浏览器没有自动跳转，请点击这里</a>
STR;
		return $buffer;
	}
}