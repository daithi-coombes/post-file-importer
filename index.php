<?php
namespace CityIndex\WP\PostImporter;
/**
 * @package cityindex
 * @subpackage ci-wp-post-external
 */
/*
Plugin Name: Post Editor
Plugin URI: http://david-coombes.com
Description: Adds a modal to the wordpress post/page editor that will allow the user to add content from many sources.
Version: 0.1
Author: David Coombes
Author URI: http://david-coombes.com
*/

/**
 * Bootstrap 
 */
//define constants
define( 'PLUGIN_DIR', WP_PLUGIN_DIR . "/" . basename(dirname( __FILE__ )));
define( 'PLUGIN_URL', WP_PLUGIN_URL . "/" . basename(dirname( __FILE__ )));

//autoload
spl_autoload_register(function($class){
		$class = ucfirst($class);
		$class = @array_pop(explode("\\", $class));
		@include_once( PLUGIN_DIR . "/application/{$class}.class.php");
		@include_once( PLUGIN_DIR . "/application/modules/{$class}.class.php");
},true);

//include lib
require_once( PLUGIN_DIR . "/application/includes/debug.func.php");

/**
 * Configuration 
 */
$config = new Config();
//$foo = new Controller();
$config->action_key = "ci-post-importer-action";
$config->debug = 1;
$config->init_modules = array(
	'Modal'
);
$config->third_party = array(
	'script' => array(),
	'css' => array()
);
$config->modal_tables = array(
	/*
	'Table_1' => array(
		'`id` INT(11) NOT NULL AUTO_INCREMENT',
		'`name` VARCHAR(20) NOT NULL',
		'`description` text NOT NULL',
		'PRIMARY KEY(`id`)'
	)*/
);
$config->build();
