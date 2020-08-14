<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

$processTime = date("Ymd-H:i:s");
die($processTime);

phpinfo();
die;

function hex_chars($data) {
    $mb_chars = '';
    $mb_hex = '';
    for ($i=0; $i<mb_strlen($data, 'UTF-8'); $i++) {
        $c = mb_substr($data, $i, 1, 'UTF-8');
        $mb_chars .= '{'. ($c). '}';
        
        $o = unpack('N', mb_convert_encoding($c, 'UCS-4BE', 'UTF-8'));
        $mb_hex .= '{'. hex_format($o[1]). '}';
    }
    $chars = '';
    $hex = '';
    for ($i=0; $i<strlen($data); $i++) {
        $c = substr($data, $i, 1);
        $chars .= '{'. ($c). '}';
        $hex .= '{'. hex_format(ord($c)). '}';
    }
    return array(
        'data' => $data,
        'chars' => $chars,
        'hex' => $hex,
        'mb_chars' => $mb_chars,
        'mb_hex' => $mb_hex,
    );
}

function writeFile($fileName)
{
	$f = fopen($fileName, 'r');
	while (!feof($f))
	{
		$line = fgets($f, 1000);
		bigecho($line);
		for ($i = 0; $i < strlen($line); $i++)
		{
			$char = substr($line, $i, 1);
			echo(ord( $char) . '.'  );
		}
	}
	fclose($f);
}

$ftpdir = "/var/www/html/CHT/h202/ftp";
$d = dir($ftpdir);
while (false !== ($entry = $d->read())) 
{
	if ($entry == '.' || $entry == '..') continue;
	echo $entry . "<br>";
	if (is_dir($ftpdir . '/' . $entry))
	{
		$subdir = dir($ftpdir . '/' . $entry);
		while (false !== ($sub_entry = $subdir->read())) 
		{
			if ($sub_entry == '.' || $sub_entry == '..') continue;
			echo $sub_entry . "<br>";
			
			if ( strpos($sub_entry, '.cfg') > 0 )
			{
				writeFile("$ftpdir/$entry/$sub_entry");
			}
			elseif ( strpos($sub_entry, '.txt') > 0 )
			{
				if ( unlink( "$ftpdir/$entry/$sub_entry" ) )
				{
					bigecho('delete successful');
				}
				else
				{
					bigecho('delete failed');
				}
			}
			
		}
		$subdir->close();
	}
}
$d->close();
die;


/*
	$res = getResult("SELECT hourlyTargets FROM processTargetHistory WHERE monitorID='USPDF1X' AND date='2011-02-26' LIMIT 1");
	if (checkResult($res))
	{
		echoResults($res);
		$line = $res->fetch_assoc();
		extract($line);
		$PROCESS_TARGET_ARRAY = unserialize($hourlyTargets);
		showArray($PROCESS_TARGET_ARRAY);		
	 	echo('<hr>');	
		
		$dailyArray = array();
		$firstVal = 'none';
		$currentTarget = '-1';
		for ($i = 0; $i < 24; $i++)
		{
			$key = str_pad("$i", 2, '0', STR_PAD_LEFT) . ':00' ;
			if ( !empty($PROCESS_TARGET_ARRAY[$key]) )
			{
				if ($firstVal == 'none')
					$firstVal = $PROCESS_TARGET_ARRAY[$key];
				$currentTarget = $PROCESS_TARGET_ARRAY[$key];
			}
			$dailyArray[$key] = $currentTarget;
		}
		reset($dailyArray);
		foreach ($dailyArray as $key => $val)
		{
			if ($val == '-1')
				$dailyArray[$key] = $firstVal;
			else
				break;
		}
		
		$titleString = "#IFF1811000000";
		$slotcnt = 0;
		for ($dow = 1; $dow <= 7; $dow++)
		{
			reset($dailyArray);
			foreach ($dailyArray as $key => $val)
			{
				$val = str_pad("$slotcnt", 3, '0', STR_PAD_LEFT) . "=$val";
				
				$titleString .= ",W4" . $val;
				$slotcnt += 2;
				if (strlen($titleString) > 240)
				{
					bigecho($titleString . '#');
					sendMail("COMS System", "noreply@customhostingtools.com", 'dneff68@gmail.com', $titleString . '#', '', "David Neff");
					$titleString = "#IFF1811000000";
				}
			}
		}
		
		bigecho($titleString . '#');
		$titleString = "#IFF1811000000,";

			// #IFF1811000000,W4000=.1,W4002=.2,W4004=.4,W4006=06,W4008=08,W4010=10,W4012=12,W4014=14,W4016=16,W4018=18,W4020=20,W4022=22,W4024=24,W4026=26,W4028=28,W4030=30,W4032=32,W4034=34,W4036=36,W4038=38,W4040=40,W4042=42,W4044=44,W4046=46#
		
	 	echo('<hr>');	
		showArray($dailyArray);		
	}


die;
*/




$str = "OCFMC1X";
$last = strtolower( $str[strlen($str)-1] );
bigecho($last);

die;//
generateStats('Tolleso', "'2010-11-13'");
die('complete');

$datestr = "9/29/2010 - 11:31:39 AM";
$datestr = str_replace(' - ', ' ', $datestr);
$datestr = strtotime($datestr);
$datestr = date('Y-m-d H:i:s', $datestr);

echo($datestr);

return;
$debug = '';
$dose = getDose('001234', 4, $debug);

echo("<h3>$dose</h3>");
echo("<h3>$debug</h3>");
die;

$timestring = strtotime("9/23/2010 12:20:21 AM");
if (!$timestring) echo 'fail';

echo ($timestring . "\n");

echo (date('Y-m-d H:i:s', $timestring));


/*
SELECT m.monitorID, sh.workKey, sh.workOrderNumber, sh.dateString from monitor m, site s, serviceHistory sh WHERE m.siteID=s.siteID AND m.monitorID=sh.monitorID AND s.siteID=92 order by sh.monitorID, sh.dateRequested
SELECT m.monitorID, sh.workKey, sh.workOrderNumber, sh.dateString from monitor m, site s, serviceHistory sh WHERE m.siteID=s.siteID AND m.monitorID=sh.monitorID AND s.siteID=93 order by sh.monitorID, sh.dateRequestedSELECT m.monitorID, sh.workKey, sh.workOrderNumber, sh.dateString from monitor m, site s, serviceHistory sh WHERE m.siteID=s.siteID AND m.monitorID=sh.monitorID AND s.siteID=94 order by sh.monitorID, sh.dateRequestedSELECT m.monitorID, sh.workKey, sh.workOrderNumber, sh.dateString from monitor m, site s, serviceHistory sh WHERE m.siteID=s.siteID AND m.monitorID=sh.monitorID AND s.siteID=95 order by sh.monitorID, sh.dateRequestedSELECT m.monitorID, sh.workKey, sh.workOrderNumber, sh.dateString from monitor m, site s, serviceHistory sh WHERE m.siteID=s.siteID AND m.monitorID=sh.monitorID AND s.siteID=225 order by sh.monitorID, sh.dateRequestedSELECT m.monitorID, sh.workKey, sh.workOrderNumber, sh.dateString from monitor m, site s, serviceHistory sh WHERE m.siteID=s.siteID AND m.monitorID=sh.monitorID AND s.siteID=251 order by sh.monitorID, sh.dateRequestedSELECT m.monitorID, sh.workKey, sh.workOrderNumber, sh.dateString from monitor m, site s, serviceHistory sh WHERE m.siteID=s.siteID AND m.monitorID=sh.monitorID AND s.siteID=279 order by sh.monitorID, sh.dateRequestedSELECT m.monitorID, sh.workKey, sh.workOrderNumber, sh.dateString from monitor m, site s, serviceHistory sh WHERE m.siteID=s.siteID AND m.monitorID=sh.monitorID AND s.siteID=299 order by sh.monitorID, sh.dateRequested
*/

?>