#!/usr/bin/php -q
<?php
session_start();

$file = fopen("/tmp/readingslog", "a");
fwrite($file, "readings.php successfully ran at ".date("Y-m-d H:i:s")."\n\n");
fclose($file);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$processType = '';
$logmsg = $_SERVER['HTTP_HOST'] . "\n\n";

$localLogPath = '/var/www/html/COMS-Migration';
//$localLogPath = '/Library/WebServer/Documents/COMS-Migration';
include_once $localLogPath . '/lib/chtFunctions.php';
include_once $localLogPath . '/lib/db_mysql.php';
include_once $localLogPath . '/h202/h202Functions.php';

//$file = fopen("/tmp/readingslog", "a");
//fwrite($file, "CHECKPOINT 1. ($logmsg)" . "\n");
//fclose($file);

$_SESSION['DATABASE'] = 'h202';

if (isRemote())
{
    $file = fopen("/tmp/readingslog", "a");
    fwrite($file, "CHECKPOINT 2." . "\n\n");
    fclose($file);

    $logmsg = "REMOTE PROCESS\n\n";
//die("Migration Server: readings.php");
    bigEcho("REMOTE Development Server");
    $hostname= 'localhost'; //'127.0.0.1'; // localhost
    $dbuser = 'DevUser';
    $dbpass = 'QsTTeVfn';
    $database = 'h202';
    $comsDir = '/var/www/html/COMS-Migration/h202';
    $localLogPath = '/var/www/html/COMS-Migration';
    $david_debug = false;
}
else
{
    $file = fopen("/tmp/readingslog", "a");
    fwrite($file, "CHECKPOINT 3." . "\n\n");
    fclose($file);

    bigEcho("LOCAL Development Server");
    $comsDir = '/Library/WebServer/Documents/COMS-Migration/h202';
    $localLogPath = '/Library/WebServer/Documents/COMS-Migration';
    $david_debug = true;
    $hostname="127.0.0.1";
    $dbuser = 'root';
    $dbpass = 'smap0tCfl';
    $database = 'h202';
    $_SESSION['DATABASE'] = 'h202';
}

function echoResultsR(&$result, $title='defalut', $width='')
{
    if ($result->num_rows <= 0) {
        return;
    }
}
$res = getResult("Select * from users");
if (checkResult($res))
{
    $file = fopen("/tmp/readingslog", "a");
    fwrite($file, "We have a result ".date("Y-m-d H:i:s")."\n");
    fclose($file);

}


function roundToNextQuarterHour($datetime)
{
    //bigEcho($datetime);
    //die;

    list($date, $time) = explode(' ', $datetime);
    list($m, $d, $y) = explode('/', $date);
    $m = sprintf("%02d", $m);
    $d = sprintf("%02d", $d);

    $date = "$y-$m-$d";

    $hour = '';
    $min = '';
    $sec = '';
    if (substr_count($time, ':') == 1)
    {
        $time = $time . ':00';
    }
    list($hour, $min, $sec) = explode(':', $time);
    $hour = sprintf("%02d", $hour);
    $min = sprintf("%02d", $min);
    $sec = sprintf("%02d", $sec);

    $sec = $sec > 59 ? 59 : $sec;
    $min = $min > 59 ? 59 : $min;

    if ( ($min > 0) && ($min < 15) )
        $min = '15';
    elseif ( ($min > 15) && ($min < 30) )
        $min = '30';
    elseif ( ($min > 30) && ($min < 45) )
        $min = '45';
    elseif ( ($min > 45) && ($min < 60) )
    {
        if ($hour == '23')
        {
            $result = getResult("select date_add('$date', interval 1 day) as date");
            $line = mysqli_fetch_assoc($result);
            extract($line);
            $hour = '00';
        }
        else
        {
            $hour++;
        }

        $min = '00';
    }

    $sec = '00';
    return "$date $hour:$min:$sec";
}

$stdin = fopen('php://stdin', 'r'); // use for delivered version
//$stdin = fopen($ftpdir . '/test.txt', 'r'); // neff
//$stdin = fopen($localLogPath . '/h202/test.txt', 'r');

$ftpdir = $comsDir . "/ftp"; // neff


$message_head = array();
$message_body = "";
$msg = '';
$cnt = 0;
$inthereads = false;
$datestr=strftime('%F %T');
$hourlyTargets = '';

$err = "-- Process Log --";
$workOrderStr = '<!-- work order -->';
$startGrabbingAlarm = false;
$alarmData = '-- alarm description --';

////// START OF WHILE //////////
$ctr = 0;
//while (!feof($stdin)) // LOCAL VERSION
while ($line = fgets($stdin)) // use for delivered version
{
    //$line = fgets($stdin, 500); // LOCAL VERSION
    if ($ctr == 0) {
        $file = fopen("/tmp/readingslog", "a");
        fwrite($file, "In while loop." . "\n");
        fclose($file);
    }
    $ctr++;

    if (substr($line, 0, 1) == '>')
    {
        $line = substr($line, 1);
    }
    $line = trim($line);

    // this is a data line for a process reading
    if (strpos(strtolower($line), "--flowline") !== false)
    {
        $processType =  'FlowLine';
    }
    if (strpos(strtolower($line), "--samplepoint") !== false)
    {
        $processType =  'SamplePoint';
    }

    if (strpos(strtolower($line), "subject: alarm") !== false)
    {
        $alarmData = '';
        $processType =  'flowAlarm';
    }

    if (strpos(strtolower($line), 'work order') !== false)
    {
        $file = fopen("/tmp/readingslog", "a");
        fwrite($file, "set type to workorder" . "\n");
        fclose($file);

        $processType =  'WorkOrder';
    }

    if (strpos(strtolower($line), 'profile change confirmation') !== false)
    {
        $file = fopen("/tmp/readingslog", "a");
        fwrite($file, "setting process type" . "\n");
        fclose($file);

        $processType =  'TargetConfirmation';
        $startWriting = false;
    }

    if ( (substr($line, 0, 1) == '"') && (strpos($line, '"', 2) > 1) && (strpos($line, ':', 2) > 1))
    {
        if (strpos($line, '=') === false)
        {
            // this is a data line for a tank Reading
            list($monitorID, $datetime, $value) = explode(',', $line);

            $file = fopen("/tmp/readingslog", "a");
            fwrite($file, "READING -> $monitorID -- $datetime -- $value" . "\n");
            fclose($file);


            if (!empty($value) && !empty($datetime) && !empty($monitorID))
            {
                if (is_numeric($value))
                {
                    list($date, $time) = explode(' ', $datetime);

                    if (strpos($date, '-') !== false)
                    {
                        list($m, $d, $y) = explode('-', $date);
                    }
                    else
                    {
                        list($m, $d, $y) = explode('/', $date);
                    }

                    // list($m, $d, $y) = explode('/', $date);

                    // some monitors have delivered a time with seconds > 59.  We'll check and fix this now

                    list($hour, $min, $sec) = explode(':', $time);
                    $sec = $sec > 59 ? 59 : $sec;
                    $min = $min > 59 ? 59 : $min;
                    $time = "$hour:$min:$sec";

                    $monitorID = str_replace('"', '', $monitorID);
                    $value = empty($value) ? 0 : $value;

                    // get the units that are stored in the monitor table.  This keeps record of the units programmed into the monitor
                    // at the time of the reading, so later if the units switch we can still graph correctly.
                    $units = 'Gallons';
                    $res = getResult("select units from monitor where monitorID='$monitorID' LIMIT 1");
                    if (checkResult($res))
                    {
                        $line = $res->fetch_assoc();
                        extract($line);
                    }

                    executeQuery("INSERT INTO data (monitorID, date, value, units, processDate) VALUES ('$monitorID', '$y-$m-$d $time', $value, '$units', NOW())", 'INSERT');
                    updateTankStats($monitorID, 3);
                }
            }
        }
    }
    $msg .= $line;
    $cnt++;


}
$file = fopen("/tmp/readingslog", "a");
fwrite($file, "Out while loop. Total Lines $ctr" . "\n");
fclose($file);

fclose($stdin);

////// END OF WHILE //////////


$file = fopen("/tmp/readingslog", "a");
fwrite($file, "readings.php complete ".date("Y-m-d H:i:s")."\n\n");
fwrite($file, $logmsg);
fclose($file);
bigEcho("New Readings Complete");
?>