<?php
/*
Plugin Name: Advanced Custom Post Types
Description: Customise WordPress with custom post types
Version: 0.0.1.0
Author: Brian Reed
Author URI: http://iambrian.com/
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class acpt {

	public $post_type = 'acpt_content_type';

	public $post_types_info = array();

	var $acf_activated = false;

	/**
	 * acpt constructor.
	 */
	function __construct() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		$this->acf_activated = (
			in_array( 'advanced-custom-fields-pro/acf.php', $active_plugins )
			||
			in_array( 'advanced-custom-fields/acf.php', $active_plugins )
		);

		$this->acf_dashicons_activated = in_array( 'advanced-custom-fields-dashicons/acf-dashicons.php', $active_plugins );

		if ( ! $this->acf_activated || ! $this->acf_dashicons_activated ) {
			if ( is_admin() ) {
				add_action( 'admin_notices', array( $this, 'admin_notice_acf_not_activated' ) );
			}

			return;
		}

		$this->set_post_types_info();

		// all back end related functionality is only loaded if needed
		if ( is_admin() ) {
			require_once dirname( __FILE__ ) . '/admin.php';
		}

		add_action( 'init', array( $this, 'init' ) );
	}

	function init() {
		$this->register_post_types();
	}

	function register_post_types() {
		$acpt_reset_last = intval( get_option( 'acpt_reset_last', 0 ) );

		$last_saved = 0;

		foreach ( $this->post_types_info as $post_type_data ) {
			register_post_type( $post_type_data['post_type'], $post_type_data['args'] );

			if ( is_array( $post_type_data['taxonomies'] ) ) {
				foreach ( $post_type_data['taxonomies'] as $taxonomy ) {
					register_taxonomy_for_object_type( $taxonomy, $post_type_data['post_type'] );
				}
			}

			$last_saved = max( $last_saved, intval( $post_type_data['saved'] ) );
		}

		if ( $last_saved > $acpt_reset_last ) {
			flush_rewrite_rules();

			update_option( 'acpt_reset_last', $last_saved );
		}
	}

	function set_post_types_info() {
		global $wpdb;

		$post_type_rows = $wpdb->get_results( "SELECT" . " * FROM $wpdb->options WHERE option_name LIKE 'acpt_post_type_%'" );

		$post_types_info = array();

		foreach ( $post_type_rows as $post_type_row ) {
			$post_types_info[] = json_decode( $post_type_row->option_value, true );
		}

		$this->post_types_info = $post_types_info;
	}

	function admin_notice_acf_not_activated() {
		$class = 'notice notice-error';

		$plugins_not_loaded = array();

		if ( ! $this->acf_activated ) {
			$plugins_not_loaded[] = '<a href="https://www.advancedcustomfields.com/" target="_blank">Advanced Custom Fields</a>';
		}

		if ( ! $this->acf_dashicons_activated ) {
			$plugins_not_loaded[] = '<a href="https://wordpress.org/plugins/advanced-custom-fields-dashicons/" target="_blank">Advanced
			Custom Fields - Dashicons</a>';
		}

		$message = __( implode( ' and ', $plugins_not_loaded ) . ' must be activated to run <strong>Advanced Custom Post Types</strong>.' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
	}

}

if ( class_exists( 'acpt' ) ) {
	$Advanced_Custom_Post_Types = new acpt();
}