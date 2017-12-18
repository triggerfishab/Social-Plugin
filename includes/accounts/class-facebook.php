<?php

namespace Triggerfish\Social\Account;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Facebook extends \Triggerfish\Social\Account {

	public function get_provider_name() {
		return 'facebook';
	}

}
