<?php
// database host
$db_host   = "localhost:3306";

// database name
$db_name   = "xueyou";

// database username
$db_user   = "root";

// database password
$db_pass   = "root";

// table prefix
$prefix    = "hunuo_";

$timezone    = "UTC";

$cookie_path    = "/";

$cookie_domain    = "";

$session = "1440";

@define('DEBUG_MODE',3);

define('EC_CHARSET','utf-8');

if(!defined('ADMIN_PATH'))
{
define('ADMIN_PATH','oteeadmin');
}
define('AUTH_KEY', 'this is a key');

define('OLD_AUTH_KEY', '');

define('API_TIME', '2018-07-13 00:34:40');



?>
