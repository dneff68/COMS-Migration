<?php
session_start();
include_once '../lib/chtFunctions.php';
include_once '../lib/db_mysql.php';
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
$markerArray = array();
$lat = 0;
$lng = 0;
$supplierID='';
$carrierID='';
$carrierName = '';
$contact = '';
$phone = '';
$email = '';
$city = '';
$supplierName = '';


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
      <style>
          #map {
              height: 400px;  /* The height is 400 pixels */
              width: 100%;  /* The width is the width of the web page */
          }
      </style>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>National Map</title>
    <link href="main.css" rel="stylesheet" type="text/css" />
  </head>
  <body onunload="unloadPage()">
  <script>
      var map;
      function showTheMap() {
          //return;
          //<![CDATA[

      }

      function comsMarkers()
      {
          if (true)
          {
              // GBrowserIsCompatible()
              function createMarker(latval, lngval ,zip,html,iconObj)
              {
                  <?php
                  $mapurl = $_SESSION['VIEWMODE'] == 'statusView' ? 'multTankDetails.php' : 'deliveryDetails.php';
                  ?>
                  html = html.replace('_singlequote_', "'");
                  html = html.replace('_singlequote_', "'");

                  var uluru = {lat: latval, lng: lngval};
                  var marker = new google.maps.Marker({position: uluru, map: map})
                  //var marker = new GMarker(point, iconObj);
                  //GEvent.addListener(marker, "click", function() {
                  //    alert('<?php //echo $mapurl?>//?' + zip);
                  //    parent.frames['detailsFrame'].location='<?php //echo $mapurl?>//' + zip;
                  //});

                  //GEvent.addListener(marker,"mouseover", function() {
                  //    <?php //// if ($_SESSION['VIEWMODE'] == 'statusView') : ?>
                  //    if (map.getZoom() > 4)
                  //        marker.openInfoWindowHtml('<div align="left">' + html + '</div>');
                  //    <?php //// endif; ?>
                  //});

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

              //var uluru = {lat: <?php echo $lat?>, lng: <?php echo $lng?>};
              //var map = new google.maps.Map(document.getElementById('map'), {zoom: 4, center: uluru});
              //var marker = new google.maps.Marker({position: uluru, map: map});

              //var map = new GMap2(document.getElementById("map"));
              //map.addControl(new GSmallZoomControl());
              //var centerPoint = new GLatLng(lat, lng);
              //map.setCenter(centerPoint, zoom);
              //map.returnToSavedPosition();
              //map.enableDoubleClickZoom();
console.log('here');
if (false) {
    var factoryicon = new GIcon();
    factoryicon.image = "images/factory.png";
    factoryicon.iconSize = new GSize(25, 25);
    factoryicon.shadowSize = new GSize(12, 20);
    factoryicon.iconAnchor = new GPoint(6, 20);
    factoryicon.infoWindowAnchor = new GPoint(6, 0);
    var terminalicon = new GIcon();
    terminalicon.image = "images/terminal.gif";
    terminalicon.iconSize = new GSize(42, 29);
    terminalicon.shadowSize = new GSize(12, 20);
    terminalicon.iconAnchor = new GPoint(6, 20);
    terminalicon.infoWindowAnchor = new GPoint(26, 20);
    var truckicon = new GIcon();
    truckicon.image = "images/truck.gif";
    truckicon.iconSize = new GSize(42, 29);
    truckicon.shadowSize = new GSize(12, 20);
    truckicon.iconAnchor = new GPoint(6, 20);
    truckicon.infoWindowAnchor = new GPoint(6, 0);
    var orangeicon = new GIcon();
    orangeicon.image = "images/mkr_orange.png";
    orangeicon.shadow = "images/mkr_shadow.png";
    orangeicon.iconSize = new GSize(12, 20);
    orangeicon.shadowSize = new GSize(12, 20);
    orangeicon.iconAnchor = new GPoint(6, 20);
    orangeicon.infoWindowAnchor = new GPoint(6, 0);
    var purpleicon = new GIcon();
    purpleicon.image = "images/mkr_purple.png";
    purpleicon.shadow = "images/mkr_shadow.png";
    purpleicon.iconSize = new GSize(12, 20);
    purpleicon.shadowSize = new GSize(12, 20);
    purpleicon.iconAnchor = new GPoint(6, 20);
    purpleicon.infoWindowAnchor = new GPoint(6, 0);
    var blueicon = new GIcon();
    blueicon.image = "images/mkr_blue.png";
    blueicon.shadow = "images/mkr_shadow.png";
    blueicon.iconSize = new GSize(12, 20);
    blueicon.shadowSize = new GSize(12, 20);
    blueicon.iconAnchor = new GPoint(6, 20);
    blueicon.infoWindowAnchor = new GPoint(6, 0);
    var greenicon = new GIcon();
    greenicon.image = "images/mkr_green.png";
    greenicon.shadow = "images/mkr_shadow.png";
    greenicon.iconSize = new GSize(12, 20);
    greenicon.shadowSize = new GSize(12, 20);
    greenicon.iconAnchor = new GPoint(6, 20);
    greenicon.infoWindowAnchor = new GPoint(6, 0);
    var redicon = new GIcon();
    redicon.image = "images/mkr_red.png";
    redicon.shadow = "images/mkr_shadow.png";
    redicon.iconSize = new GSize(12, 20);
    redicon.shadowSize = new GSize(12, 20);
    redicon.iconAnchor = new GPoint(6, 20);
    redicon.infoWindowAnchor = new GPoint(6, 0);
    var grayicon = new GIcon();
    grayicon.image = "images/mkr_gray.png";
    grayicon.shadow = "images/mkr_shadow.png";
    grayicon.iconSize = new GSize(12, 20);
    grayicon.shadowSize = new GSize(12, 20);
    grayicon.iconAnchor = new GPoint(6, 20);
    grayicon.infoWindowAnchor = new GPoint(6, 0);
    var whiteicon = new GIcon();
    whiteicon.image = "images/mkr_white.png";
    whiteicon.shadow = "images/mkr_shadow.png";
    whiteicon.iconSize = new GSize(12, 20);
    whiteicon.shadowSize = new GSize(12, 20);
    whiteicon.iconAnchor = new GPoint(6, 20);
    whiteicon.infoWindowAnchor = new GPoint(6, 0);
    var blackicon = new GIcon();
    blackicon.image = "images/mkr_black.png";
    blackicon.shadow = "images/mkr_shadow.png";
    blackicon.iconSize = new GSize(12, 20);
    blackicon.shadowSize = new GSize(12, 20);
    blackicon.iconAnchor = new GPoint(6, 20);
    blackicon.infoWindowAnchor = new GPoint(6, 0);
    var yellowicon = new GIcon();
    yellowicon.image = "images/mkr_yellow.png";
    yellowicon.shadow = "images/mkr_shadow.png";
    yellowicon.iconSize = new GSize(12, 20);
    yellowicon.shadowSize = new GSize(12, 20);
    yellowicon.iconAnchor = new GPoint(6, 20);
    yellowicon.infoWindowAnchor = new GPoint(6, 0);
}
              <?php
              if ($_SESSION['VIEWMODE'] == 'statusView')
              {
                  include "statusMap.php";
              }
              else
              {
                  include "deliveryMap.php";
              }

              if ($_SESSION['SHOWFACTORIES']=='yes')
              {
                  return; // DISABLED - neff
                  $res = getResult("Select z.lat, z.lng, s.supplierID from supplier s, zipcodes z where s.zip=z.zip and s.zip != ''");
                  if (checkResult($res))
                  {
                      while ($line = $res->fetch_assoc())
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

              if ($_SESSION['SHOWCARRIERS']=='yes')
              {
                  return; //DISABLED - NEFF
                  $res = getResult("Select z.lat, z.lng, s.carrierID, s.carrierName, s.contact, s.phone, s.email from carrier s, zipcodes z where s.zip=z.zip and s.zip != ''");
                  if (checkResult($res))
                  {
                      while ($line = $res->fetch_assoc())
                      {
                          extract($line);
                          $lat = round($lat, 4);
                          $lng = round($lng, 4);
                          $latlong = "$lat, $lng";
                          $marker = "\nvar point = new GLatLng($latlong);\n";
                          $marker .= "var marker_S" . $carrierID . " = createSimpleMarker(point, \"<div style='width:200px'><b>$carrierName</b><hr>Contact: $contact<br>$phone<br><a href='mailto:$email'>$email</a></div>\", truckicon);\n";
                          $marker .= "map.addOverlay(marker_S" . $carrierID . ");\n";
                          echo $marker;
                      }
                  }
              }

              if ($_SESSION['SHOWTERMINALS']=='yes')
              {
                  return; // DISABLED - NEFF
                  $tarr = array();
                  $res = getResult("SELECT DISTINCT z.lat, z.lng, s.contact, s.phone, z.city, s.email, s.supplierName, s.supplierID, t.zip AS tzip
									FROM terminals t, zipcodes z, supplier s
									WHERE t.zip = z.zip
									AND t.supplierID = s.supplierID
									ORDER BY t.zip");
                  if (checkResult($res))
                  {
                      $scnt = 0;
                      while ($line = $res->fetch_assoc())
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
                          $marker .= "var marker_S" . $zip .  " = createSimpleMarker(point, \"<div style='width:200px'>$city $html</div>\", terminalicon);\n";
                          $marker .= "map.addOverlay(marker_S" . $zip .  ");\n";
                          echo $marker;
                      }
                  }

              }




              if (count($markerArray) > 0)
              {
                 // error_log("In map.php line 286");
                  $counter = 0;
                  foreach($markerArray as $zip => $marker)
                  {
                      //echo $marker;
                      if (true) //$counter < 20)
                      {
                          echo $marker;
                          $counter++;
                      }
                  }
              }
              ?>

              // }

              //]]>
          }

      }

      // Initialize and add the map
      function initMap() {
          var zoom = getCookie('zoomlevel');
          zoom = Number(zoom);
          var latval = getCookie('lat');
          latval = Number(latval);
          var lngval = getCookie('lng');
          lngval = Number(lngval);
          if (zoom == 0)
              zoom = 4;
          if (latval == 0)
              latval = 39.1024;
          if (lngval == 0)
              lngval = -100.5985;
          // The location of Uluru
          var uluru = {lat: latval, lng: lngval};
          // The map, centered at Uluru
          map = new google.maps.Map(
              document.getElementById('map'), {zoom: 4, center: uluru});
          // The marker, positioned at Uluru
          //var marker = new google.maps.Marker({position: uluru, map: map});
          comsMarkers();
      }
  </script>
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyATqlJiBURx-E0WVGZrUbg6JQSOcaDbpl8&callback=initMap"
          type="text/javascript"></script>
  <SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='js/admin.js'></SCRIPT>
  <span align="LEFT" class="spinNormalText">Use the map to zoom in and out to find tanks. Click and drag on the map to pan.</span>
    <div align="left" id="map">
	<script type="text/javascript">
		function setStatusFilter(stat)
		{
			parent.window.location = "/index.php?status=" + stat;
		}

        function unloadPage()
        {
            return;
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

    </script>
	
	</div>

<?php if ($_SESSION['VIEWMODE'] == 'statusView') : ?>
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
</html>