<?php

namespace Triggerfish\Social;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Accounts {

	static private $instance;

	const SLUG = 'triggerfish-social-accounts';

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'init', [ $this, 'register_acf_fields' ], 6 );
		add_action( 'acf/save_post', [ $this, 'before_accounts_saved' ], 9 );
		add_action( 'acf/save_post', [ $this, 'after_accounts_saved' ], 11 );
	}

	public function before_accounts_saved( $post_id ) {
		if ( 'options' !== $post_id ) {
			return;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();

		$strpos_function  = ( function_exists( 'mb_strpos' ) ? 'mb_strpos' : 'strpos' );

		if ( false === call_user_func( $strpos_function, $screen->id, self::SLUG ) ) {
			return;
		}

		$accounts = self::get_all_accounts();

		if ( empty( $accounts ) ) {
			$this->accounts_before_save = [];

			return;
		}

		$account_ids = array_map( 'strval', $accounts );

		$this->accounts_before_save = array_combine( $account_ids, $accounts );
	}

	public function after_accounts_saved( $post_id ) {
		if ( 'options' !== $post_id ) {
			return;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();

		$strpos_function  = ( function_exists( 'mb_strpos' ) ? 'mb_strpos' : 'strpos' );

		if ( false === call_user_func( $strpos_function, $screen->id, self::SLUG ) ) {
			return;
		}

		$accounts = self::get_all_accounts();

		$account_ids = array_map( 'strval', $accounts );

		$current_accounts = array_combine( $account_ids, $accounts );

		$deleted_accounts = array_diff_key( $this->accounts_before_save, $current_accounts );
		$added_accounts = array_diff_key( $current_accounts, $this->accounts_before_save );

		do_action( 'tf/social/accounts/added', $added_accounts );

		if ( empty( $deleted_accounts ) && empty( $added_accounts ) ) {
			return;
		}

		foreach ( $deleted_accounts as $deleted_account ) {
			$deleted_account->delete();
		}

		foreach ( $added_accounts as $added_account ) {
			$added_account->sync();
		}
	}

	public static function delete_account( $account_id, $provider_name ) {
		$account_class = Plugin::get_account_class( $provider_name );

		if ( empty( $account_class ) ) {
			return new WP_Error( 'social-plugin', 'Unknown Provider' );
		}

		$account = new $account_class( $account_id );

		return $account->delete();
	}

	public static function sync_all_accounts() {
		$accounts = self::get_all_accounts();

		$result = [];

		foreach ( $accounts as $account ) {
			$result[] = $account->sync();
		}

		return array_filter( $result );
	}

	public static function get_all_accounts() {
		$provider_names = Plugin::get_provider_names();

		$accounts = [];

		foreach ( $provider_names as $provider_name ) {
			$accounts = array_merge( $accounts, self::get_provider_accounts( $provider_name ) );
		}

		return $accounts;
	}

	public static function get_provider_accounts( $provider ) {
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
			[
				'key' => 'field_tf_social_accounts_tf_social_facebook_account_tab',
				'name' => 'tf_social_facebook_account_tab',
				'label' => 'Facebook',
				'type' => 'tab',
			],
			[
				'key' => 'field_tf_social_accounts_tf_social_facebook_accounts',
				'name' => 'tf_social_facebook_accounts',
				'label' => __( 'Accounts', 'triggerfish-social' ),
				'max' => 5,
				'button_label' => __( 'Add account', 'triggerfish-social' ),
				'type' => 'repeater',
				'sub_fields' => [
					[
						'key' => 'field_tf_social_accounts_tf_social_facebook_accounts_id',
						'name' => 'id',
						'label' => __( 'Page ID', 'triggerfish-social' ),
						'type' => 'text',
						'instructions' => '<br>Gå till den Facebook sida vars inlägg du vill inkludera, t.ex. https://www.facebook.com/Triggerfish.se/. Sidans ID hittar du i länkadressen för sidan. I detta exempel är sidans ID "Triggerfish.se".',
						'required' => true,
					],
				],
			],
			[
				'key' => 'field_tf_social_accounts_tf_social_twitter_account_tab',
				'name' => 'tf_social_twitter_account_tab',
				'label' => 'Twitter',
				'type' => 'tab',
			],
			[
				'key' => 'field_tf_social_accounts_tf_social_twitter_accounts',
				'name' => 'tf_social_twitter_accounts',
				'label' => __( 'Accounts', 'triggerfish-social' ),
				'max' => 5,
				'button_label' => __( 'Add account', 'triggerfish-social' ),
				'type' => 'repeater',
				'sub_fields' => [
					[
						'key' => 'field_tf_social_accounts_tf_social_twitter_accounts_id',
						'name' => 'id',
						'label' => __( 'Username', 'triggerfish-social' ),
						'type' => 'text',
						'required' => true,
						'instructions' => '<br>Lägg till användarnamn för kontot vars inlägg du vill inkludera.',
						'prepend' => '@',
					],
				],
			],
			[
				'key' => 'field_tf_social_accounts_tf_social_youtube_account_tab',
				'name' => 'tf_social_youtube_account_tab',
				'label' => 'YouTube',
				'type' => 'tab',
			],
			[
				'key' => 'field_tf_social_accounts_tf_social_youtube_accounts',
				'name' => 'tf_social_youtube_accounts',
				'label' => __( 'Accounts', 'triggerfish-social' ),
				'max' => 5,
				'button_label' => __( 'Add account', 'triggerfish-social' ),
				'type' => 'repeater',
				'sub_fields' => [
					[
						'key' => 'field_tf_social_accounts_tf_social_youtube_accounts_id',
						'name' => 'id',
						'label' => __( 'Channel ID', 'triggerfish-social' ),
						'type' => 'text',
						'instructions' => '<br>Gå till en video på en YouTube kanal vars inlägg du vill inkludera. Klicka på kanalens namn vid beskrivningen av filmen för att få länkadressen till kanalen, t.ex. https://www.youtube.com/channel/UCaY-4ndPCRKp60qXF7zBJ0w. I detta exempel är Kanalens ID "UCaY-4ndPCRKp60qXF7zBJ0w".',
						'required' => true,
					],
				],
			],
			[
				'key' => 'field_tf_social_accounts_tf_social_instagram_account_tab',
				'name' => 'tf_social_instagram_account_tab',
				'label' => 'Instagram',
				'type' => 'tab',
			],
			[
				'key' => 'field_tf_social_accounts_tf_social_instagram_accounts',
				'name' => 'tf_social_instagram_accounts',
				'label' => __( 'Accounts', 'triggerfish-social' ),
				'button_label' => __( 'Add account', 'triggerfish-social' ),
				'type' => 'repeater',
				'sub_fields' => [
					[
						'key' => 'field_tf_social_accounts_tf_social_instagram_accounts_id',
						'name' => 'id',
						'label' => __( 'Username', 'triggerfish-social' ),
						'type' => 'text',
						'required' => true,
						'instructions' => '<br>För tillfället är det endast möjligt att ha ett konto.',
						'prepend' => '@',
					],
				],
			],
			[
				'key' => 'field_tf_social_accounts_tf_social_linkedin_account_tab',
				'name' => 'tf_social_linkedin_account_tab',
				'label' => 'LinkedIn',
				'type' => 'tab',
			],
			[
				'key' => 'field_tf_social_accounts_tf_social_linkedin_accounts',
				'name' => 'tf_social_linkedin_accounts',
				'label' => __( 'Accounts', 'triggerfish-social' ),
				'max' => 5,
				'button_label' => __( 'Add account', 'triggerfish-social' ),
				'type' => 'repeater',
				'sub_fields' => [
					[
						'key' => 'field_tf_social_accounts_tf_social_linkedin_accounts_id',
						'name' => 'id',
						'label' => __( 'Company ID', 'triggerfish-social' ),
						'type' => 'number',
						'required' => true,
						'instructions' => '<br>På LinkedIn, gå till företaget vars inlägg du vill inkludera.<br>T.ex. https://www.linkedin.com/company/1579477/, där "1579477" är Företagets ID.',
					],
				],
			],
		];

		acf_add_local_field_group([
			'key' => 'tf_social_accounts',
			'title' => __( 'Accounts', 'triggerfish-social' ),
			'fields' => $fields,
			'location' => [
				[
					[
						'param' => 'options_page',
						'operator' => '==',
						'value' => self::SLUG,
					],
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
