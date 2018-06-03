<?php
/*--------------------------------------------------------------------
 $ memory cache libraries of CMS@Home System
 $ 2009-4-5 20:50:00
----------------------------------------------------------------------*/

//! query field
!defined('_FID_') && define('_FID_', 'fid');

class CLASS_LIB_MEMCACHE {

	function __construct() {}

	function CLASS_LIB_MEMCACHE() {}

	function t() {}

}


class CLASS_MEM_FILE {

	var  $fid;
	var  $filename;
	var  $session_fid;
	var  $bValid;
	function __construct() {
		global $S_PATH, $APP_PATH, $S_GET;
		$this->bValid = false;
		
		$path = _KIND_INCLUDE.$S_PATH['cache'].'/_tmp_cache_file_';
		$finished = $path.'finished_';
		$this->fid = $S_GET[_FID_];

		if(!empty($this->fid)) {
			$this->filename = $path.$this->fid.'.php';
			//! cache文件存在则不需要重新创建
			if(file_exists($this->filename)) {
				$this->bValid = true;
				return;
			}
		}

		$trytimes = 100;
		
		while($trytimes > 0) {
			$trytimes--;
			$id = time();
			$fname = $path.$id.'.php';
			//! 检测id是否存在
			if(!file_exists($fname)) {
				$this->fid = $id;
				$this->filename = $fname;
				// $_SESSION[$this] = $fid;
				break;
			}
		}

		$this->write_header();
		$this->bValid = true;
	}

	function CLASS_FILE_TASK() {
		$this->__construct();
	}

	function write_header() {
		$f = @fopen($this->filename, 'w+');
		if($f) {
			static $header_buf = "<?php die('access denied.'); ?>\n\n";
			fwrite($f, $header_buf, strlen($header_buf));
			fclose($f);
		} else {
			$this->bValid = false;
		}

		
	}

	//! 检测文件是否可用
	function is_valid() {
		return $this->bValid;
	}


	function read_content(&$outData) {
		$fp = @fopen($this->$filename, 'r');
		if(!$fp) {
			return false;
		}
		//! 去除前两行固定行
		fgets($fp, 92);
		fgets($fp, 10);

		while(!feof($fp)) {
				$outData .= fgets($fp, 1024);
		}
		fclose($fp);
		return true;
	}

	function write_content($content) {
		$fp = @fopen($this->$filename, 'w+');
		if(!$fp) {
			return false;
		}
		fwrite($content, strlen($content), $fp);
		fclose($fp);
		return true;
	}
/*
	function __destruct() {
		if(file_exists($this->filename)) {
			unlink($this->filename);
		}
	}
	*/
}

class CLASS_TASK_FILE extends CLASS_MEM_FILE {
	function __construct() {
		parent::__construct();
	}

	function CLASS_FILE_TASK() {
		
	}

	function unserialize() {
		echo "CLASS_TASK_FILE::unserialize";
		$resultArr = array();
		$data = '';
		$retCode = $this->read_content($data);
		
		if(!$retCode ) {
			echo "读取文件内容失败";
			return $resultArr;
		}
		
		$resultArr = unserialize($data);
		print_r($resultArr);

	}

	function serialize($arr) {
		$this->write_content(serialize($arr));
	}
}


?>