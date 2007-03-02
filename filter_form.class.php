<?
require_once("formex.class.php");      

    class filter_form extends formex
    {
        function table_start() {
            return "<table cellpadding=\"3\" cellspacing=\"0\" border=\"0\">\n";
        }
        function table_row_begin($colspan=0) {
            return sprintf("<td style=\"%s\" valign=\"middle\">", 
                            $this->left_td_style);
        }
        function table_middle() {
            return " ";
        }
        function field_label($elem) {
            return " ";
        }
        function table_row_end() {
            return "</td>\n\n";
        }
        function field_heading($name, $fval, $maxlen, $opts) {
            return sprintf("<td style=\"%s\"><b>%s</b></td>",
                            $this->left_td_style,
                            $fval);
        }
        function end($hid) {
            return "$hid\n\n </tr></table></form>";
        }
    }
?>
