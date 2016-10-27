<?php

namespace Advanced_Custom_Post_Types;

class Post_Types {

	private $json_path = '';

	function __construct( Settings $settings ) {

		$this->json_path = $settings->get( 'save_json' );
	}

	private function get_saved_data() {
		return get_posts( array( 'post_type' => ACPT_POST_TYPE ) );
	}

	/**
	 * @param array|null $post_data posts with post_type = ACPT_POST_TYPE
	 *
	 * @return array of post_types and register_post_type arguments
	 */
	public function get_all( array $post_data = null ) {

		if ( null === $post_data ) {
			$post_data = $this->get_saved_data();
		}

		$posts = $post_data;

		$post_types = array();

		foreach ( $posts as $post ) {

			$post_data = json_decode( $post->post_content, true );

			$post_types[ $post_data['post_type'] ] = $post_data;
		}

		if ( $this->json_path ) {

			$files = scandir( $this->json_path );

			foreach ( $files as $file ) {

				if ( substr( $file, - 5 ) === '.json' ) {

					$post_type_json = file_get_contents( "$this->json_path/$file" );

					$post_data = json_decode( $post_type_json, true );

					$post_types[ $post_data['post_type'] ] = $post_data;
				}
			}
		}

		return $post_types;

	}

	public function valid_post_type( $post_type ) {
		return is_string( $post_type ) && strlen( $post_type ) > 0 && strlen( $post_type ) < 21;
	}

	public function register() {

		$acpt_reset_last = intval( get_option( 'acpt_reset_last', 0 ) );

		$last_saved = 0;

		$post_types = $this->get_all();

		foreach ( $post_types as $post_type ) {

			if ( ! isset( $post_type['post_type'] ) || ! $this->valid_post_type( $post_type['post_type'] ) || ! isset( $post_type['args'] ) ) {
				continue;
			}

			register_post_type( $post_type['post_type'], $post_type['args'] );

			if ( isset( $post_type['saved'] ) ) {
				$last_saved = max( $last_saved, intval( $post_type['saved'] ) );
			}
		}

		if ( $last_saved > $acpt_reset_last ) {

			flush_rewrite_rules();

			update_option( 'acpt_reset_last', $last_saved );
		}
	}
}