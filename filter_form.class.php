<?

/*
 * filter_form
 *
 * a simple extension to formex to create a form suitable for filtering result sets.
 *
 * simply overrides selected html methods to create a horizontally-oriented form in a <table>
 * connecting the form to the controller. Filtering based on the returned results
 * is done normally
 *
 * LICENSE
 *
 * Copyright 2007 by Sam Beam
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
 * */


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
