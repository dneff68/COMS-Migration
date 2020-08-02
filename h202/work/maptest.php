<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script src="http://www.customhostingtools.com/lib/jquery.js" type="text/javascript"></script>

<script type="text/javascript">

function getDistance(start, end)
{
	directionsService = new google.maps.DirectionsService();
	var request = {
	  origin: start,
	  destination: end,
	  travelMode: google.maps.DirectionsTravelMode.DRIVING
	};
	
	directionsService.route(request, function(response, status) {
	if (status == google.maps.DirectionsStatus.OK) {
		var distance = response.routes[0].legs[0].distance;
		  alert(distance.text);
		}
	});	
}


$(document).ready( function()
{
	var start = "33.726624,-117.865448";
	var end = "33.73833,-117.96741";
	getDistance(start,end);	
});

</script>
</head>

<body>
</body>
</html>
