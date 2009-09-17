<?php
//  mosh_tool - a utility class to autoprocess HTTP POST and GET data
//  Copyright (c) 2000-2004 S.Z.Beam, Onset Corps - sbeam@onsetcorps.net

// +----------------------------------------------------------------------+
//  This is the mosh_tool PHP utility class.
//
//  mosh_tool is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.
// 
//  mosh_tool is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
// 
//  You should have received a copy of the GNU General Public License
//  along with mosh_tool; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
// +----------------------------------------------------------------------+


//!! Class mosh_tool()
//!  a utility class to auto-process HTTP GET and POST data
/*!
    mosh_tool provides a set of simple methods to process and check incoming
    GET and POST data, and even to generate GET requests, as well as a few
    other odds and ends. It is a high-level utility class and not meant to
    take over low-level HTTP or HTML functionality that is better provided by
    PEAR classes. The basic idea is to make the common task of getting HTML
    FORM data cleaned, scrubbed, organized and INSERTed into a SQL statement
    as painless as possible.

    mosh_tool includes a set of basic utility methods to html-encode, decode,
    slash-escape, check for date, etc. strings. Also will auto-generate useful
    SQL fragments that can be safely used directly in your INSERT and UPDATE
    statements.
  
    for example: the below sample code will automatically create a properly
    escaped and formatted SQL fragment, given that $HTTP_POST_VARS
    contains keys which match the elements of $a_colmap 
     (e.g. $HTTP_POST_VARS =
     array(f_name=>"Bob", f_last=>"O'Brien", f_email=>"bob@bob.com")
  
     \code
     $mosh = new mosh_tool;
     $a_colmap = ('name', 'last', 'email');
     $sql = "INSERT INTO a_table SET " . $mosh->join_for_sql($a_colmap);
     \endcode
  
     thats it!
     $sql now is:
     "INSERT INTO a_table SET name='Bob', last='O\'Brien', email='bob@bob.com'"
  
     note the $form_field_prefix ('f_' by default) is prepended to the
     POST_VARS but not used in the column names or $a_colmap. This was an old
     safety feature from the days before auto_register_globals was usually
     turned off - we just keep it around for old time's sake.
  
     this class plays nicely with form_extruder which will make a form that
     lends itself to processing by this class. This class does not do any
     validation (as of yet).
 */
/*!TODO
    extend form validation methods to do more clever things
*/

define ("MOSH_TOOL_FIELD_STATUS_OK", 0);
define ("MOSH_TOOL_FIELD_STATUS_REQUIRED", 1);
define ("MOSH_TOOL_FIELD_STATUS_ERROR", 2);

//   $Id: mosh_tool.class.php,v 1.7 2007/02/28 20:04:40 sbeam Exp $

class mosh_tool extends PEAR 
{

    /// string used to join() SQL fragments in join_for_sql()
    var $join_str = ", ";

    /// we expect this to be prepended to each key in $posted_vars which
    /// matches the SQL column names (f_colname)
    var $form_field_prefix = "f_";

    /// Any tags in input not matching one of the below will be html-escaped
    var $allowed_html_tags = "br|b|i|p|u|a|ul|ol|li|blockquote|em|strong|hr|style|font|span|img";

    /// regexp matching the date pattern we like - this matches mySQL native
    var $date_format = '[0-9]{4}-[0-9]{2}-[0-9]{2}';

    /// name of table holding form instance data
    var $form_instance_table = 'scm_form_instances';

    /// name of POST/GET var containing instance token for a submitted form
    var $form_instance_token_key = '_fex_instance_token';

    /// string actual token as determined by algo in get_form_instance_token()
    var $form_instance_token = null;

    /// whether magic_quotes_gpc is set - set automatically in constructor
    var $have_magic_quotes = false;

    /*!
    sets the $posted_vars prop - this will be $HTTP_POST_VARS by
    default but can be set to any linear string-indexed array

    \in
    \$posted_vars (opt) ref to a linear ass. array of name => value pairs
    \accepts array
    \default $HTTP_POST_VARS (global)

    \return new mosh_tool obj
    */
    function mosh_tool() 
    {
        $posted_vars = array();
        if (func_num_args()) {
            list($posted_vars) = func_get_args();
        }
        if (empty($posted_vars) || !is_array($posted_vars)) {
            $this->posted_vars = &$_POST;
        }
        else {
            $this->posted_vars = $posted_vars;
        }

        $this->have_magic_quotes = (1 == (int) ini_get("magic_quotes_gpc"));
    }

    /*!
    calls native urlencode() but with a twist - the "escaped slashes" exposes
    an Apache bug so we switch all the %2F's BACK to slashes - this works!

    \in
    \$str string to be urlencoded
    */
    function urlencode($str) 
    {
        return str_replace("%2F","/",urlencode($str));
    }

    /*!
    just changes +'s to :plu: and &'s to :amp: - to avoid a similar Apache/PHP 'feature'
    where these are never taken literally (even if they are encoded as %2B or %26 !!)
    but as urlencoded spaces or GET args separators,
    respectively. This can be used if you plan on passing something in a GET param that 
    might have a & or a + in it.
    \in
    \$str string to be fixed up
    */
    function urlencode_apache($str) 
    {
        return $this->urlencode(str_replace("&", ";amp;", str_replace('+', ';plu;', $str)));
    }

    /*!
    reverses the effects of urlencode_apache()
    \in
    \$str string to be fixed up
    */
    function urldecode_apache($str) 
    {
        return urldecode(str_replace(";amp;", "&", str_replace(';plu;', '+', $str)));
    }

    /*!
    using $allowed_html_tags, html-encodes the delimiters of any non-matching
    HTML tags in the string. This makes HTML input safe for inclusion in your
    layout (no &lt;title> or &lt;table> tags!)

    \in
    \$str string to be escaped
    */
    function _html_escape_bad_tags($str) 
    {
        if (!empty($this->allowed_html_tags)) {
            // escape entire tags when not allowed
            $str = preg_replace("/<((?!\/?(" . $this->allowed_html_tags . ")\b)[^>]*)>/is", "&lt;\\1&gt;", $str);     
        }
        else { // none are allowed, so get rid of all
            $str = htmlspecialchars($str, ENT_QUOTES);
        }
        return $str;
    }

    /**
     * extra processing for completely untrusted html and text data - paranoid avoid of
     * any x-site-script attacks - remove all HTML attributes (no onmouseover events)
     */
    function _html_strip_attribs($str) {
        $str = preg_replace("/<(\/?\w+\b)[^>]*>/is", "<\\1>", $str);     
        return $str;
    }

    /**
     * find anything that looks like a URL in str and create a <a> tag out of it
     */
    function _html_autolink_urls($str) {
        // OK find any URLs in the text and add <a> tag
        $str = preg_replace("/((http|ftp)+(s)?:(\/\/)[^\s]+)/is", "<a href=\"\\1\">\\1</a>", $str);
        return $str;
    }

    /**
     * controller function to call the preceeding 3 functions as needed, in the
     * proper order 
     */
    function untrusted_html_proc($str, $proc_flags) {
        if (!is_array($proc_flags) or (isset($proc_flags[0]) and $proc_flags[0])) {
            $str = $this->_html_escape_bad_tags($str);
        }
        if (isset($proc_flags[1]) and $proc_flags[1]) {
            $str = $this->_html_strip_attribs($str);
        }
        if (isset($proc_flags[2]) and $proc_flags[2]) {
            $str = $this->_html_autolink_urls($str);
        }
        return $str;
    }

    /*!
    this should not be necessary but "magic_quotes_gpc" is a potential
    headache. We avoid it by only calling addslashes() if it is not on (it
    shouldn't be if you can help it)
    
    \in
    \$str string to be slashed
    */
    function addslashes($str) 
    {
        if ($this->have_magic_quotes) { return $str; }
        else { return addslashes($str); }
    }

    /*!
    a basically useless function. meant to complement addslashes() but not needed.
    */
    function stripslashes($str) 
    {
       //if (ini_get("magic_quotes_gpc")) { return $str; }
        if (0) { ; }
        else { return stripslashes($str); }
    }

    /**
    * convenience function: calls db_quote() and untrusted_html_proc() on a
    * string. Call this for anything untrusted you want to put into a SQL DB
    * that will later be displayed as a part of an HTML page. 
    *
    * (formerly fix_for_sql() but no longer need that all the time)
    */
    function fixup_untrusted_html_for_sql($str) 
    {
        return $this->db_quote($this->untrusted_html_proc($str), null);
    }


    /*!
    quote str for standard SQL insert using backslash escape and single quotes
    */
    function db_quote($str)
    {
        return '\'' . $this->addslashes($str) . '\'';
    }


    /*!
    returns a serialized version of the ass. array $arr
    but runs the vals through addslashes() FIRST so that the serialization
    does not get broken

    so - use this if you want to put a serialize array in a database. All the
    quotes will be escaped for the SQL statement, and unserialize() will still
    work when you pull it out.

    only works for one-dimensional string-indexed arrays
    */
    function serialize_slashed(&$arr) 
    {
        $keepers = array();
        foreach ($arr as $k => $v) {
            $keepers[$k] = $this->addslashes($v);
        }
        return serialize($keepers);
    }

    /*!
    returns a basic assoc array of POST or GET name/values
    with a value for each key found in $map - a $colmap style array
    */
    function get_form_vals(&$map) {
        $vals = array();
        foreach (array_keys($map) as $k) {
            if ($map[$k][1] == 'date' or $map[$k][1] == 'date_us' or $map[$k][1] == 'datetime') { // make posted_vars elem with concat'd date str
                $this->join_date_fields_to_str($k, ($map[$k][1] == 'date_us')? 1 : 0);
            }

            // special case for checkboxes which may or may not exist, and need to be 0/1 either way
            if ($map[$k][1] == 'toggle' or $map[$k][1] == 'checkbox') { 
                $val = (!empty($map[$k][2]) && is_array($map[$k][2]))? $map[$k][2] : array(0, 1);
                $vals[$k] = (isset($this->posted_vars[$this->form_field_prefix . $k]))? $val[1] : $val[0];
            }
            elseif ($map[$k][1] == 'calendar') {
                $vals[$k] = date('Y-m-d H:i:s', strtotime($this->posted_vars[$this->form_field_prefix . $k]));
            }
            elseif ($map[$k][1] == 'heading') { // do nothing, its a heading
            }
            elseif ($map[$k][1] == 'select_or') {
                $aux = $this->form_field_prefix . $k . '_aux';
                $vals[$k] = (!empty($this->posted_vars[$aux]))? $this->posted_vars[$aux] : $this->posted_vars[$this->form_field_prefix . $k];
            }
            elseif (isset($this->posted_vars[$this->form_field_prefix . $k])) {
                $vals[$k] = $this->posted_vars[$this->form_field_prefix . $k];
            }
        }
        return $vals;
    }

    /*!
    the function described in the header comments above.     
    
    \in
    \$arr the 'column map' - this should contain the names of
    each of the columns you want included in your SQL statement. Also should
    match some keys found in $posted_vars
    \accepts array

    \return string - a properly quoted and escaped SQL fragment suitable for inclusion
    in INSERT and UPDATE statements, or even in the WHERE clauses of DELETEs
    and SELECTs if $join_str is set to something like " and ". 
    */
    function join_for_sql($arr) 
    {
        $elems = array();
        // array_map() can't handle class methods as callbacks, or ass. arrays as args! feeble!
        // return join($this->join_str, array_map($this->fix_for_sql, $arr));
        while (list($k, $v) = each($arr)) {
            $elems[] = "$v = " . $this->db_quote($this->posted_vars[$this->form_field_prefix . $v]);
        }
        return join($this->join_str, $elems); 

    }
    /*!
    same as join_for_sql() but with a twist - the param $arr should contain
    flags telling whether or not to HTML-escape the given vals in $posted_vars.
    This is needed for times when one or more of the values in $posted_vals has
    been run through serialize() before this is called - which is often the
    case - (serialized objects get broken if their character count is changed -
    hence, no escape_bad_html or similar)
    
    \in
    \$arr ass. the 'column map' - the keys should contain the
    names of each of the columns you want included in your SQL statement, and
    the vals are just flags telling whether or not to HTML-escape the form
    value. 
    \accepts linear associative array 

    \return a properly quoted and escaped SQL fragment suitable for inclusion
    in INSERT and UPDATE statements - even if some of the values have been run
    through serialize() (assuming the $has_fix flag has been set to a true
    value)
    */
    function join_for_sql_serialized($arr) 
    {
        $elems = array();
        // array_map() can't handle class methods as callbacks, or ass. arrays as args! feeble!
        // return join($this->join_str, array_map($this->fix_for_sql, $arr));
        foreach ($arr as $colname => $has_fix) {
            $k = $this->form_field_prefix . $colname;
            $val = (isset($this->posted_vars[$k]))? $this->posted_vars[$k] : '';
            if (!$has_fix) { $val = $this->untrusted_html_proc($val, null); }
            $val = $this->addslashes($val);
            $elems[] = "$colname = '$val'";
        }
        return join($this->join_str, $elems); 
    }

    /*!
    same as join_for_sql() but does not attempt to detect $posted_vars and does not
    include the key. Makes a slash-escaped, comma-separated string of the values in the
    array $arr.
    
    Good for 'shorthand' INSERTs, javascript arrays, etc. e.g.:
    "'val1', 'val2', 'O\'Brian', 'don\'t bogart'"

    \in 
    \$arr list of items to be joined

    \return a slash-escaped, comma-separated string of the joined values in the
    array $arr
    */
    function join_for_list(&$arr, $do_html_proc = false) 
    {
        $elems = array();
        while (list($k, $v) = each($arr)) {
            if (is_array($do_html_proc)) {
                $v = $this->untrusted_html_proc($v, $do_html_proc);
            }
            $elems[] = $this->db_quote($v);
        }
        return join($this->join_str, $elems); 
    }

    /*!
    old alias for join_for_list()
    @deprecated
    */
    function join_for_sql_list(&$arr) 
    {
        return $this->join_for_list($arr);
    }


    /*! 
    does a join_for_sql but temporarily sets $join_str to ' and ':
    this is good for WHERE clauses, e.g. 
    "foo = 'foovalue' and lastname = 'O\'Brien' and something = 'else'"
    */
    function join_for_sql_where(&$arr) 
    {
        $old_join_str = $this->join_str;
        $this->join_str = ' and ';
        $res = $this->join_for_sql($arr);
        $this->join_str = $old_join_str;
        return $res;
    }


    /*!
    checks all fields in a std. $colmap array - if any are a select_or, and
    the aux field is set, then we replace the value in posted_vars with the aux
    */
    function set_aux_values(&$colmap) 
    {
        $ff = $this->form_field_prefix;
        foreach ($colmap as $k => $params) {
            if ($params[1] == 'select_or') {
                if ($auxval = trim($this->posted_vars[$ff . $k . '_aux'])) {
                    $this->posted_vars[$ff . $k] = $auxval;
                }
            }
        }
    }


    /*!
    creates a safe GET method string from any ass. array
    
    \in
    \$arr  ass. array of the names + values to be used (can be $HTTP_GET_VARS
    or other) 
    \accepts linear associative array

    \$ignore linear array of keys in $arr which
            should be ignored - this is good if you have certian params in
            GET_VARS you are overriding some other way
    \accepts linear array
    
    \return a typical GET string of name value pairs joined with '&' that you
            can stick in your link. does not include the preceeding '?'
    */
    function make_get_params(&$arr, $ignore=array(), $prefix = '', $glue='&amp;') 
    {
        $elems = array();
        reset($arr);
        foreach ($arr as $k => $v) {
            if (in_array($k, $ignore)) { continue; } // pretend this never happened
            if (is_array($v)) {  // it needs the magic brackets
                reset($v);
                foreach ($v as $val) {
                    $elems[] = self::urlencode($k . "[]") . '=' . self::urlencode($val);
                }
            }
            else {
                $elems[] = self::urlencode($k) . '=' . self::urlencode($v);
            }
        }
        if (count($elems)) {
            return $prefix . join($glue, $elems);
        }
        else {
            return '';
        }
    }





    /*!
    checks $posted_vars against a $formex style column-map-array. See the docs for
    formex for more info on the colmap array. Basically for each element in $map,
    if the last element of the values array is 1, checks to make sure the
    corresponding elem. in $this->posted_vars is not empty, or is a proper
    date, etc

    \in
    \$map colmap-style array of posted vars to be checked for validity

    \return an array $errs if any elements were invalid. Contains the names (keys) of the offensive elements.
    */
    function check_form(&$map) 
    {
        $errs = array();

        if (!is_array($map)) {
            trigger_error("check_form(): argument is not an array", E_USER_NOTICE);
            return false;
        }

        foreach ($map as $k => $v) {
            // if its required! (last element is TRUE or 1)
            if ($v[count($v)-1] === true or $v[count($v)-1] === MOSH_TOOL_FIELD_STATUS_REQUIRED) {

                $ff = $this->form_field_prefix . $k;  //shorthand

                if ($v[1] == 'date' or $v[1] == 'date_us') { // date field - special acrobatics
                    $day = (isset($v[3]) and isset($v[3]['suppress_day']))? '01' : sprintf("%d", $this->posted_vars[$ff . "_day"]);
                    $datefields = array(sprintf("%d", $this->posted_vars[$ff . "_month"]),
                                        $day,
                                        sprintf("%04d", $this->posted_vars[$ff . "_year"])
                                        ); 
                    if (!$this->is_proper_date($datefields)) {
                        $errs[] = "'$v[0]' is not a valid date.";
                        $map[$k][count($v)-1] = MOSH_TOOL_FIELD_STATUS_ERROR;
                    }
                }
                elseif ($v[1] == 'date_text' && !$this->is_proper_date($this->posted_vars[$ff])) {
                    $errs[] = "'".$this->posted_vars[$ff] . "' in '$v[0]' is not a valid date.";
                    $map[$k][count($v)-1] = MOSH_TOOL_FIELD_STATUS_ERROR;
                }
                elseif ($v[1] == 'numeric' && !is_numeric(trim($this->posted_vars[$ff]))) {
                    $errs[] = "'$v[0]' must be a numeric value.";
                    $map[$k][count($v)-1] = MOSH_TOOL_FIELD_STATUS_ERROR;
                }
                elseif ($v[1] == 'file' or $v[1] == 'image_upload') {
                    if (!isset($_FILES[$ff])) {
                        $errs[] = "'$v[0]' must be uploaded";
                    }
                }
                elseif ($v[1] == 'select_or') {
                    if (empty($this->posted_vars[$ff]) and empty($this->posted_vars[$ff.'_aux'])) {
                        $errs[] = "'$v[0]' is a required field.";
                        $map[$k][count($v)-1] = MOSH_TOOL_FIELD_STATUS_ERROR;
                    }
                }
                elseif (is_array($this->posted_vars[$ff]) && 0 == count($this->posted_vars[$ff])) {
                    $errs[] = "'$v[0]' is a required selection.";
                    $map[$k][count($v)-1] = MOSH_TOOL_FIELD_STATUS_ERROR;
                }
                elseif (!isset($this->posted_vars[$ff]) or (is_string($this->posted_vars[$ff]) && strlen(trim($this->posted_vars[$ff])) == 0)) {
                    $errs[] = "'$v[0]' is a required field.";
                    $map[$k][count($v)-1] = MOSH_TOOL_FIELD_STATUS_ERROR;
                }
                elseif ($v[1] == 'email') {
                    $got_val = $this->posted_vars[$ff];
                    if (!$this->is_proper_email($got_val)) {
                        $errs[] = "'$got_val' is not a valid email address. Please enter a complete
                                    email address, i.e. 'jdoe@aol.com', in the $v[0] field.";
                        $map[$k][count($v)-1] = MOSH_TOOL_FIELD_STATUS_ERROR;
                    }
                }
            }
        }
        if (count($errs) > 0) {
            return $errs;
        }
    }

    /*! alias for check_form()
    */
    function form_filled(&$map)
    {
        return $this->check_form($map);
    }

    

    /*!
    takes a standard array from PEAR::DB tableInfo() method and returns a colmap
    based on the column types found therein. Can be used directly in add_element.
    */
    function get_auto_colmap($tableinfo) {
        if (!is_array($tableinfo)) {
            $this->raiseError("$tableinfo is not an Array from PEAR::DB::tableInfo()", 1002);
        }
        else {
            $colmap = array();
            foreach ($tableinfo as $colinfo) {
                if (!isset($colinfo['name']) or !isset($colinfo['type'])) {
                    $this->raiseError("$tableinfo is not an Array from PEAR::DB::tableInfo()", 1002);
                }
                switch ($colinfo['type']) {
                    case 'int':
                      $type = 'numeric';
                      break;
                    case 'real':
                      $type = 'numeric';
                      break;
                    case 'date':
                      $type = 'date_text';
                      break;
                    case 'string':
                      if ($colinfo['len'] == 1) {
                          $type = 'checkbox';
                          break;
                      }
                      $type = 'text';
                }
                $colmap[$colinfo['name']] = array(ucfirst($colinfo['name']), $type);
            }
            return $colmap;
        }
    }


    /*! returns basic ugly HTML for formatting errors
    \in
    $errs - array (linear) of error messages
    \returns
    html fragment
    */
    function error_format($errs, $textcolor="#000000", $table_color="#ebebeb", $style="font: 10pt sans-serif") 
    {
        if (is_array($errs)) {
            $msg = "The following fields had errors and could not be processed. Please correct the
                    following errors to proceed:<br /><ul><li>";
            $msg .= join("<li>", $errs);
            $msg .= "</ul>";
        }
        else {
            $msg = $errs;
        }
        return "<p><table border=0 bgcolor=\"$table_color\" align=\"center\" cellspacing=0 cellpadding=4 width=\"100%\">
                <tr><td>
                        <span style=\"$style\">
                            <font color=\"$textcolor\"><b>FORM ERROR</b><br>$msg</font>
                        </span>
                    </td></tr></table></p>\n";
    }



    /*!
    tries to make sure a date is properly formatted and valid, based on
    $date_format and using the native checkdate() function to make
    sure nobody gives you a Feb.31

    \in
    \$date a string to be checked in YYYY-MM-DD format or an array like MM,DD,YYYY
    \return true if the date is OK, nothing if not
    */
    function is_proper_date($date) 
    {
        if (!is_array($date)) { // check string format
            if (!ereg($this->date_format, $date)) return;
            $date = split('-', $date);
            list($y,$m,$d) = $date;
        }
        else {
            list($m,$d,$y) = $date; // legacy passed in arr. as MM,DD,YY
        }
        if (count($date) != 3) return;

        if (checkdate($m,$d,$y)) return 1;
    }


    /*! 
    looks for a 'normal' style email address, i.e. has a '@' and a dot.
    \in
    \$email string to be checked
    \return true if conforms to the preg(), false if not
    */
    function is_proper_email($str) 
    {
        if (preg_match("/^([-a-zA-Z0-9_.]+)@(([-a-zA-Z0-9_]+[.])+[a-zA-Z]+)$/", $str)) {
            return true;
        }
        return false;
    }



    /*!
    creates a new element in $posted_vars by joining 3 fields that make up a
    date field in stock form_extruder() 

    side effects: creates a new key in $posted_vars for each item in $vars -
    the value is a dash-separated string consisting of the 3 values that make up
    the date (in YYYY-MM-DD format)

    \in
    \$vars - list of names of fields you want created, should
    correspond with the names of the date fields (if you passed 'start_date' to
    form_extruder::add_element, then just pass this 'start_date' - it should
    figure it out 
    \$us_fmt - t/f - if true, will return the std. US date format (MM/DD/YYYY)
    instead of international std YYYY-MM-DD
    
    \return nothing
    */
    function join_date_fields_to_str($vars, $us_fmt = 0) 
    {
        if (!is_array($vars)) {
            $vars = array($vars);
        }

        $POST =& $this->posted_vars; // shorthand

        foreach ($vars as $k => $var) {
            $ff = $this->form_field_prefix . $var;  //shorthand

            if (!isset($POST[$ff . "_year"])) break;

            if (isset($POST[$ff . "_day"])) {
                $day = sprintf("%02d", $POST[$ff . "_day"]);
            }
            else {
                $day = '01';
            }
            $str = join ("-",  array(sprintf("%04d", $POST[$ff . "_year"]),
                                     sprintf("%02d", $POST[$ff . "_month"]),
                                     $day)); 

            if ($us_fmt) {
                $str = $this->date_fmt_sql2us($str);
            }

            if (isset($POST[$ff . "_hours"])) {
                $h = intval($POST[$ff . "_hours"]);
                if (isset($POST[$ff . "_ampm"]) and $POST[$ff.'_ampm'] == 'PM') {
                    $h += 12;
                }
                $m = (isset($POST[$ff . "_min"]))? intval($POST[$ff . "_min"]) : 0;
                $s = (isset($POST[$ff . "_sec"]))? intval($POST[$ff . "_sec"]) : 0;
                $str .= sprintf(" %02d:%02d:%02d", $h, $m, $s);
            }

            $this->posted_vars[$ff] = $str;
        }
    }


    /*!
    convert SQL (mySQL) date format 2002-03-22 to US date format 03/22/2002
    */
    function date_fmt_sql2us($datestr) 
    {
        list ($y,$m,$d) = split('-', $datestr);
        return join('/', array($m,$d,$y));
    }
       

    /*!
    remove all non-RExp matching chars from $str
    by default RE removes all anything notalphanumeric or _, -, or space
    \in
        $str string string to check
        $rexp string optional other RegExp to use
    \return
        modified string, if any
    */
    function safety_check($str, $rexp = '^[A-Za-z0-9_ -]+$') 
    {
        if (empty($str) || empty($rexp)) return;
        if (preg_match("/$rexp/",$str)) return $str;
    }

    /*!
    run $str through the crypt() function with a truly random salt
    */
    function rand_crypt($str) 
    {
        $salt = '';
        mt_srand((double)microtime()*1000000);
        $chars = array_merge(range('a','z'),range('A','Z'),range(0,9));
        for($i=0;$i<2;$i++) {
            $salt .= $chars[mt_rand(0,count($chars)-1)];
        }
        return crypt($str, $salt);
    }

    /**
     * find a form instance token that might be lurking in any posted or GET vars
     * @return string the token
     */
    function get_form_instance_token($method) {

        // we did this before...
        if ($this->form_instance_token) {
            return $this->form_instance_token;
        }

        // seek and return
        $token = null;
        $k = $this->form_field_prefix . $this->form_instance_token_key;
        if ($method == 'POST' and isset($_POST[$k])) {
            $token = $_POST[$k];
        }
        elseif ($method == 'GET' and isset($_GET[$k])) {
            $token = $_GET[$k];
        }
        if ($token) {
            $this->form_instance_token = $token;
            return $token;
        }
        else {
            return $this->raiseError("Form instance token $k not found");
        }
    }

    /**
     * check if we've seen this exact form come through before. If not, record it.
    */
    function check_duplicate_submit(&$db, $page_id, $token=null) {

        $req_method = $_SERVER["REQUEST_METHOD"];
        if (!$token) {
            $token = $this->get_form_instance_token($req_method);
        }
        if (PEAR::isError($token)) {
            return $token;
        }

        if (!$page_id) {
            $page_id = $_SERVER["SCRIPT_FILENAME"];
        }

        $sql = sprintf("SELECT COUNT(*) FROM %s 
                        WHERE instance_token = %s AND page_id = %s",
                        $this->form_instance_table,
                        $this->db_quote($token),
                        $this->db_quote($page_id));
        $res = $db->getOne($sql);
        if ($res != 0) { // itssa dupe!
            return $res;
        }
        else {
            $sql = sprintf("INSERT INTO %s (instance_token, page_id, client_ipaddr, request_method)
                            VALUES (?, ?, ?, ?)",
                            $this->form_instance_table);
            $sth = $db->prepare($sql);
            $res =& $db->execute($sth, array($token, 
                                             $page_id, 
                                             isset($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR'] : 'unk!',
                                             $req_method));
            if (PEAR::isError($res)) return $res;
            else return false;
        }
    }
}
?>
