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
		
		$tweets = $service->request(
			'http://api.twitter.com/1/statuses/user_timeline.format',
			'GET',
			array(
				'user_id' => $service->user_id
			)
		);
		
		ar_print($tweets);
	}
}

?>
