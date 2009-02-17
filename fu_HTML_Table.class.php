<?

require_once("HTML/Table.php");

class fu_HTML_Table extends HTML_Table {

    var $sort_gif_asc = "&#187;";
    var $sort_gif_desc = "&#171;";
    var $td_style = "font-size: 8pt";
    var $th_class = "sortrow";
    var $td_class = "controlListingRow";
    var $bg_itr = 0;
    var $bgcolor_alts = array('#e0e0e0','#d2d2d2');
    var $bgcolor_hilite = '#fc0';
    var $invertSort = true;

    // same as addRow() but adds my special params and auto-calculates bgcolor
    function addRow_fu (&$contents, $special_class="", $hilite = true, $click_go=null, $use_rel_link=false) {
        $attrs = array("valign" => "middle",
                       "class"=> ($special_class)? $special_class : $this->td_class);
        if ($hilite) {
            $attrs['onmouseover'] = "this.style.saveBG = this.style.backgroundColor; this.style.backgroundColor = '{$this->bgcolor_hilite}'";
            $attrs['onmouseout'] = "this.style.backgroundColor = this.style.saveBG";
        }
        if ($click_go) {
            if ($use_rel_link) {
                $attrs['rel'] = $click_go;
            }
            else {
                $attrs['onclick'] = "document.location = '" . $click_go . "'"; // Bc
            }
        }
        if (!empty($this->bgcolor_alts)) {
            $attrs["style"] = "background-color: " . $this->bgcolor_alts[$this->bg_itr];
        }

        $this->addRow($contents, $attrs, 'TD', true);

        if ($this->bg_itr >= count($this->bgcolor_alts) - 1) {
            $this->bg_itr = 0;
        }
        else {
            $this->bg_itr++;
        }
    }

    // add a row for sorting, with all kinds of magic links
    function addSortRow (&$colmap, $col_ordered = null, $attribs = null, $type='TH', $extra_get_vars=null, $order_dir='A') {
        
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
        if (!isset($attribs["class"])) $attribs["class"] = $this->th_class;

        $this->addRow($cells, $attribs, $type);
    }

    function _invertDir($dir) {
        return ($dir == 'A' or $dir == 'ASC')? 'D' : 'A';
    }

}

?>
