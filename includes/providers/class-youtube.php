<?php

namespace Triggerfish\Social\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class YouTube extends \Triggerfish\Social\Basic_Provider {

	public function get_name() {
		return 'youtube';
	}

	protected function get_url( $account_id ) {
		return sprintf( 'https://www.googleapis.com/youtube/v3/search', $account_id );
	}

	protected function get_remote_request_parameters( $account_id ) {
		return [
			'channelId' => $account_id,
			'key' => \Triggerfish\Social\Settings::get_field( 'youtube_api_key' ),
			'maxResults' => $this->get_limit(),
			'part' => 'snippet',
			'order' => 'date',
			'type' => 'video',
		];
	}

	protected function format_item_to_post_array( $item, $account_id ) {
		$post_array = [
			'post_title' => sanitize_text_field( $item['snippet']['title'] ),
			'post_content' => $this->create_links( $item['snippet']['description'] ),
			'post_status' => 'publish',
			'post_date' => $this->format_date( $item['snippet']['publishedAt'] ),
			'meta_input' => [
				'account_name' => sanitize_text_field( $item['snippet']['channelTitle'] ),
				'url' => add_query_arg( 'v', $item['id']['videoId'], 'https://www.youtube.com/watch?v=' ),
			],
		];

		if ( ! empty( $item['snippet']['thumbnails']['high'] ) ) {
			$post_array['meta_input']['image'] = esc_url_raw( $item['snippet']['thumbnails']['high']['url'] );
		}

		return $post_array;
	}

	protected function get_items_from_response_body( $items ) {
		return $items['items'];
	}

	protected function get_external_unique_id( $item ) {
		return strval( $item['id']['videoId'] );
	}

}
