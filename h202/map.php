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
	<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
    <link href="main.css" rel="stylesheet" type="text/css" />
  </head>
  <body onunload="unloadPage()" onload="showTheMap()">
  <span align="LEFT" class="spinNormalText">Use the map to zoom in and out to find tanks. Click and drag on the map to pan.</span>
    <div align="left" id="map" style="width: 100pct; height: 340px">
	<script type="text/javascript">
		function setStatusFilter(stat)
		{
			parent.window.location = "/index.php?status=" + stat;
		}
	
    </script>
	
	</div>

<?php if ($VIEWMODE == 'statusView') : ?>
	<table width="676">
	  <tr class="header_1">	
	  <td nowrap="nowrap" width="64"><div align="left">Normal: <a href="javascript:setStatusFilter('Normal')"><img border="0" src='images/mkr_green.png' /></a></div></td>
	  <td nowrap="nowrap" width="90"><div align="center">No Reading: <a href="javascript:setStatusFilter('NoReading')"><img src='images/mkr_orange.png' /></a></div></td>
	  <td nowrap="nowrap" width="121"><div align="right">Exceed Capacity: <a href="javascript:setStatusFilter('ExceedCap')"><img src='images/mkr_purple.png' /></div></td>
	  <td nowrap="nowrap" width="93"><div align="right">High Dose: <a href="javascript:setStatusFilter('H_Dose')"><img src='images/mkr_blue.png' /></div></td>
	  <td nowrap="nowrap" width="80"><div align="right">Low Dose: <a href="javascript:setStatusFilter('L_Dose')"><img src='images/mkr_white.png' /></div></td>
	  <td nowrap="nowrap" width="101"><div align="right">Unmonitored: <a href="javascript:setStatusFilter('unmon')"><img src='images/mkr_black.png' /></div></td>
	  <td nowrap="nowrap" width="159"><div align="center">Temp Shutdown: <a href="javascript:setStatusFilter('TempShutdown')"><img src='images/mkr_gray.png' /></div></td>
	  </table>
<? else :?>
	<table width="359">
	  <tr class="header_1">	
	  <td width="53"><div align="left">Ok: <a href="javascript:setStatusFilter('Ok')"><img border="0" src='images/mkr_green.png' /></a></div></td>
	  <td width="107"><div align="center">Reorder: <a href="javascript:setStatusFilter('Reorder')"><img src='images/mkr_yellow.png' /></a></div></td>
	  <td width="73"><div align="right">Low: <a href="javascript:setStatusFilter('Low')"><img src='images/mkr_orange.png' /></div></td>
	  <td width="106"><div align="right">Critical: <a href="javascript:setStatusFilter('Critical')"><img src='images/mkr_red.png' /></div></td>
  </table>
<?php endif; ?>	
  </body>
	<script language="javascript">
		function showTheMap()
		{
		//<![CDATA[
			var zoom = getCookie('zoomlevel');
			zoom = Number(zoom);
			var lat = getCookie('lat');
			lat = Number(lat);
			var lng = getCookie('lng');
			lng = Number(lng);
			//alert('load lat: ' + lat + '   lng:' + lng);
			//zoom = Number(zoom);
			//alert('loading: zoom is ' + zoom);
			//zoom = (integer)zoom;
			if (zoom == 0)
				zoom = 4;
			if (lat == 0)
				 lat = 39.1024;
			if ( lng == 0)
				 lng = -094.5985;
			
			function unloadPage()
			{
				zoom = map.getZoom();
				//alert('unloading; zoom is ' + zoom);
				setCookie('zoomlevel', zoom, 100);
				
				ll = map.getCenter();
				lat = ll.lat();
				lng = ll.lng();
				//alert('unload lat: ' + lat + '   lng:' + lng);
				setCookie('lat', lat, 100);
				setCookie('lng', lng, 100);
				//map.savePosition();
				GUnload();
			}
	
	
		  if (GBrowserIsCompatible()) 
		  {
			  
			  function createMarker(point,zip,html,iconObj) 
			  {
				html = html.replace('_singlequote_', "'");
				html = html.replace('_singlequote_', "'");
			  
				var marker = new GMarker(point, iconObj);
				GEvent.addListener(marker, "click", function() {
				parent.frames['detailsFrame'].location='<?=$VIEWMODE == 'statusView' ? 'multTankDetails.php' : 'deliveryDetails.php'?>?zip=' + zip;
				});
		 
				 GEvent.addListener(marker,"mouseover", function() {
	<? // if ($VIEWMODE == 'statusView') : ?>
				 if (map.getZoom() > 4)
					  marker.openInfoWindowHtml('<div align="left">' + html + '</div>');
	<? // endif; ?>
				});        
		
				return marker;
			  }	  
	
			  function createFactoryMarker(point,html,iconObj) 
			  {
				var marker = new GMarker(point, iconObj);
				return marker;
			  }	  
		
			  function createSimpleMarker(point,html,iconObj) 
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
				var centerPoint = new GLatLng(lat, lng);
				map.setCenter(centerPoint, zoom);
				//map.returnToSavedPosition();
				map.enableDoubleClickZoom(); 
				
				var factoryicon = new GIcon();
				factoryicon.image = "http://h202.customhostingtools.com/images/factory.png";
				factoryicon.iconSize = new GSize(25, 25);
				factoryicon.shadowSize = new GSize(12, 20);
				factoryicon.iconAnchor =  new GPoint(6, 20);
				factoryicon.infoWindowAnchor =  new GPoint(6, 0);
				var terminalicon = new GIcon();
				terminalicon.image = "http://h202.customhostingtools.com/images/terminal.gif";
				terminalicon.iconSize = new GSize(42, 29);
				terminalicon.shadowSize = new GSize(12, 20);
				terminalicon.iconAnchor =  new GPoint(6, 20);
				terminalicon.infoWindowAnchor =  new GPoint(26, 20);
				var truckicon = new GIcon();
				truckicon.image = "http://h202.customhostingtools.com/images/truck.gif";
				truckicon.iconSize = new GSize(42, 29);
				truckicon.shadowSize = new GSize(12, 20);
				truckicon.iconAnchor =  new GPoint(6, 20);
				truckicon.infoWindowAnchor =  new GPoint(6, 0);
				var orangeicon = new GIcon();
				orangeicon.image = "http://h202.customhostingtools.com/images/mkr_orange.png";
				orangeicon.shadow = "http://h202.customhostingtools.com/images/mkr_shadow.png";
				orangeicon.iconSize = new GSize(12, 20);
				orangeicon.shadowSize = new GSize(12, 20);
				orangeicon.iconAnchor =  new GPoint(6, 20);
				orangeicon.infoWindowAnchor =  new GPoint(6, 0);
				var purpleicon = new GIcon();
				purpleicon.image = "http://h202.customhostingtools.com/images/mkr_purple.png";
				purpleicon.shadow = "http://h202.customhostingtools.com/images/mkr_shadow.png";
				purpleicon.iconSize = new GSize(12, 20);
				purpleicon.shadowSize = new GSize(12, 20);
				purpleicon.iconAnchor =  new GPoint(6, 20);
				purpleicon.infoWindowAnchor =  new GPoint(6, 0);
				var blueicon = new GIcon();
				blueicon.image = "http://h202.customhostingtools.com/images/mkr_blue.png";
				blueicon.shadow = "http://h202.customhostingtools.com/images/mkr_shadow.png";
				blueicon.iconSize = new GSize(12, 20);
				blueicon.shadowSize = new GSize(12, 20);
				blueicon.iconAnchor =  new GPoint(6, 20);
				blueicon.infoWindowAnchor =  new GPoint(6, 0);
				var greenicon = new GIcon();
				greenicon.image = "http://h202.customhostingtools.com/images/mkr_green.png";
				greenicon.shadow = "http://h202.customhostingtools.com/images/mkr_shadow.png";
				greenicon.iconSize = new GSize(12, 20);
				greenicon.shadowSize = new GSize(12, 20);
				greenicon.iconAnchor =  new GPoint(6, 20);
				greenicon.infoWindowAnchor =  new GPoint(6, 0);
				var redicon = new GIcon();
				redicon.image = "http://h202.customhostingtools.com/images/mkr_red.png";
				redicon.shadow = "http://h202.customhostingtools.com/images/mkr_shadow.png";
				redicon.iconSize = new GSize(12, 20);
				redicon.shadowSize = new GSize(12, 20);
				redicon.iconAnchor =  new GPoint(6, 20);
				redicon.infoWindowAnchor =  new GPoint(6, 0);
				var grayicon = new GIcon();
				grayicon.image = "http://h202.customhostingtools.com/images/mkr_gray.png";
				grayicon.shadow = "http://h202.customhostingtools.com/images/mkr_shadow.png";
				grayicon.iconSize = new GSize(12, 20);
				grayicon.shadowSize = new GSize(12, 20);
				grayicon.iconAnchor =  new GPoint(6, 20);
				grayicon.infoWindowAnchor =  new GPoint(6, 0);
				var whiteicon = new GIcon();
				whiteicon.image = "http://h202.customhostingtools.com/images/mkr_white.png";
				whiteicon.shadow = "http://h202.customhostingtools.com/images/mkr_shadow.png";
				whiteicon.iconSize = new GSize(12, 20);
				whiteicon.shadowSize = new GSize(12, 20);
				whiteicon.iconAnchor =  new GPoint(6, 20);
				whiteicon.infoWindowAnchor =  new GPoint(6, 0);
				var blackicon = new GIcon();
				blackicon.image = "http://h202.customhostingtools.com/images/mkr_black.png";
				blackicon.shadow = "http://h202.customhostingtools.com/images/mkr_shadow.png";
				blackicon.iconSize = new GSize(12, 20);
				blackicon.shadowSize = new GSize(12, 20);
				blackicon.iconAnchor =  new GPoint(6, 20);
				blackicon.infoWindowAnchor =  new GPoint(6, 0);
				var yellowicon = new GIcon();
				yellowicon.image = "http://h202.customhostingtools.com/images/mkr_yellow.png";
				yellowicon.shadow = "http://h202.customhostingtools.com/images/mkr_shadow.png";
				yellowicon.iconSize = new GSize(12, 20);
				yellowicon.shadowSize = new GSize(12, 20);
				yellowicon.iconAnchor =  new GPoint(6, 20);
				yellowicon.infoWindowAnchor =  new GPoint(6, 0);
				
				<?
					if ($VIEWMODE == 'statusView')
					{
						include "statusMap.php";
					}
					else
					{
						include "deliveryMap.php";
					}
		
				if ($SHOWFACTORIES=='yes')
				{
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
				}
	
				if ($SHOWCARRIERS=='yes')
				{
					$res = getResult("Select z.lat, z.lng, s.carrierID, s.carrierName, s.contact, s.phone, s.email from carrier s, zipcodes z where s.zip=z.zip and s.zip != ''");
					if (checkResult($res))
					{
						while ($line = mysql_fetch_assoc($res))
						{
							extract($line);
							$lat = round($lat, 4);
							$lng = round($lng, 4);
							$latlong = "$lat, $lng";
							$marker = "\nvar point = new GLatLng($latlong);\n";
							$marker .= "var marker_S" . $carrierID . " = createSimpleMarker(point, \"<div style='width:200'><b>$carrierName</b><hr>Contact: $contact<br>$phone<br><a href='mailto:$email'>$email</a></div>\", truckicon);\n";
							$marker .= "map.addOverlay(marker_S" . $carrierID . ");\n";
							echo $marker;
						}
					}
				}

				if ($SHOWTERMINALS=='yes')
				{
					$tarr = array();
					$res = getResult("SELECT DISTINCT z.lat, z.lng, s.contact, s.phone, z.city, s.email, s.supplierName, s.supplierID, t.zip AS tzip
									FROM terminals t, zipcodes z, supplier s
									WHERE t.zip = z.zip
									AND t.supplierID = s.supplierID
									ORDER BY t.zip");
					if (checkResult($res))
					{
						$scnt = 0;
						while ($line = mysql_fetch_assoc($res))
						{
							extract($line);
							
							if (!empty($tzip))
							{
								if (!array_key_exists($tzip, $tarr))
								{
									$tarr[$tzip] = array();
									$tarr[$tzip]['zip'] = $tzip;
									$tarr[$tzip]['html'] = ''; 
									$tarr[$tzip]['city'] = "<strong>$city</strong><hr>";
								}
								$lat = round($lat, 4);
								$lng = round($lng, 4);
								$latlong = "$lat, $lng";
								$tarr[$tzip]['latlong'] = $latlong; // .= $marker;
								$tarr[$tzip]['html'] .= "<a href='javascript:alert(" . '\"' . "$contact: $email $phone" . '\")' . "'>$supplierName</a><br>";
								$scnt++;
							}
						}
						
						foreach($tarr as $tzip)
						{
							$latlong 	= $tzip['latlong'];
							$html 		= $tzip['html'];
							$zip		= $tzip['zip'];
							$city		= $tzip['city'];
							$marker = "\nvar point = new GLatLng($latlong);\n";
							$marker .= "var marker_S" . $zip .  " = createSimpleMarker(point, \"<div style='width:200'>$city $html</div>\", terminalicon);\n";
							$marker .= "map.addOverlay(marker_S" . $zip .  ");\n";
							echo $marker;
						}
					}

				}
		

	
	
				if (count($markerArray) > 0)
				{
					foreach($markerArray as $zip => $marker)
					echo $marker;
				}
				?>
	
		  }	
			  
		//]]>
	}
	</script>
</html>