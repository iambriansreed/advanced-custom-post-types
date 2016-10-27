<?php

namespace Advanced_Custom_Post_Types\Admin;

use Advanced_Custom_Post_Types\Settings;

class Post_Type {

	private $fields;
	private $dashicons;
	private $settings;

	function __construct( Settings $settings, Fields $fields, Dashicons $dashicons ) {
		$this->fields    = $fields;
		$this->dashicons = $dashicons;
		$this->settings  = $settings;
	}

	public function save( $post ) {

		if ( ! is_object( $post ) ) {
			return;
		}

		$post_data = $this->get_post_data( $post->ID );

		if ( $post_data->errors ) {
			Notices::add( $post_data->errors, 'error', false );
		}

		wp_update_post( $post_data->post );

		$save_json = $this->save_json( $post_data );

		if ( $save_json->errors ) {

			Notices::add( $save_json->errors, 'error', false );
		}
	}

	public function save_json( $post_data ) {

		// vars
		$path      = $this->settings->get( 'save_json' );
		$file_name = $post_data->post_type . '.json';
		$output    = (object) array( 'errors' => false );

		// remove trailing slash
		$path = untrailingslashit( $path );

		// bail early if dir does not exist
		if ( ! is_writable( $path ) ) {
			$output->errors = "The ACPT JSON save path '$path' is not writable.";

			return $output;
		}

		// write file
		$f = fopen( "{$path}/{$file_name}", 'w' );
		fwrite( $f, $this->json_encode( $post_data->data ) );
		fclose( $f );

		// return
		return $output;

	}

	/**
	 * @param $post_id
	 *
	 * @return object
	 * @throws \Exception
	 */
	public function get_post_data( $post_id ) {

		// get fields and pre process the data
		if ( ! isset( $_POST ) || ! is_array( $_POST ) ) {
			throw new \Exception( 'No POST data to get acpt field data from.' );
		}

		$fields = array();

		$filters = array(
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

		foreach ( $this->fields->names() as $acpt_field_name ) {

			$value = isset( $_POST[ $acpt_field_name ] ) ? $_POST[ $acpt_field_name ] : '';

			$name = substr( $acpt_field_name, 5 );

			if ( array_key_exists( $name, $filters ) ) {
				$value = call_user_func( $filters[ $name ], $value );
			}

			$fields[ $name ] = is_string( $value ) ? trim( $value ) : $value;
		}

		if ( $fields['show_in_rest'] ) {

			$fields['rest_base']             =
				$fields['rest_base'] ? $fields['rest_base'] : sanitize_title( $fields['plural_name'] );
			$fields['rest_controller_class'] =
				$fields['rest_controller_class'] ? $fields['rest_controller_class'] : null;
		} else {

			$fields['rest_base']             = null;
			$fields['rest_controller_class'] = null;
		}

		$post_type = $this->sanitize_post_type( $fields['singular_name'] );

		// default rewrite_slug
		if ( $fields['rewrite'] ) {

			$fields['rewrite_slug'] = $fields['rewrite_slug'] ? $fields['rewrite_slug'] : sanitize_title( $fields['singular_name'] );
		}

		$acpt_fields = array_combine(
			array_map( create_function( '$name', 'return "acpt_".$name;' ), array_keys( $fields ) ),
			$fields
		);

		// build initial content object
		$content = (object) array(
			'post_type'               => $post_type,
			'fields'                  => $acpt_fields,
			'args'                    => array(),
			'dashicon_unicode_number' => 0,
			'error'                   => null,
			'saved'                   => null,
		);

		$args = array();

		foreach ( $fields as $name => $value ) {
			$args[ $name ] = $value;
		}

		$unique_fields = array(
			'singular_name' => 'singular name',
			'plural_name'   => 'plural name'
		);

		$unique_errors = array();

		foreach ( $unique_fields as $key => $title ) {

			$value = $args[ $key ];

			update_post_meta( $post_id, 'acpt_' . $key, $value );

			if ( ! $this->is_unique( $post_id, $key, $value ) ) {

				$unique_errors[] = "Another post type has the same value '$value'. " .
				                   "Please change the $title and save again.";
			}
		}

		if ( count( $unique_errors ) ) {
			$content->error = implode( '<br>', $unique_errors );
		}

		$args['label'] = $args['plural_name'];

		// build out label data
		if ( $args['auto_generate_labels'] ) {

			$args['labels'] = $this->generate_labels(
				$args['plural_name'],
				$args['singular_name']
			);

		} else {

			foreach ( $args as $field_name => $field_value ) {
				if ( 'label_' === substr( $field_name, 0, 6 ) ) {
					unset( $args[ $field_name ] );
					$args['labels'][ substr( $field_name, 6 ) ] = $field_value;
				}
			}
		}

		// set rewrite information
		if ( $args['rewrite'] ) {

			$args['rewrite'] = array(
				'slug'       => $fields['rewrite_slug'],
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

		$content->dashicon_unicode_number = $dashicon->unicode_number;

		$content->args = $args;

		$content->saved = time();

		return (object) array(
			'errors'    => $content->error,
			'post_type' => $post_type,
			'data'      => $content,
			'post'      => array(
				'ID'                => $post_id,
				'post_type'         => $post_type,
				'post_title'        => $args['plural_name'],
				'post_type'         => ACPT_POST_TYPE,
				'post_name'         => 'acpt_post_type_' . $post_type,
				'post_status'       => $content->error ? 'draft' : 'publish',
				'post_content'      => $this->json_encode( $content ),
				'post_content_data' => $content
			)
		);
	}

	function json_encode( $data ) {

		// create json string
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