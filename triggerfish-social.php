<?php

/**
 * Plugin name: Triggerfish Social
 * Version: 1.5.5
 * Text Domain: triggerfish-social
 * Author: Triggefish
 * Author URI: https://www.triggerfish.se/
 */

namespace Triggerfish\Social;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'PLUGIN_VERSION', '1.5.5' );

define( 'PLUGIN_DIR', __DIR__ );
define( 'PLUGIN_FILE', __FILE__ );

include_once __DIR__ . '/includes/class-plugin.php';
