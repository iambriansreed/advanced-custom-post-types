<?php

namespace Advanced_Custom_Post_Types;

class Settings {

	private $defaults;

	function __construct() {

		$this->defaults = array(
			'show_admin'   => true,
			'admin_fields' => array(),
			'capability'   => 'manage_options'
		);

		$this->dir_url = plugin_dir_url( __FILE__ );

		$this->dir_path = plugin_dir_path( __FILE__ );
	}

	public function get( $name ) {
		return apply_filters( "acpt/settings/{$name}", $this->defaults[ $name ] );
	}

	public function set( $name, $value ) {
		return ( $this->defaults[ $name ] = $value );
	}
}