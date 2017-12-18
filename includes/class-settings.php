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
			[
				'key' => 'field_tf_social_settings_tf_social_facebook_tab',
				'name' => 'tf_social_facebook_tab',
				'type' => 'tab',
				'label' => 'Facebook',
			],
			[
				'key' => 'field_tf_social_settings_tf_social_facebook_app_id',
				'name' => 'tf_social_facebook_app_id',
				'type' => 'text',
				'label' => 'App ID',
				'wrapper' => [ 'width' => 50 ],
			],
			[
				'key' => 'field_tf_social_settings_tf_social_facebook_app_secret',
				'name' => 'tf_social_facebook_app_secret',
				'type' => 'text',
				'label' => 'App Secret',
				'wrapper' => [ 'width' => 50 ],
			],
			[
				'key' => 'field_tf_social_settings_tf_social_twitter_tab',
				'name' => 'tf_social_twitter_tab',
				'type' => 'tab',
				'label' => 'Twitter',
			],
			[
				'key' => 'field_tf_social_settings_tf_social_twitter_access_token',
				'name' => 'tf_social_twitter_access_token',
				'type' => 'text',
				'label' => 'Access Token',
				'wrapper' => [ 'width' => 50 ],
			],
			[
				'key' => 'field_tf_social_settings_tf_social_twitter_access_token_secret',
				'name' => 'tf_social_twitter_access_token_secret',
				'type' => 'text',
				'label' => 'Access Token Secret',
				'wrapper' => [ 'width' => 50 ],
			],
			[
				'key' => 'field_tf_social_settings_tf_social_twitter_consumer_key',
				'name' => 'tf_social_twitter_consumer_key',
				'type' => 'text',
				'label' => 'Consumer Key',
				'wrapper' => [ 'width' => 50 ],
			],
			[
				'key' => 'field_tf_social_settings_tf_social_twitter_consumer_secret',
				'name' => 'tf_social_twitter_consumer_secret',
				'type' => 'text',
				'label' => 'Consumer Secret',
				'wrapper' => [ 'width' => 50 ],
			],
			[
				'key' => 'field_tf_social_settings_tf_social_youtube_tab',
				'name' => 'tf_social_youtube_tab',
				'type' => 'tab',
				'label' => 'YouTube',
			],
			[
				'key' => 'field_tf_social_settings_tf_social_youtube_api_key',
				'name' => 'tf_social_youtube_api_key',
				'type' => 'text',
				'label' => 'API Key',
			],
			[
				'key' => 'field_tf_social_settings_tf_social_instagram_tab',
				'name' => 'tf_social_instagram_tab',
				'type' => 'tab',
				'label' => 'Instagram',
			],
			[
				'key' => 'field_tf_social_settings_tf_social_instagram_client_id',
				'name' => 'tf_social_instagram_client_id',
				'type' => 'text',
				'label' => 'Client ID',
				'wrapper' => [ 'width' => 50 ],
			],
			[
				'key' => 'field_tf_social_settings_tf_social_instagram_client_secret',
				'name' => 'tf_social_instagram_client_secret',
				'type' => 'text',
				'label' => 'Client Secret',
				'wrapper' => [ 'width' => 50 ],
			],
			[
				'key' => 'field_tf_social_settings_tf_social_instagram_authorize_button',
				'name' => 'tf_social_instagram_authorize_button',
				'type' => 'message',
				'label' => __( 'Authorize', 'triggerfish-social' ),
				'message' => sprintf(
					'
					Redirect URI:
					<pre><code>%s</code></pre>
					<a class="button" href="%s">%s</a>
					',
					self::get_admin_page_url(),
					OAuth::get_oauth_pre_url( 'instagram' ),
					esc_html__( 'Authorize', 'triggerfish-social' )
				),
			],
		];

		$instruction_text = apply_filters( 'tf/social/settings/instructions', '' );

		if ( $instruction_text ) {
			array_unshift(
				$fields,
				[
					'key' => 'field_tf_social_settings_tf_social_instructions',
					'name' => 'tf_social_instructions',
					'type' => 'message',
					'label' => __( 'Instructions', 'triggerfish-social' ),
					'message' => $instruction_text,
				]
			);
		}

		acf_add_local_field_group([
			'key' => 'tf_social_settings',
			'title' => __( 'Settings', 'triggerfish-social' ),
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
