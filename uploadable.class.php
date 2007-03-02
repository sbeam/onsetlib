<?

/*
/* uploadable: a class to deal with 'filepicker' file uploads simply and quickly
 *  Copyright 2005 Onset Corps. <sbeam@onsetcorps.net>
 *  Permisson granted to modify, re-use and distribute provided this
 *  copyright notice remains intact.  
 * $Id: uploadable.class.php,v 1.2 2006/11/29 01:58:33 sbeam Exp $
 */

//!! class uploadable
//! a class to deal with 'filepicker' file uploads simply and quickly
/*!
  
   Handles a lot of things having to do with saving a file from a
   multipart-form to the server filesystem. Also checks for image size, proper
   MIME type, extensions, names the file how you like it etc.

   Updates: 2001-09-10 - make compatible w/ php4
   much of this was made obsolete by functionality made avail. in PHP4
   so version-detection is used heretoforthwith

   2001-11-27 - use PEAR for error handling
   
   
   \code
   $uplo = new uploadable("f_userfile");
   if ($uplo->is_uploaded_file()) {
    $uplo->setErrorHandling(PEAR_ERROR_TRIGGER, E_USER_WARNING);
   	$uplo->params(array('storage'=>'filesystem', 
   				      'path'=> "/some/path", 
   				      'fnamebase'=> "uplo_",
   				      'allowed'=>'web_images',
   				      'maxdims'=> "400x500"));
   	$res = $uplo->save_upload();
      if (!PEAR::isError($uplo->save_upload())) {
   		echo "file saved!";
      }
   \endcode
   
*/
/*!TODO
    use alternate storage systems (DB)
*/
require_once('imagestretcher.class.php');


class uploadable extends PEAR {
    /// placeholder for simple version detection
    var $phpversion = 4;
    /// array to hold "size","type","name" params discerned by PHP from POST data (automatic in php4)
    var $fileparams = Array();
    /// set to 1 to print much dubugging junk
    var $debug = 0;
    /// /private will contain the new filename that was saved
    var $_newname;
    /// will contain the image width+height (if its an image) in a format good for an image tag (e.g. 'width="150" height="210"')
    var $imgdims;
    /// contains width of image in pixels if applicable
    var $imgwidth;
    /// contains height of image in pixels if applicable
    var $imgheight;
    // contains filesize in kilobytes after save_upload();
    var $filesize;
    /// array containing config params for this file
    var $params = array();
    /// use user's original filename as basis for our new filename?
    var $preserve_original_name = false;
    /// skip all checks related to proper filetype, extenstion, mime, etc.
    var $skip_filetype_check = false;

	/*!
	   \public 
       constructor:
	   takes the name of the form variable containg the image (this is an array under
	   php4, while previuosly it was only a filename

       \in
       \$formvar string containing the NAME of the form variable that was
       posted (ie what the name of the File Upload field in the form was).
       This will be looked for in the appropriate place based on version
       \return new object
	 */
   function uploadable($formvar) {
       global $HTTP_POST_VARS,$HTTP_POST_FILES;
       if ($this->debug) { print ("received '$formvar' to construct<br>"); }
       // detect the version of php we are using
       // http://php.net/manual/en/function.phpversion.php
       if ($this->phpversion >= 4) {
           if (!isset($_FILES[$formvar])) {
               $this->file = null;
               return false;
           }
           $this->file = $_FILES[$formvar]["tmp_name"];
           $this->fileparams = $_FILES[$formvar];
       }
       else {
           $this->phpversion = 3;
           $this->file = $HTTP_POST_VARS[$formvar];
           $this->fileparams['name'] = $HTTP_POST_VARS["${formvar}_name"];
           $this->fileparams['type'] = $HTTP_POST_VARS["${formvar}_type"];
           $this->fileparams['size'] = $HTTP_POST_VARS["${formvar}_size"];
       }
   }

  /*!
     \public
     set params array to whatever you array give it

     \in
     \$arr an ass. array of params. keys should match those given here:
    	storage - right now only 'filesystem' is implemented so this is ignored <br>
   		path - path to where the files should be stored, if any<br>
   		fnamebase - string to be prefixed to new file name (is filtered to \w only)<br>
   		allowed - linear array of allowed mimetypes, or one of the pre-built groups (web_images)<br>
   		maxdims - max allowed height + width, x-separated (i.e. "400x500")<br>
   	    if any are not set they will be ignored when possible<br>
   */
   function params($arr) {
      $this->params = $arr;
   }

  /*!
     \public
     determine if a given form var has an uploaded file or is empty

     \return true if the var that was set in $params contains a file
   */
   function is_uploaded_file() {
       if ($this->debug) { print ("in is_uploaded_file()<br>"); }
       if ($this->phpversion > 3) {
           if (is_uploaded_file($this->file)) {
               return true;
           }
           else {
               if (!$this->file or 4 == $this->fileparams['error']) { // there was no file uploaded - its OK!
                   return false;
               }
               $this->raiseError('file upload error :'.$this->fileparams['error']);
           }
       }

       // straight from Rasmus
       if (!$tmp_file = get_cfg_var('upload_tmp_dir')) {
           $tmp_file = dirname(tempnam('/tmp', 'venturad'));
       }
       $tmp_file .= '/' . basename($this->file);

       if ($this->debug) { print "$tmp_file<br>"; }
       /* User might have trailing slash in php.ini... */
       return (ereg_replace('/+', '/', $tmp_file) == $this->file);
   } ////


  /*
     \public
     creates a safe filename to save our upload as -
     if $params[fnamebase] is set, removes special chars &c and uses the result, otherwise
         just an oogly uniqid() is used
     calculates extension as well based on mimetype given by browser.
     this can be called if filename is needed before $this->save_upload() is called,
     or will be called in save_upload() automatically.
     */
    function get_newname() {
       if ($this->_newname) {
           return $this->_newname;
       }
       if (isset($this->params["fnamebase"])) {
           $this->_newname = preg_replace("/\s/", "_", $this->params["fnamebase"]);
           $this->_newname = preg_replace("/[^A-Za-z0-9_.-]/", "", $this->_newname);
       }
       elseif ($this->preserve_original_name ) {
           // remove extension and replace with a uniqid()
           $orig = $this->fileparams['name'];
           $this->_newname = substr($orig, 0, strrpos($orig, '.'));
           $this->_newname = preg_replace("/\s/", "_", $this->_newname);
           $this->_newname = preg_replace("/[^A-Za-z0-9_.-]/", "", $this->_newname);
           if ($this->unique_filename) {
               $this->_newname .= '.'. uniqid('U');
           }
       }
       else { $this->_newname = uniqid('up'); }

       if (!isset($this->params["allowed"]) or $this->skip_filetype_check) { // put the old extension back
           $orig = $this->fileparams['name'];
           $this->extension = substr($orig, strrpos($orig, '.'));
           $this->_newname .= $this->extension;
       }
       else {
           $allowed = $this->find_allowed_types(); // $allowed now has array of allowed mimetypes

           // find the right extension based on the mime-type the browser told us
           if ($this->debug) echo "using $extension because of " .$this->fileparams['type']. "<br>";
           if (!isset($this->params["allowed"]) or $this->params["allowed"] == 'any'
            or (isset($allowed[$this->fileparams['type']])
                and $this->extension = $allowed[$this->fileparams['type']])) {
               $this->_newname .= $this->extension; // make sure the name is mangled appropriately
               return $this->_newname;
           }
           else { 
               return $this->raiseError("File of type '" . $this->fileparams["type"] . "' are not permitted.");
           }
       }
    }

  /*
     \public
     save the file somewhere, based on params array

	  - gets a safe unique filename if need be (params[fnamebase])

	  - checks mime type of file and applies appropriate extension 
	 	(in php3 the extension was lost so we had to re-create it)

	  - checks image dimensions id needed (params[maxdims])

	  - calls appropriate storage method (params[storage])
   */
   function save_upload() {
       if ($this->debug) { print ("in save_upload()<br>"); }

       if (substr($this->get_filetype(), 0, 6) == 'image/' and 
           (isset($this->params["maxdims"]) || isset($this->params["exact_dims"]))) {  // we will check the dimensions of it 
           $this->imginfo = getimagesize($this->file);

           if (isset($this->params["exact_dims"])) {  // we will check the dimensions of it make sure it matches the spec
               list($targ_wid, $targ_ht) = split("x", $this->params["exact_dims"]);
               if (($targ_wid != $this->imginfo[0]) || ($targ_ht != $this->imginfo[1]) ) {
                   return $this->raiseError("Uploaded image was an incorrect size! 
                        Dimensions need to be " . $this->params["exact_dims"].
                       " pixels. Yours was " . $this->imginfo[0] . "x" . $this->imginfo[1] . "."); 
               }
           }
           elseif (isset($this->params["maxdims"])) {  // we will check the dimensions of it make sure this fits
               list($img_maxwidth, $img_maxheight) = split("x", $this->params["maxdims"]);
               if (($img_maxwidth < $this->imginfo[0]) || ($img_maxheight < $this->imginfo[1]) ) {
                   return $this->raiseError("Uploaded image was too large! Maximum dimensions are " . $this->params["maxdims"].
                       " pixels. Yours was " . $this->imginfo[0] . "x" . $this->imginfo[1] . "."); 
               }
           }
           // ok we made it it checked out
           $this->imgwidth = $this->imginfo[0];
           $this->imgheight = $this->imginfo[1];

           if (isset($this->imginfo['mime'])) { // will be for PHP > 4.3.0
               $this->fileparams['type'] = $this->imginfo['mime']; // override val from browser
           }
           $this->imgdims = $this->get_img_dims();
       }

       $_newname = $this->get_newname();
       if (PEAR::isError($_newname)) { return $_newname; }

       $this->filesize = intval(filesize($this->file) / 1024);

       if (isset($this->params["storage"]) and $this->params["storage"] == 'db') {
               // TODO do something
       }
       else {
           return $this->_save_to_file();
       } 
   }

  /*!
     \private
     put the uploaded file on the filesystem 
   */
   function _save_to_file() {
      if ($this->debug) { print ("in _save_to_file()<br>"); }

      if (!copy($this->file, $this->params["path"] . "/" . $this->_newname)) {
          return $this->raiseError($this->params["path"] . "/" . $this->_newname . " could not be saved.");
      } 
      $this->fullPathtoFile = $this->params["path"] . "/" . $this->_newname;
      return 1;
   }

  /*!
     \private
     set the arr allowed_types based on params['allowed']
   */
   function find_allowed_types() {

       if (isset($this->params["allowed"]) and is_array($this->params["allowed"])) {
           return $this->params["allowed"];
       }

       $allow = null;
       if (isset($this->params["allowed"])) {
           $allow = $this->params["allowed"];
       }
       switch ($allow) {
           case "web_images":
               return array("image/png" => ".png",
                       "image/gif" => ".gif",
                       "image/pjpeg" => ".jpeg",
                       "image/jpeg" => ".jpeg" );
           break;
           case "web_images_nogif":
               return array("image/png" => ".png",
                       "image/pjpeg" => ".jpeg",
                       "image/jpeg" => ".jpeg" );
           break;
           case "web_images_etc":
               return array("image/png" => ".png",
                       "application/x-shockwave-flash"     => ".swf",
                       "image/gif" => ".gif",
                       "image/pjpeg" => ".jpeg",
                       "image/jpeg" => ".jpeg" );
           break;
           case "flash":
               return array("application/x-shockwave-flash"     => ".swf");
           break;
           case "audio":
               return array("audio/mpeg" => ".mp3",
                       "audio/x-pn-realaudio" => ".rm",
                       "application/ogg" => ".ogg",
                       "audio/x-ms-wma" => ".wma"
                       );
               
           case "text":
               return array("text/plain"                => ".txt");
           break;
           default:
               return array(
                       "application/x-gzip-compressed"     => ".tar.gz",
                       "application/x-zip-compressed"         => ".zip",
                       "application/x-tar"            => ".tar",
                       "text/plain"                => ".txt",
                       "image/gif"                 => ".gif",
                       "image/pjpeg"                => ".jpeg",
                       "image/jpeg"                => ".jpeg",
                       "application/x-shockwave-flash"     => ".swf",
                       "application/msword"            => ".doc",
                       "application/vnd.ms-excel"        => ".xls",
                       "application/vnd.ms-word"        => ".doc",
                       "application/pdf"                 => ".pdf",
                       "application/octet-stream"        => ".exe"
                       );
       } // end switch
   }

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
       if (isset($this->imgheight) and isset($this->imgwidth)) {
           return sprintf('width="%d" height="%d"', $this->imgwidth, $this->imgheight);
       }
   }

   function get_filesize() {
       if (isset($this->filesize)) {
           return $this->filesize;
       }
   }


   function get_thumb_width() {
       if (isset($this->thumbwidth)) {
           return $this->thumbwidth;
       }
   }

   function get_thumb_height() {
       if (isset($this->thumbheight)) {
           return $this->thumbheight;
       }
   }
   function get_thumb_dims() {
       if (isset($this->thumbheight) and isset($this->thumbwidth)) {
           return sprintf('width="%d" height="%d"', $this->thumbwidth, $this->thumbheight);
       }
   }
   function get_thumbfilesize() {
       if (isset($this->thumbfilesize)) {
           return $this->thumbfilesize;
       }
   }

   function get_filetype() {
       if (isset($this->fileparams['type'])) {
           return $this->fileparams['type'];
       }
   }



   /**
    * create a thumbnail from the uploaded image file and save it to the same path
    * filename is prefixed with 't_'
    * @param int max width for thumb
    * @param int max height for thumb
    * @param str method - name of imagestretcher() method to use to create the thumb
    * @param string optional thumb output format, jpeg, gif or png (png default)
    * @return filename of the new thumbnail or PEAR::Error if problem
    * @see imagestretcher.class.php
    */
   function save_thumbnail($tw, $th, $method='shrink_to_fit', $fmt='png', $filename_prefix='t_') {

       // create stch obj to do the resizing
       $stretch =& new imagestretcher($this->fullPathtoFile);
       if (!method_exists($stretch, $method)) {
           return $this->raiseError("invalid method imagestretcher()::'$method'");
       }
       $stretch->$method($tw, $th); // creates a GD "object"

       // create a new filename with the prefix and proper extension for the filetype
       $thumbname = $filename_prefix . $this->get_newname(); 
       $thumbname = preg_replace('/\.\w+$/', ".$fmt", $thumbname);

       // save thumb to a file next-door to the uploaded one:
       $fullthumbpath = $this->params['path'] . '/' . $thumbname;
       $res = $stretch->save_to_file($fullthumbpath, $fmt);
       $stretch->free();

       if (!PEAR::isError($res)) { // set up class vars to track stuff -
           $gis = getimagesize($fullthumbpath);
           $this->thumbwidth = $gis[0];
           $this->thumbheight = $gis[1];
           $this->thumbfilesize = filesize($fullthumbpath);
       }
       return $res;
   }

} #################
##############################
?>
