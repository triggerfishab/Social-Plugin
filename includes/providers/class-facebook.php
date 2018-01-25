<?php

namespace Triggerfish\Social\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Facebook extends \Triggerfish\Social\Basic_Provider {

	public function get_name() {
		return 'facebook';
	}

	protected function get_url( $account_id ) {
		return sprintf( 'https://graph.facebook.com/v2.11/%s/posts', $account_id );
	}

	protected function get_remote_request_parameters( $account_id ) {
		return [
			'fields' => 'created_time,updated_time,link,message,attachments',
			'access_token' => sprintf( '%s|%s', \Triggerfish\Social\Settings::get_field( 'facebook_app_id' ), \Triggerfish\Social\Settings::get_field( 'facebook_app_secret' ) ),
			'limit' => $this->get_limit(),
		];
	}

	protected function format_item_to_post_array( $item, $account_id ) {
		$post_array = [
			'post_title' => '',
			'post_status' => 'publish',
			'post_date' => $this->format_date( $item['created_time'] ),
			'meta_input' => [
				'account_name' => $account_id,
			],
		];

		if ( ! empty( $item['message'] ) ) {
			$post_array['post_content'] = $this->create_links( $item['message'] );
		}

		if ( ! empty( $item['link'] ) ) {
			$post_array['meta_input']['url'] = esc_url_raw( $item['link'] );
		}

		if ( ! empty( $item['attachments']['data'][0]['media']['image'] ) ) {
			$post_array['meta_input']['image'] = esc_url_raw( $item['attachments']['data'][0]['media']['image']['src'] );
		}

		return $post_array;
	}

	protected function get_items_from_response_body( $items ) {
		return $items['data'];
	}

	protected function eligible_for_sync( $item, $post_id ) {
		if ( empty( $post_id ) ) {
			return true;
		}

		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return true;
		}

		if ( ! isset( $item['updated_time'] ) ) {
			return true;
		}

		$item_date = $this->format_date( $item['updated_time'] );

		return ( $item_date > $post->post_modified );
	}

}
