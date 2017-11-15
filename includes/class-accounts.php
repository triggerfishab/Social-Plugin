<?php

namespace Triggerfish\Social;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Accounts {

	static private $instance;

	const SLUG = 'triggerfish-social-accounts';

	public static function sync_all_accounts() {
		$accounts = self::get_all_accounts();

		$result = [];

		foreach ( $accounts as $account ) {
			$result[] = $account->sync();
		}

		return array_filter( $result );
	}

	public static function get_all_accounts() : array {
		$provider_names = Plugin::get_provider_names();

		$accounts = [];

		foreach ( $provider_names as $provider_name ) {
			$accounts = array_merge( $accounts, self::get_provider_accounts( $provider_name ) );
		}

		return $accounts;
	}

	public static function get_provider_accounts( string $provider ) : array {
		$account_class = Plugin::get_account_class( $provider );

		if ( empty( $account_class ) ) {
			return [];
		}

		$field_name = sprintf( 'tf_social_%s_accounts', $provider );

		$repeater_value = get_field( $field_name, 'option', false );

		if ( empty( $repeater_value ) ) {
			return [];
		}

		$ids = array_map( 'current', $repeater_value );

		return array_map( [ $account_class, 'instance' ], $ids );
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'acf/init', [ $this, 'register_acf_fields' ] );
	}

	public function admin_menu() {
		acf_add_options_sub_page([
			'parent_slug' => Plugin::SLUG,
			'menu_slug' => self::SLUG,
			'page_title' => __( 'Accounts', 'triggerfish-social' ),
			'menu_title' => __( 'Accounts', 'triggerfish-social' ),
			'redirect' => false,
		]);
	}

	public function register_acf_fields() {
		$fields = [
			acf_tab([
				'name' => 'tf_social_facebook_account_tab',
				'label' => __( 'Facebook', 'triggerfish-social' ),
			]),
			acf_repeater([
				'name' => 'tf_social_facebook_accounts',
				'label' => __( 'Accounts', 'triggerfish-social' ),
				'sub_fields' => [
					acf_text([
						'name' => 'id',
						'label' => __( 'Page ID', 'triggerfish-social' ),
						'instructions' => __( '<br>Go to the Facebook page of which posts you want to include, for example: <a target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/Triggerfish.se/">https://www.facebook.com/Triggerfish.se/</a>.<br>The part containing "Triggerfish.se" should be entered in this field.', 'triggerfish-social' ),
						'required' => true,
					]),
				],
			]),
			acf_tab([
				'name' => 'tf_social_twitter_account_tab',
				'label' => __( 'Twitter', 'triggerfish-social' ),
			]),
			acf_repeater([
				'name' => 'tf_social_twitter_accounts',
				'label' => __( 'Accounts', 'triggerfish-social' ),
				'sub_fields' => [
					acf_text([
						'name' => 'id',
						'label' => __( 'Username', 'triggerfish-social' ),
						'required' => true,
						'prepend' => '@',
					]),
				],
			]),
			acf_tab([
				'name' => 'tf_social_youtube_account_tab',
				'label' => __( 'YouTube', 'triggerfish-social' ),
			]),
			acf_repeater([
				'name' => 'tf_social_youtube_accounts',
				'label' => __( 'Accounts', 'triggerfish-social' ),
				'sub_fields' => [
					acf_text([
						'name' => 'id',
						'label' => __( 'Channel ID', 'triggerfish-social' ),
						'required' => true,
					]),
				],
			]),
			acf_tab([
				'name' => 'tf_social_instagram_account_tab',
				'label' => __( 'Instagram', 'triggerfish-social' ),
			]),
			acf_repeater([
				'name' => 'tf_social_instagram_accounts',
				'label' => __( 'Accounts', 'triggerfish-social' ),
				'sub_fields' => [
					acf_text([
						'name' => 'id',
						'label' => __( 'Username', 'triggerfish-social' ),
						'required' => true,
						'prepend' => '@',
					]),
				],
			]),
		];

		acf_field_group([
			'key' => 'tf_social_accounts',
			'title' => __( 'Accounts', 'triggerfish-social' ),
			'fields' => $fields,
			'location' => [
				[
					acf_location( 'options_page', self::SLUG ),
				],
			],
		]);
	}

	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Accounts::instance();
