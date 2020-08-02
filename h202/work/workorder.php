<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';


function getHTMLPart($startString, $endString, $sourceString)
{
	$startStrLen = strlen($startString);
	$stpos = strpos($sourceString, $startString) + $startStrLen;
	$endpos = strpos($sourceString, $endString, $stpos);
	$subject = substr($sourceString, $stpos, $endpos-$stpos);
	return $subject;
}

function processWorkOrder($workKey)
{
	$query = "SELECT html FROM serviceHistory WHERE workKey=$workKey LIMIT 1";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = mysql_fetch_assoc($res);
		extract($line);
		$html = getHTMLPart('<HTML>', '</HTML>', $html);
		$sentDate = getHTMLPart('Sent ', '</div>', $html);
		$sentDate = str_replace("&nbsp;", ' ', $sentDate);
		$sentDate = str_replace('=', '', $sentDate);
		
		$datestr = str_replace(' - ', ' ', $sentDate);
		$datestr = strtotime($datestr);
		$datestr = date('Y-m-d H:i:s', $datestr);
		
		$workOrderNumber = getHTMLPart('>Work Order ', '</div>', $html);
		$html = str_replace("'", "''", $html);
		executeQuery("UPDATE serviceHistory 
						SET html='$html', dateRequested='$datestr', dateString='$sentDate', workOrderNumber='$workOrderNumber' 
						WHERE workKey=$workKey LIMIT 1");
		return "$sentDate~$workOrderNumber";
	}
	return false;
}


processWorkOrder(17);
processWorkOrder(18);
processWorkOrder(19);
processWorkOrder(20);
processWorkOrder(21);
processWorkOrder(22);
processWorkOrder(23);

return;
$wholeString = "";

$sentDate = getHTMLPart('Sent ', '/div', $wholeString);
$sentDate = str_replace("&nbsp;", ' ', $sentDate);
$sentDate = str_replace('=', '', $sentDate);
$sentDate = str_replace('&amp;nbsp;-&amp;nbsp;', ' ', $sentDate);
$sentDate = str_replace('&amp;nbsp;<br>&lt;', '', $sentDate);




$subject = getHTMLPart('Work Order ISSUED ', 'Date:', $wholeString);
if (strlen($subject) > 50)
{
	$subject = getHTMLPart('Work Order ISSUED ', 'From:', $wholeString);
}
$parts = explode(' ', $subject);
$monitorID = end($parts);  	




?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<body>
<?

echo("subject: $subject <br>");
showArray($parts);
echo("monitorID: $monitorID <br>");
echo("sent date: $sentDate <br>");
?>
</body>
</HTML>