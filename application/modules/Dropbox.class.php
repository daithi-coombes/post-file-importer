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
		$module = $API_Connection_Manager->get_service('dropbox/index.php');
		
		$response = $module->request(
				"https://api.dropbox.com/1/metadata{$path}",
				"get"
				);
		
		return json_decode($response['body']);
	}
	
}

?>
