<?php

namespace Triggerfish\Social;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

abstract class Basic_Provider extends \Triggerfish\Social\Provider {

	abstract protected function get_url( $account_id ) : string;
	abstract protected function get_remote_request_parameters( $account_id ) : array;

	protected function get_decoded_response_body( $account_id ) {
		$parameters = apply_filters( 'tf/social/provider/request_parameters', [] );
		$parameters = apply_filters( sprintf( 'tf/social/provider/%s/request_parameters', $this->get_name() ), $parameters );

		$parameters = wp_parse_args(
			$parameters,
			$this->get_remote_request_parameters( $account_id )
		);

		$url = $this->get_url( $account_id );
		$url = add_query_arg( $parameters, $url );

		pr_log( $parameters, '$parameters' );
		pr_log( $url, '$url' );

		$response = $this->request( $url );

		if ( is_wp_error( $responses ) ) {
			return $response;
		}

		$valid = $this->validate_response( $response );

		if ( true !== $valid ) {
			if ( ! is_wp_error( $valid ) ) {
				$valid = tf_wp_error( 'Invalid response', $response );
			}

			return $valid;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return tf_wp_error( 'Empty response body', $response );
		}

		return $this->decode_body( $body );
	}

}
