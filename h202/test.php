#!/usr/bin/php -q
<?php
session_start();
phpinfo();
error_log("This is a test");
die;

include_once '../lib/db_mysql.php';
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
showArray($_SERVER);
showSessionVars();
https://www.php.net/manual/en/function.syslog.php
die;

$file = fopen("/tmp/postfixtest", "a");
fwrite($file, "Script successfully ran at ".date("Y-m-d H:i:s")."n");
fclose($file);
die;


session_start();
echo("<h2>Test.php</h2>");
echo("<h2>" . $_SERVER['HTTP_HOST'] . "</h2>");
die;
//phpinfo();
include_once '../lib/chtFunctions.php';

//include_once $_SESSION['SYSTEM_LIB_PATH'] . "chtFunctions.php";

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 'On');

$query = "INSERT INTO processData (monitorID, flowLineID, samplePointID, date, ppm, temperature) VALUES ('SDDMA1X', 'DELMAR', 'DELMAROUTFALL', '11-08-10 11:00:00', 0, 0)";
executeQuery($query, 'INSERT');
bigEcho("Errors??");
die;


$abc = $tankName;
// $status = 'abc,xyz';
// list($statkey, $status) = explode(',', $status);
// bigEcho($statkey . ":" . $status);
?>
Hello David <?=$abc?> Neff