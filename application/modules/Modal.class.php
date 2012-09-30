<?php
namespace CityIndex\WP\PostImporter\Modules;
use CityIndex\WP\PostImporter\Controller;

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
	
	/** @var array An array of services in {$namespace} => name pairs. */
	private $services= array();
	
	/**
	 * construct 
	 */
	function __construct(){
		
		//params
		$services = array(
			'Gdrive' => 'GDrive'
		);
		$this->script_deps = array('jquery');
		$this->wp_action = array(
			'init' => array(&$this, 'editor_tinymce'),
			'admin_head' => array(&$this, 'admin_head'),
			'wp_head' => array(&$this, 'admin_head'),
			'wp_ajax_ci_post_importer_modal' => array(&$this,'get_dialog'),
			'wp_ajax_ci_post_importer_load_service' => array(&$this, 'load_service')
		);
		
		//calls
		parent::__construct( __CLASS__ );
		$this->register_services( $services );
		
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
	 * Ajax callback. Loads a service.
	 * 
	 * Loads the service class and calls methods defined in 
	 * $_REQUEST['ci_post_importer_action'].
	 */
	public function load_service(){
		
		//security check
		if(@$_REQUEST['state']) $_REQUEST['_wpnonce'] = $_REQUEST['state'];
		$this->check_nonce("post importer get service");
		
		//load service
		$class = "\CityIndex\WP\PostImporter\Modules\Modal{$_REQUEST['service']}";
		$service = new $class();
		
		//if no actions default to loading view file
		if(!@$_REQUEST[ $this->config->action_key ])
			$this->get_dialog( $service->get_page(true) );
			
		die();
	}
	
	/**
	 * Shortcode callback. Returns html list of services for the view file.
	 *
	 * @return string
	 */
	private function view_list_services(){
		
		$ret = "<ul>\n";
		
		foreach($this->services as $class => $name)
			$ret .= "<li><a href=\"javascript:void(0)\" onclick=\"ci_post_importer.connect('{$class}')\">{$name}</a></li>\n";
		
		return "{$ret}\n</ul>\n";
	}
	
	/**
	 * Registers 3rd party services. See the class description for more
	 * information.
	 */
	private function register_services( array $services ){
		$this->services = $services;
	}
}