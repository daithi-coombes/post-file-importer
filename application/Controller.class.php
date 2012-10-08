<?php
namespace CityIndex\WP\PostImporter;

/**
 * Class is extended by plugin modules.
 * 
 * Handles writing to the database and displaying the view file.
 * 
 * @author daithi
 * @package cityindex
 * @subpackage ci-wp-post-importer
 */
class Controller{
	
	/** @var string Holds actions from $_REQUEST[$action_key] for calling method
	 * in child class.
	 */
	public $action = false;
	/** @var array The configuration struct defined in plugin index.php file */
	public $config = array();
	/** @var array Array of js dependencies called in child class */
	public $script_deps = array();
	/** @var array Associative array of shortcode=>value pairs */
	public $shortcodes = array();
	/** @var array Array of css dependencies called in child class */
	public $style_deps = array();
	/** @var array An associative array of wordpress actions and callbacks */
	public $wp_action = array();
	/** @var array An associative array of wordpress filters and callbacks */
	public $wp_filter = array();
	/** @var string lowercase child class name */
	private $class = false;
	/** @var string Camel case child class name */
	private $class_name = false;
	/** @var string The html code for the view file */
	public $html = false;
	
	/**
	 * Construct controller.
	 *
	 * @param string $class The class name being extended
	 * @param string $plugin_dir The plugin dir
	 * @param string $plugin_url The plugin url
	 */
	function __construct( $class=false ){
		
		if($class==false) return;
		
		//set default fields
		global $config;
		
		$this->class = $class;
		$this->class_name = ucfirst( @array_pop(explode("\\", $class)) );
		$this->config = $config;
		(@$_REQUEST[$this->config->action_key])
			? $this->action = $_REQUEST[$this->config->action_key]
			: $this->action = false;
		
		//default methods
		$this->do_action();
		$this->set_wp_actions();
		$this->set_wp_filters();
	}

	/**
	 * Activation callback.
	 * 
	 * Installs tables 
	 */
	public function activate(){
		
		require_once( ABSPATH . '/wp-admin/includes/upgrade.php');
		
		/**
		 *CREATE TABLE IF NOT EXISTS `wp_CityIndex\WP\Login_WP_Groups_Group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) 
		 */
		
		foreach($this->modal_tables as $table=>$fields){
			$sql = "
				CREATE TABLE IF NOT EXISTS `{$this->modal_prefix}_{$table}`("
				. implode(",", $fields)
				. ");";
			dbDelta($sql);
		}
	}
	
	/**
	 * Creates nonces for the view file.
	 * 
	 * @see Controller::set_shortcodes()
	 * @return string
	 */
	public function create_nonce( $arr ){
		return wp_create_nonce($arr[0]);
	}
	
	/**
	 * Log an error.
	 * 
	 * @param string $err The error message.
	 * @return boolean false Method always returns false.
	 */
	public function error( $err ){
		$this->errors[] = $err;
		return false;
	}
	
	/**
	 * Checks $_REQUEST[_wpnonce] against the param $action.
	 * 
	 * Will die() if verification fails.
	 *  
	 * @see wp_verify_nonce()
	 * @param string $action The action word used setting up the nonce.
	 * @param boolean $json Default True. Whether to return json error or plain
	 * text.
	 */
	public function check_nonce($action, $json=true){
		if(!wp_verify_nonce($_REQUEST['_wpnonce'], $action)){
			if($json){
				print json_encode(array('error' => 'invalid nonce'));
				die();
			}
			else die("invalid nonce");
		}
		return true;
	}
	
	/**
	 * Looks for an action method to call in child class. 
	 */
	public function do_action(){
		$action = $this->action;
		if(method_exists($this, $action))
			add_action('init', array($this, $action));
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
		$this->html = file_get_contents("{$this->config->plugin_dir}/public_html/{$this->class_name}.php");
		
		//clean out phpDoc
		$this->html = preg_replace("/<\?php.+\?>/msU", "", $this->html);
		
		$this->shortcodes['errors'] = $this->get_errors();
		$this->shortcodes['messages'] = $this->get_messages();
		
		$this->set_shortcodes();
		$this->load_scripts();
		$this->load_styles();

		if(!$return) print $this->html;
		return $this->html;
	}
	
	/**
	 * Return html list of errors reported by plugin globally.
	 * 
	 * @return string Returns an html <ul> list of errors.
	 */
	public function get_errors(){
		
		$html = "<div id=\"message\" class=\"error\"><ul>\n";
		$errors = $this->config->errors;

		if(@$_REQUEST['error'])
			$errors[] = $_REQUEST['error'];

		if(!count($errors)) return false;
		foreach($errors as $err)
			$html .= stripslashes ("<li>{$err}</li>\n");

		//wp_enqueue_style('colors');
		return $html .= "</ul>\n</div>\n";
	}
	
	public function get_messages(){
		
		$html = "<div id=\"message\" class=\"message\"><ul>\n";
		$messages = $this->config->messages;

		if(@$_REQUEST['message'])
			$messages[] = $_REQUEST['message'];

		if(!count($messages)) return false;
		foreach($messages as $msg)
			$html .= stripslashes ("<li>{$msg}</li>\n");

		//wp_enqueue_style('colors');
		return $html .= "</ul>\n</div>\n";
	}
	
	/**
	 * Registers action calls with wp core.
	 * 
	 * Loops through $this->action_wp array, takes the key as the action and the
	 * value as the callback.
	 * 
	 * @return void
	 */
	private function set_wp_actions(){
		
		if(!count($this->wp_action)) return;
		foreach($this->wp_action as $action=>$call){
			add_action($action, $call);
		}
	}
	
	/**
	 * Registers action calls with wp core.
	 * 
	 * Loops through $this->action_wp array, takes the key as the action and the
	 * value as the callback.
	 * 
	 * @return void
	 */
	private function set_wp_filters(){
		
		if(!count($this->wp_filter)) return;
		foreach($this->wp_filter as $action=>$call){
			add_action($action, $call);
		}
	}
	
	/**
	 * Loads javascript files
	 * 
	 * @return void 
	 */
	public function load_scripts() {
		
		if(!file_exists("{$this->config->plugin_dir}/public_html/js/{$this->class_name}.min.js")) return;
		wp_register_script($this->class_name, "{$this->config->plugin_url}/public_html/js/{$this->class_name}.min.js", $this->script_deps);
		wp_enqueue_script( $this->class_name );
	}

	/**
	 * Loads css files
	 * 
	 * @return void 
	 */
	public function load_styles() {
		
		//include dependencies
		foreach($this->style_deps as $dep)
			wp_enqueue_style ( $dep );
		
		//look for class specific style
		if(!file_exists("{$this->config->plugin_dir}/public_html/css/{$this->class_name}.css")) return;
		wp_register_style($this->class_name, "{$this->config->plugin_url}/public_html/css/{$this->class_name}.css", $this->style_deps);
		wp_enqueue_style( $this->class_name );
	}

	/**
	 * Sets values for the shortcodes in the view file.
	 * 
	 * Replaces the codes with values in $this->html. To add shortcodes to the 
	 * view file use the syntax:
	 * <code> <!--[--identifying string--]--> </code>. 
	 * In $this->get_page() add the value to the array $this->shortcodes[].
	 * eg: $this->shortcodes['identifying string'] = "value".
	 * 
	 * @return void
	 */
	public function set_shortcodes() {
		
		//replace values
		if(count(@$this->shortcodes))
			foreach ($this->shortcodes as $code => $val){
			
				//if val is method call
				if(is_array($val)){
					$method = array_shift($val);
					if(method_exists($this, $method))
						$val = $this->$method( $val );
					else $val = "unkown shortcode";
				}
				$this->html = str_replace("<!--[--{$code}--]-->", $val, $this->html);
			}
	}

}