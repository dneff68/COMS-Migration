<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once '/var/www/html/CHT/lib/chtFunctions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 'On');


$query = "SELECT hourlyTargets FROM processTargetHistory WHERE monitorID='USPDF1X' ORDER BY date DESC LIMIT 1";
$targetRes = getResult($query);
if (checkResult($targetRes))
{
	// get target values
	$targetLine = mysql_fetch_assoc($targetRes);
	extract($targetLine);
	$targetsArray = unserialize($hourlyTargets);
	showArray($targetsArray);
	if ( empty($targetsArray['16:00']))
		bigecho( 'empty' );
}
die;



$x = generateStats('SDPS2PX', "'2010-11-29'");
// SELECT max(date), value as prevDelValue FROM data WHERE monitorID='003037' and cast(date as date) = cast(NOW() as date) or cast(date as date) = date_add(cast(NOW() as date), interval -1 day) group by cast(date as date) ORDER BY date DESC LIMIT 2
//$x = generateStats('SDPS2PX');
die("-- $x");


die;


$r = reorderInfo('003040');
showArray($r);

die;

$res = getResult("Select siteID, deliveryEmailDist from site");
if (checkResult($res))
{
	while( $line = mysql_fetch_assoc($res) )
	{
		extract($line);
		$emails = explode("\n", $deliveryEmailDist);
		foreach($emails as $email)
		{
			$email = trim($email);
			$email = fixString($email);
			$email = strtolower($email);
			$res2 = getResult("SELECT email FROM customerLoginEmail WHERE email='$email'");
			if (!checkResult($res2))
			{
			  executeQuery("INSERT INTO customerLoginEmail (email) values ('$email')");		
			}
		}
	}		
}



die;

$emails = array();
$res = getResult("select distinct email from customerLoginEmail order by email");
if (checkResult($res))
{
	executeQuery("TRUNCATE TABLE customerLoginEmail");
	while( $line = mysql_fetch_assoc($res) )
	{
		extract($line);
		$email = trim($email);
		$email = fixString($email);
		$email = strtolower($email);
		if (array_search($email, $emails) === false)
		{
			array_push($emails, $email);
			executeQuery("INSERT INTO customerLoginEmail (email) VALUES ('$email')");
		}
	}
	showArray($emails);
}
showArray($CUSTOMER_SITES);
die;

die;

$x = generateStats('003067');
die("-- $x");

//updateTankStats($monitorID
$query = "SELECT distinct monitorID FROM data ORDER BY monitorID";
$mres = getResult($query);
while ($mline = mysql_fetch_assoc($mres))
{
	extract($mline);
	updateTankStats($monitorID);
	echo("MontiorID: $monitorID<br>"); 
}

die;

//sendDeliveryEmails(901, 1);
die;


//$val = getDeliveryAvg('OC1HwkS');
//die("$val");

//
//$r = reorderInfo('SFBrynt', '2008-10-10');
////$r = reorderInfo('SJOEPri', '2008-10-16');
//showArray($r);
//die;
//

//getDeliveryAvg('Agrium_');
//die;





$deliveryDate = '2008-09-12';
updateDeliveryTankStats(575, $deliveryDate);
die;

//
//$a = reorderInfo('AlbLS01');
//showArray($a);
//echo('<hr>');
//$a = reorderInfo('AlbLS01', '2008-09-19');
//showArray($a);
//echo('<hr>');
//$a = reorderInfo('Agrium_');
//showArray($a);
//echo('<hr>');
//$a = reorderInfo('Agrium_', '2008-09-14');
//showArray($a);


//die;

// tanks without stats
//$query = "SELECT count(t.monitorID) as cnt, t.monitorID
//FROM tank t
//LEFT OUTER JOIN tankStats s ON t.monitorID = s.monitorID
//group by t.monitorID
//having cnt = 2";
//
//$query = "SELECT t.monitorID, s.readingDate, t.tankName
//FROM tank t
//LEFT OUTER JOIN tankStats s ON t.monitorID = s.monitorID
//WHERE s.monitorID IS NULL";
//
	
generateStats('TarpSpr'); 
die;

// generate stats for just one tanks
$monitorID = 'Agrium_';
$res = getResult("SELECT max(date) as date, monitorID FROM data WHERE monitorID='$monitorID' and
	cast(date as date) >= DATE_ADD(cast(NOW() as date), INTERVAL -25 day) group by cast(date as date) order by date desc"); 
if (checkResult($res))
{
	while( $line = mysql_fetch_assoc($res) )
	{
		extract($line);
		generateStats($monitorID, "'$date'");
		//echo("generateStats($monitorID, \"'$date'\");<br>");
	}
} 
die;


// generate stats for all tanks
$query = "SELECT distinct monitorID FROM data ORDER BY monitorID";

// find tanks with only 2 rows of stats (weird)
$query = "SELECT count(t.monitorID) as cnt, t.monitorID
FROM tank t
LEFT OUTER JOIN tankStats s ON t.monitorID = s.monitorID
group by t.monitorID
having cnt = 2";

$query = "SELECT distinct monitorID FROM data ORDER BY monitorID";
$mres = getResult($query);
while ($mline = mysql_fetch_assoc($mres))
{
	extract($mline);
	echo("MontiorID: $monitorID<br>"); 
	// last 25 readings from Agrium
	$res = getResult("SELECT max(date) as date, monitorID FROM data WHERE monitorID='$monitorID' and
		cast(date as date) >= DATE_ADD(cast(NOW() as date), INTERVAL -25 day) group by cast(date as date) order by date desc"); 
	if (checkResult($res))
	{
		while( $line = mysql_fetch_assoc($res) )
		{
			extract($line);
			generateStats($monitorID, "'$date'");
			//echo("generateStats($monitorID, \"'$date'\");<br>");
		}
	} 
}
die;


$time = "01:00:00";
list($h, $m, $s) = explode(':', $time);
$s = $s > 59 ? 59 : $s;
$m = $m > 59 ? 59 : $m;
$time = "$h:$m:$s";


die($time);
					

$a = 1123.75;
$b = (string)$a;
list($x, $y) = explode('.', $b);
//bigecho($a1);

die("$x  --  $y");


$zipfile = fopen('ZIP_CODES.txt', 'r');
if ($zipfile)
{
	while (!feof($zipfile))
	{
		$line = fgets($zipfile);
		list($zipcode, $lat, $long, $city, $state) = explode(',', $line);
		if (empty($zipcode) || empty($lat) || empty($long) || empty($city) || empty($state))
			continue;
	
	
		$lat = str_replace('+', '', $lat);
		$long = str_replace('+', '', $long);
		
		$result = '';
		$city = str_replace('"', '', $city);
		$words = explode(" ", $city);
		for ($i=0; $i<count($words); $i++)
		{
			$s = strtolower($words[$i]);
			$s = substr_replace($s, strtoupper(substr($s, 0, 1)), 0, 1);
			$result .= "$s ";
			$string = trim($result);
		}
		$city = '"' . $string . '"';
	
	
	
		$query = "insert into zipcodes (zip, lat, lng, city, state) values ($zipcode, $lat, $long, $city, $state)";
		executeQuery($query);
	}
	fclose($zipfile);
}

?>