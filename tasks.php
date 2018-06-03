<?php
/*--------------------------------------------------------------------
 $ module: task.php
 $ date:   2009-4-5 18:24:26
 $ author: LovelyLife 
----------------------------------------------------------------------*/

// 任务目录
$taskdir = $S_PATH['task'];
if(empty($taskdir)) {
	$taskdir = _DOCUMENT_ROOT.'/phpkind/cachedata/tasks';
}

// 如果任务目录没有则重新创建
if(!is_dir($taskdir)) {
	createfolders($taskdir);
}

// 创建默认索引页
if(!is_file($taskdir.'/index.htm')) {
	fclose(fopen($taskdir.'/index.htm', 'a'));
}


// print_r($S_PATH);

?>