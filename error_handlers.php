<?php
/***
    functions to deal with errors and warnings throughout the site. including PEAR errors
    as well as other trigger_error() events
    $Id: error_handlers.php,v 1.3 2006/11/13 00:42:16 sbeam Exp $
***/

/* are we in debug mode (will show errors in browser for one thing */
if (!defined('DEBUG')) {
    define ('DEBUG', true);
}

// some defines needed for emailing error alerts
if (!defined('SITE_DOMAIN_NAME')) {
    define ('SITE_DOMAIN_NAME', $_SERVER['HTTP_HOST']);
}
if (!defined('ERROR_EMAIL_RECIP')) {
    define('ERROR_EMAIL_RECIP', 'debug@circusmedia.com');
}
if (!defined('ERROR_EMAIL_SENDER')) {
    define('ERROR_EMAIL_SENDER', 'webmaster@'.SITE_DOMAIN_NAME);
}

error_reporting(E_ALL);


/** map readable names to all the possible PHP error numbers */
$ERRORTYPES = array (
        E_ERROR          => "Error",
        E_WARNING        => "Warning",
        E_PARSE          => "Parsing Error",
        E_NOTICE          => "Notice",
        E_CORE_ERROR      => "Core Error",
        E_CORE_WARNING    => "Core Warning",
        E_COMPILE_ERROR  => "Compile Error",
        E_COMPILE_WARNING => "Compile Warning",
        E_USER_ERROR      => "User Error",
        E_USER_WARNING    => "User Warning",
        E_USER_NOTICE    => "User Notice");
if (defined('E_STRICT')) $ERRORTYPES[E_STRICT] = "Runtime Notice";


/** nice boring html to format any text err/warning message */
function error_format($msg, $color="#333333") {
    $res = "<div class=\"userError\" style=\"color: $color\">$msg</div>\n";
    return $res;
}


/** display params from a PHP error with some custom HTML */
function error_display($errno, $errstr, $errfile, $errline) {
    global $ERRORTYPES;

    if ($errno == E_ERROR or $errno == E_USER_ERROR) {
        $color = '#cc3333';
    }
    else {
        $color = '#333333';
    }
    $msg = sprintf("<b>%s: <tt>%s</tt><br /><span style=\"color: #ccc\">at line %s of %s</span></b>",
                   isset($ERRORTYPES[$errno])? $ERRORTYPES[$errno] : "UNKNOWN ERROR",
                   htmlentities($errstr),
                   $errline,
                   $errfile);
    echo error_format($msg, $color);
}


/*** debug_error_handler()
     print all the details of the err to the browser. and exit() if its a real ERROR
     called via trigger_error() ***/
function debug_error_handler ($errno, $errstr, $errfile, $errline) {
    global $ERRORTYPES;

    if( ( $errno & error_reporting() ) != $errno ) return;

    switch ($errno) {
        case E_ERROR:
        case E_USER_ERROR:
            error_display($errno, $errstr, $errfile, $errline);
            exit -1;
            break;
        case E_WARNING:
        case E_USER_WARNING:
            error_display($errno, $errstr, $errfile, $errline);
            break;
        default:
            printf(" [%s: '%s' at line %s of %s]",
                   isset($ERRORTYPES[$errno])? $ERRORTYPES[$errno] : "UNKNOWN ERROR",
                   htmlentities($errstr),
                   $errline,
                   $errfile);
    }
}


/*** nice_error_handler()
     Put a generic msg to the browser and send the details in an email to someone... 
     called via trigger_error() ***/
function nice_error_handler ($errno, $errstr, $errfile, $errline) {
    global $smarty, $ERRORTYPES;

    if( ( $errno & error_reporting() ) != $errno ) return;

    // set of errors for which we will send an email to self
    $user_errors = array(E_ERROR, E_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
   
    // save to the error log, and e-mail me if there is a critical user error
    if (defined('SITE_ERROR_LOG_FILE')) {
        $err = sprintf("%s [%s: '%s' at line %s of %s]\n",
                        date('r'),
                        isset($ERRORTYPES[$errno])? $ERRORTYPES[$errno] : "UNKNOWN ERROR",
                        str_replace("\n", ' ', $errstr), 
                        $errline, $errfile);

        error_log($err, 3, SITE_ERROR_LOG_FILE);
    }

    if (in_array($errno, $user_errors)) {
        error_mail_alert ($errno, $errstr, $errfile, $errline);
    }

    // add html comment so we can view source if need be.
    // print("<!-- $errno at line $errline of $errfile -->");

    // only show user errs/warns, the rest, pretend they didnt happen
    switch ($errno) {
        case E_USER_ERROR:
            $tpls = array('error_header', 'user_error_header', 'site_head', 'site_header');

            if (isset($smarty)) {
                if (!isset($smarty->_tpl_vars['header_sent'])) {
                    foreach ($tpls as $head) {
                        if ($smarty->template_exists("$head.tpl")) {
                            $smarty->display("$head.tpl");
                            break;
                        }
                    }
                }

                printf("<div class=\"%s\"><b>Error: %s</b></div>",
                        'userError',
                        htmlspecialchars($errstr));

                foreach ($tpls as $foot) {
                    $foot = preg_replace('/head/', 'foot', $foot);
                    if ($smarty->template_exists("$foot.tpl")) {
                        $smarty->display("$foot.tpl");
                        break;
                    }
                }
            }
            else { // what to do for html wrapper? hmmm...
                printf("<div class=\"%s\"><b>Error: %s<b></div>", 'userError', htmlspecialchars($errstr));
            }
            exit -1;
        case E_USER_WARNING:
            if (!preg_match('/^Smarty error:/', $errstr)) {
                printf("<div class=\"%s\"><b>Warning: %s<b></div>",
                        'userWarning',
                        htmlspecialchars($errstr));
            }
        default:
            return;
    }
}

/*** pear_error_handler()
formats a detailed err msg from a PEAR::Error object, and passes to the function  defined
in set_error_handler() (see init.php) ***/
function pear_error_handler (&$err_obj) {
    $error_string = sprintf("%s [%s]\n\n(%s)",
                            $err_obj->getMessage(),
                            $err_obj->getCode(),
                            $err_obj->getDebugInfo());
    //echo $error_string;
	trigger_error ($error_string, E_USER_ERROR);
}

/*** db_error_handler()
    special handler for PEAR/DB errors - just return to the script if it's a Duplicate
    (not used) ***/
function pear_db_error_handler (&$err) {
    if ($err->getCode() == DB_ERROR_ALREADY_EXISTS) {
        return $err;
    }
    else {
        pear_error_handler($err);
    }
}



/*** error_mail_alert() 
format an err msg, include all server vars, and send off to the webmaster for inspection.  ***/
function error_mail_alert ($errno, $errstr, $errfile, $errline) {
    global $auth;
    global $ERRORTYPES;

    // send to SM only if this is the production server
    $recip = ERROR_EMAIL_RECIP;
    $sender = ERROR_EMAIL_SENDER;

    $headers = sprintf("From: %s Web <$sender>\n", SITE_DOMAIN_NAME);
    $headers .= "X-Sender: <$sender>\n"; 
    $headers .= "Return-Path: <$sender>\n";  // Return path for errors

    $type = isset($ERRORTYPES[$errno])? strtoupper($ERRORTYPES[$errno]) : "UNKNOWN ERROR";

    $msg = "The following $type occurred on ". SITE_DOMAIN_NAME ." site:
           ===========
           $errstr
           at line $errline of $errfile
           ===========\n";
    $msg .= sprintf("location : http://%s%s\n\n", $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);

    ob_start();
       print "POST data:\n";
       print_r($_POST);
       print "\n\nGET data:\n";
       print_r($_GET);
       if (!empty($auth)) {
           print "\n\nauth. data:\n";
           print_r($auth->auth);
       }
       print "\n\nCOOKIES data:\n";
       print_r($_COOKIES);
       print "\n\nENVIRONMENT:\n";
       print_r($_SERVER);
    $msg .= ob_get_contents();
    ob_end_clean();
    
    $msg = preg_replace("/^ *(.*)/m", "\\1", $msg);  //remove left-side spaces from all the lines in msg

    $subj = "site $type: incident report";
    mail($recip, $subj, $msg, $headers);
}





// have PEAR route errors through the above...
if (defined('PEAR_ERROR_RETURN')) { // will be if any PEAR class is used
    PEAR::setErrorHandling (PEAR_ERROR_CALLBACK, 'pear_error_handler');
}

/*** error handling functions are below: ***/
if (!DEBUG) {
    set_error_handler ('nice_error_handler');
}
else {
    set_error_handler ('debug_error_handler');
}

// if $db is a PEAR::DB object, set its error handler special to the above
/* (not needed in most cases)
if (isset($db) and is_object($db) and is_a($db) == 'DB') {
    $db->setErrorHandling (PEAR_ERROR_CALLBACK, 'pear_db_error_handler');
}
*/



// handles uncaught exceptions
// http://us2.php.net/manual/en/function.set-exception-handler.php
function my_exception_handler($e) {
    $error_string = sprintf("'%s' [%s] at line %d of %s\n\nTrace:\n%s",
                            $e->getMessage(),
                            $e->getCode(),
                            $e->getLine(),
                            $e->getFile(),
                            $e->getTraceAsString());
	trigger_error ($error_string, E_USER_ERROR);
}

if (function_exists('set_exception_handler')) { // PHP5+
    set_exception_handler('my_exception_handler');
}

