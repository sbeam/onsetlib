<?php
/**
 * 
 * res_pager() - a resultset pager class
 * 
 * handles all the stuff needed for displaying paginated results from a
 * database query, in a very easy way.
 *
 * takes offset, range and numrows parameters and calculates the set of values
 * needed to display your typical set of links for paging amongst pages of DB
 * results. Does not output any HTML, the pager object should be passed to a
 * template for integration into the display.
 *
 * derived from Pager class by Thomas V.V.Cox cox@idecnet.com
 * http://vulcanonet.com/soft/pager/
 * Many changes to API and logic but build() is still similar
 * 
 * @copyright    2004 S Beam, OnsetCorps <sbeam@onsetcorps.net>
 * @author       S Beam <sbeam@onsetcorps.net>
 * @version      1.2
 * @last_update  2004-11-28
 * @description  create an array useful for paging DB result sets
 * @requires:	 PHP 5.03 or higher
 *
 * *******************************************************
 * License LGPL -> http://www.gnu.org/copyleft/lesser.txt
 * (c) 2004 by S Beam, OnsetCorps
 *******************************************************
 * 
 */

//  $Id: res_pager.class.php,v 1.1.1.1 2006/06/02 20:08:01 sbeam Exp $

class res_pager {

    /*
     * int row number the pager starts at
     */
    var $offset = 0;

    /*
     * int number of rows per page
     */
    var $range = 0;

    /*
     * int number of current page we are on
     */
    var $current;

    /*
     * int total number of results
     */
    var $numrows;

    /*
     * int row number where next page starts
     */
    var $next;

    /*
     * int row number where prev page starts
     */
    var $prev;

    /*
     * int number of results remaning *in next page*
     */
    var $remain;

    /*
     * int total number of pages
     */
    var $numpages;

    /*
     * int rownum for first row on this page
     */
    var $from;

    /*
     * int rownum for last row on this page
     */
    var $to;

    /*
     * num results per page
     */
    var $limit;

    /*
     * array assoc with page "number => start row"
     */
    var $pages = array();

    /*
     * int number of rows to show in first 'sample' page only (other pages can
     * have more)
     */
    var $page1_range = null;

    /*
     * string name of GET var that has the offset in it
     */
    var $offset_var = 'set';

    /*
     * string name of GET var that gives a page# (multiplied by range)
     */
    var $page_var = 'page';

    /**
     * initializes the three vars that are used to calculate the rest
     * 
     * @param int rownum the displayed page begins at
     * @param int how many rows are shown per page
     * @param int how many rows total are available
     * @param int optional - how many to show on the 'first' page - ie 10 on page 1, 50 on the rest
	 * @return an instance
     */
    function __construct($from, $to, $num, $page1_size=0) {
        //if (!is_numeric($from)
        $this->offset = $from;
        $this->range = $to;
        $this->numrows = $num;

        if ($page1_size) {
            $this->page1_range = $page1_size;
        }

        $this->build();
    }

    function res_pager($from, $to, $num, $page1_size=0) {
        $this->__construct($from, $to, $num, $page1_size);
    }


    /**
     * return an assoc array of all pertinent class vars so you dont have to
     * pass the whole object around...
     */
    function get_struct() {
        $struct = array('current' => $this->current,    // current page you are
                        'numrows' => $this->numrows,    // total number of results
                        'next'    => $this->next,    // row number where next page starts
                        'prev'    => $this->prev,    // row number where prev page starts
                        'remain'  => $this->remain,    // number of results remaning *in next page*
                        'numpages'=> $this->numpages,   // total number of pages
                        'from'    => $this->from,    // the row to start fetching
                        'to'      => $this->to,      // the row to stop fetching
                        'limit'   => $this->limit,   // How many results per page
                        'pages'   => $this->pages);

        return $struct;
    }


    /**
    * Gets all the data needed to paginate results
    * and fills in class vars as needed
    */
    function build() {
        if (empty($this->numrows) || ($this->numrows < 0)) {
            return null;
        }

        // Total number of pages
        $numpages = intval ($this->numrows/$this->range);
         // $numpages now contains int of pages needed unless there
         // is a remainder from division
        if ($this->numrows % $this->range) {
            // has remainder so add one page
             $numpages++;
        }

        // if page1_size is set, and there are more results than page1_size,
        // add one page since the first one is a fake
        if (isset($this->page1_range) and $this->numrows > $this->page1_range) {
            $numpages++;
        }

        $this->numpages = $numpages;

        // Build pages array
        for ($i=1; $i <= $numpages; $i++) {
            if (isset($this->page1_range)) {
                $this->pages[$i-1] = $this->range * ($i-2);
            }
            else {
                $this->pages[$i-1] = $this->range * ($i-1);
            }

            // $this->offset must point to one page
            if ($this->range * ($i-1) == $this->offset) {
                // The current page we are
                $this->current = $i;
            }
        }

        // Prev link
        $prev = $this->offset - $this->range;
        $this->prev = ($prev >= 0) ? $prev : null;

        // Next link
        if (isset($this->page1_range) and $this->numrows > $this->page1_range) {
            $next = 0;
        }
        else {
            $next = $this->offset + $this->range;
        }
        $this->next = ($next < $this->numrows) ? $next : null;

        // Results remaining in next page & Last row to fetch
        if ($this->current == $numpages) {
            $this->remain = 0;
            $this->to = $this->numrows;
        } 
        else {
            if ($this->current == ($numpages - 1)) {
                $this->remain = $this->numrows - ($this->range*($numpages-1));
            } 
            else {
                $this->remain = $this->range;
            }
            $this->to = $this->current * $this->range;
        }

        $this->numrows = $this->numrows;
        $this->from = $this->offset + 1;
        $this->limit = $this->range;

        if (isset($this->page1_range)) {
            $this->to = $this->page1_range;
        }

        $this->get_params = $this->make_get_params($_GET, array($this->offset_var, $this->page_var));
    }



    /**
     * attempt to build a URL GET string from the passed arrays
     * may not work in all situations just yet...
     *
     * @param arr assoc keys and values to include (can have sub-arrays)
     * @param arr linear optional any keys in $arr that should be skipped
     * @param str prefix to be added to all GET keys (ie 'f_')
     * @return the new GET param string
     */
    function make_get_params(&$arr, $ignore=array(), $prefix = '')
    {
        $elems = array();
        reset($arr);
        foreach ($arr as $k => $v) {
            if (in_array($k, $ignore)) { continue; } // pretend this never happe ned

            if (is_array($v)) {  // it needs the magic brackets
                reset($v);
                foreach ($v as $val) {
                    $elems[] = urlencode($k . "[]") . '=' . urlencode($val);
                }
            }
            else {
                $elems[] = urlencode($k) . '=' . urlencode($v);
            }
        }
        if (count($elems)) {
            return $prefix . join('&amp;', $elems);
        }
        else {
            return '';
        }
    }

}
?>
