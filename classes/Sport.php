<?php
namespace Entrenos;

class Sport {
    // Defining sport id
    const Running = 0;
    const Cycling = 1;
    const Walking = 2;
    const Swimming = 3;

    // ToDo: better localization, move this out of here!
    public static $display_es = array(0 => "Correr", 1 => "Ciclismo", 2 => "Caminar", 3 => "Natación");

    /**
     * Check which sport corresponds to provided pace
     * @param pace int e.g. 4.75 (4'45"/km)
     * @param distance int in meters
     * @result int sport id
    **/
    static public function check($pace, $distance = 0) {
        $result = self::Running;
        if ($pace < 3 or ($pace < 3.5 and $distance > 15000)) {
            $result = self::Cycling;
        } else if ($pace > 7.5) {
            $result = self::Walking;
        }
        return $result;
    }
}
?>
