<?php

namespace Advanced_Custom_Post_Types;

use Advanced_Custom_Post_Types\Admin\Dashicons;
use Advanced_Custom_Post_Types\Admin\Fields;
use Advanced_Custom_Post_Types\Admin\Post_Type;

class Load_Main extends Load_Base {

	private $settings;

	function __construct() {

		$this->settings = new Settings();

		$this->add_actions( array( 'init' ) );
	}

	function init() {

		$this->register_post_types();

		if ( is_admin() ) {

			$dashicons = new Dashicons();
			$fields    = new Fields( $this->settings, $dashicons );
			$post_type = new Post_Type( $this, $this->settings, $fields, $dashicons );

			new Admin\Load( $this, $this->settings, $fields, $post_type );
		}
	}

	/**
	 * Returns all valid custom post types
	 *
	 * @return array|null
	 */
	public function get_post_types() {

		if ( ! $this->post_types ) {

			$this->post_types = array();

			$posts_data = array_merge(
				$this->get_posts_data_from_db(),
				$this->get_posts_data_from_json()
			);

			foreach ( $posts_data as $post_data ) {

				if ( ! $this->is_valid_post_data( $post_data ) ) {
					continue;
				}

				$this->post_types[ $post_data['post_type'] ] = $post_data;
			}
		}

		return $this->post_types;
	}

	private function get_posts_data_from_db() {

		$posts_data = array();

		$posts = get_posts( array( 'post_type' => ACPT_POST_TYPE ) );

		foreach ( $posts as $post ) {
			$posts_data[] = json_decode( $post->post_content, true );
		}

		return $posts_data;
	}

	private function get_posts_data_from_json() {

		$json_path = $this->settings->get( 'save_json' );

		$posts_data = array();

		if ( $json_path ) {

			$files = scandir( $json_path );

			foreach ( $files as $file ) {

				if ( substr( $file, - 5 ) === '.json' ) {

					$post_type_json = file_get_contents( "$json_path/$file" );

					$posts_data[] = json_decode( $post_type_json, true );
				}
			}
		}

		return $posts_data;
	}

	private $post_types = null;

	private function register_post_types() {

		$posts_data = $this->get_post_types();

		foreach ( $posts_data as $post_data ) {

			register_post_type( $post_data['post_type'], $post_data['args'] );
		}
	}

	private function is_valid_post_data( $post_data ) {

		return isset( $post_data['post_type'] )
		       && isset( $post_data['args'] )
		       && $this->is_valid_post_type_name( $post_data['post_type'] );
	}

	private function is_valid_post_type_name( $post_type_name ) {
		$error = $this->is_invalid_post_type_name( $post_type_name );

		return ! $error;
	}

	public function is_invalid_post_type_name( $post_type_name ) {

		if ( ! is_string( $post_type_name ) ) {
			return 'Post type name must be a string.';
		}

		if ( strlen( $post_type_name ) < 1 || strlen( $post_type_name ) > 20 ) {
			return 'Post type name must not be greater 20 characters.';
		}

		if ( in_array( $post_type_name, $this->get_wp_reservered_terms() ) ) {
			return 'Post type name must not use a <a target="_blank" href="https://codex.wordpress.org/Reserved_Terms">reserved term</a>.';
		}

		if ( $post_type_name !== str_replace( '-', '_', sanitize_title( $post_type_name ) ) ) {
			return 'Post type name must not contain capital letters or spaces.';
		}

		return false;
	}

	private function get_wp_reservered_terms() {

		$wp = new \WP();

		return array_merge(
			$wp->public_query_vars,
			$wp->private_query_vars,
			// other reserved terms found here: https://codex.wordpress.org/Reserved_Terms
			array(
				'category',
				'comments_popup',
				'custom',
				'customize_messenger_channel',
				'customized',
				'debug',
				'link_category',
				'nav_menu',
				'nonce',
				'post',
				'post_tag',
				'terms',
				'theme',
				'type',
			) );
	}
}