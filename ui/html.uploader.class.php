<?php
/*--------------------------------------------------------------------
 $ module: upload form controls library of CMS@Home System
 $ date:   2009-4-23 10:43:20
 $ author: LovelyLife
 
 属性：
 type:  上传文件类型  默认为附件类型， 允许的类型 image， file
 render 渲染器， 用来显示图片的img标签id
----------------------------------------------------------------------*/

class CLASS_DTL_UPLOADER extends CLASS_DTL {

  function __construct() {
    parent::__construct();
  }

  function CLASS_DTL_UPLOADER() { $this->__construct(); }
  
  function onheader() {

    $id = $this->getAttribute('id');
		if(!defined("_IsIncludeScript")) {
			define(_IsIncludeScript, 1);
			$this->html .= "<script>function _cms_upload(id) {\n";
			$this->html .= "alert(document.frames[\"iframe_\"+id].document.title);\n";
			$this->html .= "\n";
			$this->html .= "}</script>\n";
		}
		// width and height
		$width = $this->getAttribute("width") == "" ? "100%" : $this->getAttribute("width");
		$height = $this->getAttribute("height") == "" ? "27px" : $this->getAttribute("height");
		
        //! 上传类型
        if($this->getAttribute('type') != 'attach') {
            $type = 'image';
        }
        
        // 渲染器
		$render = $this->getAttribute("render");
		$id = "upload_".$this->getAttribute("id");
        $inputctrl =  $this->getAttribute("inputctrl");
        $uploaddir = urlencode($this->getAttribute("uploaddir")); 
        $resdir =  urlencode($this->getAttribute("resdir"));     
        //!应用程序名称
    $app = $this->getAttribute('app');
    $this->html .= "<iframe src=\""._IPATH."/s.php?c=uploader&type=".$type."&render={$render}&name={$target}&app={$app}&inputctrl={$inputctrl}&uploaddir={$uploaddir}&resdir={$resdir}\" id=\"iframe_{$id}\" style=\"width: {$width}; height:{$height};\" frameborder=\"no\" scrolling=\"no\"></iframe>";

    //$attrs = $this->contact_attrs( 
    //  array("type", "name","id","style","class","border","cellspacing","cellpadding")
    //);

	  return $this->html;
  }


  function onfooter() {
    return "";
  }

}

?>