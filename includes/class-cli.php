<?php

namespace Triggerfish\Social;

use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class CLI {

	/**
	 * Sync all accounts for a provider.
	 *
	 * <provider_name>
	 * : The provider name to sync.
	 *
	 * @alias sync-provider
	 *
	 */
	public function sync_provider( $args ) {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'Argument <provider_name> missing' );
		}

		$provider_name = mb_strtolower( $args[0] );

		$status = Providers::sync_provider( $provider_name );

		if ( is_wp_error( $status ) ) {
			WP_CLI::error( $status->get_error_message() );
		}

		if ( is_array( $status ) && ! empty( $status ) ) {
			foreach ( $status as $account_status ) {
				if ( is_wp_error( $account_status ) ) {
					WP_CLI::error( $account_status->get_error_message(), false );
				}
			}

			WP_CLI::line( 'Complete' );
			WP_CLI::halt( 1 );
		}

		WP_CLI::success( 'Complete' );
	}

	/**
	 * Print all accounts for a provider.
	 *
	 * <provider_name>
	 * : The provider name to get.
	 *
	 * @alias get-provider-accounts
	 *
	 */
	public function get_provider_accounts( $args ) {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'Argument <provider_name> missing' );
		}

		$provider_name = mb_strtolower( $args[0] );

		$accounts = Accounts::get_provider_accounts( $provider_name );

		if ( empty( $accounts ) ) {
			WP_CLI::success( 'No accounts found with <provider_name> ' . $args[0] );
		}

		$account_ids = array_map( function( $account ) {
			return [ 'id' => $account->get_id() ];
		}, $accounts );

		WP_CLI\Utils\format_items(
			'table',
			$account_ids,
			[ 'id' ]
		);
	}

	/**
	 * Print all accounts.
	 *
	 * @alias get-all-accounts
	 *
	 */
	public function get_all_accounts() {
		$accounts = Accounts::get_all_accounts();

		if ( empty( $accounts ) ) {
			WP_CLI::success( 'No accounts found' );
		}

		$accounts = array_map( function( $account ) {
			return [
				'id' => $account->get_id(),
				'provider name' => $account->get_provider_name(),
				'post count' => count( $account->get_posts() ),
			];
		}, $accounts );

		// Sort by provider.
		$accounts = wp_list_sort( $accounts, 'provider_name', 'ASC' );

		WP_CLI\Utils\format_items(
			'table',
			$accounts,
			[ 'id', 'provider name', 'post count' ]
		);
	}

	/**
	 * Sync all accounts.
	 *
	 * @alias sync-all-accounts
	 *
	 */
	public function sync_all_accounts() {
		$status = Accounts::sync_all_accounts();

		if ( is_wp_error( $status ) ) {
			WP_CLI::error( $status->get_error_message() );
		}

		if ( is_array( $status ) && ! empty( $status ) ) {
			foreach ( $status as $account_status ) {
				if ( is_wp_error( $account_status ) ) {
					WP_CLI::error( $account_status->get_error_message(), false );
				}
			}

			WP_CLI::line( 'Complete' );
			WP_CLI::halt( 1 );
		}

		WP_CLI::success( 'Complete' );
	}

	/**
	 * Delete account.
	 *
	 * @alias delete-account
	 *
	 * <account_id>
	 * : The Account ID to delete.
	 *
	 * <provider_name>
	 * : The Provider Name to delete.
	 *
	 */
	public function delete_account( $args ) {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'Argument <account_id> missing' );
		}

		if ( empty( $args[1] ) ) {
			WP_CLI::error( 'Argument <provider_name> missing' );
		}

		$account_id = $args[0];
		$provider_name = $args[1];

		$status = Accounts::delete_account( $account_id, $provider_name );

		if ( is_wp_error( $status ) ) {
			WP_CLI::error( $status->get_error_message() );
		}

		WP_CLI::success( 'Account deleted' );
	}

}

WP_CLI::add_command( 'tf social', '\Triggerfish\Social\CLI' );
