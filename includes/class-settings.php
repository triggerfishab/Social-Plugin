<?php

namespace Triggerfish\Social;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Settings {

	static private $instance;

	const SLUG = 'triggerfish-social-settings';

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'acf/init', [ $this, 'register_acf_fields' ] );
	}

	public function admin_menu() {
		acf_add_options_sub_page([
			'parent_slug' => Plugin::SLUG,
			'menu_slug' => self::SLUG,
			'page_title' => __( 'Settings', 'triggerfish-social' ),
			'menu_title' => __( 'Settings', 'triggerfish-social' ),
			'redirect' => false,
		]);
	}

	public function register_acf_fields() {
		$fields = [
			// acf_email([
			// 	'name' => 'tf_social_notify_email',
			// 	'label' => __( 'Notify email', 'triggerfish-social' ),
			// 	'instructions' => __( 'If something goes wrong with the social integration, an email will be sent to this email address.', 'triggerfish-social' ),
			// ]),
			acf_tab([
				'name' => 'tf_social_facebook_tab',
				'label' => 'Facebook',
			]),
			acf_text([
				'name' => 'tf_social_facebook_app_id',
				'label' => 'App ID',
				'wrapper' => [ 'width' => 50 ],
			]),
			acf_text([
				'name' => 'tf_social_facebook_app_secret',
				'label' => 'App Secret',
				'wrapper' => [ 'width' => 50 ],
			]),
			acf_tab([
				'name' => 'tf_social_twitter_tab',
				'label' => 'Twitter',
			]),
			acf_text([
				'name' => 'tf_social_twitter_access_token',
				'label' => 'Access Token',
				'wrapper' => [ 'width' => 50 ],
			]),
			acf_text([
				'name' => 'tf_social_twitter_access_token_secret',
				'label' => 'Access Token Secret',
				'wrapper' => [ 'width' => 50 ],
			]),
			acf_text([
				'name' => 'tf_social_twitter_consumer_key',
				'label' => 'Consumer Key',
				'wrapper' => [ 'width' => 50 ],
			]),
			acf_text([
				'name' => 'tf_social_twitter_consumer_secret',
				'label' => 'Consumer Secret',
				'wrapper' => [ 'width' => 50 ],
			]),
			acf_tab([
				'name' => 'tf_social_youtube_tab',
				'label' => 'YouTube',
			]),
			acf_text([
				'name' => 'tf_social_youtube_api_key',
				'label' => 'API Key',
			]),
			acf_tab([
				'name' => 'tf_social_instagram_tab',
				'label' => 'Instagram',
			]),
			acf_text([
				'name' => 'tf_social_instagram_client_id',
				'label' => 'Client ID',
				'wrapper' => [ 'width' => 50 ],
			]),
			acf_text([
				'name' => 'tf_social_instagram_client_secret',
				'label' => 'Client Secret',
				'wrapper' => [ 'width' => 50 ],
			]),
			acf_message([
				'name' => 'tf_social_instagram_authorize_button',
				'label' => __( 'Authorize', 'triggerfish-social' ),
				'message' => sprintf( '<a class="button-primary" href="%s">%s</a>', OAuth::get_oauth_pre_url( 'instagram' ), esc_html__( 'Authorize', 'triggerfish-social' ) ),
			]),
		];

		acf_field_group([
			'key' => 'tf_social_settings',
			'title' => __( 'Settings', 'triggerfish-social' ),
			'fields' => $fields,
			'location' => [
				[
					acf_location( 'options_page', self::SLUG ),
				],
			],
		]);
	}

	public static function get_field( $field_name ) {
		if ( 'tf_social_' !== substr( $field_name, 0, 10 ) ) {
			$field_name = sprintf( 'tf_social_%s', $field_name );
		}

		return get_field( $field_name, 'options' );
	}

	public static function get_admin_page_url() {
		return admin_url( sprintf( 'admin.php?page=%s', self::SLUG ) );
	}

	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Settings::instance();
