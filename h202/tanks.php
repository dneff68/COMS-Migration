<?php
session_start();
include_once '../lib/chtFunctions.php';
include_once '../lib/db_mysql.php';
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';

if (!isset($_SESSION['VIEWMODE']))
{
	$_SESSION['STATUS_FILTER'] 		= 'Normal';
	$_SESSION['SHOWINACTIVE'] 		= 'no';
	$_SESSION['SHOWTEMPSHUTDOWN'] 	= 'no';
	$_SESSION['SHOWUNMONITORED'] 	= 'no';
	$_SESSION['SHOWFACTORIES'] 		= '';
	$_SESSION['SHOWCARRIERS'] 		= '';
	$_SESSION['SHOWTERMINALS'] 		= '';
	$_SESSION['REGION_FILTER'] 		= '';
	$_SESSION['LEADTIME_OVERRIDE'] 	= '';
	$_SESSION['VIEWMODE']			= 'deliveryView';
	$_SESSION['DELIVERY_NOTES'] 	= '';
	$_SESSION['DELIVERY_TANKS'] 	= array();
	$_SESSION['TANK_DETAILS'] 		= array();			
}


if(isset($_GET['status']))
{	
    $status = $_GET['status'];
 	$_SESSION['STATUS_FILTER'] = $status;
 }

 bigEcho("STATUS_FILTER == " . $_SESSION['STATUS_FILTER']);
 bigEcho("VIEWMODE == " . $_SESSION['VIEWMODE']);

if (empty($_SESSION['LEADTIME_OVERRIDE'])) $_SESSION['LEADTIME_OVERRIDE'] = 'default';
//if (empty($_SESSION['SHOWTEMPSHUTDOWN']))  $_SESSION['SHOWTEMPSHUTDOWN'] = 'yes';
//if (empty($_SESSION['SHOWUNMONITORED']))   $_SESSION['SHOWUNMONITORED'] = 'no';

if (!isLoggedIn())
{
	include 'login.php';
	die;
}

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	extract($_POST);
	if ($_POST["genstat"] == 1)
	{
		bigecho('generating all stats');
		generateAllStats();
	}
}
else
{
	$tankAction='statusView';	
	if (isset($_GET['tankAction'])) $tankAction = $_GET['tankAction'];
}


		// bigEcho("Neff: " . gettype($_SESSION['SHOWFACTORIES']));
		// bigEcho("Neff: " . gettype($tankAction));
		// die;
//if (is_null($tankAction)) $tankAction='statusView';
if (isset($_GET['tankAction']))
{
	$_SESSION['VIEWMODE'] = $_GET['tankAction'];
	if ($_SESSION['VIEWMODE'] == 'deliveryView')
	{
		// switching, reset filter
		$ZIPCOLLECTION = 0;
		$status = 'all';
	}
}

// if ( !isset($_SESSION['VIEWMODE']) == '' || $tankAction == 'statusView')
// {
	
// 	if ($_SESSION['VIEWMODE'] == 'deliveryView')
// 	{
// 		// switching, reset filter
// 		$ZIPCOLLECTION = 0;
// 		$status = 'all';
// 	}

// 	//$_SESSION['VIEWMODE'] = 'statusView';
// }



if (!empty($tankAction))
{
	// SHOWTERMINALS
	if ($tankAction == 'deliveryView')
	{
		if ($_SESSION['VIEWMODE'] == 'statusView')
		{
			// switching, reset filter
			$ZIPCOLLECTION = 0;
			$status = 'all';
		}
	
		$_SESSION['VIEWMODE'] = 'deliveryView';
	}
	elseif ($tankAction == 'showInactive')
	{
		if ( empty($_SESSION['SHOWINACTIVE'])) $_SESSION['SHOWINACTIVE'] = 'yes';
	}	
	elseif ($tankAction == 'hideInactive')
	{
		if ( empty( $_SESSION['SHOWINACTIVE'])) $_SESSION['SHOWINACTIVE'] = 'no';
	}
	elseif ($tankAction == 'showTempShutdown')
	{
		if ( empty($_SESSION['SHOWTEMPSHUTDOWN'])) $_SESSION['SHOWTEMPSHUTDOWN'] = 'yes';
	}	
	elseif ($tankAction == 'hideTempShutdown')
	{
		if ( empty($_SESSION['SHOWTEMPSHUTDOWN'])) $_SESSION['SHOWTEMPSHUTDOWN'] = 'no';
	}
	elseif ($tankAction == 'showUnmonitored')
	{
		if ( empty($_SESSION['SHOWUNMONITORED'])) $_SESSION['SHOWUNMONITORED'] = 'yes';
	}	
	elseif ($tankAction == 'hideUnmonitored')
	{
		if ( empty($_SESSION['SHOWUNMONITORED'])) $_SESSION['SHOWUNMONITORED'] = 'no';
	}
	elseif ($tankAction == 'showFactories')
	{
		if ( empty($_SESSION['SHOWFACTORIES'])) $_SESSION['SHOWFACTORIES'] = 'yes';
	}
	else if ($tankAction == 'hideFactories')
	{
		$_SESSION['SHOWFACTORIES'] = 'no';
	}
	elseif ($tankAction == 'showCarriers')
	{
		if ( empty($_SESSION['SHOWCARRIERS'])) $_SESSION['SHOWCARRIERS'] = 'yes';
	}
	elseif ($tankAction == 'hideCarriers')
	{
		$_SESSION['SHOWCARRIERS'] = 'no';
	}
	elseif ($tankAction == 'showTerminals')
	{
		if ( empty($_SESSION['SHOWTERMINALS'])) $_SESSION['SHOWTERMINALS'] = 'yes';
		logAction("Viewing Terminals in Map");
	}
	elseif ($tankAction == 'hideTerminals')
	{
		$_SESSION['SHOWTERMINALS'] = 'no';
	}
	elseif ( strpos($tankAction, 'lead_') !== false)
	{
		list($blah, $_SESSION['LEADTIME_OVERRIDE']) = explode('_', $tankAction);
//		ddie($_SESSION['LEADTIME_OVERRIDE']);
	}
}


if (!empty($region))
{
	bigEcho($region . " tanks.php 109");
	if (empty($_SESSION['REGION_FILTER']))
	{
		$_SESSION['REGION_FILTER'] = '';
	}
	
	list($var, $regID) = explode('_', $region);
	if ($setOn == 'true')
	{
		if (strpos($_SESSION['REGION_FILTER'], $regID) === false)
		{
			$_SESSION['REGION_FILTER'] .= ":$regID";
		}
	}
	elseif ($setOn == 'false')
	{
		if (strpos($_SESSION['REGION_FILTER'], $regID) !== false)
		{
			$_SESSION['REGION_FILTER'] = str_replace(":$regID", '', $_SESSION['REGION_FILTER']);
		}
	}
}		

if (empty($_SESSION['STATUS_FILTER']))
{
	$_SESSION['STATUS_FILTER'] = 'all';
}
if (!empty($status))
{
	if ( empty($_SESSION['STATUS_FILTER'])) 
		$_SESSION['STATUS_FILTER'] = $status == 'all' ? '' : $status;
}		

if (!isset($_SESSION['USERTYPE']))	$_SESSION['USERTYPE'] = 'customer';

if ($_SESSION['USERTYPE'] == 'customer')
{
	$_SESSION['VIEWMODE'] = 'deliveryView';  // this is the only view customers are allowed to see
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

if ($_SESSION['SHOWINACTIVE'] == empty($_SESSION['SHOWINACTIVE'])) $_SESSION['SHOWINACTIVE'] = 'no';
if ($_SESSION['SHOWTEMPSHUTDOWN'] == empty($_SESSION['SHOWTEMPSHUTDOWN']))
{
	$_SESSION['SHOWTEMPSHUTDOWN'] = 'no';
} 
if ($_SESSION['SHOWUNMONITORED'] == empty($_SESSION['SHOWUNMONITORED'])) $_SESSION['SHOWUNMONITORED'] = 'no';


$inactiveFilt = $_SESSION['SHOWINACTIVE'] == 'yes' ? '' : "and m.status != 'Inactive'";
$tmpshutFilt  = $_SESSION['SHOWTEMPSHUTDOWN'] 	== 'yes' ? '' : "and m.status != 'Temporary Shutdown'";
$unmonFilt	  = $_SESSION['SHOWUNMONITORED'] 	== 'yes' ? '' : "and t.monitorID NOT LIKE 'none%'";


if ($_SESSION['VIEWMODE'] == empty($_SESSION['VIEWMODE'])) $_SESSION['VIEWMODE'] = 'statusView';

if ($_SESSION['VIEWMODE'] == 'statusView') 
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

	$nrCnt = $res->num_rows;
	
	$inac = $_SESSION['SHOWINACTIVE'] != 'yes' ? " && m.status != 'Inactive'" : '';
	if ($_SESSION['SHOWTEMPSHUTDOWN'] == 'yes')
	{
		$tmpshut = " && m.status != 'Temporary Shutdown'";	
	}
	else
	{
		$tmpshut = '';
	}
	// $tmpshut = $_SESSION['SHOWTEMPSHUTDOWN'] == 'yes' ? '' : " && m.status != 'Temporary Shutdown'";	
	// $tmpshut = " && m.status != 'Temporary Shutdown'";
	
	
	$query = "select t.tankID, t.tankName from monitor m, tank t where t.monitorID=m.monitorID and m.monitorID LIKE 'none-%' $inac $tmpshut";
	$res = getResult($query);
	$unmonCnt = $res->num_rows;
	
	$tsRes = getResult("SELECT count(monitorID) as tsCnt FROM monitor WHERE status='Temporary Shutdown'");
	$tsLine = $tsRes->fetch_assoc();
	extract($tsLine);
	
	$sess = session_id();
	showSessionVars();
//	die("session id = " . $sess);
	$foo = executeQuery("CREATE TABLE $sess SELECT max(readingDate) as readingDate, monitorID 
				FROM tankStats GROUP BY monitorID", "CREATE");
//die("CREATE TABLE $sess SELECT max(readingDate) as readingDate, monitorID FROM tankStats GROUP BY monitorID");
	$query = "SELECT sum(ts.high) as HdoseCnt, sum(ts.low) as LdoseCnt, sum(ts.normal) as normalCnt, 
				sum(ts.unass) as unassCnt, sum(ts.exceedcap) as ecCnt 
				FROM monitor m, tankStats ts, $sess gd
				where m.monitorID=ts.monitorID and ts.readingDate=gd.readingDate 
				and ts.monitorID=gd.monitorID $tmpshut $inac";
				
	$res = getResult($query);
	$line = $res->fetch_assoc();
	extract($line);
	$foo = executeQuery("drop table $sess");

	$query = "select DISTINCT m.monitorID 
			from monitor m, tank t, site s
			where 
			t.monitorID=m.monitorID and 
			m.status = 'Inactive' and
			m.siteID = s.siteID";
	$res = getResult($query);
	$unassCnt = $res->num_rows;
	$query = "select DISTINCT data.monitorID from data left join monitor ON data.monitorID=monitor.monitorID 
	where monitor.monitorID IS NULL and data.date > DATE_ADD(NOW(), INTERVAL -11 DAY)";
	$res = getResult($query);
	$unassCnt += $res->num_rows;
	
	
	$query = "SELECT count(monitorID) as noMonitorCnt FROM tank WHERE monitorID LIKE 'none-%'";
	$res = getResult($query);
	$line = $res->fetch_assoc();
	extract($line);
}


if (!empty($_SESSION['REGION_FILTER']) && $_SESSION['REGION_FILTER'] != 'all')
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
$allCnt = $res->num_rows;
if ($_SESSION['VIEWMODE'] != 'statusView')
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
		while ($line = $res->fetch_assoc())
		{
			extract($line);
			$status = checkTankLevel($monitorID);
			list($statkey, $status) = explode(',', $status);
		
			if ($_SESSION['LEADTIME_OVERRIDE'] != 'default')
			{
				$reorderData = reorderInfo($monitorID);
				if ($reorderData['daysToDelivery'] <= $_SESSION['LEADTIME_OVERRIDE'])
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
	window.location = "index.php?region=" + region.id + '&setOn=' + region.checked;
}

function setStatusFilter(stat)
{
	window.location = "index.php?status=" + stat;
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
		surfDialog("<?php echo $_SESSION['ROOT_URL'] ?>newCustomerList.php", 850, 600, window, false);
	}
	else if (action == 'newCustomerForm')
	{
		//alert("here");
		selObj = document.getElementById('selAction');
		selObj.selectedIndex = 0;
		surfDialog("<?php echo $_SESSION['ROOT_URL'] ?>newCustomerForm.php", 900, 700, window, false);
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
	
<?php 
	include 'banner.php'; 
	
	if (!empty($msg))
	{
		echo("<p align='center' class='spinAlert'>$msg</p>\n");
	}

//bigEcho($database);
?>

<?php if ($_SESSION['USERTYPE'] != 'customer'): ?>
	<table width="750" border="0" align="center" cellpadding="5" cellspacing="1">
  	<tr valign="middle" class="spinSmallTitle">    
    <td width="100%" valign="middle" nowrap="nowrap" colspan="2">
        <table width="381" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="64">Regions:</td>
            <td width="84"><input <?php echo strpos($_SESSION['USERID'], '1') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_1" id="reg_1" />
              North</td>
            <td width="85"><input <?php echo strpos($_SESSION['USERID'], '3') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_3" id="reg_3" /> 
            East
        </td>
            <td width="100"><input <?php echo strpos($_SESSION['USERID'], '5') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_5" id="reg_5" />
            S. West</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td><input <?php echo strpos($_SESSION['USERID'], '2') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_2" id="reg_2" /> 
              S. East
        </td>
            <td><input <?php echo strpos($_SESSION['USERID'], '4') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_4" id="reg_4" /> 
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
      <option value="<?php echo$_SESSION['SHOWINACTIVE']=='yes' ? 'hideInactive' : 'showInactive'?>"><?php echo$_SESSION['SHOWINACTIVE']=='yes' ? 'Hide Inactive Tanks' : 'Show Inactive Tanks'?></option>
      <option value="<?php echo$_SESSION['SHOWTEMPSHUTDOWN']=='yes' ? 'hideTempShutdown' : 'showTempShutdown'?>"><?php echo$_SESSION['SHOWTEMPSHUTDOWN']=='yes' ? 'Hide Temporary Shutdown' : 'Show Temporary Shutdown'?></option>
      <option value="<?php echo$_SESSION['SHOWUNMONITORED']=='yes' ? 'hideUnmonitored' : 'showUnmonitored'?>"><?php echo$_SESSION['SHOWUNMONITORED']=='yes' ? 'Hide Unmonitored Sites' : 'Show Unmonitored Sites'?></option>
      <option value="<?php echo$_SESSION['SHOWFACTORIES']=='yes' ? 'hideFactories' : 'showFactories'?>"><?php echo$_SESSION['SHOWFACTORIES']=='yes' ? 'Hide' : 'Show'?> Suppliers</option>
      <option value="<?php echo$_SESSION['SHOWCARRIERS']=='yes' ? 'hideCarriers' : 'showCarriers'?>"><?php echo$_SESSION['SHOWCARRIERS']=='yes' ? 'Hide' : 'Show'?> Carriers</option>
      <option value="<?php echo$_SESSION['SHOWTERMINALS']=='yes' ? 'hideTerminals' : 'showTerminals'?>"><?php echo$_SESSION['SHOWTERMINALS']=='yes' ? 'Hide' : 'Show'?> Terminals</option>
      <?php 
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
	<?php if ($_SESSION['VIEWMODE'] == 'statusView') : ?>	
	      <option value="all" <?php echo$_SESSION['STATUS_FILTER']=='all' ? 'Selected' : ''?>>All (<?php echo$allCnt?>)</option>
	      <option id="onormalCnt" value="Normal" <?php echo$_SESSION['STATUS_FILTER']=='Normal' ? 'Selected' : ''?>>Normal (<?php echo$normalCnt?>)</option>
	      <option value="NoReading" <?php echo$_SESSION['STATUS_FILTER']=='NoReading' ? 'Selected' : ''?>>No Reading (<?php echo$nrCnt?>)</option>
	      <option value="ExceedCap" <?php echo$_SESSION['STATUS_FILTER']=='ExceedCap' ? 'Selected' : ''?>>Exceed Capacity (<?php echo$ecCnt?>)</option>
	      <option value="TempShutdown" <?php echo$_SESSION['STATUS_FILTER']=='TempShutdown' ? 'Selected' : ''?>>Temporary Shutdown (<?php echo$tsCnt?>)</option>
	      <option id="oHdoseCnt" value="H_Dose" <?php echo$_SESSION['STATUS_FILTER']=='H_Dose' ? 'Selected' : ''?>>High Dose (<?php echo$HdoseCnt?>)</option>
	      <option value="L_Dose" <?php echo$_SESSION['STATUS_FILTER']=='L_Dose' ? 'Selected' : ''?>>Low Dose (<?php echo$LdoseCnt?>)</option>
	      <option value="unmon" <?php echo$_SESSION['STATUS_FILTER']=='unmon' ? 'Selected' : ''?>>Unmonitored Tanks (<?php echo$unmonCnt?>)</option>
	      <option value="unass" <?php echo$_SESSION['STATUS_FILTER']=='unass' ? 'Selected' : ''?>>Unassociated Readings (<?php echo$unassCnt?>)</option>
	<?php else : ?>
	      <option value="all" <?php echo$_SESSION['STATUS_FILTER']=='all' ? 'Selected' : ''?>>All (<?php echo$allCnt?>)</option>
	      <option value="Ok" <?php echo$_SESSION['STATUS_FILTER']=='Ok' ? 'Selected' : ''?>>Ok (<?php echo$okCnt?>)</option>
	      <option value="Reorder" <?php echo$_SESSION['STATUS_FILTER']=='Reorder' ? 'Selected' : ''?>>Reorder (<?php echo$reorderCnt?>)</option>
	      <option value="Low" <?php echo$_SESSION['STATUS_FILTER']=='Low' ? 'Selected' : ''?>>Low (<?php echo$lowCnt?>)</option>
	      <option value="Critical" <?php echo$_SESSION['STATUS_FILTER']=='Critical' ? 'Selected' : ''?>>Critical (<?php echo$criticalCnt?>)</option>
	<?php endif ; ?>	  
	    </select>
	<?php if ($_SESSION['STATUS_FILTER'] == 'Reorder'): ?>
		<span class="spinNormalText">Lead Time:</span> <select name="leadTimeOverride" id="leadTimeOverride" onchange="doAction(this.value)">
	    <option value="lead_default" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == 'default' ? 'Selected' : ''?>>-Default-</option>
	    <option value="lead_1" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '1' ? 'Selected' : ''?>>1</option>
	    <option value="lead_2" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '2' ? 'Selected' : ''?>>2</option>
	    <option value="lead_3" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '3' ? 'Selected' : ''?>>3</option>
	    <option value="lead_4" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '4' ? 'Selected' : ''?>>4</option>
	    <option value="lead_5" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '5' ? 'Selected' : ''?>>5</option>
	    <option value="lead_6" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '6' ? 'Selected' : ''?>>6</option>
	    <option value="lead_7" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '7' ? 'Selected' : ''?>>7</option>
	    <option value="lead_8" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '8' ? 'Selected' : ''?>>8</option>
	    <option value="lead_9" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '9' ? 'Selected' : ''?>>9</option>
	    <option value="lead_10" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '10' ? 'Selected' : ''?>>10</option>
	    <option value="lead_11" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '11' ? 'Selected' : ''?>>11</option>
	    <option value="lead_12" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '12' ? 'Selected' : ''?>>12</option>
	    <option value="lead_13" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '13' ? 'Selected' : ''?>>13</option>
	    <option value="lead_14" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '14' ? 'Selected' : ''?>>14</option>
	    <option value="lead_15" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '15' ? 'Selected' : ''?>>15</option>
	    <option value="lead_16" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '16' ? 'Selected' : ''?>>16</option>
	    <option value="lead_17" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '17' ? 'Selected' : ''?>>17</option>
	    <option value="lead_18" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '18' ? 'Selected' : ''?>>18</option>
	    <option value="lead_19" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '19' ? 'Selected' : ''?>>19</option>
	    <option value="lead_20" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '20' ? 'Selected' : ''?>>20</option>
	    </select>
	<?php endif; ?>    
    
    </td>
    <td width="94" align="right" valign="middle" nowrap="nowrap" class="spinSmallTitle">View Mode:  </td>
    <td width="240" align="right" valign="middle" nowrap="nowrap" class="spinSmallTitle">
      <div align="left">
        <input name="rdoStatus" type="radio" value="statusView" <?php echo$_SESSION['VIEWMODE'] == 'statusView' ? 'checked' : ''?> onclick="doAction('statusView')" />
      Status&nbsp;<input name="rdoStatus" type="radio" value="deliveryView" <?php echo$_SESSION['VIEWMODE'] == 'deliveryView' ? 'checked' : ''?>  onclick="doAction('deliveryView')"/>
        Deliveries </div>
    </div></td>
  	</tr>
	</table>
<?php endif; ?>
<center>


<?php if ($_COOKIE['mapVisible'] == 1): ?>
	<iframe frameborder="0" align="top" name="mapFrame" id="mapFrame" width="750" height=440 src="map.php" style="border-style:ridge"></iframe><br />
<?php else: ?>
	<iframe frameborder="0" align="top" name="mapFrame" id="mapFrame" width="750" height=0  style="border-style:none"></iframe><br />
<?php endif; ?>


<?php
	$id = '';
	$upd = '';
	$init = '';
	$status = isset($status) ? "&status=$status" : '';
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
	if ($_SESSION['VIEWMODE'] == 'statusView')
	{
		//include 'multTankDetails.php';
		$frameSRC = $_SESSION['ROOT_URL'] . "multTankDetails.php?sessionid=" . session_id();
		$_SESSION["TANK_PAGE"] = "MULTI_TANK_DETAILS";
		//writeLog('tanks', 609, "frameURL: $frameSRC");
	}
	else
	{
		$_SESSION["TANK_PAGE"] = "DELIVERY_DETAILS";
		$frameSRC = $_SESSION['ROOT_URL'] . "deliveryDetails.php$id$upd$init";
	}
?>
<iframe align="middle" name="detailsFrame" id="detailsFrame" width="900" height=650
		src="<?php echo $frameSRC;?>" frameborder="0" ></iframe>

</center>
<?php //showSessionVars(); ?>
</body>
</html>