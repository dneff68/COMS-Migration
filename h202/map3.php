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

      function createMarker(point,html) {
        var marker = new GMarker(point);
        GEvent.addListener(marker, "click", function() {
          marker.openInfoWindowHtml(html);
        });

        // The new marker "mouseover" listener        
        GEvent.addListener(marker,"mouseover", function() {
          marker.openInfoWindowHtml(html);
        });        
        
        return marker;
      }

      var map = new GMap2(document.getElementById("map"));
      map.addControl(new GLargeMapControl());
      map.addControl(new GMapTypeControl());
      map.setCenter(new GLatLng(43.907787,-79.359741), 9);
    
      var point = new GLatLng(43.65654,-79.90138);
      var marker = createMarker(point,'Some stuff to display in the<br>First Info Window <br>With a <a href="http://www.econym.demon.co.uk">Link</a> to my home page')
      map.addOverlay(marker);

      var point = new GLatLng(43.91892,-78.89231);
      var marker = createMarker(point,'Some stuff to display in the<br>Second Info Window')
      map.addOverlay(marker);

      var point = new GLatLng(43.82589,-79.10040);
      var marker = createMarker(point,'Some stuff to display in the<br>Third Info Window')
      map.addOverlay(marker);
    }

    
    else {
      alert("Sorry, the Google Maps API is not compatible with this browser");
    }	
    //]]>
    </script>
	
	</div>
  </body>
</html>