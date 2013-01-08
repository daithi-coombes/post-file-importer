<?php
namespace CityIndex\WP\PostImporter\Modules;

/**
 * Description of Twitter
 *
 * @author daithi
 */
class Twitter {
	
	public function get_tweets(){
		
		//get the twitter module from the API Manager
		global $API_Connection_Manager;
		$module = $API_Connection_Manager->get_service('twitter/index.php');
		
		//make request for tweets
		$response = $module->request(
			//"https://api.twitter.com/1/statuses/friends_timeline.json",
			"https://api.twitter.com/1/statuses/home_timeline.json",
			'GET'
		);
		$res = json_decode($response['body']);
		
		return $res;
	}
	
	public function parse_tweets( array $tweets ){
		
		$html = "";
		
		foreach($tweets as $tweet)
			$html .= "
				<hr/>
				<div id=\"{$tweet->id}\">
					<div class=\"title\">{$tweet->user->name}</div>
					<div class=\"time\">{$tweet->created_at}</div>
					<div class=\"content\">{$tweet->text}</div>
				</div>
				<div>&nbsp;</div>
				";
					
		return $html;
	}
}