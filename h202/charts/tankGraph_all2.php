<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if (empty($SHOWING_SITE))
{
	session_register('SHOWING_SITE');
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<title>Variance Report</title>
<link rel="stylesheet" type="text/css" href="http://h202.customhostingtools.com/main.css" />
<script language="JavaScript" type="text/javascript" src='http://www.customhostingtools.com/lib/admin.js'></script>
<script language="JavaScript" src="/datetimepicker.js" type="text/javascript"></script>
<script src="http://www.customhostingtools.com/Scripts/AC_RunActiveContent.js" type="text/javascript"></script>
</head>

<script language="JavaScript" type="text/javascript">AC_FL_RunContent = 0;</script>
<script language="JavaScript" type="text/javascript"> DetectFlashVer = 0; </script>
<script language="JavaScript" src="AC_RunActiveContent.js" type="text/javascript"></script>
<script language="JavaScript" type="text/javascript">
<!--
var requiredMajorVersion = 9;
var requiredMinorVersion = 0;
var requiredRevision = 45;
-->
</script>
<body>
<table width="200" border="0" cellspacing="0" cellpadding="6">
  <tr>
    <td width="50%" align="left"><a href='tankGraph_all2.php?skip=prev' target='mainFrame'>&lt;&lt; Previous</a></td>
    <td width="50%" align="right"><a href='tankGraph_all2.php?skip=next' target='mainFrame'>Next &gt;&gt;</a></td>
  </tr>
</table>
<hr />

<?
$groupCount = david() ? 60 : 60;

if (empty($VARIANCE_DATA) || empty($VARIANCE_TITLE) || empty($VARIANCE_TARGET_DOSE) || empty($VARIANCE_DOSE))
{
	session_register('VARIANCE_DATA');
	session_register('VARIANCE_TITLE');
	session_register('VARIANCE_TARGET_DOSE');
	session_register('VARIANCE_DOSE');
}

if (!empty($monitorID))
{
	$moreWhere = "and t.monitorID='$monitorID'";
}
elseif ($skip == 'next')
{
	$moreWhere = "and s.siteLocationName > '$SHOWING_SITE'";
}
elseif ($skip == 'prev')
{
	$moreWhere = "and s.siteLocationName < '$SHOWING_SITE'";
	$desc = 'DESC';
}
	
$msg = '';
$query = "
	select s.siteLocationName, t.targetDosage, t.tankName, t.tankID, m.monitorID, m.units, t.diameter, t.capacity, p.value, t.concentration
			from monitor m, tank t, site s, product p
			where 
			t.monitorID=m.monitorID and 
			m.siteID = s.siteID and
			t.prodID = p.prodID 
			$moreWhere
			ORDER BY s.siteLocationName $desc LIMIT 1";

$res = getResult($query);
//bigecho($query);
//echoResults($res);
if (!checkResult($res))
{
	if ($skip == 'next')
	{
		$query = "
			select s.siteLocationName, t.targetDosage, t.tankName, t.tankID, m.monitorID, m.units, t.diameter, t.capacity, p.value, t.concentration
			from monitor m, tank t, site s, product p
			where 
			t.monitorID=m.monitorID and 
			m.siteID = s.siteID and
			t.prodID = p.prodID 
			ORDER BY s.siteLocationName DESC LIMIT 1";
		$res = getResult($query);
		echo( "<H3>End of Sites</H3>" );
	}
	elseif ($skip == 'prev')
	{
		$query = "
			select s.siteLocationName, t.targetDosage, t.tankName, t.tankID, m.monitorID, m.units, t.diameter, t.capacity, p.value, t.concentration
			from monitor m, tank t, site s, product p
			where 
			t.monitorID=m.monitorID and 
			m.siteID = s.siteID and
			t.prodID = p.prodID 
			ORDER BY s.siteLocationName LIMIT 1";
		$res = getResult($query);
		echo( "<H3>Beginning of Sites</H3>" );
	}
	else
		die("NO TANKS IN DATABASE");
}



while ($line = $res->fetch_assoc())
{
		extract($line);
		$SHOWING_SITE = $siteLocationName;
		$bannerBuffer = 1;
		include "graphBanner.php";
		
		$VARIANCE_DATA = '<row><string>Tank Level Gallons</string>';
		$VARIANCE_TITLE = "\n<row><null/>";
		$VARIANCE_DOSE = '<row><string>Normalized Dose</string>';
		$VARIANCE_TARGET_DOSE = $targetDosage > 0 ? "<row><string>Target Dose ($targetDosage gallons)</string>" : '<row><string>Target Dose (not set)</string>';
		$debug = '';
		$prevDose = 0;
		$doseOut = 0;
		$deliveryWasMade = false;
		$units = 'Gallons';
		$ures = getResult("SELECT m.units, t.diameter FROM monitor m, tank t WHERE t.monitorID=m.monitorID and m.monitorID = '$monitorID' LIMIT 1");
		if (checkResult($ures))
		{
			$uline = mysqli_fetch_assoc($ures);
			extract($uline);
		}
		
		if (!empty($startDate))
		{
			// set the value of $i to go back more than 11 days
			$daysRes = getResult("SELECT DATEDIFF(  NOW(), '$startDate' ) as daysAgo");
			$daysLine = mysqli_fetch_assoc($daysRes);
			extract($daysLine);
			$stopDay = max(0, $daysAgo - 11);
		}
		else
		{
			$daysAgo = 11;
			$stopDay = 0;
		}
		
		//die("daysAgo: $daysAgo   -     stopDay: $stopDay");
	
		for ($i = $daysAgo; $i >= $stopDay; $i--)
		{
			$doseOut = getDose($monitorID, $i, $debug);
		
			$resAvgDose = getResult("SELECT avgDose FROM tankStats WHERE monitorID='$monitorID' and 
							 cast(readingDate as date) = DATE_ADD( cast( NOW() AS date ) , INTERVAL -$i DAY )");
			if (checkResult($resAvgDose))
			{
				$line = mysqli_fetch_assoc($resAvgDose);
				extract($line);
			}
			else
			{
				$status = checkTankStatus($monitorID);
				list($statkey, $status) = explode(',', $status);
				if ($statkey == 'NoReading')
				{
					$prevAvgRes = getResult("SELECT avgDose as doseOut FROM tankStats WHERE monitorID='$monitorID' ORDER BY readingDate DESC");
					if (checkResult($prevAvgRes))
					{
						$prevAvgLine = mysqli_fetch_assoc($prevAvgRes);
						extract($prevAvgLine);
					}
				}
			}
			
			if ($statkey == 'NoReading')
			{
				// doseOut was set by the last average  
			}
			elseif ($doseOut < 0)
			{
				if ($prevDose > 0)
				{	
					// Tank volume has increased. Check for delivery
					$deliveryWasMade = false;
					$DelDaysAgo = $i + 1;
					
					$query = "SELECT d.deliveryDate
								FROM delivery d, deliveryTanks dt
								WHERE d.deliveryID = dt.deliveryID
								AND (
									d.deliveryDate = DATE_ADD( cast( NOW() AS date ) , INTERVAL -$DelDaysAgo DAY ) or
									d.deliveryDate = DATE_ADD( cast( NOW() AS date ) , INTERVAL -$i DAY )
									)
								AND dt.monitorID = '$monitorID'";  				
		
		
					$resDeliveryDate = getResult($query);
					
					if (checkResult($resDeliveryDate))
					{
						$deliveryWasMade = true;
					}
				
					if ( $deliveryWasMade)
					{
						$doseOut = $avgDose;
					}
				}
				else
				{
					// no previous dose.  Set to 0
					$doseOut = 0;
				}
			}
			elseif ($doseOut == -1)
			{
				$doseOut = $avgDose; //$prevDose; // dose returned 0.  Use last valid dose
			}
			else
			{
				$prevDose = $doseOut;
			}
			
			$VARIANCE_DOSE .= "\n<number tooltip='$doseOut'>$doseOut</number>";
			$VARIANCE_TARGET_DOSE .= $targetDosage > 0 ? "\n<number>$targetDosage</number>" : '<number>0</number>';
		}

		
		for ($i = $daysAgo; $i >= $stopDay; $i--)
		{
			$resDateFormatted = getResult("SELECT DATE_FORMAT(date, '%d') as 'day', value from data 
			where monitorID='$monitorID' and cast(date as date) = DATE_ADD(cast(NOW() as date), INTERVAL -$i DAY) ORDER BY date DESC LIMIT 1");
			if (checkResult($resDateFormatted))
			{
				$line = mysqli_fetch_assoc($resDateFormatted);
				extract($line);
		
				$value = $units == 'Inches' ? inchToGal($value, $diameter) : $value;
		
		
				$VARIANCE_DATA .= "\n<number tooltip='$value'>$value</number>";
				$VARIANCE_TITLE .= "\n<string>$day</string>";
			}
			else
			{
				// no reading on this day
				$tmpres = getResult("select DATE_FORMAT(DATE_ADD(NOW(), INTERVAL -$i DAY), '%d') as day");
				
				$VARIANCE_DATA .= "\n<number>0</number>";
				$VARIANCE_TITLE .= "\n<string>n/r</string>";
			}
		}

		$VARIANCE_DATA .= "</row>";
		$VARIANCE_TITLE .= '</row>';
		$VARIANCE_DOSE .= '</row>';
		$VARIANCE_TARGET_DOSE .= '</row>';
		
		$status = checkTankStatus($monitorID);
		list($status,$statusMsg) = explode(',', $status);
		
		$pid = getmypid();
		$t 	= time();
		$rand = "$pid.$t"; 
		
		$varOut = "
		<chart>
		<license>JTAJ-9N1PLHO.945CWK-2XOI1X0-7L</license>
		<chart_value prefix=''
			suffix='' 
					  decimals='0' 
					  decimal_char='.'
					  separator=''
					  position='top_above'
					  hide_zero='false' 
					  as_percentage='false'
					  font='arial' 
					  bold='true' 
					  size='10' 
					  color='FFFFFF' 
					  alpha='90'
					  />
			
			
			  <series_color>
				<color>009933</color>
			  </series_color>
			  <chart_data>
			$VARIANCE_TITLE
			$VARIANCE_DATA
			  </chart_data>
			  <chart_type>
				  <string>column</string>
			   </chart_type>
				<chart_label shadow='low' color='ffffff' alpha='90' size='10' position='top' prefix='' suffix='' decimals='0' separator='' as_percentage='false' />
			   
			</chart>";
		
		$f = fopen("/var/www/html/CHT/h202/charts/xml/variance_$monitorID.xml", 'w');
		fwrite($f, $varOut);
		fclose($f);
		//ddie("/var/www/html/CHT/h202/charts/xml/variance_$monitorID.xml");
		
		$doseOut = "
			<chart>
			<license>JTAJ-9N1PLHO.945CWK-2XOI1X0-7L</license>
			<chart_value prefix='' 
					  suffix='' 
					  decimals='0' 
					  decimal_char='.'
					  separator=''
					  position='cursor'
					  hide_zero='false' 
					  as_percentage='false'
					  font='arial' 
					  bold='true' 
					  size='10' 
					  color='000000' 
					  alpha='90'
					  />
			
			
			  <series_color>
				<color>009933</color>
				<color>cccccc</color>
			  </series_color>
			  <chart_data>
				$VARIANCE_TITLE
				$VARIANCE_DOSE
				$VARIANCE_TARGET_DOSE
			  </chart_data>
			  <chart_type>
				<string>line</string>
				<string>area</string>
			  </chart_type>
			   <chart_guide horizontal='true'
							vertical='false'
							thickness='1' 
							color='ff4400' 
							alpha='75' 
							type='dashed' 
							
							 
							radius='8'
							fill_alpha='0'
							line_color='ff4400'
							line_alpha='75'
							line_thickness='4'
						 
							size='10'
							text_color='ffffff'
							background_color='ff4400'
							text_h_alpha='90'
							text_v_alpha='90' 
							/>
			
			   <!-- use chart_label in combination with chart_guide -->
			   <chart_label position='cursor' />
			</chart>";
		
		$f = fopen("/var/www/html/CHT/h202/charts/xml/dose_$monitorID.xml", 'w');
		fwrite($f, $doseOut);
		fclose($f);
		// %26uniqueID%3D$rand   %26uniqueID%3D$rand
		echo "
		<script language='JavaScript' type='text/javascript'>
		<!--
		if (AC_FL_RunContent == 0 || DetectFlashVer == 0) {
			alert('This page requires AC_RunActiveContent.js.');
		} else {
			var hasRightVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);
			if(hasRightVersion) { 
				AC_FL_RunContent(
					'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,45,0',
					'width', '700',
					'height', '250',
					'scale', 'noscale',
					'salign', 'TL',
					'bgcolor', '#FFFFFF',
					'wmode', 'opaque',
					'movie', 'charts',
					'src', 'charts',
					'FlashVars', 'library_path=charts_library&xml_source=http://h202.customhostingtools.com/charts/xml/variance_$monitorID.xml', 
					'id', 'chart1',
					'name', 'chart1',
					'menu', 'true',
					'allowFullScreen', 'true',
					'allowScriptAccess','sameDomain',
					'quality', 'high',
					'align', 'middle',
					'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
					'play', 'true',
					'devicefont', 'false'
					); 
			} else { 
				var alternateContent = 'This content requires the Adobe Flash Player. '
				+ '<u><a href=http://www.macromedia.com/go/getflash/>Get Flash</a></u>.';
				document.write(alternateContent); 
			}
		}
		// -->
		</script>
		<noscript>
			<P>This content requires JavaScript.</P>
		</noscript>
		
		<script language='JavaScript' type='text/javascript'>
		<!--
		if (AC_FL_RunContent == 0 || DetectFlashVer == 0) {
			alert('This page requires AC_RunActiveContent.js.');
		} else {
			var hasRightVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);
			if(hasRightVersion) { 
				AC_FL_RunContent(
					'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,45,0',
					'width', '700',
					'height', '250',
					'scale', 'noscale',
					'salign', 'TL',
					'bgcolor', '#FFFFFF',
					'wmode', 'opaque',
					'movie', 'charts',
					'src', 'charts',
					'FlashVars', 'library_path=charts_library&xml_source=xml/dose_$monitorID.xml', 
					'id', 'chart1',
					'name', 'chart1',
					'menu', 'true',
					'allowFullScreen', 'true',
					'allowScriptAccess','sameDomain',
					'quality', 'high',
					'align', 'middle',
					'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
					'play', 'true',
					'devicefont', 'false'
					); 
			} else { 
				var alternateContent = 'This content requires the Adobe Flash Player. '
				+ '<u><a href=http://www.macromedia.com/go/getflash/>Get Flash</a></u>.';
				document.write(alternateContent); 
			}
		}
		// -->
		</script>
		<noscript>
			<P>This content requires JavaScript.</P>
		</noscript><br><br>";
}

?>

</body>
</html>