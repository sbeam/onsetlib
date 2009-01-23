<?php


/** class to generate a CSV file from a DB result set or an array
 *
 * @examples
 * # using PEAR:DB resultset and dump directly to browser:
 * $csv = new CSV_Table();
 * $res = $db->query($sql); // PEAR:DB result set
 * $csv->print_csv_headers('important_data.csv');
 * $csv->dumpall($res);
 *
 * # using list of lists, saving to TAB-sep file with header
 * $csv = new CSV_Table();
 * $csv->sep = "\t";
 * $csv->header_row = true;
 * $csv->fields = array('Name', 'Age', 'Address', 'DOB');
 * $arr = get_some_stuff();
 * $fh = fopen('new_file.csv');
 * fwrite($fh, $csv->show($res));
 *
 * $Id: csv_table.class.php,v 1.3 2008/09/25 03:10:01 sbeam Exp $
 */
 
class CSV_Table {

  var $fields;                           ## Array of field names to show
  var $header_row;                       ## if set, create <th> section
  var $add_extra;                        ## Call extra cell functions
  var $map_cols;                         ## remap database columns to new names
  var $show_cols;                        ## limit columns to show to these
  var $newline = "\n";
  var $newline_input = "\r\n";
  var $quotechar = '"';
  var $sep = ',';
  var $numcols = 0;
  var $escape_method = "double";
  var $_header_row  = null;

    function CSV_Table() {
    }

    /**
    * This raiseError method works in a different way. It will always return
    * false (an error occurred) but it will call PEAR::raiseError() before
    * it. If no default PEAR global handler is set, will trigger an error.
    *
    * @param string $error The error message
    * @return bool always false
    */
    function raiseError($error) {
        // If a default PEAR Error handler is not set trigger the error
        // XXX Add a PEAR::isSetHandler() method?
        if ($GLOBALS['_PEAR_default_error_mode'] == PEAR_ERROR_RETURN) {
            PEAR::raiseError($error, null, PEAR_ERROR_TRIGGER, E_USER_WARNING);
        } else {
            PEAR::raiseError($error);
        }
        return false;
    }


    function print_csv_headers($filename) {
        header("Pragma: public");
        header("Cache-control: max-age=0");
        header("Content-type: text/comma-separated-values");
        header("Content-Disposition: attachment;filename=$filename");
    }

    function dumpall(&$stuff) {
        print $this->show($stuff);
    }

    /** add a row that will be put first - this should be heade rvlaues 
     * @param $arr array linear of column names/descripts */
    function set_header_row(&$arr) {
        $this->_header_row = $arr;
    }

    function show(&$stuff) {
        if (!empty($this->fields) && !$this->numcols) {
            $this->numcols = count($this->fields);
        }

        if (is_array($stuff)) {
            $rows = $this->build_rows($stuff); 
        }
        elseif (is_object($stuff) && $this->verify_db_res($stuff)) {
            $rows = $this->build_result_rows($stuff); 
        }
        else {
            CSV_Table::raiseError("Data was not an array or PEAR::db result set: " . gettype($stuff));
        }
        if (is_array($this->_header_row)) {
            $res = array_unshift($rows, $this->_build_table_row($this->_header_row));
        }
        return join($this->newline, $rows);
    }


    function build_rows(&$ary) {
        $rows = array();

        if (!$this->numcols) {
            $this->numcols = count($ary[0]);
        }

        if ($this->header_row) {
            $rows[] = $this->_build_table_row($this->fields);
        }
        foreach ($ary as $k => $v) {
            $rows[] = $this->_build_table_row($v);
        }
        return $rows;
    }


    function build_result_rows(&$res) {

        $rows = array();

        if (!$this->numcols) {
            $this->numcols = $res->numCols();
        }

        if ($this->header_row) {
            $rows[] = $this->_build_table_row($this->fields);
        }
        while ($row = $res->fetchRow(DB_FETCHMODE_ORDERED)) {
            $rows[] = $this->_build_table_row($row);
        }
        return $rows;
    }


    function _build_table_row(&$row) {
        $res = "";


        /* hack to see if this is a numeric or associative array */
        /*
        $has_numeric_keys = true;
        $klist = array_keys($row);
        for ($i=0; $i<count($klist); $i++) {
            if ($klist[$i] != $i) {
                $has_numeric_keys = false;
                break;
            }
        } 
        if (!$has_numeric_keys) $row = array_values($row);
         */
        if (is_array($this->show_cols) && count($this->show_cols)) {
            foreach ($this->show_cols as $c) {
                $res .= $this->_table_cell($row[$c]) . $this->sep;
            }
        }
        else {
            for ($i=0; $i < $this->numcols; $i++) {
                $res .= $this->_table_cell($row[$i]) . $this->sep;
            }
        }
        $res = substr($res, 0, -1*strlen($this->sep));
        return $res;
    }

    function _table_cell($val) {
        return sprintf("%s%s%s", 
                $this->quotechar, 
                $this->_cell_mosh($val),
                $this->quotechar);
    }

    function _cell_mosh($val) {
        $val = $this->_quote_escape($val);
        return $this->_newline_elim($val);
    }

    function _newline_elim($val) {
        return ereg_replace($this->newline_input, "", $this->_quote_escape($val));
    }

    function _quote_escape(&$val) {
        $q = $this->quotechar;
        if ($this->escape_method == 'backslash') {
            $val = ereg_replace($q, "\\$q", $val);
        }
        elseif ($this->escape_method == 'erase') {
            $val = ereg_replace($q, "", $val);
        }
        else {
            $val = ereg_replace($q, "$q$q", $val);
        }
        return $val;
    }

    function verify_db_res(&$res) {
        if (is_object($res)) {
            if (@$res->numRows() == 0) {
                CSV_Table::raiseError("No results in row");
                return false;
            }
            return 1;
        }
    }



}





/* special extension to CSV_Table to give it methods that correspond with our 
 * fu_HTML_Table, so it will work transparently, but then spit out CSV instead of HTML 
 * at the end. All we do below when CSV is requested is instantiate one of 
 * these instead, then print out the headers and data at the end. Quick and hacky. */
class CSV_Table_Fu extends CSV_Table {

    function addRow_fu($vals) {
        $this->addRow($vals);
    }

    function addRow($vals) {
        if (!isset($this->_rows)) {
            $this->_rows = array();
        }
        $this->_rows[] = $vals;
    }

    function addSortRow($vals) {
        $this->set_header_row($vals);
    }

    function displayAll() {
        return $this->show($this->_rows);
    }

}
/* ** */

