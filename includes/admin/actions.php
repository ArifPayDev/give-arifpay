<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Show transaction ID under donation meta.
 *
 * @since 1.0
 *
 * @param $transaction_id
 */
function give_arifpay_link_transaction_id( $transaction_id ) {

	$payment = new Give_Payment( $transaction_id );

	$arifpay_trans_url = 'https://www.arifpay.com/merchant/dashboard/#/paymentCompleteDetails/';

	if ( 'test' === $payment->mode ) {
		$arifpay_trans_url = 'https://test.arifpay.com/merchant/dashboard/#/paymentCompleteDetails/';
	}

	$arifpay_response = get_post_meta( absint( $_GET['id'] ), 'arifpay_donation_response', true );
	$arifpay_trans_url .= $arifpay_response['apayMoneyId'];

	echo sprintf( '<a href="%1$s" target="_blank">%2$s</a>', $arifpay_trans_url, $arifpay_response['txnid'] );
}

add_filter( 'give_payment_details_transaction_id-arifpay', 'give_arifpay_link_transaction_id', 10, 2 );


/**
 * Add arifpay donor detail to "Donor Detail" metabox
 *
 * @since 1.0
 *
 * @param $payment_id
 *
 * @return bool
 */
function give_arifpay_view_details( $payment_id ) {
	// Bailout.
	if ( 'arifpay' !== give_get_payment_gateway( $payment_id ) ) {
		return false;
	}

	$arifpay_response = get_post_meta( absint( $_GET['id'] ), 'arifpay_donation_response', true );

	// Check if phone exit in arifpay response.
	if ( empty( $arifpay_response['phone'] ) ) {
		return false;
	}
	?>
    <div class="column">
        <p>
            <strong><?php _e( 'Phone:', 'give-arifpay' ); ?></strong><br>
			<?php echo $arifpay_response['phone']; ?>
        </p>
    </div>
	<?php
}

add_action( 'give_payment_view_details', 'give_arifpay_view_details' );
