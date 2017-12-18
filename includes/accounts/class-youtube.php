<?php

namespace Triggerfish\Social\Account;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class YouTube extends \Triggerfish\Social\Account {

	public function get_provider_name() {
		return 'youtube';
	}

}
