<?php

namespace Triggerfish\Social\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Twitter extends \Triggerfish\Social\Provider {

	public function get_name() {
		return 'twitter';
	}

	protected function format_item_to_post_array( $item ) {
		$post_array = [
			'post_title' => '',
			'post_content' => $this->create_links( $item['text'] ),
			'post_status' => 'publish',
			'post_date' => $this->format_date( $item['created_at'] ),
			'meta_input' => [
				'url' => esc_url_raw( sprintf( 'https://www.twitter.com/%s/status/%s', $item['user']['screen_name'], $item['id'] ) ),
			],
		];

		if ( ! empty( $item['extended_entities']['media'][0]['media_url_https'] ) ) {
			$post_array['meta_input']['image'] = esc_url_raw( $item['extended_entities']['media'][0]['media_url_https'] );
		}

		if ( ! empty( $item['retweet_count'] ) ) {
			$post_array['meta_input']['retweet_count'] = absint( $item['retweet_count'] );
		}

		if ( ! empty( $item['favorite_count'] ) ) {
			$post_array['meta_input']['favorite_count'] = absint( $item['favorite_count'] );
		}

		if ( ! empty( $item['user'] ) ) {
			$post_array['meta_input']['user'] = [
				'id' => sanitize_text_field( $item['user']['id'] ),
				'name' => sanitize_text_field( $item['user']['name'] ),
				'screen_name' => sanitize_text_field( $item['user']['screen_name'] ),
				'location' => sanitize_text_field( $item['user']['location'] ),
				'profile_picture' => esc_url_raw( $item['user']['profile_image_url_https'] ),
			];
		}

		return $post_array;
	}

	protected function get_items_from_response_body( $items ) {
		return $items;
	}

	protected function get_decoded_response_body( $account_id ) {
		try {
			$connection = new \Abraham\TwitterOAuth\TwitterOAuth(
				\Triggerfish\Social\Settings::get_field( 'twitter_consumer_key' ),
				\Triggerfish\Social\Settings::get_field( 'twitter_consumer_secret' ),
				\Triggerfish\Social\Settings::get_field( 'twitter_access_token' ),
				\Triggerfish\Social\Settings::get_field( 'twitter_access_token_secret' )
			);

			$connection->setDecodeJsonAsArray( true );

			$parameters = [
				'include_rts' => false,
			];

			$parameters['screen_name'] = $account_id;
			$parameters['count'] = $this->get_limit();

			$items = $connection->get( 'statuses/user_timeline', $parameters );

			if ( 200 !== $connection->getLastHttpCode() ) {
				return \tf_wp_error( 'Incorrect response code.', $connection->getLastBody() );
			}

			return $items;
		} catch ( \Exception $e ) {
			return \tf_wp_error( $e->getMessage(), '', $e->getCode() );
		}
	}

	protected function eligible_for_sync( $item, $post_id ) {
		if ( empty( $post_id ) ) {
			return true;
		}

		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return true;
		}

		if ( ! isset( $item['created_at'] ) ) {
			return true;
		}

		$item_date = $this->format_date( $item['created_at'] );

		return ( $item_date > $post->post_modified );
	}

}
