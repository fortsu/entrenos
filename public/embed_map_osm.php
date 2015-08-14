<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
    use Entrenos\Utils\Parser\GPXPlusParser;
    use Entrenos\Utils\Utils;
    session_start();

    // OpenLayers execute a GET request so preparing temporary location for data source:
    // path: BASE_PATH/tmp/<user_id>/<random_filename>
    // url: BASE_URL/tmp/<user_id>/<random_filename>
    $orig_file = $base_path . "/" . $_GET['file']; // users/<user_id>/data/<activity_id>
    $activity_id = pathinfo($orig_file, PATHINFO_BASENAME);
    $ol_filename = substr(md5(mt_rand()), 0, 10);
    $tmp_path = $base_path . "/tmp/" . $_SESSION["user_id"];
    $tmp_file = $tmp_path . "/" . $ol_filename;
    $tmp_file_url = $base_url . "/tmp/" . $_SESSION["user_id"] . "/" . $ol_filename;
    // Checking path integrity (exists, can write on it)
    if (is_dir($tmp_path) === FALSE) {
        if (mkdir($tmp_path, 0755, true) === FALSE) {
            $log->error("Error when creating temporary directory \"" . $tmp_path . "\" | Error: " . json_encode(error_get_last()));
            throw new Exception("Error when parsing activity " . $activity_id);
        }
    }
    // Make sure original file is a valid gpx file -> transform
    $simplexml_tmp = simplexml_load_file($orig_file);
    $transform_result = Utils::XSLProcString ($simplexml_tmp->asXML(), $base_path . "/../transform/gpxplus2gpx.xsl", $tmp_file);
    if ($transform_result === false) {
        $log->error("Unable to produce a valid GPX for rendering on OSM");
        // TODO: display proper feedback to user!
        exit;
    } else {
        $log->debug("Valid GPX file on " . $transform_result);
    }

    // Start parsing
    $parser = new GPXPlusParser();
    list($arrLatLon, $numPointsPosition) = $parser->getPoints($tmp_file, true);
    $numPoints = count($arrLatLon);
    if ($numPointsPosition > 0) {
        $log->debug("Found " . $numPointsPosition . " out of " . $numPoints . " points with valid position data. Displaying map...");

	    $averages = $parser->getCenter($arrLatLon);
        echo "var lat=" . $averages['lat'] . ";\n\r";
        echo "var lon=" . $averages['lon'] . ";\n\r";

        $lats = array();
        $lons = array();
        // TODO: rethink this as coordinates are not linked and therefore not sure about point's position
        foreach ($arrLatLon as $index => $point){
            if (!empty($point['lat'])) {
                $lats[] = $point['lat'];
            }
            if (!empty($point['lon'])) {
                $lons[] = $point['lon'];
            }
        }
        echo "var southWest_lat =" . min($lats) . ";\n\r";
        echo "var southWest_lon =" . min($lons) . ";\n\r";
        echo "var northEast_lat =" . max($lats) . ";\n\r";
        echo "var northEast_lon =" . max($lons) . ";\n\r";

        // Format date to display as layer's name: $arrLatLon[0]['time'] 2011-03-07T12:49:19+01:00 (ISO 8601)
        // TimeZone is UTC, converted to local one
        $tmp_datetime_obj = new DateTime($arrLatLon[0]['time']);
        $tmp_datetime_obj->setTimezone(new DateTimeZone("Europe/Madrid"));
        $layer_datetime = $tmp_datetime_obj->format("Y-m-d H:i:s");

    ?>
	    var zoom=14
        var options = {
            projection: new OpenLayers.Projection("EPSG:900913"),
            displayProjection: new OpenLayers.Projection("EPSG:4326"),
            maxExtent: new OpenLayers.Bounds(-20037508.34, -20037508.34, 20037508.34, 20037508.34),
            maxResolution: 156543.0339,
            units: 'm',
            controls:[
			    new OpenLayers.Control.Navigation(),
			    new OpenLayers.Control.PanZoomBar(),
			    new OpenLayers.Control.LayerSwitcher(),
                new OpenLayers.Control.ScaleLine({geodesic: true}),
                new OpenLayers.Control.Attribution()],
            numZoomLevels: 19
        };

        var map = new OpenLayers.Map('map_canvas', options);

	    // Define the map layer
	    // Here we use a predefined layer that will be kept up to date with URL changes
        map.addLayer(new OpenLayers.Layer.OSM()); //mapnik is the default one

        layerCycle = new OpenLayers.Layer.OSM("OpenCycleMap",
          ["http://a.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png",
           "http://b.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png",
           "http://c.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png"]);
        map.addLayer(layerCycle);

        // Adding markers (start, finish, laps)
	    layerMarkers = new OpenLayers.Layer.Markers("Vueltas");
	    map.addLayer(layerMarkers);

	    // Add the Layer with the GPX Track
	    var lgpx = new OpenLayers.Layer.GML("<?php echo "Ruta " . $layer_datetime; ?>", "<?php echo $tmp_file_url; ?>", {
		    format: OpenLayers.Format.GPX,
		    style: {strokeColor: "green", strokeWidth: 5, strokeOpacity: 0.7},
		    projection: new OpenLayers.Projection("EPSG:4326")
	    });
	    map.addLayer(lgpx);

        // Centering map. Zoom value will be overriden by bounding to frame (SW, NE) positions
	    var lonLat = new OpenLayers.LonLat(lon, lat).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
	    map.setCenter(lonLat, zoom);

        // Extend map zoom to bound extreme positions
        posBounds = new OpenLayers.Bounds();
        var lonLatSW = new OpenLayers.LonLat(southWest_lon,southWest_lat).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
        var lonLatNE = new OpenLayers.LonLat(northEast_lon,northEast_lat).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
        posBounds.extend(lonLatSW);
        posBounds.extend(lonLatNE);
        map.zoomToExtent(posBounds);

	    var size = new OpenLayers.Size(32, 32);
	    var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
	    //var icon = new OpenLayers.Icon('http://www.openstreetmap.org/openlayers/img/marker.png',size,offset);
	    //layerMarkers.addMarker(new OpenLayers.Marker(lonLat,icon));

<?php
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
?>

        var startlatlng = new OpenLayers.LonLat(<?php echo $start_lon; ?>,<?php echo $start_lat; ?>)
                        .transform(
                            new OpenLayers.Projection("EPSG:4326"), // transform from WGS 1984
                            map.getProjectionObject() // to Spherical Mercator Projection
                        );
	    var finishlatlng = new OpenLayers.LonLat(<?php echo $last_lon; ?>,<?php echo $last_lat; ?> )
                        .transform (
                            new OpenLayers.Projection("EPSG:4326"), // transform from WGS 1984
                            map.getProjectionObject() // to Spherical Mercator Projection
                        );

        var startIcon = new OpenLayers.Icon("/images/start.png",size,offset);
        layerMarkers.addMarker(new OpenLayers.Marker(startlatlng,startIcon));

        var finishIcon = new OpenLayers.Icon("/images/finish.png",size,offset);
        layerMarkers.addMarker(new OpenLayers.Marker(finishlatlng,finishIcon));

        // Zoom to bound where all markers in marker layer are extended.
        // http://stackoverflow.com/questions/4084980/with-openlayers-how-do-i-make-sure-a-list-of-points-are-all-displayed
        //var bounds = layerMarkers.getDataExtent();
        //map.zoomToExtent(bounds);
<?php
    } else {
        $log->info("No position data in any of the " . $numPoints . " points available, no map to display");
    }
?>
