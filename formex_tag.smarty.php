<?php

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

