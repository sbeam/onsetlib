<?php
/*  formex - PHP class for HTML form auto-generation and management
 *  Copyright 2000-2005 SBeam, Onset Corps - sbeam@onsetcorps.net

// +----------------------------------------------------------------------+
//  This file is part of formex.
//
//  formex is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.
//
//  formex is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with formex; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
// +----------------------------------------------------------------------+

*/

/* 
 *  formex() - form controller class
 *
 *   creates a dynamic XHTML form based on arrays of elements and pre-set values
 *   which can be easily passed to a template system or dumped to the client.
 */

/*
 * USAGE GUIDE
 *
 *    this class is meant to make generation of HTML forms simple and convenient-
 *    no more, no less. You can set various public properties of a member object,
 *    add form elements as needed, and finally call display() which will assemble
 *    the HTML and return the string.
 * 
 *    formex does not have anything to do with validation, client- or
 *    server-side - that is left to subclasses or data processing classes.
 *    What it will do, is allow you to designate fields with errors which will
 *    be marked up in ways that make it easy to designate for the user.
 *
 *    By default, the output of the final display() method is a two-column XHTML
 *    table. Field labels are in the left column and the elements are on right.
 *    Hidden fields are tacked on at the end, and the submit button right-aligned
 *    in the last row, which spanns both columns.  The reset button can be turned
 *    on if needed.
 *
 *    A sample invocation is as follows:
 *    \code
 *        $fex = new formex;
 *        $fex->element_class = "magic";
 *        $fex->form_name = "my_form";
 *        $fex->form_action = "form_process.php";
 *
 *        $fex->add_element("page_id",    array("page id", "hidden"));
 *        $fex->add_element(array("name"  => array("Your Name", "text", 1),
 *                                "email" => array("Your Email", "text", 1)
 *                                ));
 *        $fex->add_element("sex",        array("Sex", "select", array(
 *                                                                    "M",
 *                                                                    "F",
 *                                                                    "Yes Please",
 *                                                                    "Other")
 *                                             ));
 *        $fex->add_element("age",        array("Age", "text", 1, array('size'=>4)));
 *        $fex->add_element("comments",   array("Comments", "textarea"));
 *        $fex->add_element("op_enter"    array("Send it!", "submit", 1));
 *        e$fex->display();
 *    \endcode
 *
 *    Also a method get_struct() is provided which returns all the HTML for the
 *    form elements and their labels in a ass.array which is very convenient for
 *    Smarty templates. So instead of display(), call:
 *    \code
 *    $smarty->assign('form', $fex->get_struct());
 *    \endcode
 *    to assign the vars to $smarty and then layout the form using a Smarty template:
 *    \code
 *    ...<whatever HTML here>
 *    <span class="<~ $form.name.CLASS ~>"><~ $form.name.LABEL ~></span>    <~ $form.name.TAG ~>
 *    <span class="<~ $form.email.CLASS ~>"><~ $form.email.LABEL ~></span>  <~ $form.email.TAG ~>
 *
 *    <Fancy stuff>blah blah</fancy stuff>
 *
 *    <span class="<~ $form.comments.CLASS ~>"><~ $form.comments.LABEL ~></span>  
 *            <~ $form.comments.TAG ~>
 *
 *    ...etc.
 *    \endcode
 *    This allows forms to be embedded in any HTML or CSS you need them, while
 *    preserving the numerous safety and convenience features that this class
 *    provides.
 *
 *    One of the best parts of this, and the original reason for building it, is
 *    that all form fields generated via this class have an intelligent way of
 *    finding their default values. This is great for editing values from a DB -
 *    the form is easily pre-populated. If there is a user error of invalid
 *    input, it is effortless to re-display the form with the previous values,
 *    and the error field highlighted.
 *
 *    Form elements will find their default values according to the following
 *    precedence:
 *        $fext->elem_vals: The 'elem_vals' property can be set to any associative
 *            array (well, 'string-indexed' array in PHP). Normally this will be
 *            a row from a DB result set. Conviniently, if any column names in
 *            the DB table then match one of the indexes passed to 'add_element', the
 *            element will magically have the value from the DB filled in. This
 *            goes for SELECT, RADIO, etc. elements also which will be set to the
 *            right value.  
 *        $fext->posted_vars: the array posted_vars is set by the constructor.
 *            This will be the global $HTTP_POST_VARS by default. If you want to
 *            set this to an array of your own choosing then go ahead. Again, any
 *            keys in 'posted_vars' that match the keys in '_elems' will result in
 *            that form element having the value from 'posted_vars'
 *            pre-filled-in. This comes in handy when there is a validation error. 
 *        $fext->elem_default_vals: use set_elem_default_vals() to set any value
 *            for a form element to be used if neither of the above match.
 *        nothing: if neither above matches, the field is left blank or
 *            unselected.
 *
 *    Field sizes/lenghts are automatically calculated from the value of
 *    'max_size' and the public array 'field_sizes'. Field sizes should be set to
 *    the column length in the DB for that field (* our extension to PEAR/DB
 *    providing the get_column_lengths() method makes this a breeeeze).  Also,
 *    the attrib 'size' and 'maxlength' can be set for individual elements (see
 *    add_element() below) Basically whichever is less (max length or the value
 *    of field_sizes[current_fieldname] or attribs[max_length]) is used to set
 *    the element's length.
 *
 *    Form element names will be prefixed with the value of the property
 *    'field_prefix' (default "f_") - this is to differentiate them from other
 *    vars in the handler script and avoid sloppy-coding errors.
 *    (Originally to try and avoid register_globals traps, this is now usually
 *    off anyway, but just in case :
 *        TURN register_globals OFF!!! )
 *
 *    HTML output can be customized by extending this class and over-riding any
 *    of the appropriate methods. Most likely these would be the one dealing with
 *    table sections rather than form elements (table_start(), table_row_begin(),
 *    table_row_middle(), table_row_end(), end()). Better yet use Smarty and 
 *    the get_struct() method.
 *
 *    The class formex_field provides the numerous handler methods for the various
 *    field types. Each element is cast as a member object of the formex_field class.
 *
 * @changelog
 * 2.0 Thu Sep 25 2008
 *      - remove all interdependency with mosh_tool, and bring in the validation() 
 *      and get_submitted_vals() code from that with claneup to do the job. 
 *      - bring in a lot of the old mosh_tool validation functions like 
 *      is_proper_date() in as static methods.
 *      - add private/protected keywords where appropriate
 * 1.6 Tue Sep 16 2008
 *      - add get_submitted_vals() and validation() methods to handle invocation of mosh_tool to do the validation, removing some of the tedious steps in this common process
 * 1.4  Thu Jan 13 2005 
 *      - add set_elem_vals() method which was sorely needed
 *      - add $instance_token and logic to include a unique identifier with each invocation 
 *      - updated comments in this file for phpdocumentor style
 *
 *
*/
// $Id: formex.class.php,v 1.5 2007/02/28 20:04:40 sbeam Exp $

// used for error conditions of individual form elements
define ('FORMEX_FIELD_NOERR', 0); // no error
define ('FORMEX_FIELD_REQUIRED', 1); // no error, but field is required
define ('FORMEX_FIELD_ERROR', 2); // field was required, there has been an error

require_once('formex_field.class.php');
require_once('formex_tag.smarty.php');

/**
 *  formex() - form controller class
 *
 *   creates a dynamic XHTML form based on arrays of elements and pre-set values
 *   which can be easily passed to a template system or dumped to the client.
 *
 * @author S Beam <sbeam@onsetcorps.net>
 * @version 1.4
 * @access public
 * @package formex
 */
class formex extends PEAR 
{

    /**
     * @var string $left_td_style the html STYLE attribute of the left table column cells (labels)
     */
    var $left_td_style = "background: #999999; font-family: sans-serif";

    /**
     * @var string $left_td_class the html class attrib of the left table column cells
     */
    var $left_td_class = "formLabelCell";

    /**
     * @var string $right_td_style ditto for the right side
     */
    var $right_td_style = "";

    /**
     * @var string $table_width width of the wrapper table, for display()ed forms
     */
    var $table_width = '';

    /**
     * @var string $field_prefix to be prefixed to the name attribute of each form element
     */
    var $field_prefix = "f_";

    /**
     * @var string $element_class class atttribute of each HTML form element
     */
    var $element_class = "fexter"; // historical.

    /**
     * @var string $label_class class atttribute of text labels for non-required fields
     */
    var $label_class = "formLabel";

    /**
     * @var string $label_class_req class atttribute of form label text for required fields 
     */
    var $label_class_req = "formLabelReq";

    /**
     * @var string $label_class_err class atttribute of form label text for fields w/ errors 
     */
    var $label_class_err = "formLabelErr";

    /**
     * @var classname for the CSS tooltips/helps optionally added after field labels 
     */
    var $label_tooltip_class = "formexHelpToolTip";

    /**
     * @var bool add a "*" after the label for each required field?
     */
    var $label_add_star_for_required_fields = true;

    /**
     * @var string class for the star used in required labels if selected
     */
    var $label_star_class = "formLabelStar";


    /**
     * @var integer $max_size the max size (length) for any text/textarea as displayed - some may be less
     */
    var $max_size = 40;

    /**
     * @var boolean $show_reset_button set to a true value to display the form's reset button (only use it for pre-filled-out forms)
     */
    var $show_reset_button = false;

    /**
     * @var integer $cols_to_span set colspan attrib to for field_heading and field_submit - if you have overridden table_start() or table_end() you may need this
     */
    var $cols_to_span = 2;

    /**
     * @var array $_elems \private - the array of form elements and their params
     */
    var $_elems = array();

    /**
     * @var array $elem_vals set the values for the elements (keys) to the corresponding values (pass array directly from $db->fetchRow)
     */
    var $elem_vals = array();

    /**
     * @var array $field_sizes map of column_name => column_length - will limit sizes and maxlengths of applicable elements
     */
    var $field_sizes = array();

    /**
     * @var string $form_name the name attrib of the FORM tag
     */
    var $form_name = "fexter";

    /**
     * @var string $form_method the method attribute
     */
    var $form_method = "POST";

    /**
     * @var string $form_action the action attribute - set to $PHP_SELF in constructor
     */
    var $form_action = "";

    /**
     * @var string $encoding type of encoding to be used on this form - auto-set as needed.
     */
    var $encoding = "application/x-www-form-urlencoded";

    /**
     * @var array $extra_form_attribs any extra attributes to be included in form tag (array, assoc) - onSubmit, etc
     */
    var $extra_form_attribs = array();

    /**
     * @var boolean whether or not to automatically find field values in posted_vals array
     */
     var $autodetect_posted_vals = true;

    /**
     * @var array $elem_extra_attribs any extra attributes to be included for certain elements (elem_name => str)
     */
    var $elem_extra_attribs = array();

    /**
     * @var array $elem_default_vals any 'default' value for certain elements that are otherwise empty (elem_name => str)
     */
    var $elem_default_vals = array();

    /**
     * @var boolean $show_file_dims show dimensions (exact or max) of image_upload fields in field_label (display() only) 
     */
    var $show_file_dims = true;
    
    /**
     * @var array $_onload_funcs array of functions to add to this window's onload event - special JS required
     */
    var $_onload_funcs = array();

    /**
     * @var array $_preload_funcs array of functions to be run at the top of the form inside <script> tags (for RTE)
     */
    var $_preload_funcs = array();

    /**
     * @var boolean $_require_extra_js flag to include special JS files - only needed for certain fields
     * @access private
     */
    var $_require_extra_js = false;

    /**
     * @var string $extra_js_src_dir URL path to formex_extras.js file, if needed
     */
    var $extra_js_src_dir = "";

    /**
     * @var string $rte_js_src_dir URL path to richTextEditor.js file, if needed
     */
    var $rte_js_src_dir = '/admin/sicoma/rte';

    /**
     * @var boolean $js_src_inline set to true to look for formex_extras.js and richTextEditor.js in include_path, and inline the contents
     */
    var $js_src_inline = false;

    /**
     * @var boolean $_has_richTextEditor flag to track whether or not this form includes a RTE component.
     * @access private
     */
    var $_has_richTextEditor = false;

    /**
     * @var string $icons_dir directory with the icons formex() needs for image_upload fields, etc.
     */
    var $icons_dir = '/icons/';


    /**
     * @var boolean do create an instance token/id for this form for checking dupes
     */
    var $do_instance_token = false;

    /**
     * @var string the instance token itself
     */
    var $instance_token = null;

    /**
     * @var string name of hidden field to add to uniqid this instance
     */
    var $form_instance_token_key = 'fex_instance_token';

    /** @var name of class that hols formex_field objects! set to a new value
     * if you'd like to subclass formex_field()
     */
    var $formex_field_classname = 'formex_field';

    /** use only XHTML? */
    var $strict_xhtml_mode = true;


   /**
    * sets up the posted_vars array and sets the form action to $PHP_SELF to
    * begin w/
    * 
    * @param mixed $posted_vars ref to array with the pre-set values for the
    *                           form - could be set to $HTTP_GET_VARS or your 
    *                           other favorite array
    *                    <p><b>-or-</b></p>
    *                     one of either "GET" or "POST" (default) - will set
    *                     posted_vars as appropriate and also set form_method 
    * @param string optional name of formex_field() class extenstion to use
   */
    function formex($posted_vars = "POST", $field_class=null) {
        if (is_array($posted_vars)) {
            $this->posted_vars = &$posted_vars;
        }
        else {
            if (!empty($posted_vars)) {
                switch ($posted_vars) {
                    case "GET":
                        $meth = "GET";
                        break;
                    default:
                        $meth = "POST";
                }
            }
            $this->form_method = $meth;
            $this->posted_vars = &$GLOBALS["_${meth}"];
        }
        if (isset($GLOBALS["PHP_SELF"])) { // if register_globals is on
            $this->form_action = $GLOBALS["PHP_SELF"];
        }
        else {
            $this->form_action = $_SERVER['PHP_SELF'];      // if not
        }

        if ($field_class) {
            $this->formex_field_classname = $field_class;
        }

        if ($this->do_instance_token) {
            $this->instance_token = md5(uniqid(rand(), true));
        }

        $this->db_row = &$this->elem_vals; // bc compat

        $this->back_compat_uc_field_keys = (defined('FORMEX_BACK_COMPAT_UC_FIELD_KEYS') and FORMEX_BACK_COMPAT_UC_FIELD_KEYS);
    }


    /**
    * adds one or more elements to the form (via the _elems() array). Each _elems() should
    * consist of ("element_name" => params_array() )
    * where a params array() is
    *   0 => Field description
    *   1 => field type (hidden, text, select, select_or, etc)
    *   2 => array options() - if field_type is a select,select_or,radio,etc this is a
    *           ass. array of ("option_name" => "Option Value), i.e.
    *           ("foo" => "I like foo", "bar" => "I prefer the bar")
    *       * for toggle,checkbox and hidden opts can be a string that sets the default value param.
    *       * for date and date_us, opts is an int indicating the time "length" - see docs
    *       * for expandable_fieldset, opts is the sub array of params - see docs
    *       * not needed for any other types
    *   3 => array attribs() - optional list of name/value pairs for tag attributes (size, maxlength, wrap, etc)
    *        and some formex-specific attribs (top_value, numsets, etc.) - see docs
    *   4 => extra_attribs - str optional any extra stuff to be added to the end of the opening tag
    *   ? => is_required flag  (the is_required flag always comes last) - optional
    * @see User Documentation and examples
    * 
    * example:
    *   * Add one element:
    *   $fex->add_element("address1", array("Street Address", "text", 1);
    *   $fex->add_element("colors", array("Fav. color", "select", array('red','blue'), 1);
    * 
    *   * Add a bunch of elements:
    *      $colmap = array(
    *          "somedate" =>    array('enter a date', 'date', 10, 1),
    *          "color" =>       array('pick a color', 'colorpicker'),
    *          "country" =>     array('Your Country', 'country_select'),
    *          "comments" =>    array('Comments', 'richTextEditor', 1, array('rows'=>14)),
    *          "secret" =>      array('hiddenthing', 'hidden', 'sekret kode X14'),
    *          "your_pic" =>    array('Upload your pic here', 'image_upload', 1),
    *          "op_do_it" =>    array('SEND IT', 'submit'));
    *      $fex->add_element($colmap);
    * 
    * @param mixed $name if array, should be string-indexed, which each value being a params array . If string, then it is a new element name
    * @param array $val if $name is a string, then $val should be a params array
    * 
    */
    function add_element ($name,$val='') 
    {
        if (!is_array($name)) {
            $name = array($name=>$val);
        }

        // name of the class to instantiate, like 'formex_field'
        $fieldclass = $this->formex_field_classname;

        // add eack k=>v to _elems
        while (list($k,$v) = each ($name)) {
            $this->_elems[$k] = new $fieldclass($this, $k, $v);

            switch ($this->_elems[$k]->type) {
                case 'file':
                    $this->encoding = "multipart/form-data";
                    break;
                case 'image_upload':
                    $this->encoding = "multipart/form-data";
                    $this->_require_extra_js = 1;
                    break;
                case 'colorpicker':
                    $this->_require_extra_js = 1;
                    break;
                case 'select_bicameral':
                    $this->_require_extra_js = 1;
                    $this->set_extra_form_attribs('onsubmit', 'formexBicameralSelectAll()');
                    break;
                case 'calendar':
                    $this->_require_extra_js = 1;
                    $this->_require_calendar_js = 1;
                    break;
                case 'autocomplete':
                    $this->_require_extra_js = 1;
                    $this->_require_autocomplete_js = 1;
                    break;
                case 'expandable_fieldset':
                    $this->encoding = "multipart/form-data"; // hack in case we have Files
                    $this->_onload_funcs['expandable_fieldset'] = 'initFields();';
                    $this->_require_extra_js = 1;
                    break;
                case 'captcha':
                    $this->_require_extra_js = 1;
                    break;
                case 'richTextEditor':
                    #$this->set_extra_form_attribs('onsubmit', 'getRteContent()');
                    #$this->_preload_funcs['richTextEditor'] = 'initRTE();';
                    $this->_has_richTextEditor = 1;
            }
        }
    }


    /** 
    * sets form element with identifier $elem to the predifined error state using set_error()
    * @param string $elem name of element to set errorstate
    */
    function set_error_state($elem) 
    {
        if (!isset($this->_elems[$elem])) $this->raiseError("element '$elem' does not exist", E_USER_WARNING);
        else $this->_elems[$elem]->set_error();
    }

    /** 
    * returns the starting form tag. Uses the form_name, form_method,
    * form_action, form_encoding and extra_form_attribs props
    * @return string HTML fragment containing complete <FORM> tag and maybe a bonus
    */
    function form_start() 
    {
        $extras = "";
        if (count($this->extra_form_attribs)) {
            foreach ($this->extra_form_attribs as $k => $v) {
                $extras .= " $k=\"$v\"";
            }
        }
        $res = sprintf("<form name=\"%s\" method=\"%s\" action=\"%s\" enctype=\"%s\"%s>\n",
                        $this->form_name,
                        $this->form_method,
                        $this->form_action,
                        $this->encoding,
                        $extras
                        );

        // little JS if needed to initialize RTE or expanders fields
        if (count($this->_onload_funcs)) {
            $res .= "\n<script type=\"text/javascript\">\nwindow.onload=function() {\n";
            $res .= join ("\n", array_values($this->_onload_funcs));
            $res .= "\n}\n</script>\n";
        }
        if (count($this->_preload_funcs)) {
            $res .= "\n<script type=\"text/javascript\">\n";
            $res .= join ("\n", array_values($this->_preload_funcs));
            $res .= "\n</script>\n";
        }
        return $res;
    }

    /** 
    * returns the starting table tag. May be over-ridden
    * @return string HTML fragment
    */
    function table_start() 
    {
        return sprintf("<table cellpadding=\"3\" cellspacing=\"2\" border=\"0\" %s>\n",
                        ($this->table_width)? 'width="'.$this->table_width.'"' : '' );
    }

    /** 
    * returns the starting &lt;tr> and &lt;td> tags for a row. May be
    * over-ridden. Uses left_td_style prop and automatically makes the colspan=2
    * if this is a submit button 
    * @return string HTML fragment
    */
    function table_row_begin($colspan=0, $align='left') 
    {
        return sprintf("<tr><td style=\"%s\" valign=\"top\" align=\"%s\" class=\"%s\" %s>", 
                        $this->left_td_style,
                        $align,
                        $this->left_td_class,
                        ($colspan)? "colspan=\"$colspan\"" : "");
    }
    /** 
    * returns the ending &lt;td> tag and the new &lt;td> for a row. May be over-ridden.
    * Uses right_td_style prop
    * @return string HTML fragment
    */
    function table_row_middle() 
    {
        return sprintf("</td>\n<td style=\"%s\">", $this->right_td_style);
    }
    /** 
    * returns the end td and tr tags for each row. May be over-ridden
    * @return string HTML fragment
    */
    function table_row_end() 
    {
        return "</td></tr>\n\n";
    }

    /*! 
     * returns the label for each field. 
     * @param elem obj the formex_field element to be labelled
    * @return string HTML fragment <label> tag
    * @access private
    */
    function field_label(&$elem)
    {
        switch ($elem->error_state) {
            case FORMEX_FIELD_REQUIRED:
                $class = $this->label_class_req;
                break;
            case FORMEX_FIELD_ERROR:
                $class = $this->label_class_err;
                break;
            default:
                $class = $this->label_class;
        }

        $star = '';
        if ($this->label_add_star_for_required_fields and $elem->error_state == FORMEX_FIELD_REQUIRED) {
            $star = sprintf('<span class="%s">*</span>', $this->label_star_class);
        }

        $help = '';
        if (!empty($elem->help_text)) {
            $help = sprintf('&nbsp;<a class="%s" href="#" onclick="return false">[?]<span>%s</span></a>',
                            $this->label_tooltip_class,
                            htmlentities($elem->help_text));
        }
        return sprintf("<label class=\"%s\" for=\"%s\">%s%s</label>%s",
                        $class, 
                        $elem->fname, 
                        $elem->descrip, 
                        $star,
                        $help);
    }

    
    /**
    * calls member functions to start the form and table
    * May be overridden
    * @return string HTML fragment
    */
    function start() 
    {
        return $this->form_start() . $this->table_start();
    }

    /**
    * html to end the form and table, and includes the hidden fields if
    * any
    * May be overridden
    * @return string HTML fragment
    */
    function end($hid) 
    {
        return "$hid\n\n </table></form>";
    }

    /**
    * calls $this->_htmlentities() on $val but only if it is not an array
    * @param string $val
    * @access private
    */
    function _html_encode(&$val) 
    {
        if (is_array($val)) { return $val; }
        else                { return $this->_htmlentities($val); }
    }


    /**
    * looks in field_sizes array for $col, if fould returns the val
    * @param string $col
    * @return integer corresponding field size for the given element
    * @access private
    */
    function _get_field_length($col) 
    {
        if (isset($this->field_sizes[$col])) {
            return $this->field_sizes[$col];
        }
        else {
            return 205; // TODO
        }
    }

    /**
    * this is where we look in $elem_vals and $posted_vals and elem_default_vals for
    * any keys that match $col. If found, return the value(s). In case of date
    * types, concatinate the Y,M+D values into a string
    * @param Object $ff formex_field object
    * @param string $colname name of corresponding field maybe found in elem_vals
    * @return string the value attrib for this element
    * @access private
    */
    function _find_form_value(&$ff, $colname) 
    {

        // what should the form field value be pre-set to?
        if (isset($this->elem_vals[$colname])) { // we seem to have a matching key from the DB
            //print "found $colname in elem_vals\n";
            $fval = $this->elem_vals[$colname];  
        }
        else if (isset($this->posted_vars[$ff->fname])) {// there was a POST that matches this 
            $fval = ($this->autodetect_posted_vals)? $this->posted_vars[$ff->fname] : '';
        }
        else if (!empty($this->posted_vars[$ff->fname . "_month"]) && 
                 ($ff->type == 'date' or $ff->type == 'date_us')) { // its a DATE field
            if ($this->autodetect_posted_vals) {
                if (isset($this->posted_vars[$ff->fname . "_day"])) {
                    $day = sprintf("%d", $this->posted_vars[$ff->fname . "_day"]);
                }
                else {
                    $day = 1;
                }
                $fval = array(sprintf("%04d", $this->posted_vars[$ff->fname . "_year"]),
                        sprintf("%d", $this->posted_vars[$ff->fname . "_month"]),
                        $day); 
            }
        }
        // a 'default' value was given via set_elem_default_vals() 
        // ("Enter text here" or whateva)
        else if (!empty($this->elem_default_vals[$ff->fname])) {
            $fval = $this->elem_default_vals[$ff->fname];
        }
        else {
            $fval = ''; // must be adding a new one
        }
        return $fval;
    }


    /**
    * writes <script> tags as appropriate to get JS for RTE and other special magic fields
    * flags to use these are set in add_element() above
    * @return string HTML fragment for JS inclusion
    * @access private
     */
    function _js_script_tags() 
    {
        $res = '';
        if ($this->_require_extra_js) {
            if ($this->js_src_inline) {
                if (!$content = file_get_contents('formex_js/formex_extras.js', 1)) { // look in include_path too
                    $content = "alert('ERROR: formex() formex_extras.js count not be found in include_path')";
                }
                $res .= "<script type=\"text/javascript\">\n<!--\n$content\n-->\n</script>";
            }
            else {
                $res = sprintf('<script type="text/javascript" src="%s/formex_extras.js"></script>',
                                $this->extra_js_src_dir);
            }
        }
        if (isset($this->_require_calendar_js)) {
            $dir = $this->extra_js_src_dir . '/dynarchCalendar';
            $res .= '
            
           <script type="text/javascript">
              function initCalendarSetup(elem, format, showtime) {
                  if (!elem.isSetup) {
                      Calendar.setup({
                        inputField     :    elem.id,   // id of the input field
                        ifFormat       :    format,       // format of the input field
                        showsTime      :    showtime, // show the time selector?
                        timeFormat     :    12
                        });
                      elem.isSetup = true;
                  }
              }
           </script>
           <script type="text/javascript" src="'.$dir.'/calendar.js"></script>
           <script type="text/javascript" src="'.$dir.'/lang/calendar-en.js"></script>
           <script type="text/javascript" src="'.$dir.'/calendar-setup.js"></script>
           <link rel="stylesheet" type="text/css" href="'.$dir.'/calendar-blue2.css">';
        }
        if (isset($this->_require_autocomplete_js)) {
            $dir = $this->extra_js_src_dir;
            $res .= '<script type="text/javascript" src="'.$dir.'/autocomplete.js" ></script>';
        }
        if ($this->_has_richTextEditor) {
            $js = 'tinymce/jscripts/tiny_mce/tiny_mce.js';
            $js2 = 'rte-tinymce.js';
            $css = 'tinymce/jscripts/tiny_mce/tiny_mce.js';
            if ($this->js_src_inline) {
                $content = "alert('formex(): js_src_inline is deprecated for _field_richTextEditor')";
                $res = '<script type="text/javascript"><!--' . "\n";
                $res .= $content . "\n// -->\n";
                $res .= '</script>';
            }
            else {
                $res .= "<script type=\"text/javascript\" src=\"{$this->rte_js_src_dir}/$js\"></script>
                         <script type=\"text/javascript\" src=\"{$this->rte_js_src_dir}/$js2\"></script>
                         <link rel=\"stylesheet\" type=\"text/css\" href=\"{$this->rte_js_src_dir}/$css\">";
            }
        }
        return $res;
    }


    /**
     * assembles and returns a "hash-of-hashes" which can be used nicely in Smarty templates
     * example returned array:
     *  Array 
     *  ("name" => Array (
     *                    "LABEL" => "Your Name",
     *                    "TAG" => &lt;input type="text" name="f_name" value="previous val" size="40" maxlength="40" class="fexter">
     *                    "STATUS" => 2
     *                    ),
     *   "email" => Array (
     *                    "LABEL" => "Email Address",
     *                    "TAG" => &lt;input type="text" name="f_email" value="" size="40" maxlength="40" class="fexter">
     *                    "STATUS" => 1
     *                    ),
     *   "FORM" => &lt;form name="fexter" method="POST" action="self.php" enctype="application/x-www-form-urlencoded">
     *   "HIDDENS" => &lt;input type="hidden" name="f_hid1" value="1">&lt;input type="hidden" name="f_hid2" value="96528">
     *  );
     * the STATUS value is one of
     *  FORMEX_FIELD_NOERR: an optional field
     *  FORMEX_FIELD_REQUIRED: a required field
     *  FORMEX_FIELD_ERROR: a required field that is in error somehow (empty or bad format)
     @return array structure with all HTML form elements and any needed info about those elements
    */
    function get_struct() 
    {
        
        $fields = array();
        $fields["HIDDENS"] = '';

        if ($this->do_instance_token) {

            if (empty($this->instance_token)) $this->instance_token = md5(uniqid(rand(), true));

            $this->add_element($this->form_instance_token_key, array('token', 'hidden'));
            $this->set_elem_value($this->form_instance_token_key, $this->instance_token);
        }

        reset($this->_elems);
        foreach ($this->_elems as $col => $ffield) {

            // one of 3 classes are possible depending on error condition ($status)
            if ($ffield->error_state == FORMEX_FIELD_NOERR) {
                $class = $this->label_class; 
            }
            elseif ($ffield->error_state == FORMEX_FIELD_ERROR) {
                $class = $this->label_class_err;
            }
            else {
                $class = $this->label_class_req;
            }

            $fval = $this->_find_form_value($ffield, $col);


            if ($ffield->type == 'submit') { // submit buttons are very weird
                $fval = $ffield->descrip; // fool it into putting the "description" on the button
                $fields[$col] = Array("op_name" => $ffield->fname, // use OP_NAME to set the button's name if needed
                                      "status" => $ffield->error_state,
                                      "class" => $class,
                                      "type" => 'submit',
                                      "name" => $col);
            }
            else {
                $fields[$col] = Array("label" => $ffield->descrip,
                                      "status" => $ffield->error_state,
                                      "class" => $class,
                                      "type" => $ffield->type,
                                      "name" => $col);
            }

            // get the form element from the approved method
            if (!method_exists($ffield, 'get_html')) {
                $this->raiseError("'$ffield' is not a field object.", E_USER_ERROR);
            }
            $fields[$col]["tag"] = $ffield->get_html($fval);
            //
            // put the hidden fields in their own special place also
            if ($ffield->type == 'hidden') {
                $fields["HIDDENS"] .= $fields[$col]["tag"];
            }

            /* uppercase LABEL, STATUS, etc. for smarty templates that still expect that. LAME */
            if ($this->back_compat_uc_field_keys) {
                foreach ($fields[$col] as $k => $v) {
                    $fields[$col][strtoupper($k)] = $v;
                    //unset($fields[$col][$k]);
                }
            }

        }
        $fields["FORM"] =  $this->_js_script_tags() . $this->form_start();

        return $fields;
    }






    /**
    * assembles and returns html for the entire form
    * @return string a block of HTML from <form> to </form> with a complete formex() instance entirely built and ready to include in a page
    */
    function render_form () 
    {
        $hiddens = '';

        // add hid field for the token if needed
        if ($this->do_instance_token) {
            $this->add_element($this->form_instance_token_key, array('token', 'hidden'));
            $this->set_elem_value($this->form_instance_token_key, $this->instance_token);
        }

        $res = $this->_js_script_tags();

        $res .= $this->start();

        reset($this->_elems);
        while (list($colname, $elem) = each($this->_elems) ) { 

            $fval = $this->_find_form_value($elem, $colname);

            // add exact dims to file fields if need be
            if (($elem->type == 'file' or $elem->type == 'image_upload') && $this->show_file_dims) {
                if (isset($elem->attribs["exact_dims"])) { 
                    $elem->descrip .= "<br>(" . $elem->attribs["exact_dims"] . " exact)";
                }
                elseif (isset($elem->attribs["maxdims"])) { 
                    $elem->descrip .= "<br>(" . $elem->attribs["maxdims"] . " max)";
                }
                elseif (isset($elem->attribs["resize_method"]) && isset($elem->attribs["dims"])) { 
                    $elem->descrip .= sprintf("<br>(%s to %s)", $elem->attribs["resize_method"], $elem->attribs["dims"]);
                }
                if (isset($elem->attribs["thumb_method"]) && isset($elem->attribs["thumb_dims"])) { 
                    $elem->descrip .= sprintf("<br>thumb:(%s to %s)", $elem->attribs["thumb_method"], $elem->attribs["thumb_dims"]);
                }
            }
            
            // check for submits - they get special colspan=2 treatment in the table row
            if ($elem->type == 'submit') { // submit buttons are very weird
                $res .= $this->table_row_begin($this->cols_to_span, 'right');
            }
            elseif ($elem->type == 'heading') { // code to implement a header
                $res .= $this->table_row_begin($this->cols_to_span);
            }
            elseif ($elem->type == 'table_break') { // code to implement a header
                $res .= '</table>';
                if (isset($elem->attribs['divider_html'])) $res .= $elem->attribs['divider_html'];
                else $res .= '</td><td valign="top">';
                $res .= $this->table_start() .  $this->table_row_begin($this->cols_to_span);
                $elem->type = 'heading';
            }
            elseif ($elem->type == 'hidden') { // add html to hiddens string and get out
                $hiddens .= $elem->get_html($fval);
                continue;
            }
            elseif ($elem->type == 'captcha') {
                $res .= $this->table_row_begin($this->cols_to_span);
                $res .= "<img id=\"captchaImage\" onclick=\"toggleCaptchaHelp()\" src=\"".$elem->attribs['img_url'] ."\" align=\"left\"/>";
                $res .= $this->field_label($elem);
                $res .= '<br />';
            }
            else { // put out the left side of the table row
                $res .= $this->table_row_begin();
                $res .= $this->field_label($elem);
                $res .= $this->table_row_middle();

                // set the field size as needed
                if (!isset($elem->attribs['maxlength'])) {
                    $elem->set_field_length($this->_get_field_length($colname));
                }
            }

            // get the form element from the approved method
            $res .= $elem->get_html($fval);

            $res .= $this->table_row_end();

        } // end while()

        $res .= $this->end($hiddens);
        return $res;

    }

    /**
    * calls print() on render_form()
    * @void
    */
    function display () 
    {
        print $this->render_form();
    }


    /**
     * grab an array of all formex_field objects we are responsible for, indexed by name
     * @return array
     */
    function get_elems()
    {
        $elems = array();
        foreach ($this->_elems as $k => $e) {
            $elems[$k] = $e;
            unset ($elems[$k]->fex);
        }
        return $elems;
    }

    /**
     * sets options for a select*, radio, or other element that takes an array
     * of options.
     * @param string $elem name of the element
     * @param string $opts array of the options, may be linear or assoc
    */
    function set_element_opts($elem, $opts) 
    {
        if (!isset($this->_elems[$elem])) {
            trigger_error("element '$elem' does not exist", E_USER_NOTICE);
            return;
        }
        $this->_elems[$elem]->opts = $opts;
    } 
    
    
    /**
     * set a single attribute of the given element
     * $fex->set_elem_attrib('op_something', 'show_reset_button', 1)
     * @param string $elem name of the element
     * @param string $attrib name of attibute to set
     * @param string $val value of attibute
    */
    function set_elem_attrib($elem, $attrib, $val) 
    {
        if (!is_object($this->_elems[$elem])) {
            $this->raiseError("$elem is not a field object");
        }
        $this->_elems[$elem]->attribs[$attrib] = $val;
    }
    
    /**
     * set the 'help_text' attrib for a given element. This could be displayed however the view/template wants to do it.
     * @param string $elem name of the element
     * @param string $txt text to set
     */
    function set_elem_helptext($elem, $txt)
    {
        if (isset($this->_elems[$elem]) && is_object($this->_elems[$elem])) {
            $this->_elems[$elem]->help_text = $txt;
        }
    }
    
    /**
     * adds an element to the extra_forms_attribs array, which will add extra
     * attribs to the FORM tag (onSubmit, etc.). 
     * 
     * @param string/array $name Pass an assoc array of attrib=>value pairs, or one pair as ($name, $value)
     * @param string $val optional value of the attrib, if $name is a string
    */
    function set_extra_form_attribs ($name, $val) 
    {
        if (is_array($name)) {
            // add eack k=>v to _elems
            while (list($k,$v) = each ($name)) {
                $this->extra_form_attribs[$k] = $v;
            }
        }
        else {
            $this->extra_form_attribs[$name] = $val;
        }
    }




    /**
     * set the official value attrib of the given element
     * @param mixed $elem name of the element or array (k>v) of element name=>value pairs
     * @param string $str what it's "value" attribute should be (or content, in case of 
                          textarea, etc) - leave empty in case first argument is array
    */
    function set_elem_value($elem, $str=null) 
    {
        if (is_array($elem) and !$str) {
            foreach ($elem as $k => $v) {
                $this->set_elem_value($k, $v);
            }
            return;
        }
        $this->elem_vals[$elem] = $str;
    }

    /**
     * any extra attributes that need to be put in a element's tag can be added here:
     * $fex->set_elem_extra_attribs('somefield', 'onClick="alert(\'You clicked me\')");
     * @param string $elem name of the element
     * @param string $str the special attributes that should be included (attrib=something)
    */
    function set_elem_extra_attribs($elem, $str) 
    {
        if (!isset($this->_elems[$elem])) $this->raiseError("$elem is not defined");
        $this->_elems[$elem]->extra_attribs = $str;
    }

    /**
     * set the last, default value to be used for an element here if both $elem_vals
     * and $posted_vals are empty - to be used for "Enter Here"  text and similar nonsense.
     * $fex->set_elem_default_vals('occupation', 'Enter here!');
     * @param string $elem name of the element
     * @param string $str value attrib string
    */
    function set_elem_default_vals($elem, $str) 
    {
        $this->elem_default_vals[$this->field_prefix . $elem] = $str;
    }

    /** just a relay for a static function of formex_field::array_stringify()
     * that can be called on this class too for conveiencec
     */
    /* static */ function array_stringify($arr) 
    {
        return formex_field::_array_stringify($arr);
    }


    /** util function to get a $colmap-style array corresponding to the current
     * array of form elements this instance knows about. Can be passed to
     * mosh_tool for validation purposes. 
    */
    function get_colmap() {
        if (empty($this->_colmap)) {
            $this->_colmap = array();
            foreach ($this->_elems as $name => $e) {
                $req  = ($e->error_state == FORMEX_FIELD_REQUIRED)? true : false;
                $this->_colmap[$name] = array($e->descrip, $e->type, $e->opts, $e->attribs, $e->extra_attribs, $req);

            }
        }
        return $this->_colmap;
    }


    
     function get_country_opts($iso=false, $code=null)
     {
         return formex_field::_get_countries($iso, $code);
     }


    /**
     * use mosh_tool to check $posted against the current instance's colmap versi on for validity
     * @param $posted array the values to be checked (usually $_POST or $_GET)
     * @return false if passed, otherwise an list of error messages
     */
    function validate($posted)
    {
        $errs = array();

        if (!is_array($posted)) {
            trigger_error("check_form(): argument is not an array", E_USER_NOTICE);
            return false;
        }

        foreach (array_keys($this->_elems) as $k) {

            if ($this->_elems[$k]->error_state === FORMEX_FIELD_REQUIRED) {

                $ff = $this->field_prefix . $k;  //shorthand
                $ferr = null;

                switch ($this->_elems[$k]->type) {

                    case 'date':
                    case 'date_us':
                        $day = (!empty($this->_elems[$k]->attribs['suppress_day']))? '01' : sprintf("%d", $posted[$ff . "_day"]);

                        $datefields = array(sprintf("%d", $posted[$ff . "_month"]),
                                            $day,
                                            sprintf("%04d", $posted[$ff . "_year"])
                                            ); 

                        if (!self::is_proper_date($datefields)) {
                            $ferr = "'%s' is not a valid date.";
                        }
                        break;

                    case 'date_text':
                        if (!self::is_proper_date($posted[$ff])) {
                            $ferr = "'%2\$s' in '%1\$s' is not a valid date.";
                        }
                        break;

                    case 'numeric':
                        if (!empty($posted[$ff]) && !is_numeric(trim($posted[$ff]))) {
                            $ferr = "'%s' must be a numeric value."; 
                        }
                        break;

                    case 'file':
                    case 'image_upload':
                        if (!isset($_FILES[$ff])) {
                            $ferr = "%s' must be uploaded";
                        }
                        break;

                    case 'select_or':
                        if (empty($posted[$ff]) and empty($posted[$ff.'_aux'])) {
                            $ferr = "'%s' is a required field.";
                        }
                        break;

                    case 'state_abbr': // if US or Canada based on "country", make sure is a 2-letter abbr.
                        if (strlen($posted[$ff]) != 2) {
                            if (empty($posted['country']) or $posted['country'] == 'US') {
                                $ferr = "Please use your 2-letter state abbreviation";
                            }
                            elseif (!empty($posted['country']) and $posted['country'] == 'CA') {
                                $ferr = "Please use your 2-letter Canadian province abbreviation";
                            }
                        }
                        break;

                    case 'email':
                        if (!empty($posted[$ff]) && !self::is_proper_email($posted[$ff])) {
                            $ferr = "'%2\$s' is not a valid email address. Please enter a complete
                                        email address, i.e. 'jdoe@example.com', in the '%1\$s' field.";
                        }

                    default:
                        if (isset($posted[$ff]) && is_array($posted[$ff]) && 0 == count($posted[$ff])) {
                            $ferr = "'%s' is a required selection.";
                        }
                        elseif (!isset($posted[$ff]) or (is_string($posted[$ff]) && strlen(trim($posted[$ff])) == 0)) {
                            $ferr = "'%s' is a required field.";
                        }
                }

                if ($ferr) {
                    $this->_elems[$k]->set_error();
                    $errs[] = sprintf($ferr, $this->_elems[$k]->descrip, $posted[$ff]);
                }
            }
        }
        if (count($errs) > 0) {
            return $errs;
        }
    }






    /**
     * use mosh_tool to grab the subset of values we are looking for from what
     * was sent to the caller in POST or GET. We are looking only for values
     * whose keys correspond with those in our $colmap.
     * @param $posted array the values to be checked (usually $_POST or $_GET)
     * @return hash of values, indexed by the same keys in $this->_elems
     */
    public function get_submitted_vals($posted) {

        $vals = array();
        foreach (array_keys($this->_elems) as $k) {

            $ff = $this->field_prefix . $k; // shorthand.

            switch ($this->_elems[$k]->type) {

                case 'date':
                case 'datetime':
                    $vals[$k] = self::join_date_fields_to_str($posted, $ff, 0);
                    break;

                case 'date_us':
                    $vals[$k] = self::join_date_fields_to_str($posted, $ff, 1);
                    break;

                // special case for checkboxes which may or may not exist, and need to be 0/1 either way
                case 'toggle':
                case 'checkbox':

                    $val = null;

                    if (!empty($this->_elems[$k]->opts)) {
                        $opt = $this->_elems[$k]->opts;
                        if (is_array($opt))
                            $val = $opt;
                        elseif (is_string($opt)) // bc
                            $val = array(false, $opt);
                    }
                    if (!$val) $val = array(0, 1);
                    $vals[$k] = (isset($posted[$ff]))? $val[1] : $val[0];
                    break;

                case 'calendar':
                    $vals[$k] = date('Y-m-d H:i:s', strtotime($posted[$ff]));

                case 'heading':
                    // do nothing, its a heading
                    break;

                case 'select_or':
                    $aux = $ff . '_aux';
                    $vals[$k] = (!empty($posted[$aux]))? $posted[$aux] : $posted[$ff];
                    break;

                default:
                    if (isset($posted[$ff])) {
                        $vals[$k] = $posted[$ff];
                    }
            }

        }
        return $vals;

    }




    /* ========== lookout the static functions are coming ================== */




    /**
     * tries to make sure a date is properly formatted and valid, based on
     * $date_format and using the native checkdate() function to make
     * sure nobody gives you a Feb.31
     *       
     * @param $date string to be checked in YYYY-MM-DD format or an array like MM,DD,YYYY
     * @return bool
     * @static
     * @public
    */
    static public function is_proper_date($date, $format=null) 
    {
        if (!is_array($date)) { // check string format
            $fmt = ($format)? $format : self::date_format;
            if (!preg_match($fmt, $date, $m)) return;
            list($y,$m,$d) = array($m[1],$m[2],$m[3]);
        }
        else {
            list($m,$d,$y) = $date; // legacy passed in arr. as MM,DD,YY
        }

        return (count($date) == 3 and checkdate($m,$d,$y));
    }


    /**
     * looks for a 'normal' style email address, i.e. has a '@' and a dot.
     * @param $str string to be checked
     * @return bool
     * @static
     * @public
     */
    static public function is_proper_email($str) 
    {
        return (preg_match("/^([-a-zA-Z0-9_.]+)@(([-a-zA-Z0-9_]+[.])+[a-zA-Z]+)$/", $str));
    }





    /**
     * looks for a date or datetime set of fields in the $posted array keys by 
     * the given $k. Finding matches for $k, it be joining 3 fields that make 
     * up a date field in formex() (or 6/7 for a datetime field). It returns 
     * the resultant date as a string which in the default format will go 
     * happily into the date or datetime fields of mysql or pgsql.
     *
     * returns format like "YYYY-MM-DD" by default or "YYYY-MM-DD HH:MM:SS" if 
     * time was included. We don't have any way of knowing the time zone or DST 
     * status right now.
     *
     * @param $posted associative array of keys=>values where our date info might be hiding
     * @param $ff string key/name for the date field
     * @param $us_fmt return the US date format instead (M/D/Y)?
     * @return string
     * @static
     * @public
     */
    public static function join_date_fields_to_str($posted, $ff, $us_fmt=false) 
    {
        if ($us_fmt) {
            $fmt = 'm/d/Y';
        }
        else {
            $fmt = 'Y-m-d';
        }

        if (!isset($posted[$ff . "_year"])) return;


        if (isset($posted[$ff . "_day"])) {
            $d = sprintf("%02d", $posted[$ff . "_day"]);
        }
        else {
            $d = '01';
        }

        $y = intval( $posted[$ff . "_year"] );
        $m = intval( $posted[$ff . "_month"] );

        if (!checkdate($m, $d, $y)) { // throw up a little bit?
            return;
        }

        $h = 0;
        $min = 0;
        $s = 0;

        $has_time = (isset($posted[$ff . "_hours"]));

        if ($has_time) {
            $h = intval($posted[$ff . "_hours"]);
            if (isset($posted[$ff . "_ampm"]) and $posted[$ff.'_ampm'] == 'PM') {
                $h += 12;
            }
            $min = (isset($posted[$ff . "_min"]))? intval($posted[$ff . "_min"]) : 0;
            $s = (isset($posted[$ff . "_sec"]))? intval($posted[$ff . "_sec"]) : 0;
            $fmt .= ' H:i:s';
        }

        $time = mktime($h,$min,$s,$m,$d,$y);

        return date($fmt, $time);

    }







}

// vim: set expandtab tabstop=4 shiftwidth=4 fdm=marker:
