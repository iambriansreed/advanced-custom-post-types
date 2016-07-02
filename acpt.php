<?php
/*
Plugin Name: Advanced Custom Post Types
Description: Customise WordPress with custom post types
Version: 0.0.1.0
Author: Brian Reed
Author URI: http://iambrian.com/
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'acpt' ) ) {

	require_once dirname( __FILE__ ) . '/acpt.class.php';

	$Advanced_Custom_Post_Types = new acpt();

}