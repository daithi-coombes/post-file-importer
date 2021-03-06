<?php
namespace CityIndex\WP\PostImporter\Modules;
use CityIndex\WP\PostImporter\Controller;

/**
 * Description of ModalGdrive.
 * 
 * This service uses Googles SSO and SAML protocol.
 * A request is made to google servers,
 * google checks public key and returns response
 * client authenticates user
 * clients sends success/false to google
 *
 * @todo in construct check if refresh token is there ($this->check_state())
 * @todo if refresh token then request new access token for this session
 * @todo if refresh and access token, then list files
 * @todo change redirect url to be same as default. In class construct determine
 * if listing files or showing login link. List files:
 * - If $_REQUEST['code'] is available then get access/refresh tokens.
 * - If refresh token, get new access token, list files
 * Show gauth login link:
 * - If no $_REQUEST['code'] and no stored refresh token show login link
 * @author daithi
 */
class ModalGdrive extends Controller{
	
	/** @var string The current valid token */
	private $access_token = false;
	/** @var string The google app client id */
	private $client_id = "525588897138.apps.googleusercontent.com";
	/** @var string The google app secret */
	private $client_secret = "5ZmQikl__N5sxnZ7g_tL2F2e";
	/** @var string The google app redirect uri */
	private $redirect_uri = "http://david-coombes.com/wp-admin/admin-ajax.php?action=ci_post_importer_load_service&service=Gdrive&saction=oauthCallback";
	/** @var string The refresh token to keep user signed in */
	private $refresh_token = false;
	/** @var string The google app scope */
	private $scope = 'https://docs.google.com/feeds/ https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email';
	/** @var object The authenticated user details */
	private $user;
	
	/**
	 * construct. 
	 */
	public function __construct(){
		
		$this->script_deps = array(
			'jquery',
			'jstree'
		);
		$this->style_deps = array(
			'jstree'
		);
		
		//params
		parent::__construct( __CLASS__ );
		
		//default methods
		$this->refresh_token = $this->get_refresh_token();	//looks in user metadata
		$this->access_token = $this->get_access_token();	//connects to gmail for token
		$this->user = $this->get_user_info();				//gets gmail user info (needed for some gdrive requests)
		
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
	
	/**
	 * Checks if refresh_token is available and sets params.
	 */
	private function check_state( $stdout=false ){
		
		if(!$this->access_token){
			if($stdout) print "<div class=\"error\">Can't get gauth user info, not connected</div>\n";
			return false;
		}
		return true;
	}
	
	/**
	 * Get the refresh token for current user.
	 *
	 * @return mixed If found returns refresh token, false on failure. 
	 */
	private function get_refresh_token(){
		$user_id = get_current_user_id();
		$user_meta = get_user_meta($user_id, "ci_post_importer_gdrive_refresh_token");
		
		if(@$user_meta[0]) return $user_meta[0];
		else return false;
	}
	
	/**
	 * Callback to get access token.
	 * 
	 * If first request then $_REQUEST['code'] will be set and a refresh token
	 * will be returned by google servers.
	 * 
	 * If not first run then refresh token will be used to get another token
	 * from the gmail servers.
	 *  
	 * @return string Returns the access token.
	 */
	private function get_access_token(){
		
		//if no code or refresh token, then this is first run. Login link will be displayed in view file
		if(@!$_REQUEST['code'] && !$this->refresh_token)
			return false;
		
		//vars
		$ch = curl_init();
		$params = array();
		
		//first access
		if(@$_REQUEST['code'])
			$params = array(
				'code' => $_REQUEST['code'],
				'client_id' => $this->client_id,
				'client_secret' => $this->client_secret,
				'scope' => $this->scope,
				'redirect_uri' => $this->redirect_uri,
				'grant_type' => 'authorization_code'
			);
		//using refresh token
		if($this->refresh_token)
			$params = array(
				'refresh_token' => $this->refresh_token,
				'client_id' => $this->client_id,
				'client_secret' => $this->client_secret,
				'grant_type' => "refresh_token"
			);
		
		//curl connect
		curl_setopt($ch, CURLOPT_URL, "https://accounts.google.com/o/oauth2/token");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		$res = json_decode(curl_exec($ch));
		
		if(@$res->error){
			$this->set_refresh_token(false);
			print "<div class=\"error\">{$res->error}</div>\n";
			return false;
		}
		
		//set params and return
		if(@$res->refresh_token) $this->set_refresh_token( $res->refresh_token );
		return $res->access_token;		
	}
	
	/**
	 * Get a list of files for a directory
	 * 
	 * Returns a list of children.
	 * 
	 * @param string $parent
	 * @return array 
	 */
	private function get_files( ){
		
		/**
		 *debug. 
		 *
		$data = file_get_contents("http://wordpress.loc/3.4.2/test/gdrive_data.php");
		$res = json_decode($data);
		foreach($res->items as $file)
			if((string) @$file->mimeType == "application/vnd.google-apps.folder")
				$folders[] = $file;
			else
				$files[] = $file;
		
		//return result
		return array(
			'folders' => $folders,
			'files' => $files
		);
		*
		 *end debug 
		 */
		
		//if not logged in
		if(!$this->check_state()) return "";
		
		//vars
		$ch = curl_init();
		$folders = array();
		$files = array();
		if(!$this->access_token) $this->get_token();
		$url = url_query_append("https://www.googleapis.com/drive/v2/files/", array(
			'access_token' => $this->access_token/*,
			'fields' => "etag,items(alternateLink,createdDate,description,downloadUrl,editable,embedLink,etag,explicitlyTrashed,exportLinks,fileExtension,fileSize,id,imageMediaMetadata,kind,lastModifyingUserName,lastViewedByMeDate,md5Checksum,mimeType,modifiedByMeDate,modifiedDate,originalFilename,quotaBytesUsed,selfLink,sharedWithMeDate,thumbnailLink,title,userPermission,webContentLink,writersCanShare),kind,nextLink,nextPageToken,selfLink",
			'folderId' => $parent*/
		));
		
		$res = wp_remote_get($url);
		$data = json_decode($res);
		ar_print( $data );
		/**
		 * get file list
		 * @deprecated
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = json_decode(curl_exec($ch));
		 * 
		 */
		
		//error report
		if(@$res->error) return new \WP_Error( $res->error->code, $res->error->message );
		
		/**
		 * Build hierarchical list of files/folders
		 */
		foreach($res->items as $file)
			if((string) @$file->mimeType == "application/vnd.google-apps.folder")
				$folders[] = $file;
			else
				$files[] = $file;
		
		//return result
		return array(
			'folders' => $folders,
			'files' => $files
		);
	}
	
	/**
	 * Get the google account info for current logged in user.
	 * 
	 * @return boolean Returns google user info on success, false on failure.
	 */
	private function get_user_info(){
		
		//check connected
		if(!$this->check_state()) return false;
		
		//get user info
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v1/userinfo?access_token={$this->access_token}");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = json_decode(curl_exec($ch));
		
		//error report
		if(@$res->error){
			print "<div class=\"error\">{$res->error}</div>\n";
			return false;
		}
		
		//set user
		return $res;
	}
	
	/**
	 * Shortcode method. Returns the style for the logged in/out containers.
	 * 
	 * @param boolean $logged_in
	 * @return string 
	 */
	private function get_view_class( $logged_in ){
		
		//vars
		$hide = "style=\"display:none\"";
		$show = "";
		
		/**
		 * debug 
		 *
		return $show;
		 * 
		 */
		
		//for logged in div
		if($logged_in)
			if($this->check_state())	//logged in
				return $show;
			else return $hide;			//logged out
			
		//for not logged in div
		if(!$logged_in)
			if($this->check_state())	//logged in
				return $hide;
			else return $show;			//logged out
	}
	
	/**
	 * Returns html list of files.
	 * 
	 * @return string 
	 */
	private function list_files(){
		
		//if not logged in
		if(!$this->check_state()) return "";
		
		$files = $this->get_files();		
		$ret = "<ul>\n";
		$ajaxurl = get_admin_url(null, 'admin-ajax.php');
		
		//error report
		if(is_wp_error($files))
			return "<div class=\"error\">{$files->get_error_message ()}</div>\n";
		
		//build list and return
		foreach($files['folders'] as $folder)
			$ret .= "<li rel=\"folder\">
				<a href=\"javascript:void(0)\">{$folder->title}</a>
				<ul></ul>
				</li>\n";
		foreach($files['files'] as $file)
			//if file is downloadable
			if($file->downloadUrl)
				$ret .= "<li rel=\"file\">
					<a href=\"javascript:void(0)\" onclick=\"ci_post_importer_gdrive.get_document_data('" . urlencode($file->downloadUrl) . "', '{$ajaxurl}')\">
						{$file->title}
					</a>
					</li>\n";
			//if not downloadable
			else
				$ret .= "<li rel=\"file\">
						{$ajaxurl}
						{$file->title}
					</li>\n";
		print $ret;
		return "{$ret}</ul>\n";
		
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
	
	/**
	 * Sets refresh token for current user.
	 * 
	 * @param string $refresh_token 
	 */
	private function set_refresh_token( $refresh_token ){
		$user_id = get_current_user_id();
		update_user_meta($user_id, "ci_post_importer_gdrive_refresh_token", $refresh_token);		
	}
}

?>
