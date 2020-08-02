<?php
session_start();
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
include_once '../lib/chtFunctions.php';
include_once 'db_mysql.php';

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 'On');

echo $SERVERNAME . " " . $DOCUMENT_ROOT;

if (david())
{
	echo "Hello David";	
}
else
{
	echo "Good day sir";
}
die;
?>