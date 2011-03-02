<?php

/**
 * utility function for smarty templates to render a single formex element.
 *
 * usage: 
 *      {formex_tag for=name}
 *      {formex_tag for=birthdate class=date}
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
 */
function smarty_function_formex_tag ($params, &$smarty) {

    if (!isset($params['for'])) {
        trigger_error('smarty_function_formex_tag: param "for" missing', E_USER_WARNING);
        return;
    }
    $attrs = array();

    if (isset($params['class'])) {
        $attrs['class'] = $params['class'];
    }
    echo $params['for']->get_html();
}




if (isset($GLOBALS['smarty'])) {
    $GLOBALS['smarty']->register_function('formex_tag', 'smarty_function_formex_tag');
}

