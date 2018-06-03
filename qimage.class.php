<?php
 
/*
* File: QImage.class.php
* Author: Q
* Copyright: 2013 wayixia
* Date: 2013/5/2
* Link: http://www.wayixia.com/
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details:
* http://www.gnu.org/licenses/gpl.html
*
*/
 
class QImage {

  var $image;
  var $image_type;
 
  function load($filename) { 
    $image_info = getimagesize($filename);
    $this->image_type = $image_info[2];
    if( $this->image_type == IMAGETYPE_JPEG ) {
      $this->image = imagecreatefromjpeg($filename);
    } elseif( $this->image_type == IMAGETYPE_GIF ) {
      $this->image = imagecreatefromgif($filename);
    } elseif( $this->image_type == IMAGETYPE_PNG ) {
      $this->image = imagecreatefrompng($filename);
    } elseif( $this->image_type == IMAGETYPE_BMP ) {
      $this->image = imagecreatefrombmp($filename);
    }
    return ($this->image);
  }

  function create($width, $height) {
    $this->destroy();    
    if($width <= 0 || $height <= 0) {
      return null;
    }
    $this->image = imagecreatetruecolor($width, $height);
  }

  function destroy() {
    if($this->image) {
      imagedestroy($this->image);
    }

    $this->image = null;
  }

  function fill($r=0xff, $g=0xff, $b=0xff) {
    $color = imagecolorallocate($this->image, $r, $g, $b);
    imagefill ( $this->image , 0 , 0 , $white );
  }

  function copyfrom($src_image, $dst_x, $dst_y, $src_x, $src_y, $src_width, $src_height) {
    return imagecopy($this->image, 
      $src_image->image, 
      $dst_x, $dst_y, 
      $src_x, $src_y, 
      $src_width, 
      $src_height);
  }

  function isvalid() {
    return !empty($this->image);
  }

  function type() {
    return $this->image_type;
  }
  
  function width() { 
    return imagesx($this->image);
  }

  function height() { 
    return imagesy($this->image);
  }

  function saveas($filename, $image_type=IMAGETYPE_JPEG, $compression=75) {
    if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) { 
         imagegif($this->image,$filename);
      } elseif( $image_type == IMAGETYPE_PNG ) { 
         imagepng($this->image,$filename);
      } elseif( $image_type == IMAGETYPE_BMP ) { 
         imagebmp($this->image,$filename);
      }
   }

   function save($filename) {
     if( $this->image_type == IMAGETYPE_JPEG ) {
       imagejpeg($this->image,$filename,$compression);
     } elseif( $this->image_type == IMAGETYPE_GIF ) { 
       imagegif($this->image,$filename);
     } elseif( $this->image_type == IMAGETYPE_PNG ) { 
       imagepng($this->image,$filename);
     } elseif( $this->image_type == IMAGETYPE_BMP ) { 
       imagebmp($this->image,$filename);
     }
   }

   function output($image_type=IMAGETYPE_JPEG) {
 
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image);
      } elseif( $image_type == IMAGETYPE_GIF ) {
          imagegif($this->image);
      } elseif( $image_type == IMAGETYPE_PNG ) {
          imagepng($this->image);
      } elseif( $image_type == IMAGETYPE_BMP ) {
        imagebmp($this->image);
      }
   }
/*
   function resizeToHeight($height) {
      $ratio = $height / $this->height();
      $width = $this->width() * $ratio;
      $this->resize($width,$height);
   }
 
   function resizeToWidth($width) {
      $ratio = $width / $this->width();
      $height = $this->height() * $ratio;
      $this->resize($width,$height);
   }
 
   function scale($scale) {
      $width = $this->width() * $scale/100;
      $height = $this->height() * $scale/100;
      $this->resize($width,$height);
   }
 */

  // attention: this method will create a new QImage instance
  function resize($width,$height) {
    $new_image = new QImage();
    $new_image->create($width, $height);
    imagecopyresampled($new_image->image, $this->image, 
      0, 0, 0, 0, $width, $height, $this->width(), $this->height());

    return $new_image;
  }
}

?>