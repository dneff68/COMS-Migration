<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if (empty($LEADTIME_OVERRIDE))
{
	session_register('LEADTIME_OVERRIDE');
	$LEADTIME_OVERRIDE = 'default';
}

if (empty($SHOWTEMPSHUTDOWN))
{
	session_register('SHOWTEMPSHUTDOWN');
	$SHOWTEMPSHUTDOWN = 'yes';
}

if (empty($SHOWUNMONITORED))
{
	session_register('SHOWUNMONITORED');
	$SHOWUNMONITORED = 'no';
}

if (empty($USERID) || empty($USERTYPE))
{
	include 'login.php';
	die;
}

if ($genstat == 1)
{
	bigecho('generating all stats');
	generateAllStats();
}

if ( empty($VIEWMODE) || $tankAction == 'statusView')
{
	session_register('VIEWMODE');
	if ($VIEWMODE == 'deliveryView')
	{
		// switching, reset filter
		$ZIPCOLLECTION = 0;
		$status = 'all';
	}
	$VIEWMODE = 'statusView';
}


if (!empty($tankAction))
{
	// SHOWTERMINALS
	if ($tankAction == 'deliveryView')
	{
		if ($VIEWMODE == 'statusView')
		{
			// switching, reset filter
			$ZIPCOLLECTION = 0;
			$status = 'all';
		}
	
		$VIEWMODE = 'deliveryView';
	}
	elseif ($tankAction == 'showInactive')
	{
		if (empty($SHOWINACTIVE))
		{
			session_register('SHOWINACTIVE');
		}
		$SHOWINACTIVE = 'yes';
	}	
	elseif ($tankAction == 'hideInactive')
	{
		if (empty($SHOWINACTIVE))
		{
			session_register('SHOWINACTIVE');
		}
		$SHOWINACTIVE = 'no';
	}
	elseif ($tankAction == 'showTempShutdown')
	{
		if (empty($SHOWTEMPSHUTDOWN))
		{
			session_register('SHOWTEMPSHUTDOWN');
		}
		$SHOWTEMPSHUTDOWN = 'yes';
	}	
	elseif ($tankAction == 'hideTempShutdown')
	{
		if (empty($SHOWTEMPSHUTDOWN))
		{
			session_register('SHOWTEMPSHUTDOWN');
		}
		$SHOWTEMPSHUTDOWN = 'no';
	}
	elseif ($tankAction == 'showUnmonitored')
	{
		if (empty($SHOWUNMONITORED))
		{
			session_register('SHOWUNMONITORED');
		}
		$SHOWUNMONITORED = 'yes';
	}	
	elseif ($tankAction == 'hideUnmonitored')
	{
		if (empty($SHOWUNMONITORED))
		{
			session_register('SHOWUNMONITORED');
		}
		$SHOWUNMONITORED = 'no';
	}
	elseif ($tankAction == 'showFactories')
	{
		if (empty($SHOWFACTORIES))
		{
			session_register('SHOWFACTORIES');
		}
		$SHOWFACTORIES = 'yes';
	}
	else if ($tankAction == 'hideFactories')
	{
		$SHOWFACTORIES = 'no';
	}
	elseif ($tankAction == 'showCarriers')
	{
		if (empty($SHOWCARRIERS))
		{
			session_register('SHOWCARRIERS');
		}
		$SHOWCARRIERS = 'yes';
	}
	elseif ($tankAction == 'hideCarriers')
	{
		$SHOWCARRIERS = 'no';
	}
	elseif ($tankAction == 'showTerminals')
	{
		if (empty($SHOWTERMINALS))
		{
			session_register('SHOWTERMINALS');
		}
		$SHOWTERMINALS = 'yes';
		logAction("Viewing Terminals in Map");
	}
	elseif ($tankAction == 'hideTerminals')
	{
		$SHOWTERMINALS = 'no';
	}
	elseif ( strpos($tankAction, 'lead_') !== false)
	{
		list($blah, $LEADTIME_OVERRIDE) = explode('_', $tankAction);
//		ddie($LEADTIME_OVERRIDE);
	}
}


if (!empty($region))
{
	if (empty($REGION_FILTER))
	{
		session_register('REGION_FILTER');
	}
	
	list($var, $regID) = explode('_', $region);
	if ($setOn == 'true')
	{
		if (strpos($REGION_FILTER, $regID) === false)
		{
			$REGION_FILTER .= ":$regID";
		}
	}
	elseif ($setOn == 'false')
	{
		if (strpos($REGION_FILTER, $regID) !== false)
		{
			$REGION_FILTER = str_replace(":$regID", '', $REGION_FILTER);
		}
	}
}		

if (!empty($status))
{
	if (empty($STATUS_FILTER))
	{
		session_register('STATUS_FILTER');
	}
	//$ZIPCOLLECTION = 0;
	$STATUS_FILTER = $status == 'all' ? '' : $status;
}		

if ($USERTYPE == 'customer')
{
	$VIEWMODE = 'deliveryView';  // this is the only view customers are allowed to see
}

// get counts
$normCnt	= 0;
$nrCnt 		= 0;
$unassCnt 	= 0;
$ecCnt 		= 0;
$tsCnt 		= 0;
$HdoseCnt 	= 0;
$LdoseCnt 	= 0;
$ndCnt 		= 0;
$unmonCnt	= 0;
$allCnt		= 0;

$allCnt2 = 0;
$okCnt = 0;
$lowCnt = 0;
$criticalCnt = 0;
$reorderCnt = 0;

$inactiveFilt = $SHOWINACTIVE 		== 'yes' ? '' : "and m.status != 'Inactive'";
$tmpshutFilt  = $SHOWTEMPSHUTDOWN 	== 'yes' ? '' : "and m.status != 'Temporary Shutdown'";
$unmonFilt	  = $SHOWUNMONITORED 	== 'yes' ? '' : "and t.monitorID NOT LIKE 'none%'";


if ($VIEWMODE == 'statusView') 
{
	// Get no reading count
	$query = "select 
		distinct m.monitorID, m.status 
		from monitor m, tank t, site s, NoReadings nr 
		where t.monitorID=m.monitorID 
		and m.siteID = s.siteID 
		and m.monitorID=nr.monitorID 
		and m.status != 'Inactive' 
		and m.status = 'Active' $unmonFilt";	
	$res = getResult($query);

	$nrCnt = mysql_num_rows($res);
	
	$inac = $SHOWINACTIVE != 'yes' ? " && m.status != 'Inactive'" : '';
	$tmpshut = $SHOWTEMPSHUTDOWN == 'yes' ? '' : " && m.status != 'Temporary Shutdown'";
	
	$tmpshut = " && m.status != 'Temporary Shutdown'";
	
	
	$query = "select t.tankID, t.tankName from monitor m, tank t where t.monitorID=m.monitorID and m.monitorID LIKE 'none-%' $inac $tmpshut";
	$res = getResult($query);
	$unmonCnt = mysql_num_rows($res);
	
	$tsRes = getResult("SELECT count(monitorID) as tsCnt FROM monitor WHERE status='Temporary Shutdown'");
	$tsLine = mysql_fetch_assoc($tsRes);
	extract($tsLine);
	
	executeQuery("CREATE TEMPORARY TABLE gendate SELECT max(readingDate) as readingDate, monitorID 
				FROM tankStats GROUP BY monitorID");
	$query = "SELECT sum(ts.high) as HdoseCnt, sum(ts.low) as LdoseCnt, sum(ts.normal) as normalCnt, 
				sum(ts.unass) as unassCnt, sum(ts.exceedcap) as ecCnt 
				FROM monitor m, tankStats ts, gendate gd
				where m.monitorID=ts.monitorID and ts.readingDate=gd.readingDate 
				and ts.monitorID=gd.monitorID $tmpshut $inac";
				
	$res = getResult($query);
	$line = mysql_fetch_assoc($res);
	extract($line);
	
	$query = "select DISTINCT m.monitorID 
			from monitor m, tank t, site s
			where 
			t.monitorID=m.monitorID and 
			m.status = 'Inactive' and
			m.siteID = s.siteID";
	$res = getResult($query);
	$unassCnt = mysql_num_rows($res);
	$query = "select DISTINCT data.monitorID from data left join monitor ON data.monitorID=monitor.monitorID 
	where monitor.monitorID IS NULL and data.date > DATE_ADD(NOW(), INTERVAL -11 DAY)";
	$res = getResult($query);
	$unassCnt += mysql_num_rows($res);
	
	
	$query = "SELECT count(monitorID) as noMonitorCnt FROM tank WHERE monitorID LIKE 'none-%'";
	$res = getResult($query);
	$line = mysql_fetch_assoc($res);
	extract($line);
}


if (!empty($REGION_FILTER) && $REGION_FILTER != 'all')
{
	// get count with regards to the site region 
	$regFilt = getRegionFilter();
	$query = "SELECT DISTINCT t.monitorID as mcnt FROM tank t, monitor m, site s where t.monitorID=m.monitorID and m.siteID=s.siteID $regFilt";
}
else
{
	$query = "select 
				s.siteID 
			from monitor m, tank t, site s
			where 
				t.monitorID=m.monitorID and
				m.siteID = s.siteID 
				$inactiveFilt $tmpshutFilt $unmonFilt";
}
$res = getResult($query);
$allCnt = mysql_num_rows($res);

if ($VIEWMODE != 'statusView')
{
	$query = "select distinct m.monitorID 
	from 
	monitor m, tank t, site s
			where 
			t.monitorID=m.monitorID and
			m.siteID = s.siteID $inactiveFilt $unmonFilt $tmpshutFilt";
			
	$res = getResult($query);
	
	$lowCnt = 0;
	$okCnt = 0;
	$criticalCnt = 0;
	$reorderCnt = 0;
	
	if (checkResult($res))
	{
		while ($line = mysql_fetch_assoc($res))
		{
			extract($line);
			$status = checkTankLevel($monitorID);
			list($statkey, $status) = explode(',', $status);
		
			if ($LEADTIME_OVERRIDE != 'default')
			{
				$reorderData = reorderInfo($monitorID);
				if ($reorderData['daysToDelivery'] <= $LEADTIME_OVERRIDE)
				{
					$statkey = 'Reorder';
				}
				else
				{
					$statkey = 'Ok';
				}
			}
			
			if ($statkey == 'Low')
				$lowCnt++;
			if ($statkey == 'Ok')
				$okCnt++;
			if ($statkey == 'Critical')
				$criticalCnt++;
			if ($statkey == 'Reorder')
				$reorderCnt++;
		}
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Inventory Tank Management</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<style type="text/css">
<!--
.style1 {font-size: xx-large}
.style8 {font-size: 12px}
-->
</style>

<script language="javascript">
function hideMap()
{
	obj = window.parent.document.getElementById("mapFrame");	
	obj.style.borderStyle='none';
	obj.height = 0;
	mapVisible = 0;
	obj.src='';
	setCookie('mapVisible', 0, 100);
}

function showMap()
{
	obj = window.parent.document.getElementById("mapFrame");	
	obj.style.borderStyle='ridge';
	obj.height = 440;
	if (mapVisible == 0)
	{
		obj.src = 'map.php';
		mapVisible = 1;
	}
	setCookie('mapVisible', 1, 100);
}

function setRegion(region)
{
	window.location = "/index.php?region=" + region.id + '&setOn=' + region.checked;
}

function setStatusFilter(stat)
{
	window.location = "/index.php?status=" + stat;
}

function doAction(action)
{
	if (action == 'varReport')
	{
		selObj = document.getElementById('selAction');
		selObj.selectedIndex = 0;
		surfDialog("http://h202.customhostingtools.com/charts/VarianceReport.html", 750, 600, window, false);
	}
	else if (action == 'newCustomerList')
	{
		selObj = document.getElementById('selAction');
		selObj.selectedIndex = 0;
		surfDialog("http://h202.customhostingtools.com/newCustomerList.php", 850, 600, window, false);
	}
	else if (action == 'newCustomerForm')
	{
		selObj = document.getElementById('selAction');
		selObj.selectedIndex = 0;
		surfDialog("http://h202.customhostingtools.com/newCustomerForm.php", 900, 700, window, false);
	}
	else if (action == 'anomalyReport')
	{
		selObj = document.getElementById('selAction');
		selObj.selectedIndex = 0;
		surfDialog("http://h202.customhostingtools.com/multTankDetails_2.php", 750, 600, window, false);
	}
	else if (action == 'hideMap')
	{
		selObj = document.getElementById('selAction');
		selObj.options[1].value = 'showMap';
		selObj.options[1].text = 'Show Map';
		selObj.selectedIndex = 0;
		hideMap();
	}
	else if (action == 'showMap')
	{
		selObj = document.getElementById('selAction');
		selObj.options[1].value = 'hideMap';
		selObj.options[1].text = 'Hide Map';
		selObj.selectedIndex = 0;
		showMap();
	}
	else if (action == 'addTank')
	{
		window.location = "addTank.php?init=yes";
	}
	else
		window.location = "tanks.php?tankAction=" + action;

}

var mapVisible = getCookie('mapVisible');
var mapFrameHeight = mapVisible = 1 ? 440 : 0;
function setmapvis()
{
	//return; 
	var mapVisible = getCookie('mapVisible');
	if (mapVisible == 1)
	{
		doAction('showMap');
	}
	else
	{
		doAction('hideMap');
	}
}

//timeoutval = 1200000; // reload in 20 minutes automatically
//setTimeout('location.reload()', timeoutval);

</script>

</head>

<body onload="setmapvis()">
<div id='ActionDiv' name='ActionDiv' style='visibility:hidden;position:absolute;left:0;top:0'>
		<iframe id='ActionFrame' name='ActionFrame' style='background-color:#FFFFFF' scrolling=no width=0 height=0 align=top frameborder=0 
		src=''  align='left' allowtransparency='true' marginheight='0' marginwidth='0' ></iframe>
		</div>
	
<? 
	include 'banner.php'; 
	
	if (!empty($msg))
	{
		echo("<p align='center' class='spinAlert'>$msg</p>\n");
	}

bigEcho($database);
?>

<? if ($USERTYPE != 'customer'): ?>
<table width="750" border="0" align="center" cellpadding="5" cellspacing="1">
  <tr valign="middle" class="spinSmallTitle">    
    <td width="100%" valign="middle" nowrap="nowrap" colspan="2">
        <table width="381" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="64">Regions:</td>
            <td width="84"><input <?= strpos($REGION_FILTER, '1') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_1" id="reg_1" />
              North</td>
            <td width="85"><input <?= strpos($REGION_FILTER, '3') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_3" id="reg_3" /> 
            East
        </td>
            <td width="100"><input <?= strpos($REGION_FILTER, '5') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_5" id="reg_5" />
            S. West</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td><input <?= strpos($REGION_FILTER, '2') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_2" id="reg_2" /> 
              S. East
        </td>
            <td><input <?= strpos($REGION_FILTER, '4') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_4" id="reg_4" /> 
            West
        </td>
            <td>&nbsp;<!-- <input type="checkbox" name="reg_all" id="reg_all" /> 
            All Regions --></td>
          </tr>
        </table>
    </td>
    <td width="53" rowspan="2" valign="top" nowrap="nowrap"><label></label></td>
    <td width="94" valign="middle" nowrap="nowrap"><div align="right">Action:
        
    </div></td>
    <td width="240" valign="top" nowrap="nowrap"><select name="selAction" id="selAction" onchange="doAction(this.value)">
      <option value="choose" selected="selected">-- Choose Action --</option>
      <option value="hideMap">Hide Map</option>
      <option value="newCustomerForm">New Customer Form</option>
      <option value="newCustomerList">New Customer List</option>
      <option value="<?=$SHOWINACTIVE=='yes' ? 'hideInactive' : 'showInactive'?>"><?=$SHOWINACTIVE=='yes' ? 'Hide Inactive Tanks' : 'Show Inactive Tanks'?></option>
      <option value="<?=$SHOWTEMPSHUTDOWN=='yes' ? 'hideTempShutdown' : 'showTempShutdown'?>"><?=$SHOWTEMPSHUTDOWN=='yes' ? 'Hide Temporary Shutdown' : 'Show Temporary Shutdown'?></option>
      <option value="<?=$SHOWUNMONITORED=='yes' ? 'hideUnmonitored' : 'showUnmonitored'?>"><?=$SHOWUNMONITORED=='yes' ? 'Hide Unmonitored Sites' : 'Show Unmonitored Sites'?></option>
      <option value="<?=$SHOWFACTORIES=='yes' ? 'hideFactories' : 'showFactories'?>"><?=$SHOWFACTORIES=='yes' ? 'Hide' : 'Show'?> Suppliers</option>
      <option value="<?=$SHOWCARRIERS=='yes' ? 'hideCarriers' : 'showCarriers'?>"><?=$SHOWCARRIERS=='yes' ? 'Hide' : 'Show'?> Carriers</option>
      <option value="<?=$SHOWTERMINALS=='yes' ? 'hideTerminals' : 'showTerminals'?>"><?=$SHOWTERMINALS=='yes' ? 'Hide' : 'Show'?> Terminals</option>
      <? 
		  if ($_SESSION['USERTYPE'] == 'super')
		  {
			echo('<option value="addTank">Add Tank</option>');
		  }
	  ?>
      <option value="varReport">View Variance Report</option>
      <option value="anomalyReport">View Anomaly Report</option>
    </select></td>
  </tr>
  <tr valign="middle" class="spinSmallTitle">
    <td width="108" valign="middle" nowrap="nowrap">Status Filter: </td>
    <td nowrap="nowrap"><select name="status" class="spinNormalText" id="status" onchange="setStatusFilter(this.value)">
<? if ($VIEWMODE == 'statusView') : ?>	
      <option value="all" <?=$STATUS_FILTER=='all' ? 'Selected' : ''?>>All (<?=$allCnt?>)</option>
      <option value="Normal" <?=$STATUS_FILTER=='Normal' ? 'Selected' : ''?>>Normal (<?=$normalCnt?>)</option>
      <option value="NoReading" <?=$STATUS_FILTER=='NoReading' ? 'Selected' : ''?>>No Reading (<?=$nrCnt?>)</option>
      <option value="ExceedCap" <?=$STATUS_FILTER=='ExceedCap' ? 'Selected' : ''?>>Exceed Capacity (<?=$ecCnt?>)</option>
      <option value="TempShutdown" <?=$STATUS_FILTER=='TempShutdown' ? 'Selected' : ''?>>Temporary Shutdown (<?=$tsCnt?>)</option>
      <option value="H_Dose" <?=$STATUS_FILTER=='H_Dose' ? 'Selected' : ''?>>High Dose (<?=$HdoseCnt?>)</option>
      <option value="L_Dose" <?=$STATUS_FILTER=='L_Dose' ? 'Selected' : ''?>>Low Dose (<?=$LdoseCnt?>)</option>
      <option value="unmon" <?=$STATUS_FILTER=='unmon' ? 'Selected' : ''?>>Unmonitored Tanks (<?=$unmonCnt?>)</option>
      <option value="unass" <?=$STATUS_FILTER=='unass' ? 'Selected' : ''?>>Unassociated Readings (<?=$unassCnt?>)</option>
<? else : ?>
      <option value="all" <?=$STATUS_FILTER=='all' ? 'Selected' : ''?>>All (<?=$allCnt?>)</option>
      <option value="Ok" <?=$STATUS_FILTER=='Ok' ? 'Selected' : ''?>>Ok (<?=$okCnt?>)</option>
      <option value="Reorder" <?=$STATUS_FILTER=='Reorder' ? 'Selected' : ''?>>Reorder (<?=$reorderCnt?>)</option>
      <option value="Low" <?=$STATUS_FILTER=='Low' ? 'Selected' : ''?>>Low (<?=$lowCnt?>)</option>
      <option value="Critical" <?=$STATUS_FILTER=='Critical' ? 'Selected' : ''?>>Critical (<?=$criticalCnt?>)</option>
<? endif ; ?>	  
    </select>
<? if ($STATUS_FILTER == 'Reorder'): ?>
	<span class="spinNormalText">Lead Time:</span> <select name="leadTimeOverride" id="leadTimeOverride" onchange="doAction(this.value)">
    <option value="lead_default" <?= $LEADTIME_OVERRIDE == 'default' ? 'Selected' : ''?>>-Default-</option>
    <option value="lead_1" <?= $LEADTIME_OVERRIDE == '1' ? 'Selected' : ''?>>1</option>
    <option value="lead_2" <?= $LEADTIME_OVERRIDE == '2' ? 'Selected' : ''?>>2</option>
    <option value="lead_3" <?= $LEADTIME_OVERRIDE == '3' ? 'Selected' : ''?>>3</option>
    <option value="lead_4" <?= $LEADTIME_OVERRIDE == '4' ? 'Selected' : ''?>>4</option>
    <option value="lead_5" <?= $LEADTIME_OVERRIDE == '5' ? 'Selected' : ''?>>5</option>
    <option value="lead_6" <?= $LEADTIME_OVERRIDE == '6' ? 'Selected' : ''?>>6</option>
    <option value="lead_7" <?= $LEADTIME_OVERRIDE == '7' ? 'Selected' : ''?>>7</option>
    <option value="lead_8" <?= $LEADTIME_OVERRIDE == '8' ? 'Selected' : ''?>>8</option>
    <option value="lead_9" <?= $LEADTIME_OVERRIDE == '9' ? 'Selected' : ''?>>9</option>
    <option value="lead_10" <?= $LEADTIME_OVERRIDE == '10' ? 'Selected' : ''?>>10</option>
    <option value="lead_11" <?= $LEADTIME_OVERRIDE == '11' ? 'Selected' : ''?>>11</option>
    <option value="lead_12" <?= $LEADTIME_OVERRIDE == '12' ? 'Selected' : ''?>>12</option>
    <option value="lead_13" <?= $LEADTIME_OVERRIDE == '13' ? 'Selected' : ''?>>13</option>
    <option value="lead_14" <?= $LEADTIME_OVERRIDE == '14' ? 'Selected' : ''?>>14</option>
    <option value="lead_15" <?= $LEADTIME_OVERRIDE == '15' ? 'Selected' : ''?>>15</option>
    <option value="lead_16" <?= $LEADTIME_OVERRIDE == '16' ? 'Selected' : ''?>>16</option>
    <option value="lead_17" <?= $LEADTIME_OVERRIDE == '17' ? 'Selected' : ''?>>17</option>
    <option value="lead_18" <?= $LEADTIME_OVERRIDE == '18' ? 'Selected' : ''?>>18</option>
    <option value="lead_19" <?= $LEADTIME_OVERRIDE == '19' ? 'Selected' : ''?>>19</option>
    <option value="lead_20" <?= $LEADTIME_OVERRIDE == '20' ? 'Selected' : ''?>>20</option>
    </select>
<? endif; ?>    
    
    </td>
    <td width="94" align="right" valign="middle" nowrap="nowrap" class="spinSmallTitle">View Mode:  </td>
    <td width="240" align="right" valign="middle" nowrap="nowrap" class="spinSmallTitle">
      <div align="left">
        <input name="rdoStatus" type="radio" value="statusView" <?=$VIEWMODE == 'statusView' ? 'checked' : ''?> onclick="doAction('statusView')" />
      Status&nbsp;<input name="rdoStatus" type="radio" value="deliveryView" <?=$VIEWMODE == 'deliveryView' ? 'checked' : ''?>  onclick="doAction('deliveryView')"/>
        Deliveries </div>
    </div></td>
  </tr>
</table>
<? else: ?>
<!--<table width="750" border="0" align="center" cellpadding="5" cellspacing="1">
  <tr valign="middle" class="spinSmallTitle">

    <td width="94" align="right" valign="middle" nowrap="nowrap" class="spinSmallTitle">View Mode:  </td>
    <td  align="left" valign="middle" nowrap="nowrap" class="spinSmallTitle">
      <div align="left">
        <input name="rdoStatus" type="radio" value="statusView" <?=$VIEWMODE == 'statusView' ? 'checked' : ''?> onclick="doAction('statusView')" />
      Status&nbsp;<input name="rdoStatus" type="radio" value="deliveryView" <?=$VIEWMODE == 'deliveryView' ? 'checked' : ''?>  onclick="doAction('deliveryView')"/>
        Deliveries </div>
    </div></td>
  </tr>
</table>
--><? endif; ?>
<center>
<? if ($_COOKIE['mapVisible'] == 1): ?>
<iframe frameborder="0" align="top" name="mapFrame" id="mapFrame" width="750" height=440 src="map.php" style="border-style:ridge"></iframe><br />
<? else: ?>
<iframe frameborder="0" align="top" name="mapFrame" id="mapFrame" width="750" height=0  style="border-style:none"></iframe><br />
<? endif; ?>

<?
	if (!empty($deliveryID))
	{
		$id = "?id=$deliveryID";
		if ($update == 1)
		{
			$upd = "&update=1";
		}
	}
	
	if (!empty($init))
	{
		$init = "&initialize=yes";
	}
?>
<iframe align="middle" name="detailsFrame" id="detailsFrame" width="900" height=650 src="http://h202.customhostingtools.com/<?=$VIEWMODE == 'statusView' ? 'multTankDetails.php' : "deliveryDetails.php$id$upd$init"?>" frameborder="0" ></iframe>

</center>
<?
showSessionVars();
?>
</body>
</html>
