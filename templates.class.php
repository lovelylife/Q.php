<?php
/*--------------------------------------------------------------------
 $ Tempaltes Parse Engineer 
 $ function to parse the html template to useful stream
 $ created: 2009-6-2 by LovelyLife
 $ lastModified: 2013-03-16 1:40:55
 $ powered by http://qlibs.com

 $ examples
 vars   
  {$globals:varname}, {$app:resname}, {$var:name}
 command 
  {#template templatename}, {#dict src="ddd.xml"}
 htmltag 
  <html:labelname attrs=".."...>innerContent</html:labelname>
  
  
 $logs
 1. support {$cfg:cfgname/} tag to read cfg data;
 2. support {$get|$post:querystring/} tag to read form data 2010-6-4 17:22
 3. add [setDir()] and [setCDir()] public method which is set Template Dir and CacheDir
 4. support {$app:name/} tag to read app enviroment ;  
 5. 修改render接口
 6. 增加parse接口，文件模板和字符串模板解析分render和parse两种，
    render是将模板标签解析成php的标签输出，如果要截获输出使用ob_start;
    parse是将模板标签替换成相应的值，返回解析之后的html输出流。使用echo
    直接打印。
 7.增加模板参数 tvars, 对应于{#template}标签属性名称
----------------------------------------------------------------------*/

class CLASS_TEMPLATES
{
  protected $theApp;
  // 模板路径
  private $tpl_dir_;
  // 模板缓冲目录
  private $tpl_cache_dir_;    
  // 字典
  private $dict;
  // 命名数据缓存
  private $data_cache_;
  // 预处理模板变量缓冲    
  private $vars_cache_;    
  // 模板文件
  private $tpl_file_;
  // 缓存文件
  private $tpl_complied_file_;
  // 标签缓存文件
  private $tpl_tags_file_;
  // 标签管理器[id] => node
  private $tags;
  private $tvars_;
  // 构造函数
  function __construct($theApp) {    
    $this->vars_cache_ = array();
    $this->data_cache_ = array();
    $this->tpl_file_ = null;
    $this->tags = array();
    $this->tvars_ = array();
    $this->theApp = $theApp;
    $this->tpl_dir_  = $theApp->getTemplatesDir();  
    $this->tpl_cache_dir_ = $theApp->getCacheDir();

    if(!file_exists($this->tpl_dir_))
      createfolders($this->tpl_dir_);
    if(!file_exists($this->tpl_cache_dir_)) 
      createfolders($this->tpl_cache_dir_);
        
    $this->dict = array();
    $theApp->add_dictionary($this);
  }
  
  function CLASS_TEMPLATES($theApp) { $this->__construct($theApp); }
  
  function load($view) {
    // 解析成php的标签，直接使用变量值 
    $phplabel = true;
    $this->tpl_cache_file_ = $this->tpl_cache_dir_.'/'.str_replace('/', '-', $view).'.php';
    $this->tpl_tags_file_ = $this->tpl_cache_file_.".tags.php";
    $need_complie = !file_exists($this->tpl_cache_file_);

    // 生成缓存文件
    if($need_complie || isdebug()) {
      // 加载模板
      $template_html = $this->load_template($view);
      if(!empty($template_html)) {
        // 编译模板内容
        $template_html = $this->compile($template_html, $phplabel);
      }
      
      // 序列化Tags, 保存早模板对应的.tags.php文件
      $cfg_file = new CLASS_CONFIG_FILE($this->tpl_tags_file_);
      $config = $cfg_file->config();
      
      foreach($this->tags as $name => $ui_object) 
        $config[$name] = $ui_object->serialize();
      // 保存tags
      $cfg_file->save();

      // 写入到缓存（/cache）目录
      $f = fopen($this->tpl_cache_file_, 'w');
      fwrite($f, $template_html, strlen($template_html));
      fclose($f);
    }

    // 初始化标签
    //@require
    $array = require_once($this->tpl_tags_file_);
    foreach($array as $key => $tag_config) {
      $ui_object = $this->create_object($tag_config['nodeName_']);
      $ui_object->unserialize($tag_config);
      $this->tags[$key] = $ui_object;
    }
  }

  function display() { $this->_render($this->tpl_cache_file_);  }

  // 渲染视图模式下, {$xxx:yyy}不能在< php ? >代码里面使用，
  //如果需要则使用parse
  function render($view) {
    $this->load($view);
    $this->display();
  }

  // 模板解析，输出解析后的数据流, 
  function parse($view) {
    // 不解析成php的标签，直接使用变量值
    $phplabel = false;
    $from_cache = false;
    $template_html = $this->load_template($view, $from_cache);
    if(!empty($template_html)) {
      // 编译模板内容
      $template_html = $this->compile($template_html, $phplabel);
    }

    return $template_html;
  }

  function render_string($html) {
    // 缓存文件
    $c = 0;
    $cache_file ='';
    do 
    {
      $cache_file = $this->tpl_cache_dir_.'/tmp.'.$c.'.php';
      $c++;
    } while(file_exists($cache_file));

    // 编译模板内容
    $template_html = $this->compile($template_html);

    // 写入到缓存（/cache）目录
    $f = fopen($cache_file, 'w');
    fwrite($f, $template_html, strlen($template_html));
    fclose($f);

    if($this->_render($cache_file)) {
      // 删除临时文件
      unlink($cache_file);
      return true;
    }
    return false;
  }

  function parse_string($template_html) {
    return $this->compile($template_html, false);
  }

  function _render($php_file) {
    // 导入应用程序和全局变量到当前符号表
    $theApp = $this->theApp;
    extract($this->theApp->getRefAPPS(), EXTR_PREFIX_ALL, 'app');
    extract($this->theApp->getRefCONFIG(), EXTR_PREFIX_ALL, 'cfg');
    extract($this->vars_cache_, EXTR_PREFIX_ALL, 'vars');
    extract($this->tags, EXTR_PREFIX_ALL, 'html_tags');
    extract($_GET, EXTR_PREFIX_ALL, 'get');
    extract($_POST, EXTR_PREFIX_ALL, 'post');
    require($php_file);
  }

  function load_template($view) {
    $this->tpl_file_ = $this->tpl_dir_.'/'.$view.'.htm';
    // 输出html流
    return $this->readtemplate( $view );
  }

  // 编译模板，根据$phplable参数生成两种类型的缓存文件
  // $replace为false时，生成的模板中的标签被替换变量的值
  // $replace为true时，生成模板中的标签被替换成php的标
  // 签"< ?=prefix_name? >", 默认使用phplabel
  function compile($content, $phplabel=true) {
    //echo $content;
    // 解析字典dic等导入标签
    $content = preg_replace_callback(
      '/\s*\{#(dict)\s+([^\}]+?)\s*\/?\}\s*/is', 
      array($this, 'import2'), 
      $content
    );

    //echo $content;
    // 解析html:标签 <html:lablename attrs='value'>tpl</html:label>
    $content = preg_replace_callback(
      '/<(html):(\w+).+?<\/\1:\2>/s',  //use:'/<(html|cms):(\w+).+?<\/\1:\2>/is',
      array($this, $phplabel?'complie_html_tag':'complie_html_tag_value'),
      $content
    );
    //$content = $this->complie_php_vars($content);
    $content = preg_replace_callback(
      '/\{\$+(vars|cfg|get|post|app):(\w+)((\.\w+)*)(\s+\w+="[^"]*")*\s*\/?\}/is', 
      array($this, $phplabel?'compile_vars':'compile_values'),
      $content
    );

    return $content;
  }

  function complie_php_vars($content) {
    return preg_replace_callback(
        '/\{\$+(vars|cfg|get|post|app):(\w+)((\.\w+)*)(\s+\w+="[^"]*")*\s*\/?\}/is', 
        array($this, 'compile_values'),
        $content
    );
  }

  function compile_vars($matches) {
    // 检测转义符号$, $$=>$, $$$=>$$
    if(preg_match('/\{\${2,}/', $matches[0])) {
      // print_r($matches);
      return "{".substr($matches[0], 2, strlen($matches[0]));
    }

    // 标签变量类型
    $vartype = $matches[1];

    // 获取变量值, 处理子值
    $varname = $matches[2];
    $subvars = $matches[3];

    // $expression 输出表达式
    $expression = $vartype.'_'.$varname;
    //! 解析属性
    $attrs = CLASS_DTL::parse_attrs($matches[0]);
    if($subvars != '') {
      $sTemp = '';
      $arr = preg_split('/\./', $subvars);
      $len = count($arr);
      for($i=1; $i<$len; $i++) {
        $sTemp .= '[\''.$arr[$i].'\']';
      }

      $expression .= $sTemp;
    }

    // 调用函数
    if(isset($attrs["func"])) {
      $expression = str_ireplace('@this', '$'.$expression, $attrs['func']);
    } else {
      $expression = '$'.$expression;
    }

    //echo $expression;
    return '<?='.$expression.'?>';
  }

  function compile_values($matches) {
    // 检测转义符号$, $$=>$, $$$=>$$    
    if(preg_match('/\{\${2,}/', $matches[0])) {    
      // print_r($matches);    
      return "{".substr($matches[0], 2, strlen($matches[0]));    
    }    
    
    // 标签变量类型    
    $vartype = $matches[1];    
    if(!method_exists($this, $vartype)) {    
    // die("{$vartype}dssdsdsdsdsdsd");
      return '';    
    }    
    
    // 获取变量值, 处理子值    
    $varname = $matches[2];    
    $subvars = $matches[3];
    $value = $this->$vartype($varname);    
    if($subvars != '') {   
      $subvars = preg_split('/\./', $subvars);
      $len = count($subvars);    
      //print_r($subvars);    
      for($i=1; $i<$len; $i++) {    
        $key = $subvars[$i];    
        if(is_array($value) && key_exists($key, $value)) {    
          $value = $value[$key];    
        } else {    
          break;    
        }    
      }    
    }    

    //! 解析属性    
    $attrs = CLASS_DTL::parse_attrs($matches[0]);    
    
    if(empty($attrs)) {    
      return $value;    
    }    
    
    // 字典映射    
    if(isset($attrs["dict"])) {    
      if(dicQuery($attrs["dict"], $dict)) {    
        $value = $dict[$value];    
      }    
    }    
    
    // 调用函数    
    if(isset($attrs["func"])) { 
      //$func = str_ireplace("@this", "'$value'", $attrs['func']);
      $func = str_ireplace("@this", addslashes( $value ), stripcslashes( $attrs["func"]));
      if(isdebug()) {    
        eval("\$value = $func;");    
      } else {    
        @eval("\$value = $func;");    
      }    
    }    
    
    if(empty($value) && !empty($attrs['default'])) {    
      $value = $attrs['default'];    
    }    
    
    return $value;    
  }    

    // 导入模板
  function readtemplate( $tplname ) 
  {
    $tplDir = $this->tpl_dir_;                              
    
    /** 解析模板文件名, 暂时不支持模板传参数 {vars:参数名称} */
    $params = parse_url($tplname);
    $tplname = $params["path"];                      
    
    /** 模板文件全路径 */
    $tplFile = $tplDir.'/'.$tplname;

    /** 如果文件不存在则搜索带.htm后缀的 */
    if(!file_exists($tplFile)) {
      $tplFile = $tplFile.'.htm';
      if(!file_exists($tplFile)) {
        trigger_error("template ({$tplFile}) is not exists.", E_USER_ERROR);
      }
    }
      
    // 获取文件内容
    $tpldata = file_get_contents($tplFile);
    /** 预处理模板标签变量传递 {$tvars:name} 
     *
     */
    $tpldata = preg_replace_callback(
       '/\{\$tvars:(\w+)\s*\/?\s*\}/is',
       array($this, 'tvars'), 
       $tpldata
    );

    // 预编译处理 - 解析资源路径变量和全局变量（固定不变的）
    // 暂不解析
    // 解析{#template|include src="tplname"/} 子模板
    $tpldata = preg_replace_callback(
       '/\{#template\s+([^\}]+?)\s*\/?\}/is',
       array($this, 'readtemplate2'), 
       $tpldata
    );
    
    $tpldata = preg_replace_callback(
       '/<\!--include\s+([^>]+?)\s*-->/is',
       array($this, 'readtemplate2'), 
       $tpldata
    );
    
    return $tpldata;
  }

  function readtemplate2($matches) {
    $attrs = CLASS_DTL::parse_attrs($matches[0]);
    //print_r( $matches );
    if( !empty( $attrs ) ) {
      $this->tvars_ = array_merge( $this->tvars_, $attrs );
    }
    if( isset( $attrs["src"] ) ) {
      return $this->readtemplate( $attrs["src"] );
    } else {
      return $this->readtemplate( $matches[1] );
    }
  }

  function tvars($matches) {
    return $this->tvars_[$matches[1]]; 
  }
      
  public function setDir($newdir) {
    $this->tpl_dir_ = $newdir;
  }

  public function setCDir($newdir) {
    $this->tpl_cache_dir_ = $newdir;
  }
 
  // 处理模板指令
  function import2($matches) {
    // 必须返回空
    return $this->import($matches[1], $matches[2]);
  }
 
  // 处理模板指令
  function import($func, $attrstr) {
    $attrs = CLASS_DTL::parse_attrs(' '.$attrstr);
    $this->$func($attrs);
    // 必须返回空
    return '';
  }

  function complie_html_tag($matches) {
    // $namespace = $matches[1];
    $tagName = $matches[2];
    $tag = $matches[0];
    $tag_object =  $this->create_object($tagName);
    if( !$tag_object ) {
      $this->error("create tag failed. ".$tag );
    }
    if( $tag_object )
      $tag_object->parse($tag);

    // 创建标签对象
    $id = $tag_object->getAttribute('id');
    if(empty($id)) 
      $this->error(htmlspecialchars("must have an id attribute. \n{$tag}"));
    $this->tags[$id] = $tag_object;
    
    // 返回标签id
    return '<'.'?=$html_tags_'.$id.'?'.'>';
  }
   
  function complie_html_tag_value($matches) {
    $tagName = $matches[2];
    $tag = $matches[0];
    $tag_object =  $this->create_object($tagName);
    $tag_object->parse($tag);
    
    return $tag_object->__toString();
  }

  // dict指令
  function dict($attrs) {
    $type = strtolower($attrs["type"]);
    if($type == "database") {
        // 加载数据库字典
      $this->load_dicFromDatabase($attrs);
    } else {
      // 加载xml字典
      $dict_file = $this->theApp->getDataDir()
                   ."/".$attrs['src'].".php";
      if(file_exists($dict_file)) {
        $arr = require_once($dict_file);
        if(is_array($arr) && !empty($arr)) {
          $this->dict = array_merge($this->dict, $arr);
        }
      } else {
          trigger_error('file['.$dict_file.'] not founded!', E_USER_ERROR);
      }
    }
  }

    // 加载数据库字典表
  function load_dicFromDatabase($attrs) {
    if(!is_array($attrs)) { return; }
    if(!isset($attrs["name"])) { return; }
    if(!isset($attrs["table"])) { return; }
    $id = $attrs["name"];
    $text = $attrs["text"];
    $value = $attrs["value"];
    $sql = "select {$value} value, {$text} text from ##__".$attrs["table"];
    $rs = array();
    if(!$this->theApp->db()->get_results($sql, $rs)) {
      trigger_error($this->theApp->db()->get_error(), E_USER_ERROR);
    }
    // 存入字典
    $this->dict[$id] = $rs;
  }
    
  // 字典查询，如果查到则把值输出到$value，并返回true
  function query_dictionary($dicName, &$value) {
    if((!key_exists($dicName, $this->dict)) || (!is_array($this->dict[$dicName])) ) 
      return false;
        
    $value = $this->dict[$dicName];
    return true;
  }

  // 获取指定id的标签
  function tag($id) { 
    return $this->tags[$id]; 
  }
  function app($varname)            { return $this->theApp->getAPPS( $varname );   }
  function user($varname)           { return $this->theApp->get_user_info( $varname ); }
  function vars($varname)           { return $this->query($varname); }  
  function cfg($name)               { return $this->theApp->getCONFIG($name);    } 
  function get($name)               { return $_GET[$name]; }    
  function post($name)              { return $_POST[$name];}


  function getApp()                 { return $this->theApp; }
  function push($key, $value)       { $this->vars_cache_[$key] = $value; }
  function push_tvars($key, $value) { $this->tvars_[$key] = $value; }
  function query($key) { return $this->vars_cache_[$key]; }

  function push_data($name, $data) {
    if(empty($name)) {
      trigger_error("name is empty", E_USER_ERROR);
    }
    if(isset($this->cache_data_[$name])) {
      trigger_error("name:{$name} is exists.", E_USER_ERROR);
    }
    $this->cache_data_[$name] = $data;
  }
  function get_data($name) {
    if(empty($name)) {
       trigger_error("name is empty", E_USER_ERROR);
    } 
    
    if(array_key_exists($name, $this->cache_data_)) { 
      return $this->cache_data_[$name]; 
    }
    
    return array();
  }

  function dump2template($record) { foreach($record as $key => $value) { $this->push($key, $value); } }
    
  // 创建标签缓冲
  function create_object($tag_name) {
    $instance = null;
    // 标签名称 类名称
    $tagName = strtolower($tag_name);
    $className = "CLASS_DTL_".strtoupper($tagName);

    // 先从APP的UI目录查找，如果没有再到框架目录查找
    $includefile = $this->theApp->getAppRoot()."/ui/html.{$tagName}.class.php";
    
    $bfoundfile = file_exists($includefile);
    if(!$bfoundfile) {
      $includefile = _QROOT."/ui/html.{$tagName}.class.php";
      $bfoundfile = (file_exists($includefile));
    }
    
    if($bfoundfile) {      
      require_once($includefile);      
      if(class_exists($className)) {
        $instance = new $className();
        $instance->setTemplate($this);
      } else {
        $this->error("class({$className}) is not founded!");
      }    
    } else {
      
    }
    
    return $instance;
  }

  function error($message) {
    trigger_error(
      "template error: {$message}.\nin template: ".$this->tpl_file_, 
      E_USER_ERROR);
  }
}

?>
