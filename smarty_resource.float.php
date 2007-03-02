<?php

function smarty_floater_get_template ($tpl_name, &$tpl_source, &$smarty)
{
    if (!isset($smarty->template_path)) {
        trigger_error("template_path is not defined", E_USER_WARNING);
    }

    foreach ($smarty->template_path as $dir) {
        if (is_readable("$dir/$tpl_name")) {
            $tpl_source = file_get_contents("$dir/$tpl_name");
            return true;
        }
    }
    return false;
}

function smarty_floater_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty) {
    if (!isset($smarty->template_path)) {
        trigger_error("template_path is not defined", E_USER_WARNING);
    }

    foreach ($smarty->template_path as $dir) {
        if (is_readable("$dir/$tpl_name")) {
            $tpl_timestamp = filemtime("$dir/$tpl_name");
            return true;
        }
    }
    return false;
}

function smarty_floater_get_secure($tpl_name, &$smarty)
{
    // assume all templates are secure
    return true;
}

function smarty_floater_get_trusted($tpl_name, &$smarty)
{
    // not used for templates
}

// register the resource name "float"
$smarty->register_resource("float", array("smarty_floater_get_template",
                                       "smarty_floater_get_timestamp",
                                       "smarty_floater_get_secure",
                                       "smarty_floater_get_trusted"));


