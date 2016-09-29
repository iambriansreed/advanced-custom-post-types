<?php
/**
 * User: iambriansreed
 * Date: 9/28/16
 * Time: 11:26 PM
 */

namespace Advanced_Custom_Post_Types;

class Debug {

	static function log_acpt() {

		global $wpdb;

		$debug_backtrace = debug_backtrace();

		$caller = array_shift( $debug_backtrace );

		$wpdb->insert(
			'log_acpt',
			array(
				'message' => json_encode( func_get_args(), JSON_PRETTY_PRINT ),
				'file'    => $caller['file'] . ' :: ' . $caller['line']
			),
			array(
				'%s',
				'%s'
			)
		);
	}

	static function pre( $var, $exit = 0 ) {

		echo '<pre>';
		print_r( $var );
		echo '</pre>';
		if ( $exit ) {
			exit;
		}
	}

	static function pre2( $var ) {

		echo '<!--';
		print_r( $var );
		echo '-->';

	}
}