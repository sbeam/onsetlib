<?php
/*
/* image_squeezer: a class to handle various image resizing paradigms using PHP/GD
 *
 *  Copyright 2005 Onset Corps. <sbeam@onsetcorps.net>
 *
 * LICENSE
 *
 * Copyright 2007 by Sam Beam
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */


/** Class image_squeezer
 * A OO wrapper for the PHP/GD image manip library with just focuses on the
 * logic needed to resize images, usually for thumbnails
 *
 * both resizing methods always keep image proportions. If image is smaller 
 * than given dimensions, it is not changed. 
 *
 * @example
 *          $squeezer = new image_squeezer($fullpath);
 *          // to_fit() will crop as needed (shortest dimension is fit)
 *          $squeezer->to_fit(MAX_W, MAX_H);
 *          $squeezer->save_to_file($fullpath);
 * // saves it to the same place, and will now fit within the bounds given
 *
 * imagesqueeze::to_size will keep proportions, but not crop (longest dimension is 
 * fit)
 *
 *
 *
*/
  

class image_squeezer extends PEAR {

    var $source_img = null;
    var $new_img = null;
    var $source_file;

    /**
     * find the file, get metrics on it, and create the GD object for it
     * @param str $file - FULL path to a image file (gif, jpeg, png)
     * @return true on succes or PEAR::Error
     */
    function __construct($file) {

        if (!is_file($file)) {
            if (!preg_match('/^\w+:\/\//', $file)) {
                return $this->raiseError("$file does not exist!");
            }
            else {
                $tmpnam = '/tmp/'. basename($file);
                if (!@copy($file, $tmpnam)) {
                    return $this->raiseError("could not access $file");
                }
                $file = $tmpnam;
            }
        }

        $this->file = $file;
        $this->source_file = $file;

        $this->imginfo = getimagesize($this->file);
        $memcheck = $this->_mem_check($this->imginfo);
        if (PEAR::isError($memcheck)) return $memcheck;

        $this->imgwidth = $this->imginfo[0];
        $this->imgheight = $this->imginfo[1];
        $this->filesize = filesize($this->file);

        $types_from_gis = array('gif', 'jpg', 'png', 'swf', 'psd', 'bmp', 'tiff', 'tiff');
        $this->extension = $types_from_gis[$this->imginfo[2]-1];

        if (isset($this->imginfo['mime'])) { // will be for PHP > 4.3.0
            $this->filetype = $this->imginfo['mime']; // override val from browser
        }
        else {
            $mimetypes = array('image/gif', 'image/jpeg', 'image/png', 
                                'application/x-shockwave-flash', 'image/psd', 'image/bmp', 
                                'image/tiff','image/tiff',);
            $this->filetype = $mimetypes[$this->imginfo[2]-1];
        }
        return true;
    }



    /** getters **/
    function get_img_width() {
        if (isset($this->imgwidth)) {
            return $this->imgwidth;
        }
    }

    function get_img_height() {
        if (isset($this->imgheight)) {
            return $this->imgheight;
        }
    }

    function get_img_dims() {
        if (isset($this->new_width) and isset($this->new_height)) {
            return sprintf('width="%d" height="%d"', $this->new_width, $this->new_height);
        }
        elseif (isset($this->imgheight) and isset($this->imgwidth)) {
            return sprintf('width="%d" height="%d"', $this->imgwidth, $this->imgheight);
        }
    }

    function get_filesize() {
        if (isset($this->filesize)) {
            return $this->filesize;
        }
    }


    function get_thumb_width() {
        if (isset($this->new_width)) {
            return $this->new_width;
        }
    }

    function get_thumb_height() {
        if (isset($this->new_height)) {
            return $this->new_height;
        }
    }
    function get_thumb_dims() {
        if (isset($this->new_height) and isset($this->new_width)) {
            return sprintf('width="%d" height="%d"', $this->new_width, $this->new_height);
        }
    }
    function get_thumbfilesize() {
        if (isset($this->new_filesize)) {
            return $this->new_filesize;
        }
    }


    /** writes the GB image object to a file
     * @param str $path full path to file to save to
     * @param str $fmt (gif/png/jpeg) - file type to create
     * @return str name of new file (w/out path */
    function save_to_file($path, $fmt=null) {

        if ($fmt) { // fixup the new filename to have the correct extension
            $new_name = preg_replace('/\.\w+$/', ".$fmt", basename($path));
            $dir = dirname($path);
            $path = $dir . '/' . $new_name;
        }
        else { // assume same as the orig
            $fmt = $this->extension;
            $new_name = basename($path);
        }

        if (empty($this->new_img)) {
            return false;
        }

        /* find the GD image function to call based on $fmt */
        switch ($fmt) {
            case 'jpeg':
            case 'jpg':
                $newimgfunc = 'imagejpeg';
                break;
            case 'gif':
                $newimgfunc = 'imagegif';
                break;
            default:
                $newimgfunc = 'imagepng';
        }

        if (!$res = $newimgfunc($this->new_img, $path)) {
            return $this->raiseError("could not call $newimgfunc(): $res");
        }
        else {
            $this->new_filesize = filesize($path);
            return $new_name;
        }
    }



    /**
     * create GD image object from the original file given in costructor
     * @private
     * @return GD image object
     */
    function get_source_img() {
        if ($this->source_img) {
            imagedestroy($this->source_img);
        }
        $this->source_img = null;
        switch ($this->filetype) {
            case 'image/png':
                $this->source_img = imagecreatefrompng($this->file);
                break;
            case 'image/gif':
                $this->source_img = imagecreatefromgif($this->file);
                break;
            case 'image/jpeg':
                $this->source_img = imagecreatefromjpeg($this->file);
                break;
        }
        if (!$this->source_img) {
            return $this->raiseError($this->filetype . ' can not be resized');
        }
        return true;
    }



    /**
     * wrapper to call the proper image processing method, because the original 
     * function names are rather confusing.
     *
     * @param $w int width
     * @param $h int height
     * @param $method str one of 'crop' or 'fit'
     * @return bool/PE
    */
    function resize($w, $h, $method='fit') {
        if ($method == 'crop') {
            return $this->to_size($w, $h);
        }
        elseif ($method == 'fit') {
            return $this->to_fit($w, $h);
        }
        else {
            trigger_error("unknown imagestretcher() method: $method", E_USER_WARNING);
        }
    }

    /**
     * create a new smaller image with the specific given dimensions. Image
     * will be resize proportionately the minimum amount needed to fit, and
     * areas in the original that do not fit in the new box will be cut off
     *
     * @todo right now this results in distortion unless $tw == $th
     *
     * @param new width
     * @param new height
     * @return true or PEAR::Error
     */
    function to_size($tw, $th) {

       $w = $this->get_img_width();
       $h = $this->get_img_height();

       if (!$w or !$h) {
           return $this->raiseError("could not find original image dimensions");
       }
       else {
           $source = $this->get_source_img();
           if (PEAR::isError($source)) return $source; 

           // the logic.
           if($h > $th || $w > $tw){
               if ($h > $w) {
                   $offset_y = (intval($h - $w) / 2);
                   $offset_x = 0;
                   $side = $w;
               }
               if ($w >= $h) {
                   $offset_x = intval(($w - $h) / 2);
                   $offset_y = 0;
                   $side = $h;
               }
           }
           else {
               return;
           }

           //create dst image
           if (!$this->new_img = imagecreatetruecolor($tw, $th)) {
               return $this->raiseError("could not create a new thumbnail image ($tw, $th)!");
           }

           $this->_preallocate_transparency($tw, $th);
           //
           //resize and copy image
           if (!imagecopyresampled($this->new_img, $this->source_img, 0,0,$offset_x,$offset_y,$tw,$th,$side,$side)) {
               return $this->raiseError("could not copy resized image");
           }

           $this->new_width = $tw;
           $this->new_height = $th;
           return true;
       }
    }



    /**
     * usually when people use PNGs, it's because they need alpha channel 
     * support (that means transparency kids). So here we jump through some 
     * hoops to create a big transparent rectangle which the resampled image 
     * will be copied on top of. This will prevent GD from using its default 
     * background, which is black, and almost never correct. Why GD doesn't do 
     * this automatically, is a good question.
     *
     * http://stackoverflow.com/questions/279236/how-do-i-resize-pngs-with-transparency-in-php/1655575
     *
     * @param $w int width of target image
     * @param $h int height of target image
     * @return void
     * @private
     */
    function _preallocate_transparency($w, $h) {
        if (!empty($this->filetype) && !empty($this->new_img) && $this->filetype == 'image/png') {
            if (function_exists('imagecolorallocatealpha')) {
                imagealphablending($this->new_img, false);
                imagesavealpha($this->new_img, true);
                $transparent = imagecolorallocatealpha($this->new_img, 255, 255, 255, 127);
                imagefilledrectangle($this->new_img, 0, 0, $tw, $th, $transparent);
            }
        }
    }



   /**
    * create a smaller image that fits inside the bounds given by $tw and $th. Original
    * image will be resized proportionately to fit inside the bounds.
    * @param int max width for thumb
    * @param int max height for thumb
    * @return true or PEAR::Error
    */
   function to_fit($tw, $th) {
       $w = $this->get_img_width();
       $h = $this->get_img_height();

       if (!$w or !$h) {
           return $this->raiseError("could not find original image dimensions");
       }
       else {

           $source = $this->get_source_img();
           if (PEAR::isError($source)) return $source; 

           if($h > $th || $w > $tw){
               if(($w / $tw) > ($h / $th)){
                   $new_w = $tw;
                   $new_h = $h / ($w / $tw);
               }
               else {
                   $new_w = $w / ($h / $th);
                   $new_h = $th;
               }
           }
           else {
               return;
           }
           $new_w = intval($new_w);
           $new_h = intval($new_h);

           //create dst image
           if (!$this->new_img = imagecreatetruecolor($new_w, $new_h)) {
               return $this->raiseError("could not create a new thumbnail image ($new_w, $new_h)!");
           }

           $this->_preallocate_transparency($tw, $th);

           //resize and copy image
           if (!imagecopyresampled($this->new_img, $this->source_img, 0,0,0,0, $new_w, $new_h, $w, $h)) {
               return $this->raiseError("could not copy resized image");
           }

           $this->new_width = $new_w;
           $this->new_height = $new_h;

       }
   }




   /** destructor
    */
   function _destroy() {
       if ($this->new_img) {
           imagedestroy($this->new_img);
       }               
       if ($this->source_img) {
           imagedestroy($this->source_img);
       }               
   }

   /** back-compat */
   function free() {
       return $this->_destroy();
   }

   /** calc the amount of memory needed to process the image (for
    * imagecreatefromjpeg anyway) - ripped from user comment at
    * http://us3.php.net/imagecreatefromjpeg 
    * @param $gis array output from getimagesize()
    * @return bool is there enough mem? */
   function _mem_check($gis) {
       if (!isset($gis['bits'])) { // php < 4.3.0
           return true;
       }
       /* deal with 16M format. Prob should deal with 128K too but whatever */
       $got = ini_get('memory_limit');
       if (!$got) { // limitless power!!
			return true;
       }
       $channels = (isset($gis['channels']))? $gis['channels'] : 4; // assume the worst 
       $need = round(($gis[0] * $gis[1] * $gis['bits'] * $channels / 8 + pow(2, 16)) * 1.65);
       if (preg_match('/(\d+)M$/', $got, $m)) {
           $got = 1024*1024*$m[1];
       }
       if ($need > $got) {
           return $this->raiseError("Your image is too large to resize! I dont have the power captain! [$need/$got]");
       }
       return true;
   }

}
?>
