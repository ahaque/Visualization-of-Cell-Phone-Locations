<?PHP
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
?>

<script language="javascript">
function ConfirmChoice(){
answer = confirm("Are you sure you want to delete the current geofence?")
	if (answer !=0){
	location = "http://24.27.110.178/phone3/gps/clearGeobox.php?phone="+"<?PHP echo $phone; ?>";
		}
}
</script>


<a href="#" onclick=" ConfirmChoice(); return false;">Click here to remove the current geofence.</a>

<link rel="stylesheet" type="text/css" media='screen' href="http://24.27.110.178/phone3/texting/custom_css.css" />

<body style="background-color:transparent;">

    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAsx9wJ9t06cTIybDic1OvDhQOXdm22uh9FewQYpO6xgBLtADO3RRDdIswRkNx3PH0FdH-Vc7VUrVZFQ"
        type="text/javascript"></script>

    <script type="text/javascript">
        var map2;
        var geocoder;
        var counter;

        function initialize() { 
            map2 = new GMap2(document.getElementById("map_canvas"));
            map2.setCenter(new GLatLng(37.77896118164,-122.41950225830), 13);
            map2.setUIToDefault();
            geocoder = new GClientGeocoder();
            map2.clearOverlays();
            document.getElementById('stop').disabled = 'disabled';
            document.getElementById('save').disabled = 'disabled';
            counter = 0;
        }
		
        function getAddress(overlay, latlng) {
            if (latlng != null) {
                counter++;
                if (counter > 10) {
                    alert("The geofence cannot have more than 10 points.");
                    stopAdding();
                    return;
                }
				
                document.getElementById('counter').value = counter;
                address = latlng;
				
                addBoundary(latlng.x, latlng.y);
                geocoder.getLocations(latlng,
                    function(response) {
                        var address = (!response || response.Status.code != 200) ?
                            "Address unavailable" : response.Placemark[0].address;
                        var marker = new GMarker(latlng);
                        map2.addOverlay(marker);
						
						updateArray(latlng.x, latlng.y);
						if(counter>1)
							drawLine(lat1,lng1,lat2,lng2);
						
                        marker.bindInfoWindowHtml(
                        '<b>LatLon: </b>' + latlng.x + ", " + latlng.y + '<br>' +
                        '<b>Address: </b>' + address);
                        changeCursor('crosshair');
                    }

                );
            }
        }
		
		var lat1;
		var lat2=0;
		var lng1;
		var lng2=0;
		
		function updateArray(lat,lng){
			lat1=lat2;
			lng1=lng2;
			
			lat2 = lat;
			lng2 = lng;
			
			if(counter>1)
			drawLine(lat1,lng1,lat2,lng2);
		}
		
		function drawLine(lat1,lng1,lat2,lng2){
				var polyline = new GPolyline([
						new GLatLng(lat1, lng1),
						new GLatLng(lat2, lng2)
						], "#FF0000", 10);
					map2.addOverlay(polyline);
				}
							
        function addBoundary(latitude, longitude) {
            var tbody = document.getElementById("boundaries");
            var row = document.createElement("tr")
            addTextCell(row, counter);
            addInputCell(row, 'longitude[]', latitude);
			addInputCell(row, 'latitude[]', longitude);
            tbody.appendChild(row);
        }
	

        function addTextCell(toRow, value) {
            var textCell = document.createTextNode(value);
            var td = document.createElement('td');
            td.appendChild(textCell);
            toRow.appendChild(td);
        }
        
        function addInputCell(toRow, name, value) {
            var inputCell = document.createElement('input');
            inputCell.type = 'text';
            inputCell.name = name;
            inputCell.value = value;
            var td = document.createElement('td');
            td.appendChild(inputCell);
            toRow.appendChild(td);
        }

        function startAdding() {
            GEvent.addListener(map2, "click", getAddress);
            changeCursor('crosshair');
            document.getElementById('start').disabled = 'disabled';
            document.getElementById('stop').disabled = '';
        }

        function stopAdding() {
            GEvent.clearListeners(map2, "click");
            changeCursor('default');
            document.getElementById('start').disabled = '';
            document.getElementById('stop').disabled = 'disabled';
            document.getElementById('save').disabled = '';
        }
       
        function clearOverlay() {
		
            map2.clearOverlays();
setTimeout("location.reload(true);",1);

}     

        function changeCursor(cursorName) {
            document.getElementById("map_canvas").childNodes[0].childNodes[0].style.cursor = cursorName;
        }
		
		function checkUnder2(){
		if (counter <3) {
                    alert("The geofence must have more than 2 points.");
                    setTimeout("location.reload(true);",1);
                }
		}
    </script>

</head>

<?PHP

if(isset($_GET["set"]))
if($_GET["set"]=="true")
	echo "<B><font color='green'>
	Geobox updated!</font></b>
	";

?>

<body onload="initialize()">
    <table>
        <tr>
            <td>
                <div id="map_canvas" style="width: 320px; height: 350px" />
            </td>
            <td style="vertical-align: top">
                <form action="http://24.27.110.178/phone3/gps/submitGeobox.php" method="post">
                <input id="counter" type="hidden" />
                <table>
                    <tr><td>Mark Boundary Points</td>
                        <td><input id="start" type="button" value="Start" onclick="startAdding()" /></td>
                        <td><input id="stop" type="button" value="Stop" onclick="stopAdding()" /></td>
                        <td><input id="save" type="submit" value="Save" onclick="checkUnder2()" /></td>
                        <td><input id="clear" type="button" value="Clear" onclick="clearOverlay()"/></td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td>
                            <table>
                                <thead>
                                    <tr>
                                        <td>#</td>
                                        <td>
                                            Longitude
                                        </td>
                                        <td>
										Latitude
                                            
                                        </td>
                                    </tr>
                                </thead>
                                <tbody id="boundaries">
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>

<?PHP

if(isset($_GET["phone"]))
$phone = $_GET["phone"];

echo "<input type='hidden' name='phone' value='";
echo $_GET["phone"];
echo "' >";
?>				
                </form>
            </td>
        </tr>
    </table>
</body>

		<?php } ?>
<?php if(!($enabled)){ ?>

<font face="Arial" size="2">
The GPS feature is not enabled.</font>

<?php } ?>