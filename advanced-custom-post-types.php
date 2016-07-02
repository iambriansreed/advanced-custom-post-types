<?php
/*
Plugin Name: Advanced Custom Post Types
Description: Customise WordPress with custom post types
Version: 0.0.1.0
Author: iambriansreed
Author URI: http://iambrian.com/
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
{
	exit;
}

require_once dirname( __FILE__ ) . '/class.acpt.php';
new acpt();