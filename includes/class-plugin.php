<?php

namespace Triggerfish\Social;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Plugin {

	const POST_TYPE = 'tf-social-item';
	const TAXONOMY = 'tf-social-provider-account';
	const SLUG = 'triggerfish-social';
	const CRON_ACTION = 'tf/social/cron';

	static private $instance;

	private function __construct() {

		// Load from abspath first and wp content second
		if ( file_exists( ABSPATH . '/vendor/autoload.php' ) ) {
			include_once ABSPATH . '/vendor/autoload.php';
		} elseif ( file_exists( PLUGIN_DIR . '/vendor/autoload.php' ) ) {
			include_once PLUGIN_DIR . '/vendor/autoload.php';
		}

		include_once PLUGIN_DIR . '/includes/class-settings.php';
		include_once PLUGIN_DIR . '/includes/class-providers.php';
		include_once PLUGIN_DIR . '/includes/class-accounts.php';
		include_once PLUGIN_DIR . '/includes/class-oauth.php';

		include_once PLUGIN_DIR . '/includes/class-account.php';
		include_once PLUGIN_DIR . '/includes/accounts/class-facebook.php';
		include_once PLUGIN_DIR . '/includes/accounts/class-twitter.php';
		include_once PLUGIN_DIR . '/includes/accounts/class-youtube.php';
		include_once PLUGIN_DIR . '/includes/accounts/class-instagram.php';
		include_once PLUGIN_DIR . '/includes/accounts/class-linkedin.php';

		include_once PLUGIN_DIR . '/includes/class-provider.php';
		include_once PLUGIN_DIR . '/includes/class-basic-provider.php';
		include_once PLUGIN_DIR . '/includes/providers/class-facebook.php';
		include_once PLUGIN_DIR . '/includes/providers/class-twitter.php';
		include_once PLUGIN_DIR . '/includes/providers/class-youtube.php';
		include_once PLUGIN_DIR . '/includes/providers/class-instagram.php';
		include_once PLUGIN_DIR . '/includes/providers/class-linkedin.php';

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			include_once PLUGIN_DIR . '/includes/class-cli.php';
		}

		add_action( 'admin_menu', [ $this, 'admin_menu' ], 1 );

		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'init', [ $this, 'load_translations' ], 1 );
		add_action( 'wp_loaded', [ $this, 'version_check' ] );

		add_action( 'wpmu_new_blog', [ $this, 'new_site_created' ], 10, 1 );

		add_action( self::CRON_ACTION, [ __NAMESPACE__ . '\Accounts', 'sync_all_accounts' ] );
	}

	function version_check() {
		$current_version = $this->get_db_version();
		$plugin_version = $this->get_plugin_version();

		if ( $current_version !== $plugin_version ) {
			if ( false !== $this->upgrade( $current_version, $plugin_version ) ) {
				update_option( 'triggerfish_social_installed_version', $plugin_version );
			}
		}
	}

	private function upgrade( $from_version, $to_version ) {
		if ( version_compare( $from_version, '1.7.0', '<' ) ) {
			$instagram_accounts = get_field( 'field_tf_social_accounts_tf_social_instagram_accounts', 'option' );

			if ( count( $instagram_accounts ) > 1 ) {
				update_field( 'field_tf_social_accounts_tf_social_instagram_accounts', array_slice( $instagram_accounts, 0, 1 ), 'option' );
			}

			$result = Providers::sync_provider( 'instagram' );
		}
	}

	function get_db_version() {
		return get_option( 'triggerfish_social_installed_version', false );
	}

	function get_plugin_version() {
		return PLUGIN_VERSION;
	}

	function load_translations() {
		load_plugin_textdomain( 'triggerfish-social', false, basename( PLUGIN_DIR ) . '/languages' );
	}

	public function register_post_type() {
		register_post_type( self::POST_TYPE, [
			'public' => false,
			'query_var' => false,
			'show_ui' => apply_filters( 'tf/social/show_ui', defined( 'WP_DEBUG' ) && WP_DEBUG ),
		]);
	}

	public function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			[
				'public' => false,
				'query_var' => false,
				'hierarchical' => true,
				'show_ui' => apply_filters( 'tf/social/show_ui', defined( 'WP_DEBUG' ) && WP_DEBUG ),
			]
		);
	}

	public static function get_provider_names() {
		return [
			'facebook',
			'twitter',
			'youtube',
			'instagram',
			'linkedin',
		];
	}

	public static function get_provider_class_map() {
		return [
			'facebook' => 'Triggerfish\Social\Provider\Facebook',
			'twitter' => 'Triggerfish\Social\Provider\Twitter',
			'youtube' => 'Triggerfish\Social\Provider\YouTube',
			'instagram' => 'Triggerfish\Social\Provider\Instagram',
			'linkedin' => 'Triggerfish\Social\Provider\LinkedIn',
		];
	}

	public static function get_provider_class( $provider ) {
		$providers = self::get_provider_class_map();

		return isset( $providers[ $provider ] ) ? $providers[ $provider ] : null;
	}

	public static function get_account_class_map() {
		return [
			'facebook' => 'Triggerfish\Social\Account\Facebook',
			'twitter' => 'Triggerfish\Social\Account\Twitter',
			'youtube' => 'Triggerfish\Social\Account\YouTube',
			'instagram' => 'Triggerfish\Social\Account\Instagram',
			'linkedin' => 'Triggerfish\Social\Account\LinkedIn',
		];
	}

	public static function get_account_class( $account ) {
		$accounts = self::get_account_class_map();

		return isset( $accounts[ $account ] ) ? $accounts[ $account ] : null;
	}

	public function admin_menu() {
		acf_add_options_page([
			'menu_slug' => self::SLUG,
			'page_title' => __( 'Social', 'triggerfish-social' ),
			'menu_title' => __( 'Social', 'triggerfish-social' ),
			'redirect' => true,
		]);
	}

	public static function on_activation() {
		if ( is_multisite() ) {
			$site_ids = get_sites([
				'number' => 1000,
				'fields' => 'ids',
			]);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );

				self::schedule_event();

				restore_current_blog();
			}
		} else {
			self::schedule_event();
		}
	}

	public static function on_deactivation() {
		if ( is_multisite() ) {
			$site_ids = get_sites([
				'number' => 1000,
				'fields' => 'ids',
			]);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );

				wp_clear_scheduled_hook( self::CRON_ACTION );

				restore_current_blog();
			}
		} else {
			wp_clear_scheduled_hook( self::CRON_ACTION );
		}
	}

	public function new_site_created( $blog_id ) {
		switch_to_blog( $blog_id );

		self::schedule_event();

		restore_current_blog();
	}

	private static function schedule_event() {
		if ( ! wp_next_scheduled( self::CRON_ACTION ) ) {
			wp_schedule_event( time(), 'twicedaily', self::CRON_ACTION );
		}
	}

	public static function debug( $debug_message ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::debug( $debug_message );
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $debug_message );
		}
	}

	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

register_activation_hook( PLUGIN_FILE, [ __NAMESPACE__ . '\Plugin', 'on_activation' ] );
register_deactivation_hook( PLUGIN_FILE, [ __NAMESPACE__ . '\Plugin', 'on_deactivation' ] );

add_action( 'after_setup_theme', function() {
	if ( ! class_exists( 'acf' ) ) {
		return;
	}

	Plugin::instance();
});

