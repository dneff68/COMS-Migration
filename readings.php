#!/usr/bin/php -q
<?php
// THIS FILE HAS BEEN RELOCATED TO 
// etc/smrsh/readings.php  
//
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$processType = '';
include_once './lib/chtFunctions.php';
include_once './lib/db_mysql.php';
include_once './h202/GlobalConfig.php';
include_once './h202Functions.php';



bigEcho("New Readings Complete");
?>