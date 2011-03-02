<?

/**
 * table_fu: extension to PEAR's HTML_Table class to allow for tables with 
 * sortable headers and clickable rows
 *
 * column headers can be clicked and should result in a GET request to sort
 * the results either way
 *
 * javascript (either inline or your own) is used to redirect on a click on any
 * row.  The destination is the $link param in addRow()
 *
 * @usage:
    $table = new table_fu(array("width" => "600"));
    $table->setAutoGrow(true);
    $table->setAutoFill("n/a");

    $header_row = array('name'=>'Name',
                  'shipType'=> 'Ship Class',
                  'taxable'=> 'Tax',
                  'product_count' => '#Products');

    $table->addSortRow($header_row, 'name', 'foo=1&bar=2", 'D');
    $sql = "SELECT * from shipping_categories ORDER BY name"; // ....
    $res = $pdb->query($sql);
    while ($row = $res->fetchRow()) {
        $link = sprintf('%s?id=%d', $_SERVER['PHP_SELF'], $row['id']);
        $table->addRow($row, '', null, $link); // $link is where you go when you click on row
    }
    ...
    
    echo $table->toHTML();
*/

 /*
 *
 * LICENSE
 *
 * Copyright 2007 by Sam Beam/OnsetCorps LLC
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
require_once("HTML/Table.php");

class fu_HTML_Table extends HTML_Table {

    var $sort_gif_asc = "&#187;";
    var $sort_gif_desc = "&#171;";
    var $th_class = "sortrow";
    var $td_class = "controlListingRow";
    var $bg_itr = 0;
    var $row_bgcolor_alts = array('#e0e0e0','#d2d2d2');
    var $row_bgcolor_hilite = '#fc0';
    var $invertSort = true;

    /**
     * same as addRow() but adds special class and click params and auto-calculates bgcolor
     *
     * @param $contents array - values for row
     * @param $special_class str - any special class attrib for the row
     * @param $hilite bool - should the row be highlighted on hover (using inline JS)
     * @param $click_go - opt - where user should be redirected to when he clicks on a row
     * @param $use_rel_link - opt - bool - if inline js is bad, use the 'rel' attrib instead
     */
    function addRow($contents, $special_class="", $hilite=false, $click_go=null, $use_rel_link=false) {
        $attrs = array("valign" => "middle",
                       "class"=> $this->td_class);

        if ($special_class) $attrs['class'] .= ' ' . $special_class;

        if ($hilite) {
            $attrs['onmouseover'] = "this.style.saveBG = this.style.backgroundColor; this.style.backgroundColor = '{$this->row_bgcolor_hilite}'";
            $attrs['onmouseout'] = "this.style.backgroundColor = this.style.saveBG";
        }
        if ($click_go) {
            if ($use_rel_link)
                $attrs['rel'] = $click_go;
            else 
                $attrs['onclick'] = "document.location = '" . $click_go . "'"; // Bc
        }
        if (!empty($this->row_bgcolor_alts))
            $attrs["style"] = "background-color: " . $this->row_bgcolor_alts[$this->bg_itr];

        if ($this->bg_itr >= count($this->row_bgcolor_alts) - 1)
            $this->bg_itr = 0;
        else
            $this->bg_itr++;

        parent::addRow($contents, $attrs, 'TD', true);
    }

    /**
     * add a TH row with clickable lables for sorting.
     *
     * the destination of links is PHP_SELF + 'by' and 'dir' params
     *
     * @param $colmap array column_ids => column labels
     * @param $col_ordered str which column the results are currently ordered by
     * @param $extra_get_vars str any extra "stuff" to append to the URl in the link
     * @param $order_dir str "A" for ascending or something else for descending
     */
    function addSortRow ($colmap, $col_ordered = null, $extra_get_vars=null, $order_dir='A') {
        
        $cells = array();
        foreach ($colmap as $col => $label) {
            if ($col == $col_ordered) {

                $link_order_dir = ($this->invertSort)?  $this->_invertDir($order_dir) : $order_dir;

                $cells[] = sprintf("<a class=\"sorted\" href=\"%s?by=%s&dir=%s&%s\">$label</a>%s",
                                   $_SERVER['PHP_SELF'],
                                   $col,
                                   $link_order_dir,
                                   $extra_get_vars,
                                   (substr($order_dir,0,1) == 'A')? $this->sort_gif_asc : $this->sort_gif_desc);
            }
            elseif (!empty($col)) {
                $cells[] = sprintf("<a href=\"%s?by=%s%s\">%s</a>",
                                   $_SERVER['PHP_SELF'],
                                   $col,
                                   (!empty($extra_get_vars))? "&" . $extra_get_vars : "",
                                   $label);
            }
            else {
                $cells[] = $label;
            }
        }

        parent::addRow($cells, $this->th_class, 'TH');
    }

    function _invertDir($dir) {
        return ($dir == 'A' or $dir == 'ASC')? 'D' : 'A';
    }

}

