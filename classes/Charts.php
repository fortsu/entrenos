<?php
namespace Entrenos;
use Entrenos\Activity;
use Entrenos\Utils\Parser\GPXPlusParser;
// No migration of external classes
//use Entrenos\Utils\Graphs\phpMyGraph;
require_once $_SERVER['DOCUMENT_ROOT'] . "/../classes/Utils/Graphs/phpMyGraph5.0.php";

//setlocale(LC_ALL,"es_ES@euro","es_ES","esp");

/**
 * Charts Class File
 *
 * This class has all actions related to charts 
 *
 */
class Charts {

    var $id;

    function __construct($data) {
        foreach ($data as $key => $value) {
            $this->__set($key,$value);
        }
    }

    public function __set($key, $value) {
        $this->$key = $value;
    }

    public function __get($key) {
        return $this->$key;
    }

    //http://www.bolducpress.com/tutorials/gd-library-bar-chart/
    public static function barChart ($data, $type) {

        //Setting chart variables
        $total_km = 0;
        foreach ($data as $key=>$value) {
            $total_km += $value;
        }
        $data_keys = array_keys($data);
        $meses = array("01"=>"Enero", "02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre");
        $dias_semana = array("1"=>"Lunes", "2"=>"Martes", "3"=>"Miércoles", "4"=>"Jueves", "5"=>"Viernes", "6"=>"Sábado", "7"=>"Domingo");        

        switch ($type) {
            case "W":
                $graphTitle = "Semana " . date("W",strtotime($data_keys[0])) . ": " . date("d\/m",strtotime($data_keys[0])) . " - " . date("d\/m",strtotime(end($data_keys))) .  " (" . $total_km . " km)";
                $xLabel 	= "días de la semana";
                $yLabel 	= "km";
                break;
            case "M":
                $pieces = explode("-",$data_keys[0]);
                $graphTitle = $meses[$pieces[1]] . " de " . $pieces[0] . " (" . $total_km . " km)";
                //$graphTitle = date("F \\d\e Y",strtotime($data_keys[0])) ." (" . $total_km . " km)";
                $xLabel 	= "días";
                $yLabel 	= "km";
                break;
            case "Y":
                $pieces = explode("-",$data_keys[0]);
                $graphTitle = $pieces[0] . " (" . $total_km . " km)";
                $xLabel 	= "meses";
                $yLabel 	= "km";
                break;
            default:
                $graphTitle = "";
                $xLabel 	= "";
                $yLabel 	= "";
                break;
        }
          
        /*
        $data['Ene'] = 120;
        $data['Feb'] = 210;
        $data['Mar'] = 300;
        $data['Abr'] = 250;
        $data['May'] = 256;
        $data['Jun'] = 170;
        $data['Jul'] = 60;
        $data['Ago'] = 100;
        $data['Sep'] = 190;
        $data['Oct'] = 290;
        $data['Nov'] = 350;
        $data['Dic'] = 120;
        

        $data['2011-04-04'] = 0;
        $data['2011-04-05'] = 12.1;
        $data['2011-04-06'] = 10.02; 
        $data['2011-04-07'] = 0;
        $data['2011-04-08'] = 0;
        $data['2011-04-09'] = 12.92; 
        $data['2011-04-10'] = 0;

        */

        //getting the maximum and minimum values for Y

        //minimum
        $min = 0;
         
        //maximum
        $tmp_data = $data;
        asort($tmp_data);
        $mod = pow(10, strlen(intval(end($tmp_data)))-1);
        $max = end($tmp_data)*1.1; 

        //storing those min and max values into an array
        $yAxis 	= array("min"=>$min, "max"=>$max);

        //------------------------------------------------
        // Preparing the Canvas
        //------------------------------------------------
        //setting the image dimensions
        $canvasWidth  = 500;
        $canvasHeight = 300;
        $perimeter    = 40; 
         
        //creating the canvas
        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight); 
         
        //allocating the colors
        $white     = imagecolorallocate($canvas, 255, 255, 255);
        $black     = imagecolorallocate($canvas, 0,0,0);
        $yellow    = imagecolorallocate($canvas, 248, 255, 190);
        $blue      = imagecolorallocate($canvas, 3,12,94);
        $grey      = imagecolorallocate($canvas, 102, 102, 102);
        $lightGrey = imagecolorallocate($canvas, 216, 216, 216);
        $green     = imagecolorallocate($canvas, 0, 255, 0);
         
        //getting the size of the fonts
        $fontwidth  = imagefontwidth(2);
        $fontheight = imagefontheight(2); 
         
        //filling the canvas with light grey
        imagefill($canvas, 0,0, $lightGrey);


        //------------------------------------------------
        // Preparing the grid
        //------------------------------------------------
        //getting the size of the grid
        $gridWidth  = $canvasWidth  - ($perimeter*2);
        $gridHeight = $canvasHeight - ($perimeter*2); 
         
        //getting the grid plane coordinates
        $c1 = array("x"=>$perimeter, "y"=>$perimeter);
        $c2 = array("x"=>$gridWidth+$perimeter, "y"=>$perimeter);
        $c3 = array("x"=>$gridWidth+$perimeter, "y"=>$gridHeight+$perimeter);
        $c4 = array("x"=>$perimeter, "y"=>$gridHeight+$perimeter);

        //------------------------------------------------
        //creating the grid plane
        //------------------------------------------------
        imagefilledrectangle($canvas, $c1['x'], $c1['y'], $c3['x'], $c3['y'], $white);  
         
        //finding the size of the grid squares
        $sqW = $gridWidth/count($data);
        if ($yAxis['max'] > 0)
            $sqH = $gridHeight/$yAxis['max'];
        else
            $sqH = 0;

        //------------------------------------------------
        //drawing vertical lines and axis values
        //------------------------------------------------
        $increment = 0;
        $verticalPadding = $sqW/2;
        foreach($data as $assoc=>$value)
        {
	        //drawing the line
	        imageline($canvas, $verticalPadding+$c4['x']+$increment, $c4['y'], $verticalPadding+$c1['x']+$increment, $c1['y'], $black);
         
	        //axis values
	        $wordWidth = strlen($assoc)*$fontwidth;
	        $xPos = $c4['x']+$increment+$verticalPadding-($wordWidth/2);

            $text = "";
            $pieces = explode("-",$assoc);
	        
            switch ($type) {
                case "W":
                    //$text = date("D",strtotime($assoc));
                    $text = substr($dias_semana[date("N",strtotime($assoc))], 0, 3);
                    break;
                case "M":
                    $text = $pieces[2];
                    break;
                case "Y":
                    $text = substr($meses[$pieces[1]],0,3);
                    break;
                default:

                    break;
            }
            
            ImageString($canvas, 2, $xPos, $c4['y'], $text, $black);
	        $increment += $sqW;
        }

        //------------------------------------------------
        //drawing horizontel lines and axis labels
        //------------------------------------------------
        //resetting the increment back to 0
        $increment = 0; 
         
        for($i=$yAxis['min']; $i<$yAxis['max']; $i++)
        {
         
	        //main lines
         
		        //often the y-values can be in the thousands, if this is the case then we don't want to draw every single
		        //line so we need to make sure that a line is only drawn every 50 or 100 units. 
         
	        if($i%$mod==0){            

		        //drawing the line
		        imageline($canvas, $c4['x'], $c4['y']+$increment, $c3['x'], $c3['y']+$increment, $black);
         
		        //axis values
		        $xPos = $c1['x']-($fontwidth*strlen($i))-5;               
            
		        ImageString($canvas, 2, $xPos, $c4['y']+$increment-($fontheight/2), $i, $black);

	        }
	        //tics
	        //these are the smaller lines between the longer, main lines.
	        elseif(($mod/5)>1 && $i%($mod/5)==0)
	        {
		        imageline($canvas, $c4['x'], $c4['y']+$increment, $c4['x']+10, $c4['y']+$increment, $grey);
	        }
	        //because these lines begin at the bottom we want to subtract
	        $increment-=$sqH;
        }

        //getting the size of the grid
        $gridWidth  = $canvasWidth  - ($perimeter*2);
        $gridHeight = $canvasHeight - ($perimeter*2);

        //getting the grid plane coordinates
        $c1 = array("x"=>$perimeter, "y"=>$perimeter);
        $c2 = array("x"=>$gridWidth+$perimeter, "y"=>$perimeter);
        $c3 = array("x"=>$gridWidth+$perimeter, "y"=>$gridHeight+$perimeter);
        $c4 = array("x"=>$perimeter, "y"=>$gridHeight+$perimeter);

        //imagefilledrectangle($canvas, $c1['x'], $c1['y'], $c3['x'], $c3['y'], $white);

        //finding the size of the grid squares
        $sqW = $gridWidth/count($data);
        $sqH = $gridHeight/$yAxis['max'];


        //------------------------------------------------
        // Making the vertical bars
        //------------------------------------------------
        $increment = 0; 		//resetting the increment value
        $barWidth = $sqW*.8;    //setting a width size for the bars, play with this number
        foreach($data as $assoc=>$value)
        {
	        $yPos = $c4['y']-($value*$sqH);
	        $xPos = $c4['x']+$increment+$verticalPadding-($barWidth/2);
	        imagefilledrectangle($canvas, $xPos, $c4['y'], $xPos+$barWidth, $yPos, $blue);
	        $increment += $sqW;
        }	
	
        //Graph Title
        ImageString($canvas, 2, ($canvasWidth/2)-(strlen($graphTitle)*$fontwidth)/2, $c1['y']-($perimeter/2), $graphTitle, $black); 
         
        //X-Axis
        ImageString($canvas, 2, ($canvasWidth/2)-(strlen($xLabel)*$fontwidth)/2, $c4['y']+($perimeter/2), $xLabel, $black); 
         
        //Y-Axis
        ImageStringUp($canvas, 2, $c1['x']-$fontheight*3, $canvasHeight/2+(strlen($yLabel)*$fontwidth)/2, $yLabel, $black);

        header ("Content-type: image/png");
        imagepng($canvas);
        imagedestroy($canvas);
    }

    public static function barChart2 ($data, $type) {

        //Setting chart variables
        $total_km = 0;
        foreach ($data as $key=>$value) {
            $total_km += $value;
        }
        $data_keys = array_keys($data);
        $meses = array("01"=>"Enero", "02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre");
        $dias_semana = array("1"=>"Lunes", "2"=>"Martes", "3"=>"Miercoles", "4"=>"Jueves", "5"=>"Viernes", "6"=>"Sabado", "7"=>"Domingo");
        $graph_data = array();
        $graphTitle = "";        

        switch ($type) {
            case "W":
                $graphTitle = "Semana " . date("W",strtotime($data_keys[0])) . ": " . date("d\/m",strtotime($data_keys[0])) . " - " . date("d\/m",strtotime(end($data_keys))) .  " (" . $total_km . " km)";
                //$text = date("D",strtotime($assoc));
                foreach ($data as $orig_key => $value) { 
                    $new_key = substr($dias_semana[date("N",strtotime($orig_key))], 0, 3);
                    $graph_data[$new_key] = $value;
                }          
                break;
            case "M":
                $pieces = explode("-",$data_keys[0]);
                $graphTitle = $meses[$pieces[1]] . " de " . $pieces[0] . " (" . $total_km . " km)";
                //$graphTitle = date("F \\d\e Y",strtotime($data_keys[0])) ." (" . $total_km . " km)";
                foreach ($data as $orig_key=>$value) {
                    $pieces_key = explode("-",$orig_key); 
                    $new_key = $pieces_key[2];
                    $graph_data[$new_key] = $value;
                }
                break;
            case "Y":
                $pieces = explode("-",$data_keys[0]);
                $graphTitle = $pieces[0] . " (" . $total_km . " km)";
                foreach ($data as $orig_key=>$value) {
                    $pieces_key = explode("-",$orig_key); 
                    $new_key = substr($meses[$pieces_key[1]],0,3);
                    $graph_data[$new_key] = $value;
                }
                break;
            default:
                $graphTitle = "";
                break;
        }

        $cfg['title'] = $graphTitle;
        $cfg['width'] = 700;
        $cfg['height'] = 300;
        $cfg['title-font-size'] = 4;
        $cfg['average-line-visible'] = False;

        header ("Content-type: image/png");
        $graph = new \phpMyGraph();
        $graph->parseVerticalSimpleColumnGraph($graph_data, $cfg);

    }

    public static function altitudeChart ($user_id, $activity_id, $conn) {
        global $base_path;
        $my_file = $base_path . "/users/" . $user_id . "/data/". $activity_id;
        $parser = new GPXPlusParser();
        $arrPointsData = $parser->getPoints($my_file);
        $current_act = new Activity(array('id' => $activity_id, 'user_id' => $user_id));
        $current_act->getActivity($conn);
        $distancia = $current_act->distance/1000;
        $puntos = count($arrPointsData);
        //error_log("Actividad: " . $activity_id . " | Puntos: " . $puntos . " | " . $distancia . " m | ". sprintf("%.2f",$distancia/$puntos) ." metros/punto", 0);
        $dist_acum = 0;
        $unit = $distancia/$puntos;
        $graph_data = array();

        // looking for minimum elevation value
        $elevations = array();
        foreach ($arrPointsData as $key => $value) {
            $elevations[] = $value['ele'];
        }    
        $altitude_offset = floor(abs(min($elevations))/50)*50;

	    foreach($arrPointsData as $key => $value) {
             $graph_data[sprintf("%.2f",$dist_acum)] = sprintf("%.2f",($value['ele']-$altitude_offset));
             $dist_acum += $unit;
        }
    
        //Set config directives
        //$cfg['title'] = 'Altura';
        $cfg['width'] = 800;
        $cfg['height'] = 300;
        $cfg['average-line-visible'] = true;
        $cfg['horizontal-divider-visible'] = true;
        $cfg['column-divider-visible'] = false;
        $cfg['round-value-range'] = true;
        $cfg['zero-line-visible'] = false;
        $cfg['file-name'] = $base_path . "/users/" . $user_id . "/reports/alt_profile_" . $activity_id . ".png";
        
        //Create phpMyGraph instance
        $graph = new \phpMyGraph();
        $graph->parseVerticalPolygonGraph($graph_data, $cfg, $altitude_offset);
        #$graph->parseVerticalLineGraph($graph_data, $cfg, $altitude_offset);

    }

    public static function paceChart ($user_id, $activity_id, $conn) {
        global $base_path;

        $current_act = new Activity(array('id' => $activity_id, 'user_id' => $user_id));
        $current_act->getActivity($conn);
        $distancia = $current_act->distance/1000;
      
        $pace = array();

        $cumulated_pace = array();
        foreach ($current_act->laps as $key => $value) {
            $pace[] = round($value['pace'] * 60, 2);
            $cumulated_pace[] = round((array_sum($pace)) / count($pace), 2);  
        }

            //Set config directives
        //$cfg['title'] = 'Altura';
        $cfg['width'] = 800;
        $cfg['height'] = 300;
        $cfg['average-line-visible'] = false;
        $cfg['horizontal-divider-visible'] = true;
        $cfg['column-divider-visible'] = false;
        $cfg['round-value-range'] = true;
        $cfg['zero-line-visible'] = false;
        $cfg['file-name'] = $base_path . "/users/" . $user_id . "/reports/alt_profile_" . $activity_id . ".png";
        $cfg['label'] = "Pace vs Cumulated Pace";

        //Create phpMyGraph instance
        #$graph = new \phpMyGraph();
        #$graph->parseVerticalLineGraph($pace, $cfg);
        #$graph->parseVerticalLineGraph($pace, $cumulated_pace, $cfg);

#        $graph = new verticalLineGraph(); 

        //Parse
       $graph = \phpMyGraph::factory('verticalLineGraph', $pace, $cfg);
       $graph->parseCompare($pace, $cumulated_pace, $cfg); 


#    $graph = new verticalLineGraph();
    //Parse
#    $graph->parseCompare($data1, $data2, $cfg); 

 #       $graph->parseCompare($pace, $cumulated_pace, $cfg); 

    }
}
?>
