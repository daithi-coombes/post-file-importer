<?php
namespace CityIndex\WP\PostImporter\Modules;
use CityIndex\WP\PostImporter\Controller;

//make sure api connection manager is loaded
require_once (WP_PLUGIN_DIR . "/api-connection-manager/application/modules/class-api-connection-manager.php");

/**
 * Class for handling the modal including tinymce integration.
 * 
 * The namespacing for class's to deal with 3rd party services is in the format:
 * Modal{$namespace}.class.php
 * 
 * All class's for each service must be registered in the services array in
 * Modal::__construct() in the format:
 * {$namespace} => 'normal name'
 * 
 * E.G. the service for googles gdrive would have the class:
 * ModalGdrive.class.php
 * and registered in Modal::services array in Modal::__construct() as:
 * 'Gdrive' => 'Google Drive'
 * 
 * @author daithi
 * @package cityindex
 * @subpackage ci-wp-post-importer
 */
class Modal extends Controller{
	
	/** @var API_Connection_Manager The api connection manager object. */
	private $api;
	/** @var array An array of services in {$namespace} => name pairs. */
	private $services= array();
	
	/**
	 * construct 
	 */
	function __construct(){
		
		//load aip-connection-manager
		global $API_Connection_Manager;
		if(!$API_Connection_Manager)
			$API_Connection_Manager = new \API_Connection_Manager ();
		$this->api = $API_Connection_Manager;
		
		//params
		$this->services = $this->load_services();
		$this->script_deps = array('jquery');
		$this->wp_action = array(
			'init' => array(&$this, 'editor_tinymce'),
			'admin_head' => array(&$this, 'admin_head'),
			'wp_head' => array(&$this, 'admin_head'),
			'wp_ajax_ci_post_importer_modal' => array(&$this,'get_dialog'),
			'wp_ajax_ci_post_importer_load_service' => array(&$this, 'get_files')
		);
		
		//calls
		parent::__construct( __CLASS__ );
		
		//set shortcodes for view file
		$this->shortcodes = array(
			'list services' => $this->view_list_services()
		);
	}
	
	/**
	 * Adds global javascript vars to the &lt;head>.
	 */
	public function admin_head(){
		
		$dialog = wp_create_nonce("post importer modal dialog");
		$services = wp_create_nonce("post importer get service");
		$ajaxurl =  admin_url('admin-ajax.php'); 
		?>
		<script type="text/javascript">
			var ci_post_importer_nonces = {
				get_dialog : '<?=$dialog?>',
				services : '<?=$services?>'
			};
			var ci_post_importer_ajaxurl = '<?=$ajaxurl?>';
		</script>
		<?php
	}
	
	/**
	 * Handles all ajax requests to this module.
	 * 
	 * @deprecated
	 */
	public function ajax(){
		
		$service = @$_GET['service'];
		
		switch($service){
			
			case 'Gdrive':
				
				break;
			
			default:
				$this->get_dialog();
				break;
		}
	}
	
	/**
	 * Adds buttons to the wp editors tinymce buttons array.
	 *
	 * @see Posteditor::editor_tinymce()
	 * @param array $buttons
	 * @return array 
	 */
	public function editor_tinymce_btns($buttons) {
		array_push($buttons, "|", "posteditormodal");
		return $buttons;
	}

	/**
	 * Adds plugins to the wp editors tinymce plugins array.
	 *
	 * @see Posteditor::editor_tinymce()
	 * @param array $plugin_array
	 * @return string 
	 */
	public function editor_tinymce_plugins($plugin_array) {
		//$plugin_array['posteditormodal'] = PLUGIN_URL . '/application/includes/tinymce/jscripts/tiny_mce/plugins/posteditormodal/editor_plugin.js';
		$plugin_array['posteditormodal'] = PLUGIN_URL . '/application/includes/posteditormodal/editor_plugin.js';
		return $plugin_array;
	}

	/**
	 * Callback to add the tinymce filters.
	 * 
	 * @return boolean
	 */
	public function editor_tinymce() {
		
		// Don't bother doing this stuff if the current user lacks permissions
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
			return false;

		// Add only in Rich Editor mode
		if (get_user_option('rich_editing') == 'true') {
			add_filter("mce_external_plugins", array(&$this, "editor_tinymce_plugins"));
			add_filter('mce_buttons', array(&$this, 'editor_tinymce_btns')); //'register_myplugin_button');
		}
		return true;
	}
	
	/**
	 * Prints the modal dialog window.
	 * 
	 * @return void
	 */
	public function get_dialog( $html=false ){
		
		//check nonce
		if(!$html)	//if an ajax request
			if(!$this->check_nonce("post importer modal dialog", false));
		
		//iframe head
		?><html><head><?php
		wp_enqueue_style('media');
		wp_enqueue_style('colors');
		wp_head();
		?></head><?php
		
		//iframe body
		?><body id="media-upload" class="js"><?php
		($html) ? print $html : $this->get_page();
		
		//footer and die()
		wp_footer();
		?></body></html>
		<?php
		die();
	}
	
	/**
	 * Prints files iframe.
	 * 
	 * Ajax callback. Makes a request to a service for its files and displays
	 * them.
	 */
	public function get_files(){
		
		//security check
		if(@$_REQUEST['state']) $_REQUEST['_wpnonce'] = $_REQUEST['state'];
		$this->check_nonce("post importer get service");
		
		//vars
		$html = "<ul>\n";
		
		//connect to service
		if($this->api->connect( $_REQUEST['service'] ))
			$files = $this->api->request( $_REQUEST['service'], array(
				'uri' => 'https://www.googleapis.com/drive/v2/files/',
				'method' => 'GET'
			));
		
		foreach($files->items as $file){
			$html .= "<li>
				{$file->title}
				</li>";
		}
		$html .= "</ul>\n";
		
		print $html;
		die();
	}
	
	/**
	 * Shortcode callback. Returns html list of services for the view file.
	 *
	 * @return string
	 */
	private function view_list_services(){
		
		$ret = "<ul>\n";
		
		foreach($this->services as $slug => $data)
			$ret .= "<li><a href=\"javascript:void(0)\" onclick=\"ci_post_importer.connect('{$slug}')\">{$data['Name']}</a></li>\n";
		
		return "{$ret}\n</ul>\n";
	}
	
	/**
	 * Loads the services available from api connection manager.
	 * 
	 * Gets the grant urls and lists links to connect to each service.
	 */
	private function load_services( ){
		
		$services = $this->api->get_services();
		return $services;
	}
}