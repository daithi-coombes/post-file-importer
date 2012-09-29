<?php
namespace CityIndex\WP\PostExternal\Modules;
use CityIndex\WP\PostExternal\Controller;

/**
 * Class for handling the modal including tinymce integration
 * 
 * @author daithi
 * @package cityindex
 * @subpackage ci-wp-post-external
 */
class Modal extends Controller{
	
	/**
	 * construct 
	 */
	function __construct(){
		
		$this->wp_action = array(
			'init' => array(&$this, 'editor_tinymce'),
			'admin_head' => array(&$this, 'admin_head'),
			'wp_ajax_get_modal_editor' => array(&$this, 'get_dialog')
		);
		
		parent::__construct( __CLASS__ );
	}
	
	/**
	 * Adds global javascript vars to the &lt;head>.
	 */
	public function admin_head(){
		
		$nonce = wp_create_nonce("post editor modal");
		?>
		<script type="text/javascript">
			var posteditor_modal_nonce = '<?=$nonce?>';
		</script>
		<?php
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
	public function get_dialog(){

		//iframe head
		?><html><head><?php
		wp_enqueue_style('media');
		wp_enqueue_style('colors');
		wp_head();
		?></head><?php
		
		//iframe body
		?><body id="media-upload" class="js"><?php
		$this->get_page();
		
		//footer and die()
		wp_footer();
		?></body></html>
		<?php
		die();
	}
}