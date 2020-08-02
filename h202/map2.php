<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>National Map</title>
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAggZ_Em_g9kxxflX7EQjuLRQENMdM6uuS34G-GTbpAM0p-5qUlBT2eVtvUQIL5QxVZ5TSo7XQp6D1pg" type="text/javascript"></script>

  </head>
  <body onunload="GUnload()">
  <p align="LEFT" class="spinNormalText">Use the map to zoom and and out to find tanks. Click and drag on the map to pan.</p>
    <div align="center" id="map" style="width: 100pct; height: 300px">
	<script type="text/javascript">
    //<![CDATA[


      if (GBrowserIsCompatible()) {
	  
      function createMarker(point,zip,iconObj) 
	  {
        var marker = new GMarker(point, iconObj);
        GEvent.addListener(marker, "click", function() {
          parent.frames[1].location='multTankDetails.php?zip=' + zip;
        });
        return marker;
      }	  

      function createFactoryMarker(point,html,iconObj) 
	  {
        var marker = new GMarker(point, iconObj);
        // The new marker "mouseover" listener        
        GEvent.addListener(marker,"mouseover", function() {
          marker.openInfoWindowHtml(html);
        });        
        
        return marker;
      }	  

	  
        var map = new GMap2(document.getElementById("map"));
		map.addControl(new GSmallZoomControl());
        map.setCenter(new GLatLng(39.1024, -094.5985), 4);
		map.enableDoubleClickZoom(); 
		var factoryicon = new GIcon();
		factoryicon.image = "http://h202.customhostingtools.com/images/factory.png";
		factoryicon.iconSize = new GSize(25, 25);
		factoryicon.shadowSize = new GSize(12, 20);
		factoryicon.iconAnchor =  new GPoint(6, 20);
		factoryicon.infoWindowAnchor =  new GPoint(6, 20);
		var redicon = new GIcon();
		redicon.image = "http://h202.customhostingtools.com/images/mkr_red.png";
		redicon.shadow = "http://h202.customhostingtools.com/images/mkr_shadow.png";
		redicon.iconSize = new GSize(12, 20);
		redicon.shadowSize = new GSize(12, 20);
		redicon.iconAnchor =  new GPoint(6, 20);
		var greenicon = new GIcon();
		greenicon.image = "http://h202.customhostingtools.com/images/mkr_green.png";
		greenicon.shadow = "http://h202.customhostingtools.com/images/mkr_shadow.png";
		greenicon.iconSize = new GSize(12, 20);
		greenicon.shadowSize = new GSize(12, 20);
		greenicon.iconAnchor =  new GPoint(6, 20);
		greenicon.infoWindowAnchor =  new GPoint(6, 20);
		var yellowicon = new GIcon();
		yellowicon.image = "http://h202.customhostingtools.com/images/mkr_yellow.png";
		yellowicon.shadow = "http://h202.customhostingtools.com/images/mkr_shadow.png";
		yellowicon.iconSize = new GSize(12, 20);
		yellowicon.shadowSize = new GSize(12, 20);
		yellowicon.iconAnchor =  new GPoint(6, 20);
		
		<?
			$markerArray = array();
		
			if (!empty($REGION_FILTER) && $REGION_FILTER != 'all')
			{
				$regfilt = "and s.regionID=$REGION_FILTER";
			}
			$query = "select s.siteID, s.siteLocationName, s.zip, z.lat, z.lng from site s, monitor m, zipcodes z where s.zip=z.zip and m.siteID=s.siteID $regfilt";
			$res = getResult($query);
			if (checkResult($res))
			{
				$zips = '';
				while ($line = mysql_fetch_assoc($res))
				{
					extract($line);
					
					$query = "select t.tankID, t.capacity, m.monitorID from monitor m, tank t where t.monitorID=m.monitorID and m.siteID=$siteID LIMIT 1";
					$tres = getResult($query);
					if (checkResult($tres))
					{
						$line = mysql_fetch_assoc($tres);
						extract($line);					

						// Filter out based on status
						$allow = true;
						$stat = checkTankStatus($monitorID);
						$mkrcolor = $stat === 0 ? 'blue' : 'red';
						if (!empty($STATUS_FILTER) && $STATUS_FILTER != 'all')
						{
							// need to pars the result if not 0.  check for each stat key
							if ($stat !== 0)
							{
								list($statkey, $statmsg) = explode(',', $stat);
								$allow = $statkey == $STATUS_FILTER;
							}
						}
						
						if ($allow)
						{
							$lat = round($lat, 4);
							$lng = round($lng, 4);
							$latlong = "$lat, $lng";

							// load array;
							$zippart = substr($zip, 0, 5);
							// show multi tank
							$marker = "\nvar point = new GLatLng($latlong);\n";
							$marker .= "var marker" . $siteID . " = createMarker(point, '$zippart', greenicon)\n";
							$marker .= "marker" . $siteID . ".tankID = \"$zippart\";\n";
							$marker .= "map.addOverlay(marker" . $siteID . ");\n";
							$markerArray[$zippart] = $marker;
						}
					}
				}
			}

			if (count($markerArray) > 0)
			{
				foreach($markerArray as $zip => $marker)
				echo $marker;
			}

			$res = getResult("Select z.lat, z.lng, s.supplierID from supplier s, zipcodes z where s.zip=z.zip and s.zip != ''");
			if (checkResult($res))
			{
				while ($line = mysql_fetch_assoc($res))
				{
					extract($line);
					$lat = round($lat, 4);
					$lng = round($lng, 4);
					$latlong = "$lat, $lng";
					$marker = "\nvar point = new GLatLng($latlong);\n";
					$marker .= "var marker_S" . $supplierID . " = createFactoryMarker(point, \"Factory Information<br><a href='javascript:alert(&quot;coming soon&quot;)'>Do something</a>\", factoryicon);\n";
					$marker .= "map.addOverlay(marker_S" . $supplierID . ");\n";
					echo $marker;
				}
			}


		?>
      }	
    //]]>
    </script>
	
	</div>
  </body>
</html>