<?php

	// List points from database		
	if ($_GET['action'] == 'listpoints') {
	
		$points = array();
	
	if(isset($_GET['phone'])){
		mysql_connect('localhost', 'root', 'fin3');		
		mysql_select_db('web-settings');
		
		$phone=$_GET['phone'];
		
		$query1 = "select * from $phone ORDER BY id desc LIMIT 1";
	    $result1 = mysql_query($query1);
		$row1 = mysql_fetch_array($result1);
		
		$numtoshow = $row1["numtoshow"];
		$daytoshow = $row1["daytoshow"];
			
			mysql_select_db('gps-locations');
			
			if($daytoshow=="0000-00-00"){
				if($numtoshow>0)
					$query4 = "SELECT * FROM $phone order by id desc limit 0,$numtoshow ;";
					
				if($numtoshow<0)
					$query4 = "SELECT * FROM $phone order by id desc;";
			}
			
			else{
				if($numtoshow>0)
					$query4 = "SELECT *FROM `$phone` WHERE `day` LIKE CONVERT( _utf8 '$daytoshow' USING latin1 ) order by id desc limit 0,$numtoshow";
				if($numtoshow<0)
					$query4 = "SELECT *FROM `$phone` WHERE `day` LIKE CONVERT( _utf8 '$daytoshow' USING latin1 ) order by id desc";
			}

			 $result = mysql_query($query4);
			  
		$count1=0;
		while ($row = mysql_fetch_array($result)) {
			$date = strtotime($row['time']);
			$date2 = date('h:i:s A M j, Y l', $date);
			
			$mps = $row['speed'];
			$mph = $mps/2.23693629;
			$mph2 = round($mph,2);
			
			$name3 = $date2 . " " .$mph2."mph";
				
			array_push($points, array('name' => $name3, 'lat' => $row['lat'], 'lng' => $row['lng']));
		}
		
		// GETS AND ADDS GEOBOX POINTS
		mysql_connect('localhost', 'root', 'fin3');		
		mysql_select_db('gps-geobox');
		
		$blank = "GeoboxPolygon";
		
		$query4 = "select * from $phone ORDER BY id desc";

			 $result = mysql_query($query4);
		
		while ($row = mysql_fetch_array($result)) {
			array_push($points, array('name' => $blank, 'lat' => $row['lat'], 'lng' => $row['lng']));
		}

		}
		
		echo json_encode(array("Locations" => $points));
		exit;
	}
	
			
	function fail($message) {
		die(json_encode(array('status' => 'fail', 'message' => $message)));
	}
	
	function success($data) {
		die(json_encode(array('status' => 'success', 'data' => $data)));
	}

?>