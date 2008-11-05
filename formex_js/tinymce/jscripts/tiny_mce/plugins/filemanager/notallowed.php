<?
#session_start();

require_once(VISYCS_HOME . '/include/authlib.local.php');
page_open(array("sess" => "visycsSession", "auth" => "visycsAuth", "perm" => "visycsPerm"));

?>
<html>
<head><title>Application Error</title></head>
<body>
<h1>VZX: Session violation</h1>
You are not allowed to access this utility. Your session may have timed out. Please close this window and log in.
</body>
</html>
