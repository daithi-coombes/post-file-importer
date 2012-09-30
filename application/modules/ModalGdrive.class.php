<?php
namespace CityIndex\WP\PostImporter\Modules;
use CityIndex\WP\PostImporter\Controller;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ModalGdrive.
 * 
 * This service uses Googles SSO and SAML protocol.
 * A request is made to google servers,
 * google checks public key and returns response
 * client authenticates user
 * clients sends success/false to google
 *
 * @author daithi
 */
class ModalGdrive extends Controller{
	
	public function __construct(){
		
		parent::__construct(__CLASS__);
		
		//look for actions
		$action = $_REQUEST['action'];
		if(method_exists($this, $action))
			$this->$action();
	}
	
	private function oauthCallback(){
		ar_print($_REQUEST);
	}
}

?>
