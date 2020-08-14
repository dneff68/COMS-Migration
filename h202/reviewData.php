<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';
include_once 'h202Functions.php';
?>

<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
<title>Inventory Tank Management</title>
<link rel='stylesheet' TYPE='text/css' href='http://h202.customhostingtools.com/main.css' >
<SCRIPT LANGUAGE='javascript' TYPE='text/javascript' SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
</head>
<body>
<h3>Statistics Review</h3>
<?
$monitorRes = getResult("SELECT DISTINCT monitorID FROM data order by monitorID");

$output = '';
$cnt = 0;
while ($monitorLine = mysqli_fetch_assoc($monitorRes))
{
  extract($monitorLine);

  $statRes = getResult("SELECT * from tankStats WHERE monitorID='$monitorID' ORDER BY readingDate DESC LIMIT 1");
  $statLine = mysqli_fetch_assoc($statRes);
  extract($statLine);

  $nodose = $nodose == 1 ? 'Yes' : 'No';
  $normal = $normal == 1 ? 'Yes' : 'No';
  $noreading = $noreading == 1 ? 'Yes' : 'No';
  $high = $high == 1 ? 'Yes' : 'No';
  $low = $low == 1 ? 'Yes' : 'No';


$output .= "
 <table width='538' height='99' border='1' cellpadding='5' cellspacing='1'>
   <tr>
     <td width='140' nowrap='nowrap'><span class='spinNormalText'><strong>Monitor ID: $monitorID</strong></span></td>
     <td width='107' align='right' class='spinTableBarOdd'><span class='spinNormalText'>Latest Dose:</span></td>
     <td width='36' nowrap='nowrap' class='spinTableBarEven'><span class='spinNormalText'>$latestDose</span></td>
     <td width='104' align='right' class='spinTableBarOdd'>Status:</td>
     <td width='83' nowrap='nowrap' class='spinTableBarEven'>$levelStat</td>
   </tr>
   <tr>
     <td nowrap='nowrap'>&nbsp;</td>
     <td align='right' class='spinTableBarOdd'><span class='spinNormalText'>No Dose: </span></td>
     <td nowrap='nowrap' class='spinTableBarEven'><span class='spinNormalText'>$nodose</span></td>
     <td align='right' class='spinTableBarOdd'>Normal:</td>
     <td nowrap='nowrap' class='spinTableBarEven'>$normal</td>
   </tr>
   <tr>
     <td nowrap='nowrap'>&nbsp;</td>
     <td align='right' class='spinTableBarOdd'><span class='spinNormalText'>No Reading:</span></td>
     <td nowrap='nowrap' class='spinTableBarEven'><span class='spinNormalText'>$noreading</span></td>
     <td align='right' class='spinTableBarOdd'>Message:</td>
     <td nowrap='nowrap' class='spinTableBarEven'>$high_low_message</td>
   </tr>
   <tr>
     <td nowrap='nowrap'>&nbsp;</td>
     <td align='right' class='spinTableBarOdd'><span class='spinNormalText'>Average Dose:</span></td>
     <td nowrap='nowrap' class='spinTableBarEven'>$avgDose</td>
     <td align='right' class='spinTableBarOdd'>Low:</td>
     <td nowrap='nowrap' class='spinTableBarEven'>$low</td>
   </tr>";

	$output .= "<tr>
			<td colspan='5' nowrap='nowrap' class='spinNormalText'><strong>Last Two Readings</strong></td>
		  </tr>";

   $readRes = getResult("SELECT date, value from data where monitorID = '$monitorID' order by date desc LIMIT 2");
   if (checkResult($readRes))
   {
	   while ($readLine = mysqli_fetch_assoc($readRes))
	   {
			extract($readLine);
			$output .= "
			   <tr>
				 <td align='right' nowrap='nowrap'>$date</td>
				 <td colspan='4' align='left' class='spinTableBarOdd'>$value</td>
			   </tr>
			";
		}  
   }
	$output .= " </table><br><hr><br>";
	if ($cnt > 50)
	{
		echo $output;
		$output = '';
		$cnt = 0;
	}
} // end while stat
?>
<?=$output?>
 <p>&nbsp;</p>
</body>

</html>