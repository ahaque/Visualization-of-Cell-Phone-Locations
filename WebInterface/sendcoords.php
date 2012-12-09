<?php

// ##########################################################
// If you encounter any problems with geofence not calculating inbounds/outbounds
// go to line ~116, the part with all the comments
// you should be able to fix it there
// ##########################################################


if(isset ($_GET["lat"]))
    $lat = $_GET["lat"];
	
if(isset ($_GET["lng"]))
    $lng = $_GET["lng"];
	
if(isset ($_GET["speed"]))
    $speed = $_GET["speed"];
	
if(isset ($_GET["phone"]))
    $phone = "p".$_GET["phone"];

$con = mysql_connect("localhost","root","fin3");
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }

// GETs current date and time
$date = date("Y-m-d H:i:s");
$day2 = date("Y-m-d");


class pointLocation {
    var $pointOnVertex = true; // Check if the point sits exactly on one of the vertices

    function pointLocation() {
    }
    
    
        function pointInPolygon($point, $polygon, $pointOnVertex = true) {
        $this->pointOnVertex = $pointOnVertex;
        
        // Transform string coordinates into arrays with x and y values
        $point = $this->pointStringToCoordinates($point);
        $vertices = array(); 
        foreach ($polygon as $vertex) {
            $vertices[] = $this->pointStringToCoordinates($vertex); 
        }
        
        // Check if the point sits exactly on a vertex
        if ($this->pointOnVertex == true and $this->pointOnVertex($point, $vertices) == true) {
            return "vertex";
        }
        
        // Check if the point is inside the polygon or on the boundary
        $intersections = 0; 
        $vertices_count = count($vertices);
    
        for ($i=1; $i < $vertices_count; $i++) {
            $vertex1 = $vertices[$i-1]; 
            $vertex2 = $vertices[$i];
            if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $point['y'] and $point['x'] > min($vertex1['x'], $vertex2['x']) and $point['x'] < max($vertex1['x'], $vertex2['x'])) { // Check if point is on an horizontal polygon boundary
                return "boundary";
            }
            if ($point['y'] > min($vertex1['y'], $vertex2['y']) and $point['y'] <= max($vertex1['y'], $vertex2['y']) and $point['x'] <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y']) { 
                $xinters = ($point['y'] - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x']; 
                if ($xinters == $point['x']) { // Check if point is on the polygon boundary (other than horizontal)
                    return "boundary";
                }
                if ($vertex1['x'] == $vertex2['x'] || $point['x'] <= $xinters) {
                    $intersections++; 
                }
            } 
        } 
        // If the number of edges we passed through is even, then it's in the polygon. 
        if ($intersections % 2 != 0) {
            return "inside";
        } else {
            return "outside";
        }
    }

    
    
    function pointOnVertex($point, $vertices) {
        foreach($vertices as $vertex) {
            if ($point == $vertex) {
                return true;
            }
        }
    
    }
        
    
    function pointStringToCoordinates($pointString) {
        $coordinates = explode(" ", $pointString);
        return array("x" => $coordinates[0], "y" => $coordinates[1]);
    }
    
    
}

/*** Example ***/
$pointLocation = new pointLocation();

// take GPS geobox polygon points and put into polygon array
$polygonArray = array();

mysql_connect('localhost', 'root', 'fin3') OR die(fail('Could not connect to database.'));
mysql_select_db('gps-geobox');
$result = mysql_query("SELECT * FROM `$phone`");

// split each point into required string

// ##########################################################
// ##########################################################
// THESE TWO BLOCKS OF CODE GIVE PROBLEMS!! ONE WORKS SOMETIMES, THE OTHER WORKS THE OTHER TIMES
// IF ANYTHING GOES WRONG WITH GEOFENCE INBOUNDS/OUTBOUNDS ITS HERE!!
// JUST SWITCH THE COMMENTS AND ACTIVATE THE OTHER SEGMENT OF CODE

// segment 1
while ($row = mysql_fetch_array($result)){
$polyPoint = $row['lat'] . " " . $row['lng'];
array_push($polygonArray,$polyPoint);
}

// ##########################################################
// ##########################################################
// segment 2

/*for($i=0; $i<10; $i++)
{
$row = mysql_query("SELECT FROM `$phone` where id = `$i`");
$polyPoint = $row['lat'] . " " . $row['lng'];
echo $row['lat'];
array_push($polygonArray,$polyPoint);
}*/

// ##########################################################
// ########################################################## end segments

// take phone coordinates and add to array -- ONLY 1 POINT AT A TIME
$points = array();

$phonePoint = $lat . " " . $lng;
array_push($points,$phonePoint);

foreach($points as $key => $point) {
    $inbounds = $pointLocation->pointInPolygon($point, $polygonArray);
}
mysql_select_db('gps-locations');
mysql_query("INSERT INTO `gps-locations`.`$phone` (`id`,`time`, `lat`, `lng`, `speed`,`day`,`inbounds`) VALUES (NULL, '$date', '$lat', '$lng','$speed','$day2','$inbounds')");

mysql_close($con);  

echo "Phone: " . $phone;
echo "<br>Lat: " . $lat;
echo "<br>Lng: " . $lng;
echo "<br>Speed: " . $speed;
echo "<br>InBounds: " . $inbounds;
echo "<BR>Written to DB at: ";
echo $date;

?>