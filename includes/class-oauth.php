<?php

namespace Triggerfish\Social;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class OAuth {

	private static $instance;

	private static $current_uid = '';

	public function __construct() {
		add_action( 'current_screen', [ $this, 'handle' ] );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
	}

	public function handle() {
		if ( 'GET' !== $_SERVER['REQUEST_METHOD'] || empty( $_GET['_tf_provider_name'] ) || ! is_user_logged_in() ) {
			return;
		}

		if ( ! session_id() ) {
			session_start();
		}

		$provider_name = sanitize_text_field( $_GET['_tf_provider_name'] );

		if ( ! self::is_valid_provider_name( $provider_name ) ) {
			return;
		}

		$action = sanitize_text_field( $_GET['_tf_oauth_action'] );

		if ( 'redirect' === $action ) {
			if ( ( isset( $_GET['uid'] ) && '' !== $_GET['uid'] ) && 'instagram' === $provider_name ) {
				$_SESSION[ $provider_name ]['uid'] = sanitize_text_field( $_GET['uid'] );
			}

			$oauth_provider = $this->get_oauth_provider( $provider_name );

			if ( is_null( $oauth_provider ) ) {
				wp_die(
					esc_html__( 'You first need to supply a Client ID and Client Secret.', 'triggerfish-social' ),
					'',
					[ 'back_link' => true ]
				);
			}

			wp_redirect( $oauth_provider->getAuthorizationUrl(
				[
					'scope' => self::get_provider_scope( $provider_name ),
					'state' => wp_create_nonce( $provider_name ),
				]
			) );
			exit;
		}

		if ( ! empty( $_GET['state'] ) && wp_verify_nonce( $_GET['state'], $provider_name ) ) {
			try {
				$oauth_provider = $this->get_oauth_provider( $provider_name );

				$token = $oauth_provider->getAccessToken(
					'authorization_code',
					[ 'code' => sanitize_text_field( $_GET['code'] ) ]
				);

				self::set_access_token( $provider_name, $token->getToken() );

				$this->oauth_result = true;

				if ( 'linkedin' === $provider_name ) {
					$provider = new Provider\LinkedIn;

					$companies = $provider->get_companies();

					if ( empty( $companies ) || is_wp_error( $companies ) ) {
						$this->oauth_result = 'Användaren ni har godkänt LinkedIn med har inget företag kopplat till sig.';
					}
				}

				wp_redirect( admin_url( '?page=triggerfish-social-settings' ) );
				exit;
			} catch ( \Exception $e ) {
				$this->oauth_result = new WP_Error( 'social-plugin', $e->getMessage(), '', $e->getCode() );
			}
		}

		if ( ! empty( $_GET['error_reason'] ) ) {
			$this->oauth_result = new WP_Error( 'social-plugin', $_GET['error_description'], $_GET['error_reason'], $_GET['error'] );
		}
	}

	public function admin_notices() {
		if ( ! isset( $this->oauth_result ) ) {
			return;
		}

		if ( is_wp_error( $this->oauth_result ) ) {
			?>
			<div class="notice notice-error">
				<p><strong><?php _e( 'The authorization failed. Try again.', 'triggerfish-social' ); ?> <em>(<?php echo esc_html( $this->oauth_result->get_error_message() ); ?>)</em></strong></p>
			</div>
			<?php
		} elseif ( true === $this->oauth_result ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php _e( 'You have successfully refreshed a token.', 'triggerfish-social' ); ?></strong></p>
			</div>
			<?php
		} elseif ( is_string( $this->oauth_result ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php _e( 'You have successfully refreshed a token.', 'triggerfish-social' ); ?></strong></p>
			</div>
			<?php
		}
	}

	public static function get_provider_names() {
		return [
			'instagram' => 'Instagram',
			'linkedin' => 'LinkedIn',
		];
	}

	public static function get_provider_class_name( $provider_name ) {
		$provider_names = self::get_provider_names();

		if ( isset( $provider_names[ $provider_name ] ) ) {
			return $provider_names[ $provider_name ];
		}

		return null;
	}

	public static function get_provider_scope( $provider ) {
		switch ( $provider ) {
			case 'instagram':
				return 'basic public_content';

			case 'linkedin':
				return 'r_basicprofile rw_company_admin';
		}

		return '';
	}

	public static function is_valid_provider_name( $provider_name ) {
		$providers = self::get_provider_names();

		return isset( $providers[ $provider_name ] );
	}

	public static function get_oauth_pre_url( $provider_name ) {
		if ( ! self::is_valid_provider_name( $provider_name ) ) {
			return '';
		}

		return admin_url( sprintf( 'admin.php?_tf_provider_name=%s&_tf_oauth_action=redirect', $provider_name ) );
	}

	public static function get_redirect_uri( $provider_name ) {
		return add_query_arg( '_tf_provider_name', $provider_name, Settings::get_admin_page_url() );
	}

	private function get_oauth_provider( $provider_name ) {
		$provider_name = strtolower( $provider_name );

		if ( ! $this->is_valid_provider_name( $provider_name ) ) {
			return null;
		}

		$client_id = self::get_client_id( $provider_name );
		$client_secret = self::get_client_secret( $provider_name );

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return null;
		}

		try {
			$class_name = sprintf( '\League\OAuth2\Client\Provider\%s', self::get_provider_class_name( $provider_name ) );

			if ( ! class_exists( $class_name ) ) {
				return null;
			}

			return new $class_name([
				'clientId' => $client_id,
				'clientSecret' => $client_secret,
				'redirectUri' => self::get_redirect_uri( $provider_name ),
			]);
		} catch ( \Exception $e ) {
			return null;
		}
	}

	private static function get_current_account_uid( $provider_name ) {
		if ( ! session_id() ) {
			session_start();
		}

		return $_SESSION[ $provider_name ]['uid'];
	}

	public static function get_client_id( $provider_name ) {
		if ( '' !== self::get_current_account_uid( $provider_name ) ) {
			$fields = get_field( 'tf_social_' . $provider_name . '_repeater', 'options' );
			$uid = self::get_current_account_uid( $provider_name );

			foreach ( $fields as $field ) {
				if ( $field[ 'tf_social_' . $provider_name . '_username' ] === $uid ) {
					return $field[ 'tf_social_' . $provider_name . '_client_id' ];
				}
			}
		}

		return Settings::get_field( sprintf( '%s_client_id', $provider_name ) );
	}

	public static function get_client_secret( $provider_name ) {
		if ( '' !== self::get_current_account_uid( $provider_name ) ) {
			$fields = get_field( 'tf_social_' . $provider_name . '_repeater', 'options' );
			$uid = self::get_current_account_uid( $provider_name );

			foreach ( $fields as $field ) {
				if ( $field[ 'tf_social_' . $provider_name . '_username' ] === $uid ) {
					return $field[ 'tf_social_' . $provider_name . '_client_secret' ];
				}
			}
		}

		return Settings::get_field( sprintf( '%s_client_secret', $provider_name ) );
	}

	public static function set_access_token( $provider_name, $access_token ) {
		if ( 'instagram' === $provider_name ) {
			return update_option( sprintf( '_tf_social_%s_%s_access_token', $provider_name, self::get_current_account_uid( $provider_name ) ), $access_token, false );
		}

		return update_option( sprintf( '_tf_social_%s_access_token', $provider_name ), $access_token, false );
	}

	public static function get_access_token( $provider_name, $uid = '' ) {
		if ( 'instagram' === $provider_name ) {
			$uid = ! empty( $uid ) ? $uid : self::get_current_account_uid( $provider_name );

			return get_option( sprintf( '_tf_social_%s_%s_access_token', $provider_name, $uid ), '' );
		}

		return get_option( sprintf( '_tf_social_%s_access_token', $provider_name ), '' );
	}

	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

OAuth::instance();
