<?php
/*  formex - PHP class for HTML form auto-generation and management
 *
 * Copyright 2000-2005 SBeam, Onset Corps - sbeam@onsetcorps.net
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
 */

/**
 *  formex() - form controller class
 *
 *   creates a dynamic HTML form based on arrays of elements and pre-set values
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
 *    By default, the output of the final display() method is a two-column XHTML
 *    table. Field labels are in the left column and the elements are on right.
 *    Hidden fields are tacked on at the end, and the submit button right-aligned
 *    in the last row, which spans both columns.  The reset button can be turned
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
 *    VALIDATION BUILT-INS
 *    validate() will check each of the submitted values against the definitions
 *    for that form elements, and return an array listing any errors found, as a list of strings.
 *
 *    In add_element(), the fifth argument is a boolean. If true, the field will be checked
 *    by type against the pre-built-in validators for that element type. See validate() for supported
 *    types and other arguments/
 *
 *    VALIDATION CALLBACKS
 *    If you need to write custom validation for certain elements, this can always be done
 *    before you call get_submitted_vals(). Or, you can inform formex() of the existence of 
 *    a validation function - and it will call it for you in validate().
 *
 *    So, any form element created with the attribute 'validate' can be automatically passed
 *    to the given validation function for checking. The value of 'validate' should be
 *    a function or static method as callable by PHP's <call_user_func()>. This function
 *    will be passed 2 arguments, the fieldname and the submitted value. It
 *    should return a string describing the error, if it fails.
 *
 *       $fex->add_element('zipcode', array('ZIP', 'text', null, array('validate' => array('someClass', 'validate_zipcode')), false);
 *       ....
 *       class someClass {
 *         static function validate_zipcode($field, $value) {
 *             if (!empty($value)) {
 *                 if (!preg_match('/^[0-9]{5}$/', $value)) 
 *                     return "Enter a 5-digit US ZIP code";
 *                 elseif (!check_against_real_zipcode_DB($value)) 
 *                     return "Enter a valid US ZIP code";
 *             }
 *         }
 *       }
 *
 * @changelog
 * 2.1 Apr 2010
 *      - add validation callbacks
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


// used for error conditions of individual form elements
define ('FORMEX_FIELD_NOERR', 0); // no error
define ('FORMEX_FIELD_REQUIRED', 1); // no error, but field is required
define ('FORMEX_FIELD_ERROR', 2); // field was required, there has been an error

require_once('formex_field.class.php');

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
    var $field_prefix = "";

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
     * contains elem => msg map of any errors returned by validate()
     */
    var $errors = array();

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
     * if true, incoming values in get_submitted_vals() will be converted to NULL if empty($val) 
     */
    var $convert_empty_string_to_null = false;

    /**
     * path to save uploaded files. can also be set per-file with element attrib 'save_path'
     */
    var $upload_save_dir = '/tmp';


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
                case 'image_upload':
                    $this->encoding = "multipart/form-data";
                    break;
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
        if ($this->label_add_star_for_required_fields and $elem->error_state >= FORMEX_FIELD_REQUIRED) {
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
        $fields["FORM"] =  $this->form_start();

        return $fields;
    }






    /**
    * assembles and returns html for the entire form
    * @return string a block of HTML from <form> to </form> with a complete formex() instance entirely built and ready to include in a page
    */
    function render () 
    {
        $hiddens = '';

        // add hid field for the token if needed
        if ($this->do_instance_token) {
            $this->add_element($this->form_instance_token_key, array('token', 'hidden'));
            $this->set_elem_value($this->form_instance_token_key, $this->instance_token);
        }

        $res .= $this->start();

        reset($this->_elems);
        while (list($colname, $elem) = each($this->_elems) ) { 

            $fval = $this->_find_form_value($elem, $colname);

            // add exact dims to file fields if need be
            if (($elem->type == 'file' or $elem->type == 'image_upload') && $this->show_file_dims) {
                if (isset($elem->attribs["exact_dims"])) { 
                    $elem->descrip .= "<br>(" . $elem->attribs["exact_dims"] . " exact)";
                }
                elseif (isset($elem->attribs["max_dims"])) { 
                    $elem->descrip .= "<br>(" . $elem->attribs["max_dims"] . " max)";
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
        print $this->render();
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
     * check $posted against the current instance's colmap versi on for validity
     * @param $posted array the values to be checked (usually $_POST or $_GET)
     * @return false if passed, otherwise an list of error messages
     */
    function validate($posted=null)
    {
        if (!$posted)
            $posted = $this->posted_vars;

        if (!is_array($posted)) {
            trigger_error("validate(): argument is not an array", E_USER_NOTICE);
            return false;
        }

        foreach (array_keys($this->_elems) as $k) {

            $ferr = null;
            $ff = $this->field_prefix . $k;  //shorthand
            $attribs = $this->_elems[$k]->attribs;

            if ($this->_elems[$k]->error_state === FORMEX_FIELD_REQUIRED) {

                switch ($this->_elems[$k]->type) {

                    case 'date':
                    case 'date_us':
                    case 'datetime':

                        $day = (!empty($attribs['suppress_day']))? '01' : sprintf("%d", $posted[$ff . "_day"]);

                        $datefields = array(sprintf("%d", $posted[$ff . "_month"]),
                                            $day,
                                            sprintf("%04d", $posted[$ff . "_year"])
                                            ); 

                        if (!self::is_proper_date($datefields)) {
                            $ferr = "'%s' is not a valid date. "; 
                        }
                        elseif (!empty($attribs['date_min']) or !empty($attribs['date_max'])) {
                            if (! ($date = strtotime(join('/', $datefields)))) 
                                $ferr = "'%s' is not a valid date. "; 
                            elseif (!empty($attribs['date_min']) and $date < strtotime($attribs['date_min'])) 
                                $ferr = sprintf("Date must be greater than '%s'", $attribs['date_min']);
                            elseif (!empty($attribs['date_max']) and $date > strtotime($attribs['date_max'])) 
                                $ferr = sprintf("Date must be before '%s'", $attribs['date_max']);
                        }
                        $posted[$ff] = join('/', $datefields); // to avoid a notice only
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
                        if (!self::is_uploaded_file($ff)) {
                            $ferr = "%s' must be uploaded";
                        }
                        break;

                    case 'select_or':
                        if (empty($posted[$ff]) and empty($posted[$ff.'_aux'])) {
                            $ferr = "'%s' is a required field.";
                        }
                        break;

                    case 'state_abbr': // if US or Canada based on "country", or there no "country", make sure is a 2-letter abbr.
                        $country_formfield = $this->field_prefix . 'country';
                        if (empty($posted[$country_formfield]) or $posted[$country_formfield] == 'US') {
                            if (strlen($posted[$ff]) != 2 or !formex_field::get_states_opts(true, strtoupper($posted[$ff]))) {
                                $ferr = "Please use a valid 2-letter state abbreviation";
                            }
                        }
                        elseif (!empty($posted[$country_formfield]) and $posted[$country_formfield] == 'CA') {
                            if (strlen($posted[$ff]) != 2 or !formex_field::get_canadian_provs(true, strtoupper($posted[$ff]))) {
                                $ferr = "Please use a valid 2-letter Canadian province abbreviation";
                            }
                        }
                        break;

                    case 'email':
                        if (!empty($posted[$ff]) && !self::is_proper_email($posted[$ff])) {
                            $ferr = "'%2\$s' is not a valid email address. Please enter a complete
                                        email address, i.e. 'jdoe@example.com', in the '%1\$s' field.";
                            break;
                        }

                    default:
                        if (isset($posted[$ff]) && is_array($posted[$ff]) && 0 == count($posted[$ff])) {
                            $ferr = "'%s' is a required selection.";
                        }
                        elseif (!isset($posted[$ff]) or (is_string($posted[$ff]) && strlen(trim($posted[$ff])) == 0)) {
                            $ferr = "'%s' is a required field.";
                        }
                }
            }

            /* check if the image is not too crazy huge */
            if (empty($ferr) && $this->_elems[$k]->type == 'image_upload' && self::is_uploaded_file($ff)) {
                $ferr = $this->_validate_image_dims($k);
            }

            /* attrib 'validate' in any field can point to a function or method that should return a error message on error */
            if (empty($ferr) and isset($attribs['validate']) and is_callable($attribs['validate'])) {
                $val = (!empty($posted[$ff]))? $posted[$ff] : null;
                $ferr = call_user_func($attribs['validate'], $ff, $val);
            }

            if ($ferr) {
                $this->_elems[$k]->set_error();
                $this->add_error($k, sprintf($ferr, $this->_elems[$k]->descrip, $posted[$ff]));
            }
        }
        return (count($this->errors) == 0);
    }


    /**
     * add an error to this form instance
     *
     * @param $elem str element key
     * @param $msg str error text
     */
    function add_error($elem, $msg) {
        $this->errors[$elem] = $msg;
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

                case 'image_upload':
                case 'file':
                    if (self::is_uploaded_file($ff)) {
                        $vals[$k] = $this->_save_uploaded_file($k);
                    }
                    break;

                default:
                    if (isset($posted[$ff])) {
                        $vals[$k] = $posted[$ff];
                    }
            }

            if ($this->convert_empty_string_to_null && isset($vals[$k]) && empty($vals[$k])) {
                $vals[$k] = null;
            }
        }
        return $vals;

    }



    /**
     * check that an uploaded image conforms to the max_dims or exact_dims 
     * attributes of the element, if given
     *
     * @return string if it does not conform
     */
    protected function _validate_image_dims($elem_id) {

        if (!isset($this->_elems[$elem_id])) return;

        $ff = $this->field_prefix . $elem_id; 

        $file = $_FILES[$ff]["tmp_name"];
        $eattr =& $this->_elems[$elem_id]->attribs;

        if (substr($_FILES[$ff]['type'], 0, 6) == 'image/' and (isset($eattr["max_dims"]) || isset($eattr["exact_dims"]))) {
            $imginfo = getimagesize($file);

            if (isset($eattr["exact_dims"])) {  // we will check the dimensions of it make sure it matches the rect

                list($targ_wid, $targ_ht) = split("x", $eattr["exact_dims"]);

                if (($targ_wid != $imginfo[0]) || ($targ_ht != $imginfo[1]) ) {
                    return "Uploaded image was an incorrect size! Dimensions need to be " . $eattr["exact_dims"].
                        " pixels. Yours was " . $imginfo[0] . "x" . $imginfo[1] . "."; 
                }

            }
            elseif (isset($eattr["max_dims"])) {  // make sure this fits within

                list($img_maxwidth, $img_maxheight) = split("x", $eattr["max_dims"]);

                if (($img_maxwidth < $imginfo[0]) || ($img_maxheight < $imginfo[1]) ) {
                    return "Uploaded image was too large! Maximum dimensions are " . $eattr["max_dims"].
                        " pixels. Yours was " . $imginfo[0] . "x" . $imginfo[1] . ".";
                }
            }
        }
    }


    /**
     * save the file for the associated field to the configured location and 
     * with a new name
     */
    protected function _save_uploaded_file($elem_id) {
        $imginfo = array();

        $ff = $this->field_prefix . $elem_id; 
        $file = $_FILES[$ff]["tmp_name"];

        $newname = $this->_safe_upload_filename($elem_id, $_FILES[$ff]["name"]);

        if (PEAR::isError($newname)) { return $newname; }

        $imginfo = array('name' => $newname, 
                         'type' => $_FILES[$ff]["type"],
                         'size' => intval(filesize($file) / 1024));


        $path = $this->_get_upload_save_path($elem_id);

        $dest = $path . "/" . $newname;

        if (!copy($file, $dest)) 
            return $this->raiseError("$dest could not be saved.");

        $imginfo['fullpath'] = $dest;

        return $imginfo;
    }


    /**
     * return the full path to where we are supposed to be saving the file
     *
     * look in the elem's attrib 'save_path', otherwise $this->upload_save_dir
     */
    protected function _get_upload_save_path($elem_id) {
        return (isset($this->_elems[$elem_id]->attribs['save_path']))?  $this->_elems[$elem_id]->attribs['save_path'] : $this->upload_save_dir;
    }


    /**
     * gen a safe filename for a new uploaded file - whitelist filter 
     * characters, and dont overwrite existing files
     *
     * elem's attribs: 'filename' override the user-supplied filename with this
     *                 'rand_filename' create a random-ish filename
     *                 'unique_filename' use supplied filename, append a uniqid
     *                 default - use supplied, increment until not overwriting
     *
     * @param $elem_id  an element id
     * @param $orig original filename 
     * @return string
     */
    protected function _safe_upload_filename($elem_id, $orig) {

        $ext = strtolower(substr($orig, strrpos($orig, '.')));

        $eattr =& $this->_elems[$elem_id]->attribs;

        if (isset($eattr["filename"])) {
            $newname = preg_replace("/\s/", "_", $eattr["filename"]);
            $newname = preg_replace("/[^A-Za-z0-9_.-]/", "", $newname);
        }
        elseif (isset($eattr['rand_filename'])) {
            $newname = uniqid();
        } 
        else {

            $newname = substr($orig, 0, strrpos($orig, '.'));
            $newname = preg_replace("/\s/", "_", $newname);
            $newname = preg_replace("/[^A-Za-z0-9_.-]/", "", $newname);

            if (isset($eattr['unique_filename'])) {
                $newname .= '.'. uniqid();
            }
            else {

                $path = $this->_get_upload_save_path($elem_id);

                if (file_exists($path . "/" . $orig)) {
                    $inc = 1;
                    $base = $newname;
                    do {
                        $newname = $base . '.' . $inc;
                        $dest = $path  .'/'. $newname . $ext;
                        $inc++;
                    }
                    while (file_exists($dest));
                }
            }
        }
        $newname .= $ext; // put the old extension back
        return $newname;
    }


    /* ========== lookout the static functions are coming ================== */


    /**
     * check if the given form var contains an uploaded file
     */
    static public function is_uploaded_file($formvar) {

        $file = $_FILES[$formvar]["tmp_name"];

        if (is_uploaded_file($file)) {
            return true;
        } elseif (empty($file) or 4 == $_FILES[$formvar]['error']) { // there was no file uploaded - its OK!
            return false;
        } else {
            $this->raiseError('file upload error :'.$_FILES[$formvar]['error']);
        }
    }



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
            $d = intval($posted[$ff . "_day"]);
        }
        else {
            $d = 1;
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

        if (version_compare(PHP_VERSION, '5.1.0', '<')) {
            if ($y < 100) $y += 1900;
            if ($y < 1903) return; // php5.1 mktime() doesn't work w dates <1903
        }

        $time = mktime($h,$min,$s,$m,$d,$y);
        if ($time === false or $time === -1) {
            trigger_error("Invalid args to mktime(): $h,$min,$s,$m,$d,$y", E_USER_WARNING);
            return;
        }

        return date($fmt, $time);
    }







}

// vim: set expandtab tabstop=4 shiftwidth=4 fdm=marker:
