<?php

namespace Advanced_Custom_Post_Types;

use Advanced_Custom_Post_Types\Admin\Dashicons;
use Advanced_Custom_Post_Types\Admin\Field_Groups;
use Advanced_Custom_Post_Types\Admin\Fields;
use Advanced_Custom_Post_Types\Admin\Post_Type_Manage;

class Load extends Load_Base {

	function __construct() {

		$this->add_actions( array( 'init' ) );
	}

	function init() {

		$post_types = new Post_Types();

		$post_types->register();

		if ( is_admin() ) {

			$settings         = new Settings();
			$field_groups     = new Field_Groups();
			$dashicons        = new Dashicons();
			$fields           = new Fields( $settings, $field_groups, $dashicons );
			$post_type_manage = new Post_Type_Manage( $fields, $dashicons );

			new Admin\Load( $settings, $post_types, $fields, $field_groups, $post_type_manage );
		}
	}
}