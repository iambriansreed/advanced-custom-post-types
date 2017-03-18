<?php

namespace Advanced_Custom_Post_Types\Admin;

use Advanced_Custom_Post_Types\Settings;

class Fields {

	private $groups = array();
	private $names = array();
	private $defaults = array();
	private $dashicons;

	public function __construct( Settings $settings, Dashicons $dashicons ) {

		$this->dashicons = $dashicons;

		$this->groups = json_decode( file_get_contents( plugin_dir_path( __FILE__ ) . 'fields.json' ), false );

		foreach ( $this->groups as $name => $field_group ) {

			foreach ( $field_group->fields as $index => $field ) {

				$this->groups->{$name}->fields[ $index ] = $this->filter_field( $field );

				if ( isset( $field->name ) && $field->name ) {
					$this->names[] = $field->name;
				}

				if ( isset( $field->default_value ) ) {
					$this->defaults[ $field->name ] = $field->default_value;
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
	 * @param $field
	 *
	 * @return mixed
	 */
	private function filter_field( $field ) {

		$filters = array(
			'acpt_rewrite_slug',
			'acpt_rewrite_with_front',
			'acpt_menu_icon',
			'acpt_taxonomies',
			'acpt_show_under_parent'
		);

		if ( in_array( $field->name, $filters ) ) {

			$field = $this->{'field_filter_' . $field->name}( $field );
		}

		return $field;
	}

	/**
	 * @param $field
	 *
	 * @return mixed
	 */
	private function field_filter_acpt_rewrite_slug( $field ) {

		global $post;
		global $wp_rewrite;

		$slug_prepend = trim( $wp_rewrite->front, "/" );

		if ( $post ) {
			$field->default_value = esc_url( get_post_meta( $post->ID, 'singular_name', 1 ) );
		}

		$field->prepend = $slug_prepend ? $slug_prepend : '/';

		return $field;
	}

	/**
	 * @param $field
	 *
	 * @return mixed
	 */
	private function field_filter_acpt_rewrite_with_front( $field ) {

		global $wp_rewrite;

		$slug_prepend = trim( $wp_rewrite->front, "/" );

		$field->hidden = ! $slug_prepend;

		return $field;
	}

	/**
	 * @param $field
	 *
	 * @return mixed
	 */
	private function field_filter_acpt_menu_icon( $field ) {

		$field->choices = array(
			'' => 'Select Icon'
		);

		foreach ( $this->dashicons->get_all() as $class => $unicode ) {
			$field->choices[ 'dashicons-' . $class ] = $class;
		}

		return $field;
	}

	/**
	 * @param $field
	 *
	 * @return mixed
	 */
	private function field_filter_acpt_taxonomies( $field ) {

		$field->choices = array();

		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

		foreach ( $taxonomies as $value => $taxonomy ) {
			$field->choices[ $value ] = $taxonomy->labels->name;
		}

		$field->multiple = true;

		unset( $field->choices['post_format'] );

		// return the field
		return $field;
	}

	/**
	 * @param $field
	 *
	 * @return mixed
	 */
	private function field_filter_acpt_show_under_parent( $field ) {

		$field->choices = array();

		if ( isset( $GLOBALS['menu'] ) && is_array( $GLOBALS['menu'] ) ) {
			foreach ( $GLOBALS['menu'] as $menu_item ) {
				if ( $menu_item[0] && $menu_item[2] != 'edit.php?post_type=acpt_content_type' ) {
					// strip html counts
					$field->choices[ $menu_item[2] ] = preg_replace( "/ <.*$/", "", $menu_item[0] );
				}
			}
		}

		// return the field
		return $field;
	}

}
