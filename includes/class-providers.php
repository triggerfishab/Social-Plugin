<?php

namespace Triggerfish\Social;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Providers {

	public static function sync_provider( $provider_name ) {

		$providers = Plugin::get_provider_class_map( $provider_name );

		if ( ! isset( $providers[ $provider_name ] ) ) {
			return new WP_Error( 'social-plugin', 'Unknown provider', $provider_name );
		}

		$accounts = Accounts::get_provider_accounts( $provider_name );

		if ( empty( $accounts ) ) {
			return new WP_Error( 'social-plugin', 'No accounts found.' );
		}

		$result = [];

		foreach ( $accounts as $account ) {
			$result[] = $account->sync();
		}

		return array_filter( $result );
	}

}
