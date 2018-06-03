<?php
/*--------------------------------------------------------------------
 $ file.lib.class.php
 $ files management
----------------------------------------------------------------------*/

define('FILE_FILTER_ALL',  0);
define('FILE_FILTER_DIR',  1);
define('FILE_FILTER_FILE', 2);


class CLASS_FILE {

    function __construct() {}
  
    function CLASS_FILE () {
        $this->__construct();  
    }
  
    static function mv($srcFile, $dstFile) {
        $srcFile = utf2gbk($srcFile);
  $dstFile = utf2gbk($dstFile);
  if(!file_exists($srcFile)) {
    print($srcFile." is not exists!\n");
    return false;
  }
  return rename($srcFile, $dstFile);
    }
  
    // 获取文件夹信息，可以根据指定的过滤器过滤相应的文件类型
    static function ls($path, $filter_type = FILE_FILTER_ALL, $ext = array()) {
        $result = array();
  $handle = opendir($path);
  $extlen = count($ext);
  if(!$handle) { return; }
  while($file = readdir($handle)){
            if($file=="."||$file=="..") { continue; }
            $newFilePath = $path.$file;  // 路径
            $tm = fmtdatetime(filemtime($newFilePath));  // 时间
            $name = gbk2utf($file);
            if(is_dir($newFilePath) && ($filter_type != FILE_FILTER_FILE)) {
                array_push($result, 
                    array("isfolder" => true, "path" => $newFilePath, "name" => $name, "filesize" => "", "lastupdated" => $tm));
            } else if(is_file($newFilePath) && ($filter_type != FILE_FILTER_DIR)) {
                $fext = getfileext($name);
                if( $extlen > 0) {
                    if(!in_array($fext, $ext)) { continue; }
                }
    $fsize = filesize($newFilePath);
    array_push($result, array("isfolder" => false, "path" => $path, "name" => $name, "filesize" => $fsize,"lastupdated" => $tm));
      }
        }
  closedir($handle);
  return $result;
    }


    static function cp($src, $des) {
        return copy($src, $des);
    }

}

?>
