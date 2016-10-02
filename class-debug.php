<?php

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

	static function json( $data, $backtrace = false ) {

		if ( count( ob_get_status() ) ) {
			ob_clean();
		}

		$output_data = $backtrace ? array(
			'data'  => $data,
			'trace' => debug_backtrace()
		) : $data;

		echo json_encode( $output_data, JSON_PRETTY_PRINT );

		exit;
	}

	static function print_r( $data, $backtrace = false ) {

		if ( count( ob_get_status() ) ) {
			ob_clean();
		}

		$output_data = $backtrace ? array(
			'data'  => $data,
			'trace' => debug_backtrace()
		) : $data;

		echo '<pre>' . print_r( $output_data, 1 ) . '</pre>';

		exit;
	}

}