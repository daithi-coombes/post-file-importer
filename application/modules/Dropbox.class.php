<?php
namespace CityIndex\WP\PostImporter\Modules;
use CityIndex\WP\PostImporter\Controller;
/**
 * Description of Dropbox
 *
 * @author daithi
 */
class Dropbox {
	
	public function build_html( $files ){
		
		$ret = "<ul>\n";
		
		foreach($files->contents as $file){
			if(is_object($file))
				$ret .= "<li>{$file->path}</li>\n";
		}
		
		return $ret."</ul>\n";
	}
	
	public function get_files( $path="/dropbox" ){
		
		global $API_Connection_Manager;
		$files = array();
		$module = $API_Connection_Manager->get_service('dropbox/index.php');
		
		$response = $module->request(
			"https://api.dropbox.com/1/metadata{$path}",
			"get",
			null,
			true
		);

		//check for 401, redirect to login url
		if( (int) $response['response']['code']==401 ){
			var_dump(basename(__FILE__));
			die();
			$url = $module->get_login_button();
			print "<a href=\"{$url}\" target=\"_new\">Login to DropBox</a>";
			die();
		}

		foreach(json_decode($response['body'])->contents as $file){
			$files[] = array(
				'name' => $file->path
			);
		}

		return $files;
	}
	
}

?>
