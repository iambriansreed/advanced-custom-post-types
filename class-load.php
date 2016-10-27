<?php

namespace Advanced_Custom_Post_Types;

use Advanced_Custom_Post_Types\Admin\Dashicons;
use Advanced_Custom_Post_Types\Admin\Field_Groups;
use Advanced_Custom_Post_Types\Admin\Fields;
use Advanced_Custom_Post_Types\Admin\Post_Type;

class Load extends Load_Base {

	function __construct() {

		$this->add_actions( array( 'init' ) );
	}

	function init() {
		
		$settings = new Settings();

		$post_types = new Post_Types( $settings );

		$post_types->register();

		if ( is_admin() ) {

			$dashicons = new Dashicons();
			$fields    = new Fields( $settings, $dashicons );
			$post_type = new Post_Type( $settings, $fields, $dashicons );

			new Admin\Load( $settings, $post_types, $fields, $post_type );
		}
	}
}