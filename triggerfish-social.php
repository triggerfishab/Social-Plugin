<?php

/**
 * Plugin name: Triggerfish Social
 * Version: 0.1
 * Author: Triggefish
 * Author URI: https://www.triggerfish.se/
 */

namespace Triggerfish\Social;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'PLUGIN_DIR', __DIR__ );

add_action( 'plugins_loaded', function() {
	if ( ! class_exists( 'acf' ) ) {
		return;
	}

	include_once __DIR__ . '/includes/class-plugin.php';
});
