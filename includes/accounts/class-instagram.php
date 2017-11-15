<?php

namespace Triggerfish\Social\Account;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Instagram extends \Triggerfish\Social\Account {

	public function get_provider_name() : string {
		return 'instagram';
	}

}
