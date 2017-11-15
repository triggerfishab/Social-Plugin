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

		return $provider->sync_account( $this->get_id() );
	}

	public function get_provider() {
		$provider_name = $this->get_provider_name();
		$provider_class = Plugin::get_provider_class( $provider_name );

		return $provider_class ? new $provider_class : null;
	}

	public static function instance() {
		$class_name = get_called_class();

		return new $class_name( ...func_get_args() );
	}
}
