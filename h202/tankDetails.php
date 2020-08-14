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
<title>Tank Details</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
</script>
<style type="text/css">
<!--
.style1 {font-size: 12px}
.style2 {font-size: 14px}
.style3 {font-size: 16px}
-->
</style>
</head>

<body>
<p class="spinNormalText"><a href="javascript:window.location='multTankDetails.php'">Return to List</a></p>
<?

if (empty($SELECTED_TANK))
{
	session_register('SELECTED_TANK');
}

$SELECTED_TANK = empty($tankID) ? $SELECTED_TANK : $tankID;
include "tankDetailsBanner.php";
$query = "select s.siteLocationName, t.tankName, t.tankID, m.monitorID, m.units, t.diameter
		from monitor m, tank t, site s
		where 
		t.monitorID=m.monitorID and 
		m.siteID = s.siteID and
		t.tankID='$SELECTED_TANK'";

$res = getResult($query);
if (checkResult($res))
{
	$line = $res->fetch_assoc();
	extract($line);
}

$query = "select DATE_FORMAT(date, '%m/%d/%Y %r') as 'date', value, DATE_FORMAT(processDate, '%m/%d/%Y %r') as 'processDate' from data where monitorID='$monitorID' order by date DESC LIMIT 1";
$res = getResult($query);
if (checkResult($res))
{
	$line = $res->fetch_assoc();
	extract($line);
}

$status = checkTankStatus($monitorID);
list($status,$statusMsg) = explode(',', $status);
?>
<table width="100%" border="1" align="center" cellpadding="5" cellspacing="1">
  <tr class="spinTableTitle">
	<td colspan="2">
			<table width="100%"><tr>	<td width="293" class="style2">Tank Information: </td>
			<td width="404"><div align="right">Status: <?=$statusMsg?></div></td>
		</tr></table></td>
  </tr>
  <tr class="spinTableBarOdd">
    <td width="92" class="spinSmallTitle"><span class="style2">Location</span></td>
    <td width="579" class="spinLargeTitle"><span class="style1 style1"><?=$siteLocationName?></span></td>
  </tr>
  <tr class="spinTableBarOdd">
    <td class="spinSmallTitle style2">Name</td>
    <td class="spinLargeTitle"><span class="style1">
      <?=$tankName?>
    </span> </td>
  </tr>
  <tr class="spinTableBarOdd">
    <td class="spinSmallTitle style2">Monitor ID </td>
    <td class="spinLargeTitle"><span class="style1">
      <?=$monitorID?>
    </span> </td>
  </tr>
  <tr class="spinTableTitle">
	<td colspan="2">
	<? $galval = $units == 'Inches' ? inchToGal($value, $diameter) : $value?>
			<table width="100%"><tr>	<td width="318" class="style2">Latest Tank Reading: <?= empty($value) ? 'No Data' : "$galval Gallons"?> </td>
			    <td width="379" nowrap="nowrap">
			      <div align="right" class="spinSmallTitle">
			         reading modified by --- on date		          </div>
		      </td>
		</tr></table></td>
  </tr>
    <tr class="spinTableBarOdd">
    <td nowrap="nowrap" class="spinSmallTitle">Reading Date/Time </td>
    <td class="spinLargeTitle"><span class="style1"><?=empty($date) ? 'No Data' : $date?> </span></td>
  </tr>
</table>

</body>
</html>
