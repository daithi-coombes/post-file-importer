<?php
namespace CityIndex\WP\PostImporter\Modules;

//make sure api connection manager is loaded
@require_once (WP_PLUGIN_DIR . "/api-connection-manager/class-api-connection-manager.php");

/**
 * Description of Facebook
 *
 * @author daithi
 */
class Facebook {
	
	public $plugin_url;
	private $api;
	
	public function __construct(){
		$this->api = new \API_Connection_Manager();
	}
	
	public function get_album(){
		
		$res = $this->api->request($_REQUEST['service'],array(
			'uri' => "https://graph.facebook.com/{$_GET['id']}/photos?access_token=<!--[--access-token--]-->",
			'method' => 'get'
		));
		$html = "<ul id=\"fb-album-list\">\n";
		
		foreach(json_decode($res['body'])->data as $pic){
			
			//build url
			$params = $_GET;
			$params['id'] = $pic->id;
			$params['fb_action'] = 'get_photo';
			$query = http_build_query($params);
			
			$html .= "<li>
					<a href=\"/wp-admin/admin-ajax.php?{$query}\">
						<img src=\"{$pic->images[6]->source}\" width=\"{$pic->images[6]->width}\" height=\"{$pic->images[6]->height}\" border=\"0\"/>
					</a>
				</li>";
		}
		
		return $html."</ul>\n";
	}
	
	/**
	 * Build html list of albums
	 * 
	 * @return string Returns the html
	 */
	public function get_contents(){
		
		//get list of albums
		$res = $this->api->request($_REQUEST['service'],array(
			'uri' => "https://graph.facebook.com/me/albums?access_token=<!--[--access-token--]-->",
			'method' => 'get'
		));
		$albums = json_decode($res['body'])->data;
		
		//build list of albums
		$html = "<ul id=\"fb-album-list\">";
		foreach($albums as $album){
			
			//get url to parse album cover @see $this->parse_img()
			$img_vars = $album_vars = $_GET;
			$img_vars['fb_action'] = "parse_img";
			$img_vars['id'] = $album->id;
			$img_query = http_build_query($img_vars);
			$album_vars['fb_action'] = "get_album";
			$album_vars['id'] = $album->id;
			$album_query = http_build_query($album_vars);
			
			$html .= "<li>
				<span class=\"title\">
					<a href=\"/wp-admin/admin-ajax.php?{$album_query}\">{$album->name}</a>
				</span>
				<a href=\"/wp-admin/admin-ajax.php?{$album_query}\">
					<img src=\"/wp-admin/admin-ajax.php?{$img_query}\" border=\"0\"/>
				</a>
			</li>";
		}
		
		return $html."</ul>";
	}
	
	public function get_photo($id){
		
		$res = $this->api->request($_REQUEST['service'],array(
			'uri' => "https://graph.facebook.com/{$id}?access_token=<!--[--access-token--]-->",
			'method' => 'get'
		));
		$photo = json_decode($res['body']);
		$url = $photo->source;
		
		return media_sideload_image($url, 0);
	}
	
	/**
	 * Prints album cover using raw data from facebook.
	 * 
	 * @param integer $id The album id
	 */
	public function parse_img($id){
		$photo = $this->api->request($_REQUEST['service'],array(
			'uri' => "https://graph.facebook.com/{$id}/picture?access_token=<!--[--access-token--]-->",
			'method' => 'get'
		));
		
		//headers
		foreach($photo['headers'] as $key=>$val)
			header($key . ": " . $val);
		
		//img body
		die($photo['body']);
	}
}

?>
