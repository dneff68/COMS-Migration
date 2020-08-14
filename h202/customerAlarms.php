<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml"> 
<head> 
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" /> 
<title>Alarms</title> 
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" > 
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT> 
<script src="http://www.customhostingtools.com/lib/jquery.js" type="text/javascript"></script>

<script type="text/javascript">

function clearAlarm(id)
{
		$.get("customerSummaryAjax.php?action=clearAlarm&alarmID=" + id, function(data) {
			 $('#alarmLinkDiv' + id).html(data);
			//window.reload();
			
		});
}
</script>
 
</head> 
<body> 
<table width="500" border="1" align="center" cellpadding="5" cellspacing="0"> 
  <tr class="spinTableTitle">
  
<?
	// get flowLine Name
	$errorOut = "";

	$query = "SELECT flowLineID FROM processData WHERE monitorID='$monitorID' ORDER BY date DESC LIMIT 1";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
	}
	else
	{
		$flowLineID = "No Flow Line Information";
	}

	$tankName = getTankName($monitorID);

?>   
    <td colspan="3">Flowline Alarms: <?=$tankName?></td> 
  </tr> 
  <tr class="spinTableBarEven"> 
    <td width="102">Date</td>
    <td width="273">Description</td>
    <td width="87">&nbsp;</td> 
			  </tr>
           
<?         
//	$query = "SELECT alarmID, flowLineID, alarm, description, cleared, DATE_FORMAT(date, '%M %d, %Y %r') as alarmDate 
//				FROM flowAlarm 
//				where cleared=0 and monitorID='$monitorID' 
//				ORDER BY date DESC";
	$query = "SELECT alarmID, flowLineID, alarm, description, cleared, DATE_FORMAT(date, '%M %d, %Y %r') as alarmDate 
				FROM flowAlarm 
				where monitorID='$monitorID' 
				ORDER BY cleared DESC, date DESC";
	$res = getResult($query);
	if (checkResult($res))
	{
		while($line = $res->fetch_assoc())
		{
			extract($line);
			if ($alarm == 'low')
				$color = "#ffff33";
			elseif ($alarm == 'high')
				$color = "#dd0000";
					
			$description = str_replace('~', "<br /><div style='padding-left:15px'>", $description) . "</div>";
			
			$actionLink = $cleared == 1 ? "-- Cleared --" : "<a href='javascript:clearAlarm(\"$alarmID\")'>clear alarm</a>";
			
			echo("<tr class='spinTableBarOdd'>
				<td style='font-size:smaller'>$alarmDate</td>
				<td>$description</td>
				<td><div id='alarmLinkDiv$alarmID'>$actionLink</div></td>
			  </tr>");
		}
	}
	else
	{
		echo( "<tr class='spinTableBarOdd'>
				<td colspan='3'>-- no alarms --</td>
			  </tr>" );	
	}
?>            
    
</table> 
</body> 
</html> 