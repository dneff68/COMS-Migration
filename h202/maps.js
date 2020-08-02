// JavaScript Document
  var geocoder;
  var map;
  var mapView;
  var ge = null;
  var infoWindow;
  
	function xid( a )
	{
	  	return window.document.getElementById( a )
	}
	
	function globalize_id( the_id )
	{
		window [ the_id ] = xid(the_id)
	}
	
	function initCallback(object) {
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
  
	function initialize() 
	{
		
		
		globalize_id('earth_canvas');
		globalize_id('map_canvas');
		
		
		geocoder = new google.maps.Geocoder();
		var latlng = new google.maps.LatLng(33.688907,-117.342374);
		var myOptions = {
		  center: latlng,
		  zoom: 15,
		  mapTypeId: google.maps.MapTypeId.HYBRID
		}
		map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
		infoWindow = new google.maps.InfoWindow();
		drawPipeline();
		drawTank(33.687782,-117.334671);
		

		
		// Setup Earth
		// Key: ABQIAAAAggZ_Em_g9kxxflX7EQjuLRQaZYXD59w1j_0Pi1c44Pv67rfgUxTXRXcFDtnjJi6VDb8y-TvTWaMR9A
		 google.earth.createInstance("map3d", initCallback, failureCallback);
	}
	
	function switchView()
	{
		if (mapView == 'earth')
		{
			mapView = 'map';
			map_canvas.style.visibility = 'visible';
			earth_canvas.style.visibility = 'hidden'
			div_switch_view.innerHTML = "<a href='javascript:switchView()'>Earth View</a>";
		}
		else
		{
			mapView = 'earth';
			map_canvas.style.visibility = 'hidden';
			earth_canvas.style.visibility = 'visible'
			div_switch_view.innerHTML = "<a href='javascript:switchView()'>Map View</a>";
		}
	}
	
	function drawTank(lat, lang)
	{
		var myLatlng = new google.maps.LatLng(lat, lang);
		
		var marker = new google.maps.Marker({
			  position: myLatlng, 
			  map: map, 
			  title:"Tank 123"
		  });
		
		google.maps.event.addListener(marker, 'mouseover', function() 
			{ 	
			infoWindow.close();
			infoWindow.setContent('<b>My Tank</b>');
			infoWindow.open(map,marker);} );		
			marker.setMap(map);	

	}
	
	function drawPipeline()
	{
		var flightPlanCoordinates = [
			new google.maps.LatLng(33.687782,-117.334671),
			new google.maps.LatLng(33.688585,-117.335465),
			new google.maps.LatLng(33.684943,-117.339714),
			new google.maps.LatLng(33.687353,-117.342761)];
		var flightPath = new google.maps.Polyline({
		  path: flightPlanCoordinates,
		  strokeColor: "#FF0000",
		  strokeOpacity: 1.0,
		  strokeWeight: 4
		});
//		var infowindow = new google.maps.InfoWindow();
		google.maps.event.addListener(flightPath, 'click', function(event, index) 
			{ 	infoWindow.position = event.latLng;
				infoWindow.setContent('<b>Pipeline</b>');
				infoWindow.close();
			  	infoWindow.open(map);});		
   				flightPath.setMap(map);	
		
   }
