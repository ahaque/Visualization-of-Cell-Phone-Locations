<link rel="stylesheet" type="text/css" media='screen' href="http://24.27.110.178/phone3/texting/custom_css.css" />
<body style="background-color:transparent;">
<?PHP

mysql_connect("localhost", "root", "fin3") or die(mysql_error());
mysql_select_db("web-settings") or die(mysql_error());

if(isset($_GET['phone1']))
	$phone1 = $_GET['phone1'];
	
if(isset($_GET['phone2']))
	$phone2 = $_GET['phone2'];
	
if(isset($_GET['phone3']))
	$phone3 = $_GET['phone3'];
	
if(isset($_GET['phoneOriginal']))
	$phoneO = $_GET['phoneOriginal'];
	
	
$result = mysql_query("SELECT * FROM $phoneO ORDER BY id DESC LIMIT 1");
$row = mysql_fetch_array($result);
$enabled = $row['gpsenable'];
$value = false;

if($enabled==1)
	$value = true;
if($enabled==0)
	$value=false;

if($value){

$rate = $row["webRF"];

if($rate ==0)
	$rate = 10000000;
?>

<meta http-equiv="refresh" content=" <?php echo $rate ?> ">
</head>

<?php
mysql_connect('localhost', 'root', 'fin3') OR die(fail('Could not connect to database.'));
mysql_select_db('web-settings');

$result = mysql_query("SELECT * FROM `$phoneO` ORDER BY id DESC LIMIT 1");
$row = mysql_fetch_array($result);

$daynum = $row['numtoshow'];

mysql_select_db("gps-locations") or die(mysql_error());

if($phone1!="p746517641354"){
$result = mysql_query("SELECT * FROM $phone1");
$phone1numpoints = mysql_num_rows($result);
}
if($phone1=="p746517641354")
	$phone1numpoints=0;
	
if($phone2!="p746517641354"){
$result2 = mysql_query("SELECT * FROM $phone2");
$phone2numpoints = mysql_num_rows($result2);
}
if($phone2=="p746517641354")
	$phone2numpoints=0;

if($phone3!="p746517641354"){
$result3 = mysql_query("SELECT * FROM $phone3");
$phone3numpoints = mysql_num_rows($result3);
}
if($phone3=="p746517641354")
	$phone3numpoints=0;

if($phone1numpoints>100)
	$phone1numpoints=100;
if($phone2numpoints>100)
	$phone2numpoints=100;
if($phone3numpoints>100)
	$phone3numpoints=100;
	
?>


	
 <script type="text/javascript" src="http://www.google.com/jsapi?key=ABQIAAAAsx9wJ9t06cTIybDic1OvDhQOXdm22uh9FewQYpO6xgBLtADO3RRDdIswRkNx3PH0FdH-Vc7VUrVZFQ"></script>
		
		<script type="text/javascript">
			google.load("jquery", '1.3');
			google.load("maps", "2.x");
		</script>
		<style type="text/css" media="screen">
			#map { float:left; width:680px; height:350px; }
			#pano { float:left; width:225px; height:200px; }
			#pano2 { float:left; width:225px; height:200px; }
			#pano3 { float:left; width:225px; height:200px; }
			#list { float:left; width:150px; background:#eee; list-style:none; padding:0; }
			#list li { padding:5px; }
			#list li:hover { background:#555; color:#fff; cursor:pointer; cursor:hand; }
			#message { background:#555; color:#fff; position:absolute; display:none; width:90px; padding:5px; }
			#add-point { float:left; }
			div.input { padding:3px 0; }
			label { display:block; font-size:50%; }
			input, select { width:300px; }
			button { float:right; }
			div.error { color:red; font-weight:bold; }
		</style>
		<script type="text/javascript" charset="utf-8">
			$(function(){
				var map = new GMap2(document.getElementById('map'));
				var homepoint = new GLatLng(33.03424072266,-96.65575408936);
				map.setCenter(homepoint, 13);
				var bounds = new GLatLngBounds();
				var geo = new GClientGeocoder(); 
				
				map.setMapType(G_HYBRID_MAP);
				map.addControl(new GLargeMapControl());
				map.addControl(new GMapTypeControl());
				map.enableScrollWheelZoom();
				map.addMapType(G_PHYSICAL_MAP);

				var reasons=[];
				reasons[G_GEO_SUCCESS]            = "Success";
				reasons[G_GEO_MISSING_ADDRESS]    = "Missing Address";
				reasons[G_GEO_UNKNOWN_ADDRESS]    = "Unknown Address.";
				reasons[G_GEO_UNAVAILABLE_ADDRESS]= "Unavailable Address";
				reasons[G_GEO_BAD_KEY]            = "Bad API Key";
				reasons[G_GEO_TOO_MANY_QUERIES]   = "Too Many Queries";
				reasons[G_GEO_SERVER_ERROR]       = "Server error";
				
				
				var sStr = "map-service.php?action=listpoints&phone1="+"<?php echo $phone1; echo "&phone2="; echo $phone2; echo "&phone3="; echo $phone3; ?>";
	
				var numphone1 = <?PHP echo $phone1numpoints; ?>;
				var numphone2 = <?PHP echo $phone2numpoints; ?>;
				var numphone3 = <?PHP echo $phone3numpoints; ?>;

				// initial load points
				$.getJSON(sStr, function(json) {
	
						for (i=0; i<numphone1;  i++) {
							var location = json.Locations[i];
							
							if(i<numphone1-1){
								var location1 = json.Locations[i+1];
								drawLine(location,location1,"#00FF00");
								}
							addLocation(location);
						}

						var	fenwayPark = new GLatLng(json.Locations[numphone1].lat,json.Locations[numphone1].lng);
						panoramaOptions = { latlng:fenwayPark };
						var myPano = new GStreetviewPanorama(document.getElementById("pano"), panoramaOptions);
						
						for (y=numphone1; y<(numphone2+numphone1);  y++) {
							var location3 = json.Locations[y];
							
							if(y<numphone2+numphone1-1){
								var location4 = json.Locations[y+1];
								drawLine(location3,location4,"#00E5EE");
								}
							addLocation2(location3);
						}
						
						var	fenwayPark = new GLatLng(json.Locations[numphone2+numphone1-1].lat,json.Locations[numphone2+numphone1-1].lng);
						panoramaOptions = { latlng:fenwayPark };
						var myPano = new GStreetviewPanorama(document.getElementById("pano2"), panoramaOptions);
						
						for (y=numphone1+numphone2; y<(numphone3+numphone2+numphone1);  y++) {
							var location5 = json.Locations[y];
							
							if(y<numphone2+numphone1+numphone3-1){
								var location6 = json.Locations[y+1];
								drawLine(location5,location6,"#FF0000");
								}
							addLocation3(location5);
						}
						
						var	fenwayPark = new GLatLng(json.Locations[numphone2+numphone1+numphone3-1].lat,json.Locations[numphone2+numphone1+numphone3-1].lng);
						panoramaOptions = { latlng:fenwayPark };
						var myPano = new GStreetviewPanorama(document.getElementById("pano3"), panoramaOptions);
						
						zoomToBounds();
				});
					
				
				$("#add-point").submit(function(){
					geoEncode();
					return false;
				});
				
				function drawLine(location, location1,color){
				var polyline = new GPolyline([
						new GLatLng(location.lat, location.lng),
						new GLatLng(location1.lat, location1.lng)
						], color, 10);
					map.addOverlay(polyline);
				}
				
				function savePoint(geocode) {
					var data = $("#add-point :input").serializeArray();
					data[data.length] = { name: "lng", value: geocode[0] };
					data[data.length] = { name: "lat", value: geocode[1] };
					$.post($("#add-point").attr('action'), data, function(json){
						$("#add-point .error").fadeOut();
						if (json.status == "fail") {
							$("#add-point .error").html(json.message).fadeIn();
						}
						if (json.status == "success") {
							$("#add-point :input[name!=action]").val("");
							var location = json.data;
							addLocation(location);
							zoomToBounds();
						}
					}, "json");
				}
				
				function geoEncode() {
					var address = $("#add-point input[name=address]").val();
					geo.getLocations(address, function (result){
						if (result.Status.code == G_GEO_SUCCESS) {
							geocode = result.Placemark[0].Point.coordinates;
							savePoint(geocode);
						} else {
							var reason="Code "+result.Status.code;
							if (reasons[result.Status.code]) {
								reason = reasons[result.Status.code]
							} 
							$("#add-point .error").html(reason).fadeIn();
							geocode = false;
						}
					});
				}
				
				function addLocation(location) {
					var point = new GLatLng(location.lat, location.lng);		
					var marker = new GMarker(point);
					map.addOverlay(marker);
					bounds.extend(marker.getPoint());
					
					$("<li />")
						.html(location.name)
						.mouseover(function(){
							showMessage(marker, location.name);
							
						})
						.appendTo("#list");
					
					GEvent.addListener(marker, "mouseover", function(){
						showMessage(this, location.name);
					});
					
					GEvent.addListener(marker, "mouseout", function(){
						removeMessage(this, location.name);
					});
					
					GEvent.addListener(marker, "click", function(){
							updateStreet(location.lat,location.lng);
					});

				}
								
				function addLocation2(location) {
					var point = new GLatLng(location.lat, location.lng);		
					var marker = new GMarker(point);
					map.addOverlay(marker);
					bounds.extend(marker.getPoint());
					
					$("<li />")
						.html(location.name)
						.mouseover(function(){
							showMessage(marker, location.name);
						})
						.appendTo("#list");
					
					GEvent.addListener(marker, "mouseover", function(){
						showMessage(this, location.name);
					});
					
					GEvent.addListener(marker, "mouseout", function(){
						removeMessage(this, location.name);
					});
					
					GEvent.addListener(marker, "click", function(){
							updateStreet2(location.lat,location.lng);
					});
				}
				
				function addLocation3(location) {
					var point = new GLatLng(location.lat, location.lng);		
					var marker = new GMarker(point);
					map.addOverlay(marker);
					bounds.extend(marker.getPoint());
					
					$("<li />")
						.html(location.name)
						.mouseover(function(){
							showMessage(marker, location.name);
						})
						.appendTo("#list");
					
					GEvent.addListener(marker, "mouseover", function(){
						showMessage(this, location.name);
					});
					
					GEvent.addListener(marker, "mouseout", function(){
						removeMessage(this, location.name);
					});
					
					GEvent.addListener(marker, "click", function(){
							updateStreet3(location.lat,location.lng);
					});
				}
				
				function zoomToBounds() {
					map.setCenter(bounds.getCenter());
					map.setZoom(map.getBoundsZoomLevel(bounds)-1);
				}
				
				$("#message").appendTo( map.getPane(G_MAP_FLOAT_SHADOW_PANE) );
				
				function showMessage(marker, text){
					var markerOffset = map.fromLatLngToDivPixel(marker.getPoint());
					$("#message").hide().fadeIn()
						.css({ top:markerOffset.y, left:markerOffset.x })
						.html(text);
				}
				
				function removeMessage(marker, text){
					var markerOffset = map.fromLatLngToDivPixel(marker.getPoint());
					$("#message").fadeOut()
				}
				
				function updateStreet(lat,lng){
						var myPano = new GStreetviewPanorama(document.getElementById("pano"));
						fenwayPark = new GLatLng(lat,lng);
						myPOV = {yaw:370.64659986187695,pitch:-20};
						myPano.setLocationAndPOV(fenwayPark, myPOV);
				}
			
				
				function updateStreet2(lat,lng){
						var myPano = new GStreetviewPanorama(document.getElementById("pano2"));
						fenwayPark = new GLatLng(lat,lng);
						myPOV = {yaw:370.64659986187695,pitch:-20};
						myPano.setLocationAndPOV(fenwayPark, myPOV);
				}
				
				function updateStreet3(lat,lng){
						var myPano = new GStreetviewPanorama(document.getElementById("pano3"));
						fenwayPark = new GLatLng(lat,lng);
						myPOV = {yaw:370.64659986187695,pitch:-20};
						myPano.setLocationAndPOV(fenwayPark, myPOV);
				}
			});
		</script>

	</head>
	<body>
	<div id="map"></div><BR>
<table border="0">
<tr>
<td><font size="2"><?PHP echo $phone1; ?> - Green</font><br><div id="pano"></div></td>
<td><font size="2">
<?PHP 
if($phone2numpoints!=0){
	echo $phone2;
	echo " - Cyan";
	echo "</font><BR>	<div id='pano2'></div>";
	}
?></td>
<td><font size="2">

<?PHP 
if($phone3numpoints!=0){
	echo $phone3;
	echo " - Red";
	echo "</font><BR>	<div id='pano3'></div>";}
?></td>
</tr>
</table>
		<!--<ul id="list"></ul>-->
		<div id="message"></div><BR><BR>
		
		<?php } ?>
<?php if(!($enabled)){ ?>

<font face="Arial" size="2">
The GPS feature is not enabled.</font>

<?php } ?>
		
	</body>
</html>