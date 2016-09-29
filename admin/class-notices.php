<?php
/**
 * Created by PhpStorm.
 * User: iambriansreed
 * Date: 9/28/16
 * Time: 10:43 PM
 */

namespace Advanced_Custom_Post_Types\Admin;

class Notices {

	/**
	 * add a notice to users notices
	 *
	 * @param $message
	 * @param string $type
	 * @param bool $is_dismissible
	 */
	public static function add( $message, $type = 'info', $is_dismissible = true ) {

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
	 * save notices
	 *
	 * @param $notices
	 */
	public static function set( $notices ) {

		if ( $notices === false ) {
			delete_option( 'acpt_admin_notices_' . get_current_user_id() );
		} else {
			update_option( 'acpt_admin_notices_' . get_current_user_id(), json_encode( $notices ) );
		}

		return;
	}

}