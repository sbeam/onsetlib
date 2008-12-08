<?php
/**
 * Onset DB Container
 * 
 * @package onset
 * @author sbeam <sbeam@onsetcorps.net>
 */

/**
 * require PEAR for error handling only
 */
require_once "PEAR.php";

// error codes
define('DBCON_ZERO_EFFECT', 999); // no rows affected in store()
define('DBCON_NUMERIC_ID', 1000);  // non-numeric passed to set_id(0
define('DBCON_UNSET_TABLE', 1001); // set_table() needs to be called
define('DBCON_NO_RECORD_MATCH', 1003); // no record found matching given PK

/**
 * db_container Class
 * a base class for fetch and storage of single row DB content
 * meant to be extended for specific app objects or tables as needed (though
 * it can be used as-is, not recommended - at a minimum, define 
 * $colmap, $_pk_col, and $_table in the child class)
 *
 * @abstract
 * @package smrnet
 * @version $Id: db_container.class.php,v 1.9 2006/12/11 03:58:23 sbeam Exp $
 *
 */
class db_container extends PEAR {
    /**
     * id of the row holding data for this instance, should correspond w/ _pk_col 
     * @access protected
     */
    var $_id;
    /**
     * name of the column holding the primary key in the main table
     * @var string
     * @access protected
     */
    var $_pk_col = 'id';
    /**
     * name of the main table holding data for this instance
     * @var string
     * @access protected
     */
    var $_table = array();
    /**
     * the master 'colmap' for this table -  a $fex-style configuration array
     * @var array
     * @access protected
     */
    var $colmap = array();
    /**
     * whether or not the primary key of this instance is numeric/integer
     * @var bool
     * @access protected
     */
    var $numeric_pk = true;

    /**
     * stores any row values that we happen to have come across during fetch() 
     * @var array
     * @access protected
     */
    var $header = array();

    /**
     * tables that are related to us in a many-to-one fashion. 
     * tablename => colname
     * where tablename is the name of the related table
     * and colname is the column that points _to_ the relation, whatever it is
     * @var array
     * @see _store_related_kids()
     * @access protected
     */
    var $child_relations = array();

    /** in fetch_any(), where to begin the resultset fetching and how many to get */
    var $_offset = 0;
    /** 0 means unlimited */
    var $_range = 0;

    /** name of sequence to get new row'ids from - if null the table name will be used */
    var $_id_sequence = null;


    /**
     * constructor function
     * just sets $this->db as a reference to the passed $db object
     * @param object $db - initialized and ready PEAR::DB object
     */
    function db_container(&$db) {
        if (!$db) trigger_error('db needed', E_USER_ERROR);
        $this->db = &$db;
    }

    /**
     * not in the text box. Create another instance of ourself. utility function
     * @param $table str name of table to assign
     * @return new db_container object
     * @static
     */
    function factory(&$db, $table) {
        $dbc = new db_container($db);
        $dbc->set_table($table);
        return $dbc;
    }

    /**
     * sets pk/id for this instance.
     * 
     * @param integer $id
     * @param string $id if numeric_pk is false
     */
    function set_id($id)
    {
        if (!is_numeric($id) && $this->numeric_pk) {
            $this->raiseError('id must be numeric', DBCON_NUMERIC_ID);
        }
        else {
            $this->_id = $id;
        }
    }

    /**
     * retrive the primary-key/id of this instance
     * @return mixed
     */
    function get_id()
    {
        return $this->_id;
    }

    /**
     * get the name of column(s) holds primary key
     * @return str or array
     */
    function get_pk_col()
    {
        return $this->_pk_col;
    }


    /**
     * set the primary-key containing column name - should be in $this->_table of course
     * @param string $col
     */
    function set_pk_col($col)
    {
        $this->_pk_col = $col;
    }

    /**
     * set the name of the main table where object data is found
     * @param string $str
     */
    function set_table($str)
    {
        $this->_table = $str;
    }


    /**
     * retreive name of the main table
     * @return string tablename
     */
    function get_table_name()
    {
        return $this->_table;
    }

    /**
     * set the name of the sequence to use to get row id's from 
     * @param string $str
     */
    function set_sequence($str)
    {
        $this->_id_sequence = $str;
    }


    /** get the number of rows matched by the last fetch_any() type of query
     * @return int or null if no such query has been made
     */
    function get_numrows() {
        return (isset($this->numRows))? $this->numRows : null;
    }


    /**
     * get a copy of the colmap
     * @return array the colmap 
     */
    function get_colmap() {
        return $this->colmap;
    }

    /**
     * return all data associated with this object instance.
     * calls PEAR::DB via $this->db on the current table
     * by default does a "SELECT *..." unless $cols are passed
     * @param array $cols limited selected columns to ones in this array
     * @return array k=>v pairs from PEAR::DB::fetchRow() | false if $this->set_id() has not been called
     */
    function fetch($cols = '', $kids=false)
    {
        if (!$this->_table) {
            return $this->raiseError('must call set_table', DBCON_UNSET_TABLE);
        }
        if (!$this->get_id()) {
            return;
        }

        if (is_array($cols)) {
            $coldef = join(', ', $cols);
        }
        else {
            $coldef = '*';
        }

        $sql = $this->_fetch_sql($coldef);
        $res =& $this->db->query($sql);

        if (! $row = $res->fetchRow()) {
            $err = $this->raiseError('No matching record found.', 1002, PEAR_ERROR_RETURN);
        }
        else {
            $this->header = $row;
            if ($this->child_relations) { /* this table has some kids - get the vals */
                if ($kids === true) {
                    $kids = array_keys($this->child_relations);
                }
                if ($kids) {
                    foreach ($kids as $k) {
                        if (isset($this->child_relations[$k])) {
                            $sql = sprintf("SELECT %s FROM %s WHERE %s_id = %d",
                                           $this->child_relations[$k],
                                           $k,
                                           $this->get_table_name(),
                                           $this->get_id());
                            $this->header[$k] = $this->db->getCol($sql);
                        }
                    }
                }
            }
            return $this->header;
        }
    }

    /**
     * fetch one specific column/header
     * @param key of column/header to look up
     * @return string
     */
    function get_header($k) {
        if (!isset($this->header[$k])) {
            if (! ($this->fetch(array($k)))) {
                trigger_error("unknown header '$k' of ".get_class($this), E_USER_NOTICE);
                return;
            }
        }
        return $this->header[$k];
    }


    /**
     * create SQL string to be passed to $db to do the fetching
     * may be over-ridden
     * @return string SQL SELECT statement
     * @access protected
     */
    function _fetch_sql($cols)
    {
        if (!is_array($this->_pk_col)) {
            $sql = sprintf("SELECT %s FROM %s WHERE %s = '%s'",
                                    $cols,
                                    $this->_table,
                                    $this->_pk_col,
                                    $this->get_id());
        }
        else {
            $sql = sprintf("SELECT %s FROM %s WHERE ",
                                    $cols,
                                    $this->_table);
            foreach ($this->_pk_col as $pkcol) {
                $sql .= sprintf("%s = '%s' AND ",
                        $pkcol,
                        addslashes($this->_id[$pkcol]));
            }
            $sql = substr($sql, 0, -4);
        }
        return $sql;
    }

    /**
     * store values back to the DB for this instance, or insert new values as needed.
     * Will do an UPDATE on the main table if set_id() has been
     * called, otherwise an INSERT - and will call set_id() with newly inserted id.
     *
     * if pk is not numeric, and you want to do an insert, then don't call set_id(), but
     * pass the new id in $vals, since it cannot be autogenerated
     * 
     * @param array $vals ass.array of values to store, like array("columnname"=>"value")
     * @param bool optional force insert instead of update attempt?
     * @return integer number of affected rows (should be 1)
     */
    function store($vals, $force_insert=false)
    {
        if (count($vals) == 0 or !isset($this->_table)) {
            return $this->raiseError("no values were given to be stored or no table was identified", DB_ERROR_NEED_MORE_DATA);
        }

        /* pinch lists of child items for sep. table inserts. */
        $kids = array();
        if (!empty($this->child_relations)) {
            foreach ($this->child_relations as $k => $tabvar) {
                if (!empty($vals[$k])) {
                    $kids[$k] = $vals[$k];
                    unset($vals[$k]);
                }
            }
        }

        /* its an update if $this->_pk_col is in the $vals already or $force_insert */
        if (isset($this->_id) and !$force_insert) {
            if (!is_array($this->_pk_col)) { /* normal one-col pk */
                $sql_where = sprintf("%s = '%s'", $this->_pk_col, $this->get_id());
            }
            else { /* pk is multicol! */
                foreach ($this->_pk_col as $col) {
                    $wheres[] = sprintf("%s = '%s'", $col, $this->_id[$col]);
                    $sql_where = join(' AND ', $wheres);
                }
            }
            $res = $this->db->autoExecute($this->get_table_name(), $vals, DB_AUTOQUERY_UPDATE, $sql_where);
        }
        else { /* insert to be done */
            if (!is_array($this->_pk_col)) {
                if (!isset($vals[$this->_pk_col])) { // create new id
                    $seq_name = (!empty($this->_id_sequence))? $this->_id_sequence : $this->get_table_name();
                    $vals[$this->_pk_col] = $this->db->nextId($seq_name);
                }
                $this->set_id($vals[$this->_pk_col]); // note frm here on out, This->_id
            }
            $res = $this->db->autoExecute($this->get_table_name(), $vals, DB_AUTOQUERY_INSERT);
        }
        if (PEAR::isError($res)) {
            return $res;
        }
        else {
            /* this->header always contains the key->val pairs for this instance */
            $this->header = array_merge($this->header, $vals);
            $effect = $this->db->affectedRows();

            /* perhaps have dependent tables and values to add to them? */
            if (count($kids)) {
                foreach ($kids as $k => $kidvals) {
                    $res = $this->_store_related_kids($k, $kidvals);
                }
            }

            if ($effect != 1) { // inconveniently, this is fatal TODO exceptions
                return $this->raiseError("warning: $effect rows were changed", DBCON_ZERO_EFFECT, PEAR_ERROR_RETURN);
            }
            else {
                return $effect;
            }
        }
    }



    /** 
     * simple brane-ded function to store things that have a many-to-one
     * relation to this object. Looks in $child_relations class var to find
     * tables that are related to us. If so does the usual thing - DELETE all
     * the current ones, then re-insert a row for each of $vals, with our id
     *
     * see cshop categories and related items for this implementated
     *
     * @param $kidtable key in $child_relations that we are looking for
     * @param $vals array list of values to be store in the table, with our id
     * @return db result
     */
    function _store_related_kids($kidtable, $vals)
    {
        if (!isset($this->child_relations[$kidtable])) {
            return $this->raiseError("child related table '$tabkey' is unknown", E_USER_ERROR);
        }

        $sql = "INSERT INTO $kidtable VALUES (?,?,?)";
        $sth_in = $this->db->prepare($sql);

        // clear cats out from this product...
        $sql = sprintf("DELETE FROM $kidtable
                        WHERE %s_id = %d", 
                        $this->get_table_name(),
                        $this->get_id());
        $res = $this->db->query($sql);

        if (!is_array($vals)) {
            $vals = array($vals);
        }

        if (!empty($vals)) {
            $myid = $this->get_id();
            foreach ($vals as $v) {
                $res = $this->db->execute($sth_in, array($myid, $v, 0));
            }
        }
        return $res;
    }



    /**
     * removes data for current instance from underlying DB
     * performs a fetch() first and returns the data, for reporting or recording purposes
     * @return array (assoc) DB row that has been removed
     */
    function kill() {
        if (!isset($this->_id)) {
            $this->raiseError('must set_id() before kill', DB_ERROR_NEED_MORE_DATA);
        }
        $row = $this->fetch();
        if (!empty($row) and !PEAR::isError($row)) {
            if (!is_array($this->_pk_col)) {
                $sql = sprintf("DELETE FROM %s WHERE %s = '%s'",
                               $this->_table,
                               $this->_pk_col,
                               $this->get_id());
            }
            else {
                $sql = sprintf("DELETE FROM %s WHERE ",
                               $this->_table);
                foreach ($this->_pk_col as $pkcol) {
                    $sql .= sprintf("%s = '%s' AND ",
                                    $pkcol,
                                    addslashes($this->_id[$pkcol]));
                }
                $sql = substr($sql, 0, -4);
            }
            $this->db->query($sql);
            if (!$this->db->affectedRows()) {
                return $this->raiseError('warning: no matching record found.', DBCON_NO_RECORD_MATCH);
            }
        }
        return $row;
    }


    /**
    * does the actual DB query for the given flavor
    * @param $flavor the type of chunkd to get
    * @return an array of chunky goodness 
    */
    function fetch_any($cols=null, $offset=0, $range=0, $orderby=null, $where='', $orderdir='ASC') {

        $chunks = array();
        /* I no longer understand why SELECT * wouldn't work. maybe before it 
         * seemed ugly, but now looking at Rails there is nothing to stop us. 
         * It is def. more convenient because I want to get all the stuff in 
         * case $cols is null here
         *
         * if (!$cols and $this->colmap) {
         *
         *  $cols = array();
         *  foreach (array_keys($this->colmap) as $c) {
         *      if (!in_array($c, array_keys($this->child_relations))) {
         *          $cols[] = $c;
         *      }
         *  }
         *  if (is_array($this->_pk_col)) {
         *      $cols = array_merge($cols, $this->_pk_col);
         *  }
         *  else {
         *      if (is_array($cols) && !in_array($this->_pk_col, $cols)) $cols[] = $this->_pk_col;
         *  }
         *}
         */

        $orderdir = (substr($orderdir, 0, 1) == 'A')? 'ASC' : 'DESC'; // this is silly

        $sql = $this->_get_fetch_any_sql($cols, $orderby, $where, $orderdir);

        if ($offset) $this->set_offset($offset);
        if ($range) $this->set_range($range);

        return $this->_do_fetch_array($sql);
    }



    /** util function to perform the query of the given SQL against the $db
     * object. Returning all results rows in a big array, limited by $offset
     * and $range in a way that also lets us count the total results
     * efficiently.
     * @param $sql string
     * @param $offset int
     * @param $range int
     * @return array
     */
    function _do_fetch_array($sql)
    {
        $res = $this->db->query($sql);

        if (PEAR::isError($res)) {
            trigger_error("Bad result from the database: " . $res->getDebugInfo(), E_USER_ERROR);
        }

        $this->numRows = $res->numRows();

        if ($res->numRows() == 0) {
            return false;
        }
        else {                      // go get em
            for ($ptr = $this->_offset; 
                 ($this->_range == 0) or (($this->_offset + $this->_range) > $ptr);
                 $ptr++) { 
                if (! $row = $res->fetchRow(DB_FETCHMODE_ASSOC, $ptr)) break;

                $chunks[] = $row;
            }
            return $chunks;
        }
    }


    /** 
     * create the general SQL stmt needed to fetch any
     * meant to be overridden by subclasses as needed
     */
    function _get_fetch_any_sql($cols, $orderby, $where, $orderdir) {

        $sql = sprintf("SELECT %s FROM %s",
                                ($cols)? join(',', $cols) : '*',
                                $this->get_table_name());
        if ($where) $sql .= "\nWHERE $where";
        if ($orderby) {
            $sql .= "\nORDER BY $orderby $orderdir";
        }

        return $sql;
    }






    /**
     * set options for any form elements as needed.
     * meant to be extended, to initialize any form values from DB 
     * @param object $fex - ref to formex() object
     */
    function set_form_opts(&$fex) {
    }

    /**
     * set a datetime column in the current instance 
     * data will set to now() by default or a valid datetime expression used by
     * mySQL (!)
     * 
     * @param string $col - name of the datetime column
     * @param string $expr optional datetime expression besides now()
     * @deprecated due to mysql-only leanings
     */
    function set_datetime($col, $expr = '') {
        $sql = sprintf('UPDATE %s SET %s = %s WHERE %s = \'%s\'',
                        $this->_table,
                        $col,
                        ($expr)? $expr : 'now()',
                        $this->_pk_col,
                        $this->_id);
        return $this->db->query($sql);
    }

    /**
     * set colmap element to an error state for later
     * convenience function to be called when user-entered data to be inserted
     * via store() is not up to snuff. Will affect colmap, setting the "error bit" as
     * we like to call it, to value > 1 - which, when the colmap is then passwd to formex,
     * formex will mark the field as error (using CSS)
     * 
     * @param string $col name of column/field that is in error
     */
    function set_storage_error($col) {
        if ($this->colmap[$col][count($this->colmap[$col])-1] === 1) {
            $this->colmap[$col][count($this->colmap[$col])-1] += 1;
        }
    }

    /**
     * setter for the offset
     * @param int
     */
    function set_offset($int)
    {
        if ($int < 0 or !is_integer($int)) {
            trigger_error("offset must be a positive integer", E_USER_WARNING);
            return;
        }
        $this->_offset = $int;
    }

    /**
     * setter for the range
     * @param int
     */
    function set_range($int)
    {
        if ($int < 0 or !is_integer($int)) {
            trigger_error("range must be a positive integer", E_USER_WARNING);
            return;
        }
        $this->_range = $int;
    }

    /** setter for both offset and range in one fell swoope
     * @param int
     * @param int
     */
    function set_resultset_limits($offset, $range)
    {
        $this->set_offset($offset);
        $this->set_range($range);
    }
}

?>
