<?php
	$markerArray = array();
    $siteID = '';
    $monitorID = '';
    $lat = '';
    $lng = '';
    $zip = '';
    $units = '';
    $siteLocationName = '';
    $tankID = '';
    $contact = '';
    $contactPhone = '';
    $address = '';
    $city = '';
    $state = '';

	if (!empty($_SESSION['REGION_FILTER']) && $_SESSION['REGION_FILTER'] != 'all')
	{
		$regfilt = "and s.regionID=" . $_SESSION['REGION_FILTER'];
		if (true)
		{
			$regfilt = getRegionFilter();
		}
	}
	$query = "select DISTINCT s.siteID, s.siteLocationName, s.address, s.city, s.state, s.contact, s.contactPhone, s.contactEmail, s.zip, z.lat, z.lng 
	from site s, monitor m, zipcodes z where s.zip=z.zip and m.siteID=s.siteID $regfilt";
//die($query);
	$res = getResult($query);
	$debug = '';

	//$debug .= $query . '<br>';

	if (checkResult($res))
	{
		$zips = '';
		while ($line = $res->fetch_assoc())
		{
			extract($line);
			
			if ($_SESSION['STATUS_FILTER'] == 'unmon')
			{
				$query = "select t.tankID, t.tankName, t.capacity, m.monitorID, m.units from monitor m, tank t where t.monitorID=m.monitorID and m.siteID=$siteID and m.monitorID LIKE 'none-%'";
			}
			else
			{
				$hideInactiveOnMap = '';	
				if ($_SESSION['SHOWINACTIVE'] != 'yes')
				{
					$hideInactiveOnMap = "m.status != 'Inactive' and";	
				}

				$hideTmpShutdownOnMap = '';	
				if ($_SESSION['SHOWTEMPSHUTDOWN'] != 'yes')
				{
					$hideTmpShutdownOnMap = "m.status != 'Temporary Shutdown' and";	
				}

				$hideUnmonitoredOnMap = '';	
				if ($_SESSION['SHOWUNMONITORED'] != 'yes')
				{
					$hideUnmonitoredOnMap = "t.monitorID NOT LIKE 'none%' and";	
				}

				$query = "select t.tankID, t.tankName, t.capacity, m.monitorID, m.units from monitor m, tank t 
				where $hideUnmonitoredOnMap $hideInactiveOnMap $hideTmpShutdownOnMap t.monitorID=m.monitorID and m.siteID=$siteID"; // and m.monitorID NOT LIKE 'none-%'";
			}
			$tres = getResult($query);
			if (checkResult($tres))
			{
				while ($line = mysqli_fetch_assoc($tres))
				{
					extract($line);					

					// Filter out based on status
					$debug .= substr($monitorID, 0, 5) . '<br>';
					$stat = checkTankStatus($monitorID, $_SESSION['STATUS_FILTER']);
					//$debug .= "$_SESSION['STATUS_FILTER'] == $stat<BR>";
					$allow = true;
					$mkrcolor = 'green';

					list($statkey, $statmsg) = explode(',', $stat);
						//$debug .= "$stat<br>";

					if ($statkey == 'TempShutdown')
					{
						$mkrcolor = 'gray';
					}					
					elseif ($statkey == 'NoReading')
					{
						$mkrcolor = 'orange';
					}					
					elseif ($statkey == 'ExceedCap')
					{
						$mkrcolor = 'purple';
					}					
					elseif ($statkey == 'H_Dose')
					{
						$mkrcolor = 'blue';
					}					
					elseif ($statkey == 'L_Dose')
					{
						$mkrcolor = 'white';
					}					
					elseif ($statkey == 'unmon')
					{
						$mkrcolor = 'black';
					}					
					
					if (!empty($_SESSION['STATUS_FILTER']))
					{
						// need to pars the result if not 0.  check for each stat key
						//$debug .= "$monitorID : $statkey == $_SESSION['STATUS_FILTER']<br>";
						$allow = $statkey == $_SESSION['STATUS_FILTER'];
					}
					
					$html = '';
					$email = '';
					$zippart = '';
					if ($allow)
					{
						//$debug .= "$monitorID --- $statkey<br>";
						$lat = round($lat, 4);
						$lng = round($lng, 4);
						$latlong = "$lat, $lng";

						// load array;
						$zippart = substr($zip, 0, 5);
						// show multi tank
						$marker = "\nvar point = new GLatLng($latlong);\n";
						$phone = str_replace('(', '\(', $phone);
						$phone = str_replace(')', '\0', $phone);
						$email = empty($contactEmail) ? '' : '<br>'.$contactEmail;
						
						$lastRead = '';
						$readRes = getResult("select value as lastRead, date as lastReadDate from data where monitorID='$monitorID' ORDER BY date DESC LIMIT 1");
						if (checkResult($readRes))
						{
							$readLine = mysqli_fetch_assoc($readRes);
							extract($readLine);
							$lastRead = "Last Read: $lastRead $units<br>"; 
						}

//						$html = str_replace("'", "&#039;", "<div style=\"width:200\"><!--<b>$tankName<br>Monitor: $monitorID</b><br>Site: $siteLocationName<br>-->Contact: $contact&nbsp;&nbsp;(<a href=\"javascript:surfDialog(\'tankGraph.php?tab=2&tankID=$tankID\', 725, 600, window, false)\">graph</a>&nbsp;<a href=\"multTankDetails.php?zip=$zippart\" target=\"detailsFrame\">list</a>)<br>$contactPhone$email<hr>$address<br>$city, $state $zip<br><b>$lastRead$statmsg</b></div>");
						$html = str_replace("'", "&#039;", "<div style=\"width:300px\">Site: $siteLocationName (<a href=\"javascript:surfDialog(\'/charts/tankGraph.php?tab=2&tankID=$tankID\', 725, 600, window, false)\">graph</a>&nbsp;<a href=\"deliveryDetails.php?zip=$zippart\" target=\"detailsFrame\">group</a>)<br>Contact: $contact<br>$contactPhone$email<hr>$address<br>$city, $state $zip<br><b>$lastRead$statmsg</b></div>");
					    $mkey = str_replace('-', '_', $monitorID);
						$marker .= "marker" . $mkey . " = createMarker(point, '$zippart', '$html', $mkrcolor" . "icon);\n";
						$marker .= "marker" . $mkey . ".tankID = \"$zippart\";\n";
						$marker .= "marker" . $mkey . ".html = '$html';\n";
						$marker .= "map.addOverlay(marker" . $mkey . ");\n";
						$zippart = $mkey;
						$markerArray[$zippart] = $marker;
					}
				}		
			}
		}
	}



?>