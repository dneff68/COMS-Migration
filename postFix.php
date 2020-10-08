#!/usr/bin/php
<?php
error_log("In postFix.php test file");
openlog("/tmp/myPHPLog", LOG_PID | LOG_PERROR, LOG_LOCAL0);
syslog(LOG_NOTICE, "Successful call to postFix Test File");
closelog();

error_log('entering postFix.php', 0, '/var/log/httpd/error_log');


$file = fopen("/tmp/postfixtest", "a");
fwrite($file, "postFix.php successfully ran at ".date("Y-m-d H:i:s")."\n\n");


// file_get_contents
//$message = file_get_contents( 'php://stdin' );
//fwrite($file, "Email Contents: $message"."\n\n");


$stdin = fopen('php://stdin', 'r'); // use for delivered version
while ($line = fgets($stdin)) // use for delivered version
{
    //$line = fgets($stdin, 500); // neff (remove)

    $line = trim($line);
    fwrite($file, $line."\n\n");
}
fclose($stdin);
fclose($file);

?>