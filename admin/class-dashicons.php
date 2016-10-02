<?php

namespace Advanced_Custom_Post_Types\Admin;

class Dashicons {

	private $dashicons = null;

	public function __construct() {

		$this->dashicons = array();

		$dashicons = (array) json_decode( file_get_contents( plugin_dir_url( __FILE__ ) . 'dashicons.json' ) );

		foreach ( $dashicons as $dashicon ) {
			$this->dashicons[ $dashicon->class ] = $dashicon->content;
		}
	}

	/**
	 * @param $class_name
	 *
	 * @return object
	 */
	public function get( $class_name ) {

		$class_name = $this->exists( $class_name ) ? $class_name : 'dashicons-admin-post';

		return (object) array(
			'class_name'     => $class_name,
			'unicode_number' => $this->dashicons[ substr( $class_name, 10 ) ]
		);
	}

	public function exists( $class_name ) {
		return strlen( $class_name ) > 11 && isset( $this->dashicons[ substr( $class_name, 10 ) ] );
	}

	/**
	 * get dashicons from a json file
	 *
	 * @return array|bool
	 */
	public function get_all() {

		return $this->dashicons;
	}
}