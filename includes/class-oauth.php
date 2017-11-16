<?php

namespace Triggerfish\Social;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class OAuth {

	private static $instance;

	public function __construct() {
		add_action( 'current_screen', [ $this, 'handle' ] );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
	}

	public function handle() {
		if ( 'GET' !== $_SERVER['REQUEST_METHOD'] || empty( $_GET['_tf_provider_name'] ) || ! is_user_logged_in() ) {
			return;
		}

		$provider_name = sanitize_text_field( $_GET['_tf_provider_name'] );

		if ( ! self::is_valid_provider_name( $provider_name ) ) {
			return;
		}

		$action = sanitize_text_field( $_GET['_tf_oauth_action'] );

		if ( 'redirect' === $action ) {
			$oauth_provider = $this->get_oauth_provider( $provider_name );

			if ( is_null( $oauth_provider ) ) {
				wp_die(
					esc_html__( 'You first need to supply a Client ID and Client Secret.', 'triggerfish-social' ),
					'',
					[ 'back_link' => true ]
				);
			}

			wp_redirect( $oauth_provider->getAuthorizationUrl( [ 'scope' => 'basic public_content' ] ) );
			exit;
		}

		if ( ! empty( $_GET['state'] ) ) {
			try {
				$oauth_provider = $this->get_oauth_provider( $provider_name );

				$token = $oauth_provider->getAccessToken(
					'authorization_code',
					[ 'code' => sanitize_text_field( $_GET['code'] ) ]
				);

				self::set_access_token( $provider_name, $token->getToken() );

				$this->oauth_result = true;
			} catch ( \Exception $e ) {
				$this->oauth_result = tf_wp_error( $e->getMessage(), '', $e->getCode() );
			}
		}

		if ( ! empty( $_GET['error_reason'] ) ) {
			$this->oauth_result = tf_wp_error( $_GET['error_description'], $_GET['error_reason'], $_GET['error'] );
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
		}
	}

	public static function get_provider_names() {
		return [ 'instagram' ];
	}

	public static function is_valid_provider_name( $provider_name ) {
		return in_array( $provider_name, self::get_provider_names() );
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
			$class_name = sprintf( 'League\OAuth2\Client\Provider\%s', ucfirst( $provider_name ) );

			if ( ! class_exists( $class_name ) ) {
				return null;
			}

			return new $class_name([
				'clientId' => $client_id,
				'clientSecret' => $client_secret,
				'redirectUri' => self::get_redirect_uri( $provider_name ),
			]);
		} catch ( Exception $e ) {
			return null;
		}
	}

	public static function get_client_id( string $provider_name ) {
		return Settings::get_field( sprintf( '%s_client_id', $provider_name ) );
	}

	public static function get_client_secret( string $provider_name ) {
		return Settings::get_field( sprintf( '%s_client_secret', $provider_name ) );
	}

	public static function set_access_token( string $provider_name, string $access_token ) : bool {
		return update_option( sprintf( '_tf_social_%s_access_token', $provider_name ), $access_token, false );
	}

	public static function get_access_token( string $provider_name ) : string {
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
