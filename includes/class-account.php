<?php

namespace Triggerfish\Social;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

abstract class Account {

	private $id;

	abstract public function get_provider_name() : string;

	public function __construct( string $id ) {
		$this->id = $id;
	}

	public function get_id() {
		return $this->id;
	}

	public function sync() {
		$provider = $this->get_provider();

		if ( empty( $provider ) ) {
			return tf_wp_error( 'Unknown provider' );
		}

		return $provider->sync_account( $this );
	}

	public function get_provider() {
		$provider_name = $this->get_provider_name();
		$provider_class = Plugin::get_provider_class( $provider_name );

		return $provider_class ? new $provider_class : null;
	}

	public function delete() {
		Plugin::debug( 'Deleting account: ' . $this->get_id() );

		do_action( 'tf/social/account/deleting', $this );
		do_action( 'tf/social/account/' . $this->get_provider_name() . '/deleting', $this );

		$posts = $this->get_posts();

		foreach ( $posts as $post ) {
			wp_delete_post( $post->ID, true );
		}

		$provider = $this->get_provider();

		$account_term_id = $provider->get_account_term_id( $this->get_id() );

		wp_delete_term( $account_term_id, Plugin::TAXONOMY );

		do_action( 'tf/social/account/deleted', $this );
		do_action( 'tf/social/account/' . $this->get_provider_name() . '/deleted', $this );

		return true;
	}

	public function get_posts( int $posts_per_page = -1 ) : array {
		$query = new \WP_Query([
			'post_type' => Plugin::POST_TYPE,
			'posts_per_page' => $posts_per_page,
			'ignore_sticky_posts' => true,
			'no_found_rows' => true,
			'tax_query' => [
				[
					'taxonomy' => Plugin::TAXONOMY,
					'terms' => $this->get_id(),
					'field' => 'name',
				],
			],
		]);

		return $query->posts;
	}

	public static function instance() {
		$class_name = get_called_class();

		return new $class_name( ...func_get_args() );
	}
}
