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
	
	/** @var string The google app client id */
	private $client_id = "525588897138.apps.googleusercontent.com";
	/** @var string The google app redirect uri */
	private $redirect_uri = "http://david-coombes.com/wp-admin/admin-ajax.php?action=ci_post_importer_load_service&service=Gdrive&saction=oauthCallback";
	
	public function __construct(){
		
		//params
		$this->shortcodes = array(
			'gauth url' => $this->get_url()
		);
		
		parent::__construct(__CLASS__);
		
		//look for actions
		$action = @$_REQUEST['saction'];
		if(method_exists($this, $action))
			$this->$action();
	}
	
	/**
	 * Returns the url for requesting the authorization code. 
	 */
	public function get_url(){
		
		$url = "https://accounts.google.com/o/oauth2/auth";
		return url_query_append($url, array(
			'response_type' => 'code',
			'client_id' => $this->client_id,
			'redirect_uri' => $this->redirect_uri,
			'scope' => 'https://docs.google.com/feeds/',
			'state' => wp_create_nonce("post importer get service"),
			'access_type' => 'offline',
			'approval_prompt' => 'auto'
		));
	}
	
	/**
	 * Callback to get access token
	 *  
	 */
	private function get_token(){
		
		$params = array(
			'code' => $_REQUEST['code'],
			'client_id' => $this->client_id,
			'redirect_uri' => $this->redirect_uri,
			'grant_type' => 'authorization_code'
		);
		ar_print($params);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://accounts.google.com/o/oauth2/token");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_PORT, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		$res = curl_exec($ch);
		ar_print($res);
	}
	
	/**
	 * Callback to handle the authorization code.
	 * 
	 * This code is then sent back to google to get an access token. 
	 */
	private function oauthCallback(){
		
		//html head
		?><html><head><?php
		$this->load_styles();
		$this->load_scripts();
		wp_enqueue_script('jquery');
		wp_enqueue_style('media');
		wp_enqueue_style('colors');
		wp_head();
		?>
			<script type="text/javascript">
				function getUrlVars() {
					var vars = {};
					var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
						vars[key] = value;
					});
					return vars;
				}
				jQuery(document).ready(function($){
					var code = getUrlVars()['code'];
					var url = window.opener.document.URL
						+ '&saction=get_token&code='+code;
					window.opener.location.href = url;
					window.close();
				});
			</script>
		</head><?php
		
		//html body
		?><body id="media-upload" class="js">
			redirecting back to david-coombes.com...
			<?php
		
		//footer and die()
		wp_footer();
		?></body></html>
		<?php
		die();
	}
}

?>
