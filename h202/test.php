<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once '/var/www/html/CHT/lib/chtFunctions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 'On');

phpinfo();

die;
?>