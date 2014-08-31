<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
    use Entrenos\Utils\Parser\GPXPlusParser;
    use Entrenos\Activity;
    session_start();
?>
    var startimage = "<?php echo $base_url; ?>/images/start.png";
    var finishimage = "<?php echo $base_url; ?>/images/finish.png";
    var startIcon = new google.maps.MarkerImage(startimage,
        // If size is not supplied, Gmaps makes a call to download the image and reads its size
        new google.maps.Size(32, 32),
        // The origin for this image is 0,0.
        new google.maps.Point(0,0),
        // The anchor for this image is the base of the flagpole
        new google.maps.Point(16, 32));

    var finishIcon = new google.maps.MarkerImage(finishimage,
        // This marker is 32 pixels wide by 32 pixels tall.
        new google.maps.Size(32, 32),
        // The origin for this image is 0,0.
        new google.maps.Point(0,0),
        // Anchor is defined as fourth argument, relative to the top left corner of the image with positive X going right and positive Y going down
        new google.maps.Point(16, 32));

<?php            
    $my_file = $_GET['file'];
    $activity_id = pathinfo($my_file, PATHINFO_BASENAME);
    $parser = new GPXPlusParser();
    list($arrLatLon, $numPointsPosition) = $parser->getPoints($my_file, true);
    $numPoints = count($arrLatLon);
    if ($numPointsPosition > 0) {
        $log->debug("Found " . $numPointsPosition . " out of " . $numPoints . " points with valid position data. Displaying map...");
        // Some trackpoints may not have valid position data, find closest one with it
        // Start point
        $start_lon = "";
        $start_lat = "";
        for ($i = 0; $i < $numPoints; $i++) {
            if (!empty($arrLatLon[$i]['lon'])) {
                $start_lon = $arrLatLon[$i]['lon'];
                if (!empty($arrLatLon[$i]['lat'])) {
                    $start_lat = $arrLatLon[$i]['lat'];
                    break;
                }
            }
        }
        echo "var startlatlng = new google.maps.LatLng(" . $start_lat . ", " . $start_lon . ");\n";
        // Last point
        $last_lon = "";
        $last_lat = "";
        for ($last_point = 1; $last_point < $numPoints; $last_point++) {
            if (!empty($arrLatLon[$numPoints - $last_point]['lon'])) {
                $last_lon = $arrLatLon[$numPoints - $last_point]['lon'];
                if (!empty($arrLatLon[$numPoints - $last_point]['lat'])) {
                    $last_lat = $arrLatLon[$numPoints - $last_point]['lat'];
                    break;
                }
            }
        }
		echo "var endlatlng = new google.maps.LatLng(" . $last_lat . ", " . $last_lon . ");\n";
		$averages = $parser->getCenter($arrLatLon);
        echo "var mylatlng = new google.maps.LatLng(" . $averages['lat'] . ", " . $averages['lon'] . ");\n";
?>

        var myOptions = {
            zoom: 13,
            center: mylatlng,
            scaleControl: true,
            mapTypeId: google.maps.MapTypeId.ROADMAP};

		var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

<?php
            $lats = array();
            $lons = array();
            foreach ($arrLatLon as $index => $point){
                if (!empty($point['lat'])) {
                    $lats[] = $point['lat'];
                }
                if (!empty($point['lon'])) {
                    $lons[] = $point['lon'];
                }
            }
            echo "var southWest = new google.maps.LatLng(" . min($lats) . "," . min($lons) . ");";
            echo "var northEast = new google.maps.LatLng(" . max($lats) . "," . max($lons) . ");";
?>

        var bounds = new google.maps.LatLngBounds(southWest,northEast);
        map.fitBounds(bounds);

        var startmarker = new google.maps.Marker({
            position: startlatlng,
            map: map,
            icon: startIcon,
            title:"Start"});

        var finishmarker = new google.maps.Marker({
            position: endlatlng,
            icon: finishIcon,
            map: map,
            title:"End"});

<?php
        $current_act = new Activity(array('id' => $activity_id));
        $current_act->getLapsActivity($conn);
        $num_laps = count($current_act->laps);
        if ($num_laps > 0) {
            $log->info("Retrieving lap points data - Laps: " . $num_laps . " | Activity: " . $current_act->id);
            foreach($current_act->laps as $index => $lap_values) {
                $lap_index = $index + 1;
                $content = "<div class='info_content'>End of lap " . $lap_index . "<br/>Elapsed time: " . $lap_values['duration'] . "</div>";

                if ($lap_index == count($current_act->laps)) {
                    echo "var lapIcon" . $lap_index ." = new google.maps.MarkerImage(\"" . $base_url ."/images/finish.png\",";
                } else {
                    echo "var lapIcon" . $lap_index ." = new google.maps.MarkerImage(\"" . $base_url . "/images/". $lap_index .".png\",";
                }
                echo "  new google.maps.Size(16, 16), new google.maps.Point(0, 0),new google.maps.Point(0, 0));";

                echo "var lap" . $lap_index . "marker = new google.maps.Marker({";
                echo "  position: new google.maps.LatLng(" . $lap_values['end_lat'] . ", ". $lap_values['end_lon'] . "),";
                echo "  icon: lapIcon" . $lap_index .", map: map, title:'Lap " . $lap_index . "'});";
                echo "\r\n";
                echo "var lap". $lap_index ." = new google.maps.InfoWindow({content: \"" . $content . "\" });";
                echo "\r\n";
                echo "google.maps.event.addListener(lap" . $lap_index . "marker, 'click', function() { lap" . $lap_index . ".open(map,lap" . $lap_index . "marker); });";
            }
        } else {
            $log->info("Retrieving lap points data - No lap info found | Activity " . $current_act->id);
        }
        /*
        var lap1marker = new google.maps.Marker({
            position: new google.maps.LatLng(43.543912, -5.645886), 
            icon: lapimage, 
            map: map,  
            title:"Lap1"});
 
        var lap1 = new google.maps.InfoWindow({
            content: "<div class='info_content'>End of lap:1<br>Elapsed time:5m:9.49s<br>Distance:1.00 km<br>Calories:84</div>" });
        google.maps.event.addListener(lap1marker, 'click', function() { lap1.open(map,lap1marker); });
        */

?>
		var polylineCoordinates = [
<?php 
		foreach($arrLatLon as $key => $value) {
            if (!empty($value['lat']) and !empty($value['lon'])) {
			    echo "new google.maps.LatLng(" . $value['lat'] . ", " . $value['lon'] . ")";
			    // Array in MSIE must finish without comma ","
			    if ($key+1 < $numPoints)
				    echo ",";
                    echo "\r\n";
            }
		} 
?>
        ];

        // Add a polyline.
        var polyline = new google.maps.Polyline({
            path: polylineCoordinates,
            strokeColor: "#3333cc",
            strokeOpacity: 0.6,
            strokeWeight: 5});
        polyline.setMap(map);
<?php
    } else {
        $log->info("No position data in any of the " . $numPoints . " points available, no map to display");
    }
?>
	
