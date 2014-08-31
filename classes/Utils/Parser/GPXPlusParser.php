<?php
namespace Entrenos\Utils\Parser;
/**
 * GPXPlusParser Class File
 *
 * This class loads an XML document into a SimpleXMLElement that can
 * is parsed by laps
 */
class GPXPlusParser {
    /**
     * While parsing, parse the supplied XML document.
     *
     * Sets up a SimpleXMLElement object based on success of parsing
     * the XML document file. It stores lap data in arrays and then
     * collect them in an overall array.
     *
     * @param string $my_file the xml document location path
     * @return array 
     */
    public static function getLaps ($my_file) {
        if (file_exists($my_file)) {
       		$xml = simplexml_load_file($my_file);
			if ($xml) {
                //try {
				    // All parsed data is stored in arrWorkout. Each element contains lap information
		            $arrWorkout = array();
            		$my_field = "gpxdata:lap";
                    $xmlNode = $xml->xpath("//" . $my_field);
				    if ($xmlNode) {
                        $nodeCount = count($xmlNode);
                        $i=0;
                        foreach($xmlNode as $key => $lap) {
                            $arrLap = array();
		                    foreach($lap->children('gpxdata',$isprefix=true) as $a => $b) {
                    	        $fieldValue = $b;
                                switch($a) {
                                    case "summary": //"summary":{"name":"MaximumHeartRateBpm","kind":"max"}
                                        $arrLap[(string)$b->attributes()->name] = (string)$fieldValue;
                                        break;
                                    case "trigger": //"trigger":{"kind":"MANUAL"}
                                        $arrLap[$a] = (string)$b->attributes()->kind;
                                        break;
                                    default:
                                        if ($b->attributes()) {
                                            $subNode = array();
                                            foreach($b->attributes() as $key => $value) {
                                                $subNode[$key]=(string)$value;
                                            }
                                            $arrLap[$a]=(array)$subNode;
                                        } else {
                                            $arrLap[$a] = (string)$fieldValue;
                                        }
                                }
                            }
                            //error_log("lap: " . json_encode($arrLap), 0);
                            $arrWorkout[$i]=(array)$arrLap;
                            $i++;
                        }
				        return $arrWorkout;
                    } else {
                        return FALSE;
                    }
			//	}
        	} else {
                $xml_errors = "Parsing error ";
                foreach(libxml_get_errors() as $error) {
                     $xml_errors .= $error->message . " ";
                }
                throw new Exception($xml_errors);
        	}
		} else {
            $error_txt ="File not found: " . $my_file;
            throw new Exception($error_txt);
		}
	}

	public static function getPoints ($my_file, $get_metadata = false) {
		if (file_exists($my_file)) {
            $xml = simplexml_load_file($my_file);
            if ($xml) {
                $arrPoints = array();
                $numPointsPosition = 0;
                foreach($xml->trk->trkseg->trkpt as $a => $b) {
                    $point = array();
                    foreach($b->attributes() as $key => $value) {
                        $point[$key]=(string)$value;
                    }  
                    foreach($b->children() as $key => $value) {
                        if ($key == 'extensions') {
                            foreach($value->children('gpxdata',$isprefix = true) as $clave => $valor) {
                                $point[$clave]=(string)$valor;
                            }
                        } else {
                            $point[$key]=(string)$value;
                        }
                    }
                    //Array ( [lat] => 43.54065207 [lon] => -5.65088273 [ele] => 2.06005859 [time] => 2011-03-07T12:49:19+01:00 [hr] => 55 )
                    if (!empty($point["lat"]) and !empty($point["lon"])) {
                        $numPointsPosition += 1;
                    }
                    $arrPoints[] = $point;
                }
                if ($get_metadata) {
                    return array($arrPoints, $numPointsPosition);
                } else {
                    return $arrPoints;
                }
            } else {
                exit("Not able to load " . $my_file . " Not well formed xml");
            }
        } else {
            exit('File not found: ' . $my_file);
        }
    }

	public static function getCenter ($arrLatLon) {
		$latSum = 0.0;
        $lonSum = 0.0;
		$numPoints = count($arrLatLon);
        foreach($arrLatLon as $key => $value) {
            $latSum += (float)$value['lat'];
            $lonSum += (float)$value['lon'];
        }
        $latAver = $latSum / $numPoints;
        $lonAver = $lonSum / $numPoints;
        $averArray = array("lat" => $latAver, "lon" => $lonAver);
		return $averArray;
	}	
}
?>
