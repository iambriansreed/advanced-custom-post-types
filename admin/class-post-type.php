<?php

namespace Advanced_Custom_Post_Types\Admin;

use Advanced_Custom_Post_Types\Load_Main;
use Advanced_Custom_Post_Types\Settings;

class Post_Type {

	private $loader;
	private $settings;
	private $fields;
	private $dashicons;

	private $post_data;
	private $errors = array();
	private $field_values;

	public function __construct( Load_Main $loader, Settings $settings, Fields $fields, Dashicons $dashicons ) {

		$this->loader    = $loader;
		$this->settings  = $settings;
		$this->fields    = $fields;
		$this->dashicons = $dashicons;
	}

	public function save( $post ) {

		// get fields and pre process the data
		if ( ! isset( $_POST ) || ! is_array( $_POST ) ) {
			throw new \Exception( 'No POST data to create custom post type with.' );
		}

		$this->set_field_values( $post->ID );

		$this->post_data = (object) array(
			'post_type' => $this->field_values['post_type_name'],
			'args'      => $this->get_args()
		);

		$this->save_wp_post( $post->ID );

		$this->save_wp_post_meta( $post->ID );

		$this->save_json();

		if ( count( $this->errors ) ) {
			Notices::add_error( implode( '<br>', $this->errors ), false );
		}

		flush_rewrite_rules();
	}

	private function save_wp_post( $post_id ) {

		wp_update_post( array(
			'ID'           => $post_id,
			'post_title'   => $this->field_values['plural_name'],
			'post_type'    => ACPT_POST_TYPE,
			'post_name'    => 'acpt_post_type_' . $this->field_values['post_type_name'],
			'post_status'  => count( $this->errors ) ? 'draft' : 'publish',
			'post_content' => $this->json_encode( $this->post_data )
		) );
	}

	private function save_wp_post_meta( $post_id ) {

		$unique_fields = $this->get_unique_fields();

		$field_names = array_keys( $unique_fields );

		foreach ( $field_names as $field_name ) {

			update_post_meta( $post_id, 'acpt_' . $field_name, $this->field_values[ $field_name ] );
		}
	}

	private function save_json() {

		// vars
		$path = $this->settings->get( 'save_json' );

		// bail early if $path isn't set
		if ( ! $path ) {
			return false;
		}

		$file_name = $this->post_data->post_type . '.json';

		// remove trailing slash
		$path = untrailingslashit( $path );

		// bail early if dir does not exist
		if ( ! is_writable( $path ) ) {
			$this->errors[] = "The ACPT JSON save path '$path' is not writable.";

			return false;
		}

		// write file
		$f = fopen( "{$path}/{$file_name}", 'w' );
		fwrite( $f, $this->json_encode( $this->post_data ) );

		return fclose( $f );
	}

	/**
	 * Cleans up the acpt field values
	 *
	 * @param $post_id
	 *
	 */
	private function set_field_values( $post_id ) {

		$this->field_values = array();

		$filters = $this->get_field_filters();

		foreach ( $this->fields->names() as $acpt_field_name ) {

			$value = isset( $_POST[ $acpt_field_name ] ) ? $_POST[ $acpt_field_name ] : '';

			$name = substr( $acpt_field_name, 5 );

			if ( array_key_exists( $name, $filters ) ) {
				$value = call_user_func( $filters[ $name ], $value );
			}

			$this->field_values[ $name ] = is_string( $value ) ? trim( $value ) : $value;
		}

		if ( $this->field_values['show_in_rest'] ) {

			$this->field_values['rest_base'] =
				$this->field_values['rest_base'] ? $this->field_values['rest_base'] : sanitize_title( $this->field_values['plural_name'] );

			$this->field_values['rest_controller_class'] =
				$this->field_values['rest_controller_class'] ? $this->field_values['rest_controller_class'] : null;

		} else {
			$this->field_values['rest_base']             = null;
			$this->field_values['rest_controller_class'] = null;
		}

		$this->field_values['post_type_name'] = $this->field_values['post_type_name'] ?
			$this->field_values['post_type_name'] : $this->sanitize_post_type( $this->field_values['singular_name'] );

		$invalid_post_type_name_reason = $this->loader->is_invalid_post_type_name( $this->field_values['post_type_name'] );

		if ( $invalid_post_type_name_reason ) {
			$this->errors[] = $invalid_post_type_name_reason;
		}

		// default rewrite_slug
		if ( $this->field_values['rewrite'] ) {
			$this->field_values['rewrite_slug'] = $this->field_values['rewrite_slug'] ? $this->field_values['rewrite_slug'] :
				sanitize_title( $this->field_values['singular_name'] );
		}

		foreach ( $this->get_unique_fields() as $key => $title ) {

			$value = $this->field_values[ $key ];

			if ( ! $this->is_unique( $post_id, $key, $value ) ) {
				$errors[] = "Another post type has the same value '$value'. " .
				            "Please change the $title and save again.";
			}
		}
	}


	/**
	 * creates the register_post_type arguments from the acpt fields values
	 *
	 * @return array
	 */
	private function get_args() {

		$args = array();

		foreach ( $this->field_values as $name => $value ) {
			$args[ $name ] = $value;
		}

		$args['label'] = $args['plural_name'];

		// build out label data
		if ( $args['auto_generate_labels'] ) {

			$args['title_placeholder'] = "Enter " . strtolower( $args['singular_name'] ) . " name";

			$args['labels'] = $this->generate_labels(
				$args['plural_name'],
				$args['singular_name']
			);

		} else {

			foreach ( $args as $field_name => $field_value ) {
				if ( 'label_' === substr( $field_name, 0, 6 ) ) {
					$args['labels'][ substr( $field_name, 6 ) ] = $field_value;
				}
			}
		}

		// set rewrite information
		if ( $args['rewrite'] ) {

			$args['rewrite'] = array(
				'slug'       => $this->field_values['rewrite_slug'],
				'with_front' => $args['rewrite_with_front'],
				'feeds'      => $args['rewrite_feeds'],
				'pages'      => $args['rewrite_pages']
			);
		}

		// set show_in_menu to bool or to parent if set
		if ( $args['show_under_a_parent'] && $args['show_under_a_parent'] ) {
			$args['show_in_menu'] = $args['show_under_parent'];
		}

		$args['taxonomies'] = (array) $args['taxonomies'];
		$args['supports']   = (array) $args['supports'];

		// set menu position from select or custom input
		$args['menu_position'] = intval( $args['menu_position'] );

		if ( $args['menu_position'] === - 1 ) {
			$args['menu_position'] = intval( $args['menu_position_custom'] );
		}

		// validate and set dashicon
		$dashicon = $this->dashicons->get( $args['menu_icon'] );

		$args['menu_icon'] = $dashicon->class_name;

		$args['dashicon_unicode_number'] = $dashicon->unicode_number;

		return $args;
	}

	private function get_field_filters() {
		return array(
			'plural_name'         => 'ucwords',
			'singular_name'       => 'ucwords',
			'public'              => 'boolval',
			'has_archive'         => 'boolval',
			'exclude_from_search' => 'boolval',
			'publicly_queryable'  => 'boolval',
			'show_ui'             => 'boolval',
			'show_in_menu'        => 'boolval',
			'show_in_nav_menus'   => 'boolval',
			'show_in_admin_bar'   => 'boolval',
			'hierarchical'        => 'boolval',
			'can_export'          => 'boolval',
			'show_in_rest'        => 'boolval',
			'rewrite'             => 'boolval',
			'rewrite_with_front'  => 'boolval',
			'rewrite_feeds'       => 'boolval',
			'rewrite_pages'       => 'boolval'
		);
	}

	private function get_unique_fields() {
		return array(
			'singular_name' => 'singular name',
			'plural_name'   => 'plural name'
		);
	}

	private function json_encode( $data ) {

		if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {

			// PHP at least 5.4
			return json_encode( $data, JSON_PRETTY_PRINT );
		} else {

			// PHP less than 5.4
			return json_encode( $data );
		}
	}

	/**
	 * @param $singular_name
	 *
	 * @return mixed
	 */
	public static function sanitize_post_type( $singular_name ) {
		return str_replace( '-', '_', sanitize_title( $singular_name ) );
	}

	/**
	 * is meta value meta key combination unique
	 *
	 * @param $post_id
	 * @param $field_name
	 * @param $value
	 *
	 * @return bool
	 * @internal param $key
	 */
	public static function is_unique( $post_id, $field_name, $value ) {

		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT" . " COUNT(*) 
					FROM $wpdb->posts as posts
					LEFT JOIN $wpdb->postmeta as postmeta ON postmeta.post_id = posts.ID
					AND postmeta.meta_key = %s
					WHERE 1 = 1
					AND posts.ID != %d
					AND posts.post_type = 'acpt_content_type'
					AND posts.post_status = 'publish'
					AND postmeta.meta_value = %s; ", "acpt_$field_name", $post_id, $value );

		return 0 === intval( $wpdb->get_var( $sql ) );
	}

	/**
	 * generate all labels based on the plural and singular names
	 *
	 * @param $plural_name
	 * @param $singular_name
	 *
	 * @return array
	 * @internal param $labels
	 *
	 */
	public static function generate_labels( $plural_name, $singular_name ) {

		return array(
			'add_new'               => 'Add New',
			'add_new_item'          => 'Add New ' . $singular_name,
			'edit_item'             => 'Edit ' . $singular_name,
			'new_item'              => 'New ' . $singular_name,
			'view_item'             => 'View ' . $singular_name,
			'search_items'          => 'Search ' . $plural_name,
			'not_found'             => 'No ' . strtolower( $plural_name ) . ' found',
			'not_found_in_trash'    => 'No ' . strtolower( $plural_name ) . ' found in Trash',
			'parent_item_colon'     => 'Parent ' . $singular_name,
			'all_items'             => 'All ' . $plural_name,
			'archives'              => $plural_name . ' Archives',
			'insert_into_item'      => 'I' . 'nsert into ' . strtolower( $singular_name ),
			'uploaded_to_this_item' => 'Uploaded to this ' . strtolower( $singular_name ),
			'featured_image'        => 'Featured Image',
			'set_featured_image'    => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image'    => 'Use as featured image',
			'menu_name'             => $plural_name,
			'filter_items_list'     => $plural_name,
			'items_list_navigation' => $plural_name,
			'items_list'            => $plural_name,
			'name_admin_bar'        => $singular_name
		);
	}
}