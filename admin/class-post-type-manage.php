<?php
/**
 * User: iambriansreed
 * Date: 9/29/16
 * Time: 1:00 AM
 */

namespace Advanced_Custom_Post_Types\Admin;

class Post_Type_Manage {

	private $fields;
	private $dashicons;

	function __construct( Fields $fields, Dashicons $dashicons ) {
		$this->fields    = $fields;
		$this->dashicons = $dashicons;
	}

	public function save( $post ) {

		$post_data = $this->get_post_data( $post->ID );

		if ( $post_data['error'] ) {

			Notices::add( $post_data['error'], 'error', false );
			unset( $post_data['error'] );
		}

		wp_update_post( $post_data );
	}

	/**
	 * @param $post_id
	 *
	 * @return array
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

		if ( $fields['public'] ) {

			$fields['exclude_from_search'] = false;
			$fields['publicly_queryable']  = true;
			$fields['show_ui']             = true;
			$fields['show_in_nav_menus']   = true;
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

		$post_type = Load::sanitize_post_type( $fields['singular_name'] );

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

			if ( ! Load::is_unique( $post_id, $key, $value ) ) {

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

			$args['labels'] = Load::generate_labels(
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

		return array(
			'ID'           => $post_id,
			'post_title'   => $args['plural_name'],
			'post_name'    => 'acpt_post_type_' . $post_type,
			'post_status'  => $content->error ? 'draft' : 'publish',
			'post_content' => json_encode( $content ),
			'error'        => $content->error
		);
	}
}