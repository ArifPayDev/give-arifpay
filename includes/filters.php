<?php
/**
 * @param $messages
 *
 * @return mixed
 */
function give_apay_form_validation_message( $messages ) {
	$messages['give_arifpay_phone'] = __( 'Please enter valid phone number without zero', 'give-arifpay' );

	return $messages;
}

add_filter( 'give_form_translation_js', 'give_apay_form_validation_message' );
