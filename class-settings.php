<?php

namespace Advanced_Custom_Post_Types;

class Settings {

	private $defaults;

	function __construct() {

		$this->defaults = array(
			'show_admin'   => true,
			'capability'   => 'manage_options'
		);
	}

	public function get( $name ) {
		return apply_filters( "acpt/settings/{$name}", $this->defaults[ $name ] );
	}

	public function set( $name, $value ) {
		return ( $this->defaults[ $name ] = $value );
	}
}