<?php

namespace Triggerfish\Social;

use WP_Error;
use ReflectionClass;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

abstract class Provider {

	const DATE_FORMAT = 'Y-m-d H:i:s';

	abstract public function get_name();
	abstract protected function format_item_to_post_array( $item, $account_id );
	abstract protected function get_items_from_response_body( $body );
	abstract protected function get_decoded_response_body( $account_id );

	final public function sync_account( $account ) {
		$account_id = $account->get_id();

		$body = $this->get_decoded_response_body( $account_id );

		$valid = $this->validate_body( $body );

		if ( true !== $valid ) {
			if ( ! is_wp_error( $valid ) ) {
				$valid = new WP_Error( 'social-plugin', 'Invalid response body', $valid );
			}

			return $valid;
		}

		$items = $this->get_items_from_response_body( $body );

		$limit = $this->get_limit();

		$current_external_ids = [];

		for ( $i = 0; $i < $limit; $i++ ) {
			if ( ! isset( $items[ $i ] ) ) {
				break;
			}

			$item = $items[ $i ];

			$valid = $this->validate_item( $item );

			if ( true !== $valid ) {
				continue;
			}

			$external_id = $this->get_external_unique_id( $item );

			$current_external_ids[] = $external_id;

			$post_id = $this->get_post_id( $external_id );

			if ( true !== $this->eligible_for_sync( $item, $post_id ) ) {
				continue;
			}

			$post_array = $this->format_item_to_post_array( $item, $account_id );

			$post_array = apply_filters( 'tf/social/provider/post_array', $post_array, $item );
			$post_array = apply_filters( sprintf( 'tf/social/provider/%s/post_array', $this->get_name() ), $post_array, $item );

			if ( ! empty( $post_id ) ) {
				$post_array['ID'] = $post_id;
			}

			$post_array['post_type'] = Plugin::POST_TYPE;

			if ( ! isset( $post_array['meta_input'] ) ) {
				$post_array['meta_input'] = [];
			}

			$post_array['meta_input']['_external_id'] = $external_id;
			$post_array['meta_input']['_provider_name'] = $this->get_name();
			$post_array['meta_input']['_account_id'] = $account_id;

			$post_id = wp_insert_post( $post_array, true );

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			$terms_to_set = $this->get_terms_to_set( $account_id );

			wp_set_post_terms( $post_id, $terms_to_set, Plugin::TAXONOMY, false );

			do_action( 'tf/social/provider/post_inserted', $post_id, $item );
			do_action( sprintf( 'tf/social/provider/%s/post_inserted', $this->get_name() ), $post_id, $item );
		}

		$this->delete_old_posts( $current_external_ids, $account_id );
	}

	private function delete_old_posts( $current_external_ids, $account_id ) {
		$query = new \WP_Query([
			'post_type' => Plugin::POST_TYPE,
			'post_status' => 'any',
			'nopaging' => true,
			'ignore_sticky_posts' => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'no_found_rows' => true,
			'meta_query' => [
				[
					'key' => '_external_id',
					'compare' => 'NOT IN',
					'value' => $current_external_ids,
				],
				[
					'key' => '_provider_name',
					'value' => $this->get_name(),
				],
				[
					'key' => '_account_id',
					'value' => $account_id,
				],
			],
			'fields' => 'ids',
		]);

		foreach ( $query->posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	protected function get_terms_to_set( $account_id ) {
		$provider_term_id = $this->get_provider_term_id( $account_id );

		if ( empty( $provider_term_id ) ) {
			$result = wp_insert_term(
				$this->get_name(),
				Plugin::TAXONOMY
			);

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$provider_term_id = $result['term_id'];
		}

		$account_term_id = $this->get_account_term_id( $account_id );

		if ( empty( $account_term_id ) ) {
			$result = wp_insert_term(
				$account_id,
				Plugin::TAXONOMY,
				[ 'parent' => $provider_term_id ]
			);

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$account_term_id = $result['term_id'];
		}

		return [ $provider_term_id, $account_term_id ];
	}

	protected function get_post_id( $external_id ) {
		$query = new \WP_Query([
			'post_type' => Plugin::POST_TYPE,
			'post_status' => 'any',
			'posts_per_page' => 2,
			'ignore_sticky_posts' => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'no_found_rows' => true,
			'tax_query' => [
				[
					'taxonomy' => Plugin::TAXONOMY,
					'terms' => $this->get_name(),
					'field' => 'name',
				],
			],
			'meta_query' => [
				[
					'key' => '_external_id',
					'value' => $external_id,
				],
			],
			'fields' => 'ids',
		]);

		if ( 1 === $query->post_count ) {
			return absint( $query->posts[0] );
		}

		return 0;
	}

	public function get_account_term_id( $account_id ) {
		$terms = get_terms([
			'taxonomy' => Plugin::TAXONOMY,
			'number' => 2,
			'hide_empty' => false,
			'update_term_meta_cache' => false,
			'parent' => $this->get_provider_term_id(),
			'name' => $account_id,
			'fields' => 'ids',
		]);

		if ( 1 === count( $terms ) ) {
			return absint( $terms[0] );
		}

		return 0;
	}

	public function get_provider_term_id() {
		$terms = get_terms([
			'taxonomy' => Plugin::TAXONOMY,
			'number' => 2,
			'hide_empty' => false,
			'update_term_meta_cache' => false,
			'parent' => 0,
			'name' => $this->get_name(),
			'fields' => 'ids',
		]);

		if ( 1 === count( $terms ) ) {
			return absint( $terms[0] );
		}

		return 0;
	}

	protected function get_external_unique_id( $item ) {
		return strval( $item['id'] );
	}

	protected function decode_body( $body ) {
		return json_decode( $body, true );
	}

	protected function validate_response( $response ) {
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			Plugin::debug( sprintf( 'Incorrect response code (%d)', wp_remote_retrieve_response_code( $response ) ) );

			if ( ! empty( wp_remote_retrieve_body( $response ) ) ) {
				Plugin::debug( 'Body: ' . wp_remote_retrieve_body( $response ) );
			}

			return new WP_Error( 'social-plugin', 'Incorrect response code', $response );
		}

		return true;
	}

	protected function validate_body( $body ) {
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		return ( ! empty( $body ) );
	}

	protected function get_limit() {
		$limit = apply_filters( 'tf/social/provider/limit_per_account', 10 );
		return apply_filters( sprintf( 'tf/social/provider/%s/limit_per_account', $this->get_name() ), $limit );
	}

	protected function request( $url ) {
		$wp_remote_parameters = $this->get_wp_remote_parameters();
		$wp_remote_parameters = apply_filters( 'tf/social/provider/wp_remote_parameters', $wp_remote_parameters );
		$wp_remote_parameters = apply_filters( sprintf( 'tf/social/provider/%s/wp_remote_parameters', $this->get_name() ), $wp_remote_parameters );

		Plugin::debug( sprintf( 'Requesting %s', $url ) );
		$wp_remote_parameters && Plugin::debug( sprintf( 'with %s', wp_json_encode( $wp_remote_parameters ) ) );

		return wp_remote_get(
			$url,
			$wp_remote_parameters
		);
	}

	protected function format_date( $date_string ) {
		$datetime = new \DateTime( $date_string );
		$datetime->setTimeZone( new \DateTimeZone( get_option( 'timezone_string', 'Europe/Stockholm' ) ) );

		return $datetime->format( $this->get_date_format() );
	}

	protected function create_links( $string ) {
		return preg_replace(
			'~[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]~',
			"<a href=\"\\0\">\\0</a>",
			sanitize_text_field( $string )
		);
	}

	protected function validate_item( $item ) {
		return true;
	}

	protected function eligible_for_sync( $item, $post_id ) {
		return true;
	}

	final public function get_date_format() {
		return self::DATE_FORMAT;
	}

	protected function get_access_token() {
		return \Triggerfish\Social\OAuth::get_access_token( $this->get_name() );
	}

	protected function get_wp_remote_parameters() {
		return [];
	}

	public static function instance() {
		$class_name = get_called_class();

		$reflection_class = new ReflectionClass( $class_name );

		return $reflection_class->newInstanceArgs( func_get_args() );
	}
}
