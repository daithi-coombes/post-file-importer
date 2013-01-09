<?php
namespace CityIndex\WP\PostImporter\Modules;
use CityIndex\WP\PostImporter\Controller;
/**
 * Description of Dropbox
 *
 * @author daithi
 */
class Dropbox {
	
	public function get_files(){
		
		global $API_Connection_Manager;
		$module = $API_Connection_Manager->get_service('dropbox/index.php');
		
		$files = $module->request(
			'https://api.dropbox.com/1/metadata/dropbox',
			'GET'
		);
		ar_print($files);
	}
}

?>
