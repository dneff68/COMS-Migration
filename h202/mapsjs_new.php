<?
error_reporting(E_PARSE | E_ERROR); 
ini_set("display_errors", 1); 		

session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

function stripEditorFormatting($str)
{
	$str = str_replace(chr(13), "", $str);
	$str = str_replace(chr(10), "", $str);
	$str = str_replace(chr(9), "", $str);
	return $str;
	
}
//die("$customerEmail = empty($customerEmail) ? $USERID : $customerEmail;");

$CUSTOMER_EMAIL = empty($CUSTOMER_EMAIL) ? $USERID : $CUSTOMER_EMAIL;

// get site location
$query = "SELECT distinct
				s.siteLocationName, 
				s.address, 
				s.city, 
				s.state, 
				s.zip, 
				cust.siteID as siteIDs, 
				s.LatLng, 
				s.mapZoomLevel,
				c.customerID, cust.name as customerName

			FROM 
				site s, customer cust, customerLoginEmail c, monitor m 
			WHERE 
c.customerID=cust.customerID and
				cust.siteID=s.siteID AND 
				c.email='$CUSTOMER_EMAIL' AND
				m.siteID=s.siteID"; // limit 1

$res = getResult($query);

if (checkResult($res))
{
	$line = $res->fetch_assoc();
	extract($line);
}

?>
var geocoder;
var map;
var mapView;
var ge = null;
var infoWindow;  

function initialize() 
{	
    
	globalize_id('map_canvas');
	geocoder = new google.maps.Geocoder();

	var latlng = new google.maps.LatLng(<?=$LatLng?>);
	var myOptions = {
	  center: latlng,
	  zoom: <?=$mapZoomLevel?>,
	  mapTypeId: google.maps.MapTypeId.TERRAIN
	}
	map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
	// one infoWindow us used for all balloons.
	infoWindow = new google.maps.InfoWindow();
   	var shadowMarker = new google.maps.MarkerImage('http://h202.customhostingtools.com/images/mkr_shadow.png', null, null, new google.maps.Point(6, 20), null);

	
	<?
	// draw each of the tank, trunkline, and sample points
	$sites = explode(',', $siteIDs);
	foreach ($sites as $siteID)
	{
		if (!empty($siteID)) drawSiteTanks($siteID);
	}
	?>
} // end initialize

<?
function drawSiteTanks($siteID)
{
	// get tanks for this site
	$query = "SELECT m.monitorID, 
				m.LatLng as tankLatLng
				FROM monitor m, site s WHERE m.siteID=s.siteID AND s.siteID=$siteID and m.LatLng != ''"; // AND m.monitorID NOT LIKE 'none-%' 

	$tanksRes = getResult($query);
	if (!checkResult($tanksRes)) return;

	$i = 1;
	while ($tankLine = mysql_fetch_assoc($tanksRes))
	{
		$tankLatLng = '';
		extract($tankLine); // gives us tankID
		
		if ( !empty($tankLatLng))
		{
			drawTrunkLine($monitorID);
			drawSamplePoints($monitorID);
			drawTank($monitorID, $tankLatLng, "$siteID$i");
		}
		$i++;
	}
	echo("\n");
}

function drawTank($tankID, $LatLng, $i)
{
	$query = "SELECT t.prodID, t.concentration as con, p.value
		FROM tank t, product p
		WHERE t.prodID = p.prodID
		AND t.monitorID =  '$tankID'";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		//die("$value $concentration");
		if (empty($con))
			$prodOut = htmlentities(" - $value");
		else
			$prodOut = htmlentities(" - $value ($con)"); 
	}

	$tankName = getTankName($tankID);
	$tankName .= $prodOut;
	list($lat, $lang) = explode(',', $LatLng);
	
	
	if ( $prodID == 2 || $prodID == 6 ) // fe
		echo( "\nimage = 'http://h202.customhostingtools.com/images/mkr_fe.png';" );
	elseif ($prodID == 5 || $prodID == 3 ) // h2o2
		echo( "\nimage = 'http://h202.customhostingtools.com/images/mkr_h2o2.png';" );
	else // bioxide
		echo( "\nimage = 'http://h202.customhostingtools.com/images/mkr_bioxide.png';" );

	
	echo("\ntankLatLng$i = new google.maps.LatLng($lat, $lang);");
	
	$marker = "marker$i";
	echo("\nvar $marker = new google.maps.Marker({
		  position: tankLatLng$i, 
		  map: map, 
		  title:\"$tankName\",
		  icon: image,
		  shadow: shadowMarker
	  });");
	
		// get tank balloon information
		$query = "SELECT 
				s.siteLocationName, 
				s.address, 
				s.city, 
				s.state, 
				s.zip, 		
				t.capacity, 
				t.usableVolume, 
				t.tankName, t.pumpCapacity
			FROM tank t, monitor m, site s
			WHERE t.monitorID=m.monitorID AND m.siteID=s.siteID and m.monitorID = '$tankID'";
		
		$tankInfoResult = getResult($query);
		if (checkResult($tankInfoResult))
		{
			$line = mysql_fetch_assoc($tankInfoResult);
			extract($line);
			$capacity = number_format($capacity);
			$usableVolume = number_format($usableVolume);

		}
		else
		{
			$pumpCapacity = 'na';
			$usableVolume = 'na';
			$tankName = $tankID;
		}
		
		$query = "SELECT value as tankLevel, DATE_FORMAT(date, '%Y-%m-%d %h:%m:%s') as readingDate FROM data WHERE monitorID='$tankID' ORDER BY date DESC LIMIT 1";
		$levelRes = getResult($query);
		if (checkResult($levelRes))
		{
			$levelLine = mysql_fetch_assoc($levelRes);
			extract($levelLine);
			$tankLevel = number_format($tankLevel);
			$levelOut = "$tankLevel<p style='font-size:smaller'>($readingDate)</p>";
		}
		
		$query = "SELECT latestDose as normalizedDose FROM tankStats WHERE monitorID='$tankID' ORDER BY readingDate DESC LIMIT 1";
		$doseRes = getResult($query);
		if (checkResult($doseRes))
		{
			$doseLine = mysql_fetch_assoc($doseRes);
			extract($doseLine);
			$normalizedDose = number_format($normalizedDose);
		}
			
		$alarmURL = 'javascript:surfDialog(\"customerAlarms.php?monitorID=' . $tankID . '\", 550, 300, window, false)';
		$balloon = "
			<table width='300' border='0' cellpadding='5' cellspacing='1'>
			  <tr class='spinMedTitle'>
				<td colspan='2' class='spinTableBarEven'>$tankName<br>
				  $address<br>
				  $city, $state $zip</td>
			  </tr>
			  <tr class='spinTableBarOdd'>
				<td width='126' align='right'>Capacity:</td>
				<td width='151'>$capacity</td>
			  </tr>
			  <tr class='spinTableBarOdd'>
				<td align='right'>Usable Volume:</td>
				<td>$usableVolume</td>
			  </tr>
			  <tr class='spinTableBarOdd'>
				<td align='right'>Pump Capacity:</td>
				<td>$pumpCapacity</td>
			  </tr>
			  <tr class='spinTableBarOdd'>
				<td align='right'>Tank Level:</td>
				<td>$levelOut</td>
			  </tr>
			  <tr class='spinTableBarOdd'>
				<td align='right'>Normalized Dose:</td>
				<td>$normalizedDose</td>
			  </tr>
			  <tr class='spinMedTitle'>
				<td colspan='2' align='center' class='spinNormalText'><a target='_blank' href='http://www.h2o2.com/technical-library/default.aspx?pid=135&name=H2O2-Manufacturer-Data'>MSDS</a>
				&nbsp;&nbsp;<a href='$alarmURL'>Alarms</a>
				</td>
			  </tr>
			</table>";	
		$balloon = stripEditorFormatting($balloon);
		$balloon = fixString($balloon);
		
	echo ("
	google.maps.event.addListener($marker, 'click', function(event, index) 
		{ 	
		infoWindow.setContent(\"$balloon\");
		infoWindow.close();
		infoWindow.position = tankLatLng$i;
		infoWindow.open(map,$marker);} );		
		$marker.setMap(map);");
	echo("\n// end of drawTank\n\n");
}

function drawSamplePoints($tankID)
{
	// get lat and lng
	$query = "select LatLng, samplePointID from samplePoint where tankID='$tankID'";
	$res = getResult($query);
	if (!checkResult($res)) return;
	
	$line = $res->fetch_assoc();
	extract($line);
	list($lat, $lang) = explode(',', $LatLng);
	
	echo( "\nimage = 'http://h202.customhostingtools.com/images/samplePoint.gif';
	var spLatLng = new google.maps.LatLng($lat, $lang);
	
	var marker = new google.maps.Marker({
		  position: spLatLng, 
		  map: map, 
		  title:\"$samplePointID\",
		  icon: image
	  });
	
	google.maps.event.addListener(marker, 'click', function() 
		{ 	
		infoWindow.close();
		setTimeout( 'surfDialog(\"/charts/customerGraph.php?monitorID=$tankID\", 835, 550, window, false, true)', 750 );	
		});");
}

function drawTrunkline($tankID)
{			
	// get samplePoint and trunkLine Names from processData
	$samplePointID 	= "-- n/a --";
	$flowlineID 	= "-- n/a --";
	$flowRate 		= "-- n/a --";
	$lagMinutes 	= '-- n/a --';
	$query = "SELECT p.samplePointID, p.flowlineID, p.flowRate, p.PPM, lt.lagMinutes FROM processData p, processLagTime lt 
			WHERE p.samplePointID=lt.samplePointID and p.monitorID = '$tankID' and p.samplePointID != '' ORDER BY date DESC LIMIT 1";
	$res = getResult($query);

	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
	}
	
	// get the count of lines in this trunk line. 
	$lineCount = 1;
	$query = "SELECT max(lineNumber) as lineCount FROM trunkLine WHERE tankID='$tankID'";
	$lineCountRes = getResult($query);
	if (checkResult($lineCountRes))
	{
		$countLine = mysql_fetch_assoc($lineCountRes);
		extract($countLine);
	}
	// $lineCount = 1;



for ($i = 1; $i <= $lineCount; $i++)
{
	// get tanks for site
	$query = "SELECT t.tankID, m.LatLng as tankLatLng, tl.position, tl.LatLng, tl.lineNumber 
		FROM tank t, monitor m, trunkLine tl 
		WHERE t.monitorID=tl.tankID AND t.monitorID=m.monitorID AND m.monitorID='$tankID' AND lineNumber=$i ORDER BY tl.position";
	error_log("line iteration: $query");
	$tankInfoResult = getResult($query);
	if (!checkResult($tankInfoResult))
	{
		return;
	}
	echo ("\n // BEGIN TRUNK LINE COORDINATES");
	$pathValues = '';
	$loopFlag = true;
	while ( $trunkLineValues = mysql_fetch_assoc($tankInfoResult) )
	{
		extract($trunkLineValues);
		if ($loopFlag)
		{
			echo ("\nvar trunkLineCoordinates" . $lineNumber . " = [];");
			$loopFlag = false;
		}
		if ($LatLng != '') 
		{
			echo ("\nvar latlng = new google.maps.LatLng($LatLng);\n"); 
			echo ("\ntrunkLineCoordinates" . $lineNumber . ".push(latlng);");
		}
	}

	$l = strlen($pathValues);
	if ($l > 0)
	{
		$samplePointLatLng = $LatLng;
	}

	$trunkColor = "#008800";
	$query = "SELECT f.monitorID FROM flowAlarm f, processData p WHERE f.monitorID=p.monitorID and p.flowlineID='$flowlineID' and f.cleared=0 ORDER BY f.date DESC LIMIT 1";
	$alarmRes = getResult($query);
	if (checkResult($alarmRes))
	{
		$trunkColor = "#dd0000";
	}
	
	$los = getLevelOfService($tankID);
	if ($los < 90)
	{
		$trunkColor = "#ffff33";
	}

	echo ("\n
	var trunkLinePath" . $lineNumber . " = new google.maps.Polyline({
	  path: trunkLineCoordinates" . $lineNumber . ",
	  strokeColor: \"$trunkColor\",
	  strokeOpacity: 1.0,
	  strokeWeight: 4
	});
	");

	$notes = '&nbsp;';
	if ($flowlineID != '-- n/a --')
	{
		$notes = "<a href='javascript:surfDialog(" . '\"' . "trunkLineNotes.php?id=$flowlineID" . '\"' . ", 600, 315, window, false, false)'>add/view notes</a>";
		
		$lastNoteLine = '';
		$query = "SELECT DATE_FORMAT(date, '%m/%d/%Y') as notedate, note as lastNote, user as noteAuthor FROM trunkLineNotes WHERE trunkLineID='$flowlineID' order by date desc limit 1";
		//error_log($query);
		$lastNoteRes = getResult($query);
		if (checkResult($lastNoteRes))
		{
			$lastNoteLine = mysql_fetch_assoc($lastNoteRes);
			extract($lastNoteLine);
			if (!empty($lastNote))
			{
				if (strlen($lastNote) > 50)
					$lastNote = substr($lastNote, 0, 50) . '... ';
			}
			//$notes = "$notedate: $noteAuthor<hr>$lastNote $notes";
			$notes = "$notedate by $noteAuthor<br><br>$lastNote &nbsp;&nbsp; $notes";
		}
	}
	$lagHr = round($lagMinutes / 60, 2);
	$balloon = "<table width='300' border='0' cellpadding='5' cellspacing='1'>
			  <tr class='spinTableTitle'>
				<td colspan='2'>$flowlineID</td>
			  </tr>
			  <tr class='spinTableBarOdd'>
				<td width='126' align='right'>Flow Rate:</td>
				<td width='151'>$flowRate (GPH)</td>
			  </tr>
			  <tr class='spinTableBarOdd'>
				<td width='126' align='right'>Lag Time:</td>
				<td width='151'>$lagHr (hr)</td>
			  </tr>
			  <tr class='spinTableBarOdd'>
				<td width='126' align='right'>Level of Service:				<div style='float:right; font-size:smaller'>past 24 hours</div>
				</td>
				<td width='151'>$los
				</td>
			  </tr>
			  <tr class='spinTableBarOdd'>
				<td width='100%' colspan='2' align='left' style='font-size:smaller'>$notes</td>
			  </tr>
			  <tr class='spinMedTitle'>
				<td colspan='2' align='center' class='spinNormalText'>&nbsp;</td>
			  </tr>
			</table>";
	$balloon = stripEditorFormatting($balloon);

	echo ("
	google.maps.event.addListener(trunkLinePath" . $lineNumber . ", 'click', function(event, index) 
		{ 	infoWindow.position = event.latLng;
			infoWindow.setContent(\"$balloon\");
			infoWindow.close();
			infoWindow.open(map);});		
			trunkLinePath" . $lineNumber . ".setMap(map);");
} // line loop
}

?>


	
function initCallback(object) 
{
  ge = object;
  ge.getWindow().setVisibility(true);
}
	 
function failureCallback(object) {
}
	

function codeAddress(address) 
{
geocoder.geocode( { 'address': address}, function(results, status) 
	{
	  if (status == google.maps.GeocoderStatus.OK) {
		map.setCenter(results[0].geometry.location);
		var marker = new google.maps.Marker({
			map: map, 
			position: results[0].geometry.location
		});
	} 
	else 
	{
		alert("Error: " + status);
	}
});
}
