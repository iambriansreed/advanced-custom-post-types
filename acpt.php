<?php
/*
Plugin Name: Advanced Custom Post Types
Description: Customise WordPress with custom post types
Version: 0.4.5
Author: iambriansreed
Author URI: http://iambrian.com/
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if acpt already loaded
if ( ! defined( 'ACPT_POST_TYPE' ) ) {

	define( 'ACPT_POST_TYPE', 'acpt_content_type' );

	function acpt_spl_autoload_register( $namespaces_class_name ) {

		$class_name_parts = explode( '\\', $namespaces_class_name );

		if ( $class_name_parts[0] !== 'Advanced_Custom_Post_Types' ) {
			return false;
		}

		array_shift( $class_name_parts ); // remove namespace Advanced_Custom_Post_Types

		$class_name = array_pop( $class_name_parts );

		$class_name_parts[] = "class-$class_name.php";

		$filename = dirname( __FILE__ ) . '/' . str_replace( '_', '-', strtolower( implode( '/', $class_name_parts ) ) );

		if ( file_exists( $filename ) ) {

			include( $filename );

			return class_exists( $class_name );
		}

		return false;
	}

	spl_autoload_register( 'acpt_spl_autoload_register' );

	new \Advanced_Custom_Post_Types\Load_Main();

}