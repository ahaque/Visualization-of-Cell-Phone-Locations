<?PHP

Header("Cache-control: private, no-cache");
Header("Expires: Mon, 26 Jun 1997 05:00:00 GMT");
Header("Pragma: no-cache");

mysql_connect("localhost", "root", "fin3") or die(mysql_error());
mysql_select_db("web-settings") or die(mysql_error());

$phone = $_GET['phone'];

$result = mysql_query("SELECT * FROM $phone ORDER BY id DESC LIMIT 1");
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
<link rel="stylesheet" type="text/css" media='screen' href="http://24.27.110.178/phone3/texting/custom_css.css" />
<body style="background-color:transparent;">
<meta http-equiv="refresh" content=" <?php echo $rate ?> ">
</head>

<?php
mysql_connect('localhost', 'root', 'fin3') OR die(fail('Could not connect to database.'));
mysql_select_db('web-settings');


$result = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row = mysql_fetch_array($result);

$daynum = $row['numtoshow'];

mysql_select_db("gps-locations") or die(mysql_error());
$result2 = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row2 = mysql_fetch_array($result2);
echo "Current Speed: ";
$mps = $row2['speed'];$mph = $mps/2.23693629;printf ("%02.3f", $mph);
echo " mph";

$link = mysql_connect("localhost", "root", "fin3");
mysql_select_db("gps-locations", $link);

mysql_connect('localhost', 'root', 'fin3');		
mysql_select_db('web-settings');
		
$query1 = "select * from $phone ORDER BY id desc LIMIT 1";
$result1 = mysql_query($query1);
$row1 = mysql_fetch_array($result1);
		
$numtoshowX = $row1["numtoshow"];
$daytoshowX = $row1["daytoshow"];

mysql_select_db('gps-locations');
			
if($daytoshowX=="0000-00-00"){
	if($numtoshowX>0)
		$query4 = "SELECT * FROM $phone order by id desc limit 0,$numtoshowX ;";
					
	if($numtoshowX<0)
		$query4 = "SELECT * FROM $phone order by id desc;";
	}
			
	else{
	if($numtoshowX>0)
		$query4 = "SELECT *FROM `$phone` WHERE `day` LIKE CONVERT( _utf8 '$daytoshowX' USING latin1 ) order by id desc limit 0,$numtoshowX";
	if($numtoshowX<0)
		$query4 = "SELECT *FROM `$phone` WHERE `day` LIKE CONVERT( _utf8 '$daytoshowX' USING latin1 ) order by id desc";
	}

$result = mysql_query($query4);
$phone1numpoints = mysql_num_rows($result);

$geoboxnum = 10;

mysql_select_db("web-settings") or die(mysql_error());
$result4 = mysql_query("SELECT * FROM `$phone` ORDER BY id DESC LIMIT 1");
$row4 = mysql_fetch_array($result4);

$showgeobox = $row4['showgeobox'];

?><BR>

	
			<script type="text/javascript" src="http://www.google.com/jsapi?key=ABQIAAAAsx9wJ9t06cTIybDic1OvDhQOXdm22uh9FewQYpO6xgBLtADO3RRDdIswRkNx3PH0FdH-Vc7VUrVZFQ"></script>
		
		<script type="text/javascript">
			google.load("jquery", '1.3');
			google.load("maps", "2.x");
		</script>
		<style type="text/css" media="screen">
			#map { float:left; width:320px; height:320px; }
			#pano { float:left; width:320px; height:320px; }
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
				
				
				var sStr = "map-service.php?action=listpoints&phone="+"<?php echo $phone; ?>";
				
				var numphone1 = <?PHP echo $phone1numpoints; ?>;
				var geoboxnum = <?PHP echo $geoboxnum; ?>;
				var showgeobox = <?PHP echo $showgeobox; ?>;

				$.getJSON(sStr, function(json) {
	
						for (i=0; i<numphone1;  i++) {
							var location = json.Locations[i];
							
							if(i<numphone1-1){
								var location1 = json.Locations[i+1];
								drawLine(location,location1,"#FF0000");
								}
							addLocation(location);
						}

						var	fenwayPark = new GLatLng(json.Locations[numphone1-1].lat,json.Locations[numphone1-1].lng);
						panoramaOptions = { latlng:fenwayPark };
						var myPano = new GStreetviewPanorama(document.getElementById("pano"), panoramaOptions);
					
					if(showgeobox>0){
					
						for (y=numphone1; y<(geoboxnum+numphone1);  y++) {
							var location3 = json.Locations[y];
						}
						
						    var polygon = new GPolygon([
							new GLatLng(json.Locations[numphone1].lat, json.Locations[numphone1].lng),
							new GLatLng(json.Locations[numphone1+1].lat, json.Locations[numphone1+1].lng),
							new GLatLng(json.Locations[numphone1+2].lat, json.Locations[numphone1+2].lng),
							new GLatLng(json.Locations[numphone1+3].lat, json.Locations[numphone1+3].lng),
							new GLatLng(json.Locations[numphone1+4].lat, json.Locations[numphone1+4].lng),
							new GLatLng(json.Locations[numphone1+5].lat, json.Locations[numphone1+5].lng),
							new GLatLng(json.Locations[numphone1+6].lat, json.Locations[numphone1+6].lng),
							new GLatLng(json.Locations[numphone1+7].lat, json.Locations[numphone1+7].lng),
							new GLatLng(json.Locations[numphone1+8].lat, json.Locations[numphone1+8].lng),
							new GLatLng(json.Locations[numphone1+9].lat, json.Locations[numphone1+9].lng),
							new GLatLng(json.Locations[numphone1].lat, json.Locations[numphone1].lng)
						  ], "#00FF00", 5, 1, "#00FF00", 0.08);
						  // line color, line thickness, ?, fill color, fill transparancy
						  map.addOverlay(polygon);
					}
						
						zoomToBounds();
				});
					
				
				$("#add-point").submit(function(){
					geoEncode();
					return false;
				});
				
	
	
				function drawLine(location, location1){
				var polyline = new GPolyline([
						new GLatLng(location.lat, location.lng),
						new GLatLng(location1.lat, location1.lng)
						], "#0000FF", 10);
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
			});
		</script>

	</head>
	<body>
	<div id="map"></div>
	<div id="pano"></div>
		<!--<ul id="list"></ul>-->
		<div id="message"></div><BR><BR>
		
		<?php } ?>
<?php if(!($enabled)){ ?>

<font face="Arial" size="2">
The GPS feature is not enabled.</font>

<?php } ?>
		
	</body>
</html>
