<?php
/**
 * Auto set pending payment to abandoned.
 *
 * @since 1.0
 *
 * @param int $payment_id
 */
function give_arifpay_set_donation_abandoned_callback( $payment_id ) {
	/**
	 * @var Give_Payment $payment Payment object.
	 */
	$payment = new Give_Payment( $payment_id );

	if ( 'pending' === $payment->status ) {
		$payment->update_status( 'abandoned' );
	}
}

add_action( 'give_arifpay_set_donation_abandoned', 'give_arifpay_set_donation_abandoned_callback' );

/**
 * Add phone field.
 *
 * @since 1.0
 *
 * @param $form_id
 *
 * @return bool
 */
function give_arifpay_add_phone_field( $form_id ) {
	// Bailout.
	if (
		'arifpay' !== give_get_chosen_gateway( $form_id )
		|| ! give_is_setting_enabled( give_get_option( 'arifpay_phone_field' ) )
	) {
		return false;
	}
	?>
	<p id="give-phone-wrap" class="form-row form-row-wide">
		<label class="give-label" for="give-phone">
			<?php esc_html_e( 'Phone', 'give-arifpay' ); ?>
			<span class="give-required-indicator">*</span>
			<span class="give-tooltip give-icon give-icon-question"
				  data-tooltip="<?php esc_attr_e( 'Enter only phone number.', 'give-arifpay' ); ?>"></span>

		</label>

		<input
				class="give-input required"
				type="tel"
				name="give_arifpay_phone"
				id="give-phone"
				value="<?php echo isset( $give_user_info['give_phone'] ) ? $give_user_info['give_phone'] : ''; ?>"
				required
				aria-required="true"
				maxlength="10"
				pattern="\d{10}"
		/>
	</p>
	<?php
}

add_action( 'give_donation_form_after_email', 'give_arifpay_add_phone_field' );

/**
 * Do not print cc field in donation form.
 *
 * Note: We do not need credit card field in donation form but we need billing detail fields.
 *
 * @since 1.0
 *
 * @param $form_id
 *
 * @return bool
 */
function give_arifpay_cc_form_callback( $form_id ) {

	if ( give_is_setting_enabled( give_get_option( 'arifpay_billing_details' ) ) ) {
		give_default_cc_address_fields( $form_id );

		return true;
	}

	return false;
}

add_action( 'give_arifpay_cc_form', 'give_arifpay_cc_form_callback' );


/**
 * Register Gateway Admin Notices for Arifpay add-on.
 *
 * @since 1.0.5
 *
 * @return void
 */
function give_arifpay_show_admin_notice() {

	// Bailout, if not admin.
	if ( ! is_admin() ) {
		return;
	}

	// Show currency notice, if currency is not set as "Ethiopian Birr".
	if (
		current_user_can( 'manage_give_settings' ) &&
		'ETB' !== give_get_currency() &&
		! class_exists( 'Give_Currency_Switcher' ) // Disable Notice, if Currency Switcher add-on is enabled.
	) {
		Give()->notices->register_notice( array(
			'id'          => 'give-arifpay-currency-notice',
			'type'        => 'error',
			'dismissible' => false,
			'description' => sprintf(
				__( 'The currency must be set as "Ethiopian Birr (br)" within Give\'s <a href="%s">Currency Settings</a> in order to collect donations through the Arifpay Payment Gateway.', 'give-arifpay' ),
				admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=general&section=currency-settings' )
			),
			'show'        => true,
		) );
	}

}

add_action( 'admin_notices', 'give_arifpay_show_admin_notice' );
