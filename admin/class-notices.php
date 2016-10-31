<?php

namespace Advanced_Custom_Post_Types\Admin;

class Notices {

	/**
	 * add an error notice to users notices
	 *
	 * @param $message
	 * @param bool $is_dismissible
	 */
	public static function add_error( $message, $is_dismissible = true ) {
		self::add( $message, 'error', $is_dismissible );
	}

	/**
	 * add a warning notice to users notices
	 *
	 * @param $message
	 * @param bool $is_dismissible
	 */
	public static function add_warning( $message, $is_dismissible = true ) {
		self::add( $message, 'warning', $is_dismissible );
	}

	/**
	 * add an success notice to users notices
	 *
	 * @param $message
	 * @param bool $is_dismissible
	 */
	public static function add_success( $message, $is_dismissible = true ) {
		self::add( $message, 'success', $is_dismissible );
	}

	/**
	 * add an info notice to users notices
	 *
	 * @param $message
	 * @param bool $is_dismissible
	 */
	public static function add_info( $message, $is_dismissible = true ) {
		self::add( $message, 'info', $is_dismissible );
	}

	/**
	 * add a notice to users notices
	 *
	 * @param $message
	 * @param string $type (error, warning, success, or info)
	 * @param bool $is_dismissible
	 */
	private static function add( $message, $type = 'info', $is_dismissible = true ) {

		$notices = self::get_all();

		$new_notice = (object) compact( 'message', 'type', 'is_dismissible' );

		foreach ( $notices as $notice ) {
			if ( serialize( $notice ) === serialize( $new_notice ) ) {
				return;
			}
		}

		$notices[] = $new_notice;

		self::set( $notices );
	}

	/**
	 * get all users notices
	 * @return array
	 */
	public static function get_all() {

		$notices = (array) json_decode( get_option( 'acpt_admin_notices_' . get_current_user_id() ) );

		return $notices;
	}

	/**
	 * clear all users notices
	 */
	public static function remove_all() {

		self::set( false );
	}

	/**
	 * save notices
	 *
	 * @param $notices
	 */
	private static function set( $notices ) {

		if ( $notices === false ) {
			delete_option( 'acpt_admin_notices_' . get_current_user_id() );
		} else {
			update_option( 'acpt_admin_notices_' . get_current_user_id(), json_encode( $notices ) );
		}

		return;
	}

}