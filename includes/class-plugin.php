<?php

namespace Triggerfish\Social;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Plugin {

	const POST_TYPE = 'tf-social-item';
	const TAXONOMY = 'tf-social-provider-account';

	static private $instance;

	const SLUG = 'triggerfish-social';

	private function __construct() {
		include_once PLUGIN_DIR . '/vendor/autoload.php';

		include_once PLUGIN_DIR . '/includes/class-settings.php';
		include_once PLUGIN_DIR . '/includes/class-providers.php';
		include_once PLUGIN_DIR . '/includes/class-accounts.php';
		include_once PLUGIN_DIR . '/includes/class-oauth.php';

		include_once PLUGIN_DIR . '/includes/class-account.php';
		include_once PLUGIN_DIR . '/includes/accounts/class-facebook.php';
		include_once PLUGIN_DIR . '/includes/accounts/class-twitter.php';
		include_once PLUGIN_DIR . '/includes/accounts/class-youtube.php';
		include_once PLUGIN_DIR . '/includes/accounts/class-instagram.php';

		include_once PLUGIN_DIR . '/includes/class-provider.php';
		include_once PLUGIN_DIR . '/includes/class-basic-provider.php';
		include_once PLUGIN_DIR . '/includes/providers/class-facebook.php';
		include_once PLUGIN_DIR . '/includes/providers/class-twitter.php';
		include_once PLUGIN_DIR . '/includes/providers/class-youtube.php';
		include_once PLUGIN_DIR . '/includes/providers/class-instagram.php';

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			include_once PLUGIN_DIR . '/includes/class-cli.php';
		}

		add_action( 'admin_menu', [ $this, 'admin_menu' ], 1 );

		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
	}

	public function register_post_type() {
		register_post_type( self::POST_TYPE, [
			'public' => true,
			'query_var' => false,
			'supports' => false,
		]);
	}

	public function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			[
				'public' => true,
				'query_var' => false,
				'hierarchical' => true,
			]
		);
	}

	public static function get_provider_names() {
		return [
			'facebook',
			'twitter',
			'youtube',
			'instagram',
		];
	}

	public static function get_provider_class_map() {
		return [
			'facebook' => 'Triggerfish\Social\Provider\Facebook',
			'twitter' => 'Triggerfish\Social\Provider\Twitter',
			'youtube' => 'Triggerfish\Social\Provider\YouTube',
			'instagram' => 'Triggerfish\Social\Provider\Instagram',
		];
	}

	public static function get_provider_class( string $provider ) {
		$providers = self::get_provider_class_map();

		return $providers[ $provider ] ?? null;
	}

	public static function get_account_class_map() {
		return [
			'facebook' => 'Triggerfish\Social\Account\Facebook',
			'twitter' => 'Triggerfish\Social\Account\Twitter',
			'youtube' => 'Triggerfish\Social\Account\YouTube',
			'instagram' => 'Triggerfish\Social\Account\Instagram',
		];
	}

	public static function get_account_class( string $account ) {
		$accounts = self::get_account_class_map();

		return $accounts[ $account ] ?? null;
	}

	public function admin_menu() {
		acf_add_options_page([
			'menu_slug' => self::SLUG,
			'page_title' => __( 'Social', 'triggerfish-social' ),
			'menu_title' => __( 'Social', 'triggerfish-social' ),
			'redirect' => true,
		]);
	}

	public function output_menu_page() {
		include __DIR__ . '/templates/menu-page.php';
	}

	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

Plugin::instance();
