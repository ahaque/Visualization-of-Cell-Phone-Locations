<?php

	// List points from database
	if ($_GET['action'] == 'listpoints') {
	
		$points = array();
		mysql_connect('localhost', 'root', 'fin3');
	
	if(isset($_GET['phone1'])){
				
		$phone1=$_GET['phone1'];
		
		$numtoshow = 100;
		$daytoshow = "0000-00-00";
			
		mysql_select_db('gps-locations');
		$query4 = "SELECT * FROM $phone1 order by id desc;";
		$result = mysql_query($query4);

		while ($row = mysql_fetch_array($result)) {
			$date = strtotime($row['time']);
			$date2 = date('h:i:s A M j, Y', $date) . " " .$phone1;	
			array_push($points, array('name' => $date2, 'lat' => $row['lat'], 'lng' => $row['lng']));
			}
		}
		
		// gets second phone and adds it to array
		if(isset($_GET['phone2'])){
		
		$phone2=$_GET['phone2'];

		mysql_select_db('gps-locations');
			
		$query4 = "SELECT * FROM $phone2 order by id desc;";
		$result = mysql_query($query4);
			 
		while ($row = mysql_fetch_array($result)) {
			$date = strtotime($row['time']);
			$date2 = date('h:i:s A M j, Y', $date) . " " . $phone2;	
			array_push($points, array('name' => $date2, 'lat' => $row['lat'], 'lng' => $row['lng']));
			}
		}
		
		if(isset($_GET['phone3'])){
		$phone3=$_GET['phone3'];

		mysql_select_db('gps-locations');
		$query4 = "SELECT * FROM $phone3 order by id desc;";
		$result = mysql_query($query4);

		while ($row = mysql_fetch_array($result)) {
			$date = strtotime($row['time']);
			$date2 = date('h:i:s A M j, Y', $date). " " .$phone3;	
			array_push($points, array('name' => $date2, 'lat' => $row['lat'], 'lng' => $row['lng']));
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