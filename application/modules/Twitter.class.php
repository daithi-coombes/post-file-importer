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
		
		return $service->request(
			'http://api.twitter.com/1/statuses/user_timeline.format'
		);
	}
}

?>
