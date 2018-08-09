<?php
/*----------------------------------------------------
 $ 名称: isdebug
 $ 功能: 检测当前是否为调试模式
 $ 参数: 无
 $ 返回值: 调试返回true 否则为false
------------------------------------------------------*/
function isdebug() { return _QDEBUG; }

// 用于打印Q.PHP环境变量，不包括php的环境变量
function qinfo() {
  $dumpstr = "\r\n<pre>\n<h4>Q.PHP enviroments</h4>";
  $dumpstr .= "_QDOCUMENT_ROOT:\t\t\t"._QDOCUMENT_ROOT;
  $dumpstr .= "\r\n_QROOT:\t\t\t\t"._QROOT;
  $dumpstr .= "\r\n</pre>\r\n";

  echo $dumpstr;
}

function qruntime( ) {
  echo microtime()-$GLOBALS['_start']."ms\n";
}

/*----------------------------------------------------
 $ 名称: daddslashes
 $ 功能: 添加单引号转义字符的‘' -> \'
 $ 参数:
     $str      预处理的字符串
     $force    强制转换
 $ 返回值: 处理结果
------------------------------------------------------*/
function daddslashes($str, $force = false) {
  // 检查MAGIC_QUOTES_GPC宏是否定义
  if(!defined('MAGIC_QUOTES_GPC')) {
    define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
  }

  if(!MAGIC_QUOTES_GPC || $force) {
    if(is_array($str)) {
      foreach($str as $key => $val) {
        $str[$key] = daddslashes($val, $force);
      }
    } else {
      $str = addslashes($str);
    }
  }
  return $str;
}

function get_path($dir) {
  return str_ireplace(_QDOCUMENT_ROOT, "", $dir);
}

/*----------------------------------------------------
 $ 检测链接是否是SSL连接
 $ @return bool
------------------------------------------------------*/
function is_ssl()
{
  if(!isset($_SERVER['HTTPS']))
    return false;
  if($_SERVER['HTTPS'] === 1) {  //Apache
    return true;
  } elseif($_SERVER['HTTPS'] === 'on') { //IIS
    return true;
  } elseif($_SERVER['SERVER_PORT'] == 443) { //其他
    return true;
  }
  
  return false;
}

if(!function_exists('str_split')) {
  function str_split($str) {
    return preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
  }
}

function rndstr($len) {
  // 生成字母表
  $chars = str_split("abcdef1234567890");
  //$chars = str_split("ABCDEFGHIJKLMNOPQRSTUVWXYZ");
  // 打乱字符顺序
  shuffle($chars);
  
  // 字母表长度
  $charlen = count($chars);
  
  // 生成随机字符串
  $s = "";
  for($i=0; $i < $len; $i++) {
    $s .= $chars[rand(0,$charlen)];
  }
  return $s;
}

function error($errmsg, $isdie=false) {
  if($isdie) {
    die($errmsg);
  } else {
    printf('<br>%s<br>', $errmsg);
  }
  
}

// 获得文件扩展名
function getfileext($filename) {
  $retval="";
  $pt=strrpos($filename, ".");
  if ($pt) $retval=substr($filename, $pt+1, strlen($filename) - $pt);
  return ($retval);
}

function createIndex($dir) {
  if(is_dir($dir) && !empty($dir)) {
    $indexfile = fopen($dir.'/index.htm', 'w+');
    if($indexfile) {
    fwrite($indexfile, '403 access denied.');
    fclose($indexfile);
    } else {
      //trigger_error('create dir('.$dir.') index file failed.', E_USER_ERROR);
    }
  }
}

// 创建文件夹，如果不存在，则创建
function createfolders($dir, $createindex=true){
  $bSuccess = (is_dir($dir) or (createfolders(dirname($dir), $createindex) and mkdir($dir, 0777)));
  if($bSuccess&&$createindex) {
    createIndex($dir);
  }
  return $bSuccess;
}


function convertSizeName($tsize, $tname = 0) {
    $tnames = array("B", "KB", "MB", "GB", "TB");
    $tsize = floatval($tsize);
    $tname = 0;
    while($tsize >= 1024) {
        $tsize = $tsize/1024;
        $tname++;
    }
    return sprintf("%.1f %s &nbsp;",$tsize,$tnames[$tname]);
}


// 查询字典
function dicQuery($name, &$dic) {
    return CLASS_APPLICATION::theApp()->dicQuery($name, $dic);
}

// 写日志文件
function writelog($str) {
  global $_APP,$_PHPKIND_ENVS;

  $logfile = _KIND_INCLUDE.$_PHPKIND_ENVS['log'];

  //echo $APP_NAME;
  if(!empty($_APP['NAME'])) {
    $logfile .= '/'.$_APP['NAME'];
  }

  if(!is_dir($logfile)) {
    if(!createfolders($logfile)) {
      die('you have no pemessioned.');
    }
  }

  $logfile .= '/'.strftime('%Y%m%d', time()).'.php';
  $preHeader = '';
  $bWriteHeader = (!file_exists($logfile));
  $f = fopen($logfile, 'a');
  if($f)
  {
    if($bWriteHeader) {
      fwrite($f, "<? die(\"access denied.\"); ?>\n\n");
    }
    
    if(!is_string($str))
    {
      $str = var_export($str, true);
    }

    $s = sprintf("\r\nmodule:%s\r\ntime: %s\r\nerror: %s\r\n*****************************************************************\r\n\r\n", 
      $_APP['NAME'], 
      strftime("%Y-%m-%d %H:%M:%S", time()), 
      $str
    );
    fwrite($f, stripslashes($s));
    fclose($f);
  } else {
    die('access denied. you have no pemession.');
  }
}

// 获得客户端IP
function getip() {
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if (isset($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR'];
    else $ip = "UNKNOWN";
    return $ip;
}

// 将UTF-8转换成GBK
function latin2utf($str) {
    return iconv("latin1", "gbk//IGNORE", $str);
}

// 将UTF-8转换成GBK
function utf2gbk($str) {
    return iconv("UTF-8", "gb2312//IGNORE", $str);    
}

// 将GBK转换成UTF-8
function gbk2utf($str) {
    return iconv("gbk", "UTF-8", $str);    
}

function getserialid($len) {
    
    // 生成字符串的长度
    // 生成字母表
    $chars = str_split("abcdef1234567890");
    //$chars = str_split("ABCDEFGHIJKLMNOPQRSTUVWXYZ");
    // 打乱字符顺序
    shuffle($chars);
    
    // 字母表长度
    $charlen = count($chars);
    
    // 生成随机字符串
    $s = "";
    for($i=0; $i < $len; $i++) {
        $s .= $chars[rand(0,$charlen)];
    }
    return $s;
}

function getfilename(){
    // 生成字符串的长度
    $len = 32;
    
    // 生成字母表
    //$chars = str_split("AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz1234567890");
    $chars = str_split("abcdef0123456789");
    // 打乱字符顺序
    shuffle($chars);
    
    // 字母表长度
    $charlen = count($chars);
    
    // 生成随机字符串
    $s = "";
    for($i=0; $i < $len; $i++) {
        if($i != 0 && $i%8 == 0) {
            $s .= "-";
        } else {
            $s .= $chars[rand(0,$charlen)];
        }
    }
    return $s;
}

// 格式化时间
function fmtdatetime($timestamp) {
  $timestamp = intval($timestamp,10);
  if($timestamp == 0) {
    return "";      
  }

  return strftime("%Y-%m-%d %H:%M:%S", $timestamp);
}

// 格式化时间
function fmtdatetime2cn($timestamp) {
    $timestamp = intval($timestamp);
    if($timestamp == 0) {
        return "";
    }
    return strftime("%Y年%m月%d日 %H:%M:%S", $timestamp);
}

function get_default_value($new_value, $default_value) 
{ 
  return empty($new_value) ? $default_value : $new_value; 
}

/** \brief Encrypt input data with key(32bit)
 *
 * \param input - Input data to be encrypted
 * \param key - Private key
 * \return iv+encrypted data with base64 encoded
 */
function q_encrypt($input, $key) 
{
    $id = $input;
    $data['iv']=base64_encode(substr('fdakinel;injajdji',0,16));
    $data['value']=openssl_encrypt($id, 'AES-256-CBC',$key,0,base64_decode($data['iv']));
    $encrypt=base64_encode(json_encode($data));
    return $encrypt;
/*
  // Padding
  $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
  $input = q_pkcs5_pad($input, $size);
  // Create IV
  $iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB );
  $iv = mcrypt_create_iv( $iv_size, MCRYPT_RAND );
  // Encrypt data 
  $data = mcrypt_encrypt( MCRYPT_RIJNDAEL_128, $key, $input, MCRYPT_MODE_ECB, $iv); 
  // Base64 encode
  $data = base64_encode($iv.$data);

  return $data;
  */
}
 
function q_pkcs5_pad($text, $blocksize) {
  $pad = $blocksize - (strlen($text) % $blocksize);
  return $text . str_repeat(chr($pad), $pad);
}


/** \brief Decode the input with $key(32 chars)
 *
 * \param input - Encoded data
 * \param key - Private key(32 chars) to decode input
 * \return Decode data
 */
function q_decrypt($input, $key) 
{
  $encrypt = json_decode(base64_decode($input), true);
  $iv = base64_decode($encrypt['iv']);
  $decrypt = openssl_decrypt($encrypt['value'], 'AES-256-CBC', $key, 0, $iv);
  return $decrypt;

/*
  // Base64 decode
  $input = base64_decode( $input );

  // Get iv from input
  $iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB );
  $iv = substr( $input, 0, $iv_size );
  
  // Get Encode data from input
  $text = substr( $input, $iv_size );
 
  // Decode text
  $decrypted= mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $text, MCRYPT_MODE_ECB, $iv);
	
  // Trim 
  $dec_s = strlen($decrypted);
	$padding = ord($decrypted[$dec_s-1]);
	$decrypted = substr($decrypted, 0, -$padding);

  return $decrypted;
  */
}	

function q_inject_check($sql_str) 
{ 
    return preg_match('/select|insert|and|or|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile/i', $sql_str);
}

function qsubstr( $str, $start, $len, $ellipse ) {
  $substr = mb_substr( $str, 0, $len, "UTF-8");
  if( $ellipse ) {
    if( mb_strlen( $str ) > $len ) {
       return $substr."...";
    }
  } 
   
  return $substr;
}

?>
