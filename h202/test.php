<?php
session_start();
  include_once 'GlobalConfig.php';
  include_once 'h202Functions.php';
  include_once '../lib/db_mysql.php';
  include_once '../lib/chtFunctions.php'; 

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 'On');


$status = 'abc,xyz';
list($statkey, $status) = explode(',', $status);
bigEcho($statkey . ":" . $status);
?>