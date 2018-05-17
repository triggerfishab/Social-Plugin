<?php

namespace Triggerfish\Social\Provider;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Instagram extends \Triggerfish\Social\Provider {

	public function get_name() {
		return 'instagram';
	}

	protected function format_item_to_post_array( $item, $account_id ) {
		$post_array = [
			'post_title' => '',
			'post_content' => $this->create_links( $item['caption']['text'] ),
			'post_status' => 'publish',
			'post_date' => date_i18n( $this->get_date_format(), $item['created_time'] ),
			'meta_input' => [
				'account_name' => $account_id,
				'url' => esc_url_raw( $item['link'] ),
			],
		];

		if ( ! empty( $item['images']['standard_resolution']['url'] ) ) {
			$post_array['meta_input']['image'] = esc_url_raw( $item['images']['standard_resolution']['url'] );
		}

		if ( ! empty( $item['likes']['count'] ) ) {
			$post_array['meta_input']['like_count'] = absint( $item['likes']['count'] );
		}

		if ( ! empty( $item['comments']['count'] ) ) {
			$post_array['meta_input']['comment_count'] = absint( $item['comments']['count'] );
		}

		if ( ! empty( $item['user'] ) ) {
			$post_array['meta_input']['user'] = [
				'id' => sanitize_text_field( $item['user']['id'] ),
				'username' => sanitize_text_field( $item['user']['username'] ),
				'profile_picture' => esc_url_raw( $item['user']['profile_picture'] ),
			];
		}

		return $post_array;
	}

	protected function get_items_from_response_body( $items ) {
		return $items['data'];
	}

	protected function get_decoded_response_body( $account_id ) {
		$access_token = \Triggerfish\Social\OAuth::get_access_token( $this->get_name() );

		if ( empty( $access_token ) ) {
			return new WP_Error( 'social-plugin', 'No Access Token found.', $access_token );
		}

		$url = $this->get_api_url( 'users/self/media/recent/' );
		$url = add_query_arg( 'count', $this->get_limit(), $url );

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'social-plugin', 'Incorrect response code', $response );
		}

		$body = wp_remote_retrieve_body( $response );

		return $this->decode_body( $body );
	}

	protected function get_api_url( $endpoint ) {
		$base = 'https://api.instagram.com/v1/';

		$url = $base . trim( $endpoint, '/' );

		return add_query_arg( 'access_token', \Triggerfish\Social\OAuth::get_access_token( $this->get_name() ), $url );
	}

}
