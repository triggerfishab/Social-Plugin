<?php

/**
 * Plugin name: Triggerfish Social
 * Version: 1.4.3
 * Text Domain: triggerfish-social
 * Author: Triggefish
 * Author URI: https://www.triggerfish.se/
 */

namespace Triggerfish\Social;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'PLUGIN_VERSION', '1.4.3' );

define( 'PLUGIN_DIR', __DIR__ );
define( 'PLUGIN_FILE', __FILE__ );

include_once __DIR__ . '/includes/class-plugin.php';

if ( ! function_exists( 'tf_wp_error' ) ) {
	function tf_wp_error( $message, $data = '', $code = '' ) {
		if ( empty( $code ) ) {
			$code = sanitize_key( $message );
		}

		return new WP_Error( $code, $message, $data );
	}
}
