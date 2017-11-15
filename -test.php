<?php

$facebook_accounts = Triggerfish\Social\Accounts::get_accounts( 'facebook' );

pr_log( $facebook_accounts );

foreach ( $facebook_accounts as $facebook_account ) {
	$facebook_account->sync();
}
