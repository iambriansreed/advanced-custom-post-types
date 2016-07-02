<?php

class acpt {
	const ACPT_POST_TYPE = 'acpt_content_type';

	public function __construct() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		$acf_activated = (
			in_array( 'advanced-custom-fields-pro/acf.php', $active_plugins )
			||
			in_array( 'advanced-custom-fields/acf.php', $active_plugins )
		);

		if ( ! $acf_activated && is_admin() ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_acf_not_activated' ) );
			return;
		}

		// all back end related functionality is only loaded if needed
		if ( is_admin() ) {
			require_once dirname( __FILE__ ) . '/class.admin.php';

			new acpt_admin( $this->get_post_types_info() );
		}

		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		$this->register_post_types();
	}

	private function register_post_types() {
		$acpt_reset_last = intval( get_option( 'acpt_reset_last', 0 ) );

		$last_saved = 0;

		foreach ( $this->get_post_types_info() as $post_type_data ) {
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

	private $post_types_info = null;

	private function get_post_types_info() {
		if ( ! $this->post_types_info ) {
			global $wpdb;

			$post_type_rows = $wpdb->get_results( "SELECT" . " * FROM $wpdb->options WHERE option_name LIKE 
			'acpt_post_type_%'" );

			$info = array();

			foreach ( $post_type_rows as $post_type_row ) {
				$info[] = json_decode( $post_type_row->option_value, true );
			}

			$this->post_types_info = $info;
		}

		return $this->post_types_info;
	}

	public function admin_notice_acf_not_activated() {
		$class = 'notice notice-error';


		$message = '<a href="https://www.advancedcustomfields.com/" target="_blank">Advanced Custom Fields</a>' . ' must be activated to run <strong>Advanced Custom Post Types</strong>.';

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
	}
}