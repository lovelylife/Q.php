<?php
/*--------------------------------------------------------------------
 $ file: application.php
 $ date: 2010-8-22 00:24:26
 $ last modify: 2012-07-23 12:59:40
 $ author: LovelyLife 
 $ 应用程序框架
 ----------------------------------------------------------------------*/

// 应用程序基类
class CLASS_APPLICATION {
  // application 环境
  private $root_;
  private $path_;
  private $host_;

  // 模块操作
  protected $module_;
  protected $action_;
  protected $inajax_;

  // 数据库集合
  private $databases_;
    
  // 路径
  private $log_dir_;
  private $template_dir_;
  private $cache_dir_;
  private $data_dir_;
  private $path_themes_;
  private $theme_;
        
  // 模板里的变量名称映射
  private $_refCONFIG;
  private $_refAPPS;
  private $_refDICT;

  function __construct() {
    //! 初始化应用程序环境
    $this->_refAPPS = array();
    $this->_refDICT = array();
    $this->databases_ = array();
  }

  function CLASS_APPLICATION($args = array()) { $this->__construct($args); }
 
  function initialize( $args ) {
    $this->root_ = $args['root'];
    $this->path_ = $args['path'];
    $this->host_ = $args['host'];
    $this->module_ = $args['module'];  
    $this->action_ = $args['action'];  
    $this->inajax_ = $args['inajax'];

    // 注册模板变量 
    $this->register( 'path', $this->path_ );
    $this->register( 'app', $this->host_.$this->path_ );
    $this->register( 'module', $this->_refAPPS['app'] .'?'. 'mod='. $this->module_ );
    $this->register( 'host', $this->host_ );

    // 导入配置数据
    $this->_refCONFIG = require($this->root_.'/config.php');
    
    // url rewrite
    $url_rewrite = $this->Config("site.url_rewrite");
    
    //print_r($url_rewrite);
    if(is_array($url_rewrite)) {
      if(isset($url_rewrite[$this->module_])) {
        $this->register( 'module', $this->_refAPPS['app'].$url_rewrite[$this->module_] );
      }
    }

    // 初始化资源和主题路径
    $paths = $args['settings'];
    $this->cache_dir_    = $this->root_.get_default_value($paths['cache_dir'], '/cache');
    $this->data_dir_     = $this->root_.get_default_value($paths['data_dir'], '/data');
    $this->theme_        = get_default_value($paths['theme_current'], '/default');
    $this->theme_dir_    = get_default_value($paths['theme_dir'], '/themes');
    $this->theme_path_   = $this->theme_dir_.$this->theme_; 
    $this->template_dir_ = $this->root_.get_default_value($paths['templates'], '/templates').$this->theme_;

    // 注册app模板变量
    $this->register( 'theme', $this->theme_path_ );

    // 导入包含文件
    $this->requireFiles($this->root_.'/includes.required.php');
  }

  // appMain入口函数
  function appMain($args) {
    // 初始化环境
    $this->initialize($args);

    // 初始化默认模块
    $default_module_file = $this->getAppRoot().'/modules/default.class.php';
    $module_file = $this->getAppRoot().'/modules/'.$this->module_.'.class.php';
    if(!file_exists($module_file))  
      trigger_error('module ['.$this->module_.'] not exists.', E_USER_ERROR);
    
    // 访问控制
    if(!$this->access_control($this->module_, $this->action_, $handlered)) {
      trigger_error("have no authority", E_USER_ERROR);
    }
    // 模块文件
    include($module_file);
    
    // 加载模块
    $class = 'CLASS_MODULE_'.strtoupper($this->module_);
    if(class_exists($class)) {
      $module = new $class;
      if($this->inAjax()) {
        $module->__doAjax($this, $this->action_);
      } else {
        $module->__doMain($this, $this->action_);
      }
    } else {
      trigger_error($class. ' is not found.');
    }  
  }

  function access_control($module, $action, &$handlered) {
    return true;
  }

  //! operations
  function getAppRoot()      { return $this->root_; }
  function getAppPath()      { return $this->path_; }
  function getUrlApp()       { return $this->_refAPPS['app'];}
  function getUrlModule()    { return $this->_refAPPS['module'];}
  function getAppLogDir()    { return $this->log_dir_; }
  function getTemplatesDir() { return $this->template_dir_; }
  function getDataDir()      { return $this->data_dir_; }
  function getCacheDir()     { return $this->cache_dir_; }
  function getTheme()        { return $this->theme_; }
  function inAjax()          { return $this->inajax_; }

  function& getCONFIG($segName=null) {
    if(empty($segName)) {
      return $this->_refCONFIG;
    } else {
      return $this->_refCONFIG[$segName];
    }
  }
 
  // deliver char is '.', for example: dbs.default.host
  function Config($cfg_name) {
    $subvars = str_split('\.', $cfg_name);
    $len = count($subvars);
    $value = $this->_refCONFIG[$subvars[0]];
    for($i=1; $i<$len; $i++) {
      $key = $subvars[$i];
      if(is_array($value) && array_key_exists($key, $value)) {
        $value = $value[$key];
      } else {
        break;
      }
    }
    return $value;
  }
    
    
  // 支持多数据库，根据名称访问数据对象,默认访问default数据库
  function db($name='default') {
    $db = null;

    // 检测数据库实例是否存在
    if(is_array($this->databases_) 
      && array_key_exists($name, $this->databases_))
    {
      $db = &$this->databases_[$name];
      if( $db != null)
        return $db;
    }

    $dbs = &$this->_refCONFIG['dbs'];
    if(is_array($dbs) && array_key_exists($name, $dbs)) {
      // 创建数据库实例
      $db = $this->create_database($dbs[$name]);
    }

    return $db;
  }

  function add_dictionary(&$dict) { $this->_refDICT[] = $dict; }

  function query_dictionary($name, &$dic) {
    foreach($this->_refDICT as $tpl) {
      if($tpl->query_dictionary($name, $dic)) {
        return true;
      }
    }
        
    return false;
  }

  function create_database($dbcfgs) {
    // print_r($dbcfgs);
    //'type' => 'mysql',
    //'host' => '',
    //'user' => '',
    //'pwd' => '',
    //'dbname' => '',
    //'lang' => '',
    //'prefix' => 'ch_',
    return createdb($dbcfgs['type'], $dbcfgs);
  }

  private function requireFiles($cfgfile) {
    if(!file_exists($cfgfile)) {
      if(isdebug()) {
        die('file['.$cfgfile.'] not founded.');
      }
    }
    
    $require_files = require($cfgfile);
    if(is_array($require_files)) {
      foreach($require_files as $file) {
        if(file_exists($file)) {
          require($file);
        } else {
          require($this->getAppRoot().$file);
        }
      }
    }
  }
    
  function register( $name, $value ) {
    if( !empty( $name ) ) {
      $this->_refAPPS[ $name ] = $value;
    }
  }

  function unregister( $name ) {
    if( !empty( $name ) ) {
      unset( $this->_refAPPS[ $name ] );
    }
  }

  function getAPPS($name)   { return $this->_refAPPS[$name]; }
  function getTHEMES($name) { return $this->_refTHEMES[$name]; }
  function getRefAPPS()     { return $this->_refAPPS; }
  function getRefTHEMES()   { return $this->_refTHEMES; }
  function getRefCONFIG()   { return $this->_refCONFIG; }
}

?>
