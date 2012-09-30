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
	/** @var string The google app secret */
	private $client_secret = "5ZmQikl__N5sxnZ7g_tL2F2e";
	/** @var string The google app redirect uri */
	private $redirect_uri = "http://david-coombes.com/wp-admin/admin-ajax.php?action=ci_post_importer_load_service&service=Gdrive&saction=oauthCallback";
	/** @var string The refresh token to keep user signed in */
	private $refresh_token = "";
	/** @var string The google app scope */
	private $scope = 'https://docs.google.com/feeds/ https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email';
	/** @var object The authenticated user details */
	private $user;
	
	public function __construct(){
		
		//params
		parent::__construct( __CLASS__ );
		
		$this->check_state();
		
		//look for actions
		$action = @$_REQUEST['saction'];
		if(method_exists($this, $action))
			$this->$action();
	}
	
	/**
	 * Prints the view html.
	 * 
	 * Loads the html then sets shortcodes,loads scripts and styles then prints 
	 * html.
	 * 
	 * @param boolean $return Default false. If true will return html if not
	 * will print.
	 * @return type 
	 */
	public function get_page( $return=false ) {

		//vars
		$this->html = file_get_contents("{$this->config->plugin_dir}/public_html/ModalGdrive.php");
		
		//clean out phpDoc
		$this->html = preg_replace("/<\?php.+\?>/msU", "", $this->html);
		
		$this->shortcodes = array(
			'gauth url' => $this->get_url(),
			'list files' => $this->list_files(),
			'class logged in' => $this->get_view_class(true),
			'class logged out' => $this->get_view_class(false)
		);		
		$this->shortcodes['errors'] = $this->get_errors();
		$this->shortcodes['messages'] = $this->get_messages();
		
		$this->set_shortcodes();
		$this->load_scripts();
		$this->load_styles();

		if(!$return) print $this->html;
		return $this->html;
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
			'scope' => $this->scope,
			'state' => wp_create_nonce("post importer get service"),
			'access_type' => 'offline',
			'approval_prompt' => 'auto'
		));
	}
	
	private function check_state(){
		
		if(!$this->refresh_token) $this->user = false;
	}
	
	/**
	 * Callback to get access token
	 *  
	 */
	private function get_token(){
		
		ar_print("<h1>get_token()</h1>");
		
		$params = array(
			'code' => $_REQUEST['code'],
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'scope' => $this->scope,
			'redirect_uri' => $this->redirect_uri,
			'grant_type' => 'authorization_code'
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://accounts.google.com/o/oauth2/token");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		$res = json_decode(curl_exec($ch));
		
		//error report
		if(@$res->error)
			return print "<div class=\"error\">{$res->error}</div>\n";
		
		//set params
		if(@$res->refresh_token) $this->refresh_token = $res->refresh_token;
		//$this->refresh_token = "1/19eqqPiEFRdYNDqQ8X8vH-hpKq7cSS9YDgFrX7lj4v8";
		$this->access_token = $res->access_token;
		
		//get user info
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v1/userinfo?access_token={$this->access_token}");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = json_decode(curl_exec($ch));
		
		//error report
		if(@$res->error)
			return print "<div class=\"error\">{$res->error}</div>\n";
		
		//set user
		$this->user = $res;		
	}
	
	/**
	 * Shortcode method. Returns the style for the logged in/out containers.
	 * 
	 * @param boolean $logged_in
	 * @return string 
	 */
	private function get_view_class( $logged_in ){
		if( $logged_in && !$this->user ) return "style=\"display:none\"";
		return "";
	}
	
	/**
	 * 
	 * @return string 
	 */
	private function list_files(){
		
		if(!$this->user) return "";
		ar_print("listing files...");
		
		//vars
		$ch = curl_init();
		$url = url_query_append("https://www.googleapis.com/drive/v2/files", array(
			'access_token' => $this->access_token
		));
		$res = "<ul>\n";
		if(!$this->access_token) $this->get_token();
		
		//get file list
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = json_decode(curl_exec($ch));
		
		//error report
		if(@$res->error)
			return print "<div class=\"error\">{$res->error}</div>\n";
		
		//build html and return
		foreach($res->items as $file)
			var_dump ($file->title);
			//$res .= "<li>" . (string) $file->title ."</li>\n";
			
		return "{$res}</ul>\n";
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
