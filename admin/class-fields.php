<?php

namespace Advanced_Custom_Post_Types\Admin;

use Advanced_Custom_Post_Types\Settings;

class Fields {

	private $slug_prepend = null;
	private $groups = array();
	private $names = array();
	private $defaults = array();
	private $dashicons;

	public function __construct( Settings $settings, Field_Groups $field_groups, Dashicons $dashicons ) {

		global $wp_rewrite;

		$this->dashicons = $dashicons;

		$this->slug_prepend = trim( $wp_rewrite->front, "/" );

		$settings->set( 'groups', $field_groups->get() );

		$this->groups = $settings->get( 'groups' );

		$field_filters = $this->filter_fields(
			'acpt_taxonomies',
			'acpt_menu_icon',
			'acpt_rewrite_slug',
			'acpt_show_under_parent',
			'acpt_rewrite_with_front'
		);

		foreach ( $this->groups as $name => $field_group ) {

			foreach ( $field_group['fields'] as $index => $field ) {

				if ( in_array( $field['name'], $field_filters ) ) {

					$this->groups[ $name ]['fields'][ $index ] = $this->{'filter_' . $field['name']}( $field );
				}

				if ( isset( $field['name'] ) && $field['name'] ) {
					$this->names[] = $field['name'];
				}

				if ( isset( $field['default_value'] ) ) {
					$this->defaults[ $field['name'] ] = $field['default_value'];
				}
			}
		}
	}

	public function names() {
		return $this->names;
	}

	public function groups() {
		return $this->groups;
	}

	public function defaults() {
		return $this->defaults;

	}

	/**
	 *
	 */
	private function filter_fields() {

		$filters = array();

		foreach ( func_get_args() as $field_name ) {

			$filters[] = $field_name;
		}

		return $filters;
	}

	/**
	 * @param $field
	 *
	 * @return mixed
	 */
	public function filter_acpt_menu_icon( $field ) {

		$field['choices'] = array(
			'' => 'Select Icon'
		);

		foreach ( $this->dashicons->get_all() as $class => $unicode ) {
			$field['choices'][ 'dashicons-' . $class ] = $class;
		}

		return $field;
	}

	/**
	 * @param $field
	 *
	 * @return mixed
	 */
	public function filter_acpt_rewrite_slug( $field ) {

		global $post;

		if ( $post ) {
			$field['default_value'] = esc_url( get_post_meta( $post->ID, 'singular_name', 1 ) );
		}

		return $field;
	}

	/**
	 * @param $field
	 *
	 * @return mixed
	 */
	public function filter_acpt_taxonomies( $field ) {

		$field['choices'] = array();

		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

		foreach ( $taxonomies as $value => $taxonomy ) {
			$field['choices'][ $value ] = $taxonomy->labels->name;
		}

		$field['multiple'] = true;

		unset( $field['choices']['post_format'] );

		// return the field
		return $field;
	}

	/**
	 * @param $field
	 *
	 * @return mixed
	 */
	public function filter_acpt_show_under_parent( $field ) {

		$field['choices'] = array();

		if ( isset( $GLOBALS['menu'] ) && is_array( $GLOBALS['menu'] ) ) {
			foreach ( $GLOBALS['menu'] as $menu_item ) {
				if ( $menu_item[0] && $menu_item[2] != 'edit.php?post_type=acpt_content_type' ) {
					// strip html counts
					$field['choices'][ $menu_item[2] ] = preg_replace( "/ <.*$/", "", $menu_item[0] );
				}
			}
		}

		// return the field
		return $field;
	}

	/**
	 * @param $field
	 *
	 * @return null
	 */
	public function filter_acpt_rewrite_with_front( $field ) {

		if ( ! $this->slug_prepend ) {
			$field['wrapper']['class'] = 'hidden';
		}

		return $field;
	}
}
