<?php
session_start();
//phpinfo();
  include_once 'GlobalConfig.php';
  include_once 'h202Functions.php';
  include_once 'db_mysql.php';
  //include_once 'chtFunctions.php'; 

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 'On');


$abc = "Patrick";
// $status = 'abc,xyz';
// list($statkey, $status) = explode(',', $status);
// bigEcho($statkey . ":" . $status);
?>
Hello David <?=$abc?> Neff