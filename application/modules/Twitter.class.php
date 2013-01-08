<?php
namespace CityIndex\WP\PostImporter\Modules;

/**
 * Description of Twitter
 *
 * @author daithi
 */
class Twitter {
	
	
	
	public function get_tweets(){
		
		global $API_Connection_Manager;
		$service = $API_Connection_Manager->get_service('twitter/index.php');
		
		$response = $service->request(
			"https://api.twitter.com/1/statuses/friends_timeline.json",
			'GET'
		);
		$res = json_decode($response['body']);
		ar_print($res);
	}
}

?>
