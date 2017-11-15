<?php

/**
 * Plugin name: Triggerfish Social
 * Author: Triggefish
 * Author URI: https://www.triggerfish.se/
 */

namespace Triggerfish\Social;

define( 'PLUGIN_DIR', __DIR__ );

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

add_action( 'plugins_loaded', function() {
	if ( ! class_exists( 'acf' ) ) {
		return;
	}

	include_once __DIR__ . '/includes/class-plugin.php';
});
