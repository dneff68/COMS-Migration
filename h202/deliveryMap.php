<?php
	$markerArray = array();
	$_SESSION['DELIVERY_DATA'] = false;
    $zipfilt = '';
    $siteID = '';
    $monitorID = '';
    $lat = '';
    $lng = '';
    $zip = '';
    $siteLocationName = '';
    $tankID = '';
    $contact = '';
    $contactPhone = '';
    $address = '';
    $city = '';
    $state = '';
    $lastRead = '';
    $statmsg = '';
    $marker = '';

error_log("REGION_FILTER: " . $_SESSION['REGION_FILTER']);

	if (!empty($_SESSION['REGION_FILTER']) && $_SESSION['REGION_FILTER'] != 'all')
	{
		$regfilt = "and s.regionID=" . $_SESSION['REGION_FILTER'];
		if (true)
		{
			$regfilt = getRegionFilter();
		}
	}
error_log("regfilt is = " . $regfilt);

if ($_SESSION['DELIVERY_DATA'])
	{
		//$zipfilt = " AND s.zip LIKE '" . $DELIVERY_DATA['zip'] . "%'";
	}
	
	$query = "select DISTINCT s.siteID, s.siteLocationName, s.address, s.city, s.state, s.contact, s.contactPhone, s.contactEmail, s.zip, z.lat, z.lng 
	from site s, monitor m, zipcodes z where s.zip=z.zip and m.siteID=s.siteID $zipfilt $regfilt";
	$res = getResult($query);
	$debug = '';
	$neffstop = true;
	if (checkResult($res))
	{
		$zips = '';
		while ($line = $res->fetch_assoc())
		{
			extract($line);
			
			$query = "select t.tankID, t.tankName, t.capacity, m.monitorID from monitor m, tank t where t.monitorID=m.monitorID and m.siteID=$siteID";
			$tres = getResult($query);

			if (checkResult($tres))
			{
				while ($line = mysqli_fetch_assoc($tres))
				{
					extract($line);					
//error_log("Tank: $tankName");
					// Filter out based on status
					$allow = true;
					$level = checkTankLevel($monitorID);
					$mkrcolor = 'green';

					list($levelkey, $levelmsg) = explode(',', $level);
					if ($levelkey == 'Reorder')
					{
						$mkrcolor = 'yellow';
					}					
					elseif ($levelkey == 'Low')
					{
						$mkrcolor = 'orange';
					}					
					elseif ($levelkey == 'Critical')
					{
						$mkrcolor = 'red';
					}					

					if (!empty($_SESSION['STATUS_FILTER']))
					{
					    if ($_SESSION['STATUS_FILTER'] != "all")
                        {
                            // need to pars the result if not 0.  check for each stat key
                            //$debug .= "$monitorID : $levelkey == $_SESSION['STATUS_FILTER']<br>";
                            $allow = $levelkey == $_SESSION['STATUS_FILTER'];
                        }
					}
//                    if ($neffstop) error_log("$allow = $levelkey == ". $_SESSION['STATUS_FILTER']);
//                    $neffstop = false;

                    $neffstop = false;
                    $html = '';
					$email = '';
					$zippart = '';
					if ($allow)
					{
						//$debug .= "$monitorID --- $levelkey<br>";
						$lat = round($lat, 4);
						$lng = round($lng, 4);
						$latlong = "$lat, $lng";

						// load array;
						$zippart = substr($zip, 0, 3);
						// show multi tank
                        $html = str_replace("'", "&#039;", "<div style=\"width:300px\">Site: $siteLocationName (<a href=\"javascript:surfDialog(\'/charts/tankGraph.php?tab=2&tankID=$tankID\', 725, 600, window, false)\">graph</a>&nbsp;<a href=\"deliveryDetails.php?zip=$zippart\" target=\"detailsFrame\">group</a>)<br>Contact: $contact<br>$contactPhone$email<hr>$address<br>$city, $state $zip<br><b>$lastRead$statmsg</b></div>");
					    $mkey = str_replace('-', '_', $monitorID);
						//$marker = "\nvar point = new GLatLng($latlong);\n";
						//$marker .= "marker" . $mkey . " = createMarker($lat, $lng, '$zippart', '$html', $mkrcolor" . "icon);\n";
                        $marker .= "createMarker($lat, $lng, '$zippart', '$html', $mkrcolor" . "icon);\n";
						//$marker .= "marker" . $mkey . ".tankID = \"$zippart\";\n";
						//$marker .= "marker" . $mkey . ".html = '$html';\n";
						//$marker .= "map.addOverlay(marker" . $mkey . ");\n";
						$zippart = $mkey;
						//error_log($marker);
						$markerArray[$zippart] = $marker;
					}
				}		
			}
			//showArray($markerArray);
		}
	}



?>