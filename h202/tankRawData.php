<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

function echoRawData(&$result, $title='defalut', $width='', $units='Gallons', $diameter=1)
{
	global $monitorID, $_SESSION['USERTYPE'];
	if (mysql_num_rows($result) <= 0)
	{
	  return;
	}
	if ($title == 'defalut')
	{
		echo "Total Rows: ".mysql_num_rows($result);
	}
	else
	{
		echo "<span class='spinMedTitle'>$title</span>";	
	}
	mysql_data_seek($result, 0);
	$fieldCnt = mysql_num_fields($result);
	
	$width = empty($width) ? '' : "width='$width'";
	
	echo "<table border='1' cellspacing='0' cellpadding='3' bordercolor='#cccccc' $width>\n<tr>\n";
	$ReadingFieldPos = -1;
	for ($i = 0; $i < $fieldCnt; $i++)
	{
	  $fn = mysql_field_name($result, $i);
	  if ($fn == 'Reading')
	  {
			$ReadingFieldPos = $i;
	  }
	  
	  if ($fn == 'badReading') // exclude this field
	  {
		  continue;
	  }
	  
	  echo "<td align='center' class='spinTableTitle'>$fn</th>";
	}
	echo "</tr>";
	
	while ($line = mysql_fetch_array($result))
	{
			$badReading = $line['badReading'] == 1 ? ' (MR)' : '';
			$dt = $line[2]; // reading date 
			$query = "SELECT 
						dt.monitorID, 
						d.deliveryDate
					from 
						delivery d, 
						deliveryTanks dt 
					WHERE 
						d.deliveryID=dt.deliveryID and 
						d.deliveryDate = cast('$dt' as date) and 
						dt.monitorID='$monitorID'";
			$res = getResult($query);
			
			$class = checkResult($res) ? "class='spinAlert'" : '';
			echo "<tr class='spinTableBarOdd'>";
			for ($i = 0; $i < $fieldCnt; $i++)
			{
				$fn = mysql_field_name($result, $i);
				  if ($fn == 'badReading') // exclude this field
				  {
					  continue;
				  }
				
				
				if ($ReadingFieldPos == $i)
				{
				  if ($line['units'] == 'Inches')
				  {
				  	$line[$i] = inchToGal($line[$i], $diameter);
				  }
				  $datetime=$line[$i-1]; // date should be previous field value
					// check to see if the date matches the date of a delivery

				  $monitorID = $line[$i-2]; // monitorID should be two positoins to the left
				  if ( $_SESSION['USERTYPE'] != 'customer' )
				  {
				  	$line[$i] = "<a href='javascript:surfDialog(\"changeReading.php?monitorID=$monitorID&datetime=$datetime\", 460, 210, window, true)'>" . $line[$i] . '</a>' . $badReading;
				  }
				}
				echo "<td align='left' $class>$line[$i]</td>";
			}
			echo "</tr>";
	}
	echo "</table>";
	mysql_data_seek($result, 0);
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Statistics</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="JavaScript" src="datetimepicker.js"></script>
<script src="http://www.customhostingtools.com/Scripts/AC_RunActiveContent.js" type="text/javascript"></script>
</head>

<body>
<div class='spinAlert'>Readings in red indicate a delivery was scheduled</div>
<?
if (empty($DATE_ASCENDING))
{
	session_register('DATE_ASCENDING');
}


$DATE_ASCENDING = $desc == 0 || empty($desc) ? 'DESC' : '';

$readingDateTitle = 'Reading Date';
$dateLink = $desc == '0' || empty($desc) ? "<a style=\"color:#EEE\" href=\"/tankRawData.php?monitorID=$monitorID&desc=1\">descending</a>" : "<a style=\"color:#EEE\" href=\"/tankRawData.php?monitorID=$monitorID&desc=0\">ascending</a>";
$readingDateTitle = "Reading Date ($dateLink)";

if ($usingDefaultStart == FALSE)
{
	$query = "SELECT distinct units, monitorID as 'Monitor ID', date as '$readingDateTitle', if(units='Gallons',round(value), value) as 'Reading', badReading FROM data WHERE monitorID='$monitorID' and date >= '$startDate' and date < DATE_ADD(date, INTERVAL 1000 DAY)  ORDER BY date $DATE_ASCENDING";
	$res = getResult($query);
}
else
{
	$res = getResult("SELECT units, monitorID as 'Monitor ID', date as '$readingDateTitle', if(units='Gallons',round(value), value) as 'Reading', badReading FROM data WHERE monitorID='$monitorID' and date >= DATE_ADD(NOW(), INTERVAL -1000 DAY) ORDER BY date $DATE_ASCENDING limit 1000");
}

if (checkResult($res))
{
	$units = 'Gallons';
	$diameter = 1;
	$unitRes = getResult("Select t.diameter from tank t where t.monitorID='$monitorID' LIMIT 1");
	if (checkResult($unitRes))
	{
		$unitLine = mysql_fetch_assoc($unitRes);
		extract($unitLine);
	}
	
	echoRawData($res, '', '', $units, $diameter);
}
?>

</body>
</html>