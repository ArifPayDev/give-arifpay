<?php
// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get Give core settigns.
$give_settings = give_get_settings();

// List of plugin settings.
$plugin_settings = array(
	'arifpay_payment_method_label',
	'arifpay_sandbox_api_key',
	'arifpay_sandbox_salt_key',
	'arifpay_production_api_key',
	'arifpay_live_salt_key',
);

// Unset all plugin settings.
foreach ( $plugin_settings as $setting ) {
	if( isset( $give_settings[ $setting ] ) ) {
		unset( $give_settings[ $setting ] );
	}
}

// Remove arifpay from active gateways list.
if( isset( $give_settings['gateways']['arifpay'] ) ) {
	unset( $give_settings['gateways']['arifpay'] );
}


// Update settings.
update_option( 'give_settings', $give_settings );