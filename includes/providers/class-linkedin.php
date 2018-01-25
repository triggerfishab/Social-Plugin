<?php

namespace Triggerfish\Social\Provider;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class LinkedIn extends \Triggerfish\Social\Basic_Provider {

	public function get_name() {
		return 'linkedin';
	}

	protected function get_url( $account_id ) {
		return sprintf( 'https://api.linkedin.com/v1/companies/%s/updates', $account_id );
	}

	protected function get_remote_request_parameters( $account_id ) {
		return [
			'format' => 'json',
		];
	}

	protected function format_item_to_post_array( $item, $account_id ) {
		$share = $item['updateContent']['companyStatusUpdate']['share'];

		$post_array = [
			'post_title' => $share['content']['title'],
			'post_status' => 'publish',
			'post_date' => $this->format_date( $share['timestamp'] ),
			'meta_input' => [],
		];

		if ( ! empty( $share['comment'] ) ) {
			$post_array['post_content'] = $this->create_links( $share['comment'] );
		}

		if ( ! empty( $share['content']['shortenedUrl'] ) ) {
			$post_array['meta_input']['url'] = esc_url_raw( $share['content']['shortenedUrl'] );
		}

		if ( ! empty( $item['numLikes'] ) ) {
			$post_array['meta_input']['like_count'] = absint( $item['numLikes'] );
		}

		if ( ! empty( $share['content']['submittedImageUrl'] ) ) {
			$post_array['meta_input']['image'] = esc_url_raw( $share['content']['submittedImageUrl'] );
		}

		if ( ! empty( $item['updateContent']['company'] ) ) {
			$post_array['meta_input']['company'] = [
				'id' => sanitize_text_field( $item['updateContent']['company']['id'] ),
				'name' => sanitize_text_field( $item['updateContent']['company']['name'] ),
			];

			$post_array['meta_input']['account_name'] = sanitize_text_field( $item['updateContent']['company']['name'] );
		}

		return $post_array;
	}

	protected function validate_body( $body ) {
		if ( ! parent::validate_body( $body ) ) {
			return false;
		}

		return ( ! empty( $body['values'] ) );
	}

	protected function validate_item( $item ) {
		if ( ! parent::validate_item( $item ) ) {
			return false;
		}

		if ( ! isset( $item['updateContent']['companyStatusUpdate']['share']['visibility']['code'] ) || 'anyone' !== $item['updateContent']['companyStatusUpdate']['share']['visibility']['code'] ) {
			return false;
		}

		return isset( $item['updateContent']['companyStatusUpdate'] );
	}

	protected function eligible_for_sync( $item, $post_id ) {
		if ( empty( $post_id ) ) {
			return true;
		}

		$post = get_post( $post_id );

		return ( empty( $post ) );
	}

	protected function format_date( $date_string ) {
		$date_string = date_i18n( $this->get_date_format(), $date_string / 1000 );

		return parent::format_date( $date_string );
	}

	protected function get_items_from_response_body( $items ) {
		return $items['values'];
	}

	protected function get_wp_remote_parameters() {
		return [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->get_access_token(),
			],
		];
	}

	protected function get_external_unique_id( $item ) {
		return strval( $item['updateContent']['companyStatusUpdate']['share']['id'] );
	}

	public function get_companies() {
		$url = 'https://api.linkedin.com/v1/companies?is-company-admin=true';

		$url = add_query_arg( $this->get_remote_request_parameters(), $url );

		$response = $this->request( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$valid = $this->validate_response( $response );

		if ( true !== $valid ) {
			if ( ! is_wp_error( $valid ) ) {
				$valid = new WP_Error( 'social-plugin', 'Invalid response', $response );
			}

			return [];
		}

		$body = wp_remote_retrieve_body( $response );

		$data = $this->decode_body( $body );

		if ( ! isset( $data['values'] ) ) {
			return [];
		}

		return $data['values'];
	}

}
