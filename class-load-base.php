<?php

namespace Advanced_Custom_Post_Types;

abstract class Load_Base {

	function __construct() {
	}

	function add_actions( $actions ) {
		foreach ( $actions as $action ) {
			add_action( $action, array( $this, $action ) );
		}
	}
}