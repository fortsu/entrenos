<?php
namespace Entrenos\Utils;
use Entrenos\User;

class Facebook {

    const FB_APP_ID = "115493161855991";
    const FB_SECRET = "f91c885f2554888e48e4d8e3db4f717a";

    public static function requestFacebook($url, $args, $post) {
	    $ch = curl_init();
        if ($post) {
            curl_setopt($ch, CURLOPT_URL, $url);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
        } else { 
            curl_setopt($ch, CURLOPT_URL, $url . "?" . http_build_query($args));
        }
	    curl_setopt($ch, CURLOPT_POST, $post);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

	    $result = curl_exec($ch);
	    curl_close($ch);

       	return $result;
    }

    public static function postFBWall($conn, $user_id, $msg, $privacy) {
        $user = new User(array('id'=>$user_id));
        $user->getFBToken($conn);
        if ($user->fb_access_token) {
            $privacy_array = array('value' => "CUSTOM", 'friends' => $privacy);
            $result = self::requestFacebook('https://graph.facebook.com/me/feed',array(
                                'access_token' => $user->fb_access_token,
                                'message' => $msg,
                                'actions' => '{"name":"Ver en FortSu","link":"http://www.fortsu.com"}',
                                'privacy' => json_encode($privacy_array)),1);
        } else { // ToDo: try to retrieve it
            $result = json_encode(array('error' => "No FB access token available for user " . $user->id));
        }
        return $result;
    }
}
?>
