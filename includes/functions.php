<?php

/**
 * Check if the Arifpay payment gateway is active or not.
 *
 * @since 1.0
 * @return bool
 */
function give_is_apay_active()
{
	$give_settings = give_get_settings();
	$is_active     = false;

	if (
		array_key_exists('arifpay', $give_settings['gateways'])
		&& (1 == $give_settings['gateways']['arifpay'])
	) {
		$is_active = true;
	}

	return $is_active;
}


/**
 * Get payment method label.
 *
 * @since 1.0
 * @return string
 */
function give_arifpay_get_payment_method_label()
{
	return (give_get_option('arifpay_payment_method_label', false) ?  give_get_option('arifpay_payment_method_label', '') : __('Arifpay', 'give-arifpay'));
}


/**
 * Check if sandbox mode is enabled or disabled.
 *
 * @since 1.0
 * @return bool
 */
function give_arifpay_is_sandbox_mode_enabled()
{
	return give_is_test_mode();
}


/**
 * Get arifpay merchant credentials.
 *
 * @since 1.0
 * @return array
 */
function give_arifpay_get_merchant_credentials()
{
	$credentials = array(
		'merchant_key' => give_get_option('arifpay_sandbox_api_key', ''),
	);

	if (!give_arifpay_is_sandbox_mode_enabled()) {
		$credentials = array(
			'merchant_key' => give_get_option('arifpay_production_api_key', ''),
		);
	}

	return $credentials;
}


/**
 * Get api urls.
 *
 * @since 1.0
 * @return string
 */
function give_arifpay_get_api_url()
{

	// LIVE Endpoint for Arifpay as well as PayUBiz.
	$api_url = 'https://secure.apay.in/_payment';

	if (give_arifpay_is_sandbox_mode_enabled()) {
		$endpoint =  ('arifpay' === give_arifpay_get_selected_account()) ? 'sandboxsecure' : 'test';
		$api_url  = "https://{$endpoint}.apay.in/_payment";
	}

	return $api_url;
}

/**
 * This function will help you get the selected PayUIndia account by admin.
 *
 * @since 1.0.4
 *
 * @return string
 */
function give_arifpay_get_selected_account()
{
	return give_get_option('give_arifpay_environment', 'arifpay');
}
