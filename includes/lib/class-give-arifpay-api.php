<?php
if (is_readable(__DIR__ . '/vendor/autoload.php')) {
	require __DIR__ . '/vendor/autoload.php';
}

use Give\Views\Form\Templates\Sequoia\Sequoia;

use Arifpay\Phpsdk\Arifpay;

use Arifpay\Phpsdk\Helper\ArifpaySupport;
use Arifpay\Phpsdk\Lib\ArifpayBeneficary;
use Arifpay\Phpsdk\Lib\ArifpayCheckoutItem;
use Arifpay\Phpsdk\Lib\ArifpayCheckoutRequest;
use Arifpay\Phpsdk\Lib\ArifpayOptions;

class Give_Arifpay_API
{
	/**
	 * Instance.
	 *
	 * @since  1.0
	 * @access static
	 * @var
	 */
	static private $instance;

	/**
	 * @var
	 */
	static private $sandbox;

	/**
	 * @var
	 */
	static private $merchant_key;

	/**
	 * @var
	 */
	static private $arifpay;

	/**
	 * Singleton pattern.
	 *
	 * @since  1.0
	 * @access private
	 * Give_Arifpay_API constructor.
	 */
	private function __construct()
	{
	}


	/**
	 * Get instance.
	 *
	 * @since  1.0
	 * @access static
	 * @return static
	 */
	static function get_instance()
	{
		if (null === static::$instance) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Create session.
	 *
	 * @since  1.0
	 * @access public
	 * @return string
	 */

	public static function create_session($name, $amount, $nonce, $success_url, $failed_url, $notify_url, $donation_id)
	{
		try {
			$expired = "2055-01-13T17:09:42.411";
			$data = new ArifpayCheckoutRequest(
				$failed_url,
				$failed_url,
				'https://gateway.arifpay.net/test/callback',
				$expired,
				$nonce,
				[
					ArifpayBeneficary::fromJson([
						"accountNumber" => '01320811436100',
						"bank" => 'AWINETAA',
						"amount" => $amount,
					]),
				],
				[],
				$success_url,
				[
					ArifpayCheckoutItem::fromJson([
						"name" => $name,
						"price" => $amount,
						"quantity" => 1,
					]),
				],
			);

			$session =  self::$arifpay->checkout->create($data, new ArifpayOptions(sandbox: self::$sandbox));
			return $session->payment_url;
		} catch (Exception $e) {

			error_log(
				print_r($e->getMessage(), true) . "\n",
				3,
				WP_CONTENT_DIR . '/debug.log'
			);

			give_record_gateway_error(
				esc_html__('Arifpay Error', 'give-arifpay'),
				esc_html__('The Arifpay Gateway returned an error while charging a donation.', 'give-arifpay') . '<br><br>' . sprintf(esc_attr__('Details: %s', 'give-arifpay'), '<br>' . print_r($e, true)),
				$donation_id
			);
		}
	}

	/**
	 * Setup params.
	 *
	 * @since  1.0
	 * @access public
	 * @return mixed
	 */
	public function setup_params()
	{
		$merchant = give_arifpay_get_merchant_credentials();

		self::$merchant_key = $merchant['merchant_key'];
		self::$arifpay   =  new Arifpay(self::$merchant_key);

		self::$sandbox  = give_arifpay_is_sandbox_mode_enabled();

		return self::$instance;
	}

	/**
	 * Setup hooks.
	 *
	 * @since 1.0.8 Handle arifpay redirect on template_redirect action hook.
	 * @since  1.0
	 * @access public
	 * @return mixed
	 */
	public function setup_hooks()
	{
		add_filter('template_include', array($this, 'show_apay_form_template'));
		add_action('template_redirect', array($this, 'show_apay_payment_success_template'));

		return self::$instance;
	}

	/**
	 * Show arifpay form template.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param $template
	 *
	 * @return string
	 */
	public function show_apay_form_template($template)
	{
		if (isset($_GET['process_apay_payment']) && 'processing' === $_GET['process_apay_payment']) {
			$template = GIVE_APAY_DIR . 'templates/form.php';
		}

		return $template;
	}

	/**
	 * Show success template
	 *
	 * @since 1.0.8 Load file to handle arifpay redirect.
	 * @since  1.0
	 * @access public
	 */
	public function show_apay_payment_success_template()
	{
		if (isset($_REQUEST['process_apay_payment']) && in_array($_REQUEST['process_apay_payment'], array('success', 'failure'))) {
			require_once GIVE_APAY_DIR . 'templates/success.php';
		}
	}

	/**
	 * Get form
	 *
	 * @since  1.0
	 * @since 1.0.7 add logic to submit donation form to parent when donation form is in iframe.
	 *
	 * @access public
	 * @return string
	 */
	public static function get_form()
	{
		$donation_data = Give()->session->get('give_purchase');
		$donation_id   = absint($_GET['donation']);
		$form_id       = absint($_GET['form-id']);

		$form_url = trailingslashit(current(explode('?', $donation_data['post_data']['give-current-url'])));

		$apaypaisa_args = array(
			'key'              => self::$merchant_key,
			'txnid'            => "{$donation_id}-" . date('ymds') . "-" . floor(rand() * 10000),
			'amount'           => give_sanitize_amount(give_donation_amount($donation_id)),
			'firstname'        => $donation_data['post_data']['give_first'],
			'email'            => $donation_data['post_data']['give_email'],
			'phone'            => (isset($donation_data['post_data']['give_arifpay_phone']) ? $donation_data['post_data']['give_arifpay_phone'] : ''),
			'productinfo'      => sprintf(__('This is a donation payment for %s', 'give-arifpay'), $donation_id),
			'surl'             => $form_url . '?process_apay_payment=success',
			'furl'             => $form_url . '?process_apay_payment=failure',
			'nurl'             => $form_url . '?process_apay_payment=notify',
			'lastname'         => $donation_data['post_data']['give_last'],
			'udf1'             => $donation_id,
			'udf2'             => $form_id,
			'udf3'             => $form_url,
			'udf5'             => 'givewp',
			'title'			   => $donation_data['post_data']['give-form-title'],
		);

		// Pass address info if present.
		if (give_is_setting_enabled(give_get_option('arifpay_billing_details'))) {
			$apaypaisa_args['address1'] = $donation_data['post_data']['card_address'];
			$apaypaisa_args['address2'] = $donation_data['post_data']['card_address_2'];
			$apaypaisa_args['city']     = $donation_data['post_data']['card_city'];
			$apaypaisa_args['state']    = $donation_data['post_data']['card_state'];
			$apaypaisa_args['country']  = $donation_data['post_data']['billing_country'];
			$apaypaisa_args['zipcode']  = $donation_data['post_data']['card_zip'];
		}



		/**
		 * Filter the arifpay form arguments
		 *
		 * @since 1.0
		 *
		 * @param array $apaypaisa_args
		 */
		$apaypaisa_args = apply_filters('give_arifpay_form_args', $apaypaisa_args);

		// Create input hidden fields.
		$payment_url = self::create_session($apaypaisa_args["title"], $apaypaisa_args["amount"], $apaypaisa_args["txnid"], $apaypaisa_args["surl"], $apaypaisa_args["furl"], $apaypaisa_args["nurl"], $donation_id);

		ob_start();

		/* @var Sequoia $sequoiaTemplateClass */
		$sequoiaTemplateClass = give(Sequoia::class);
?>
		<form action="<?php echo $payment_url; ?>" method="get" name="apayForm" style="display: none" <?php if ($sequoiaTemplateClass->getID() === Give\Helpers\Form\Template::getActiveID($form_id)) {
																											echo 'target="_parent"';
																										} ?>>

			<input type="submit" value="Submit" />
		</form>
<?php
		$form_html = ob_get_contents();
		ob_get_clean();

		return $form_html;
	}


	/**
	 * Process arifpay success payment.
	 *
	 * @since  1.0
	 *
	 * @access public
	 *
	 * @param int $donation_id
	 */
	public static function process_success($donation_id)
	{
		$donation = new Give_Payment(absint($donation_id));
		$donation->update_status('pending');
		$donation->add_note(sprintf(__('Arifpay payment pending (Transaction id: %s)', 'give-arifpay'), $_REQUEST['txnid']));

		wp_clear_scheduled_hook('give_arifpay_set_donation_abandoned', array(absint($donation_id)));

		give_set_payment_transaction_id($donation_id, $_REQUEST['txnid']);
		update_post_meta($donation_id, 'arifpay_donation_response', $_REQUEST);

		give_send_to_success_page();
	}

	/**
	 * Process arifpay notify payment.
	 *
	 * @since  1.0
	 *
	 * @access public
	 *
	 * @param int $donation_id
	 */
	public static function process_notify()
	{
		error_log(
			print_r($_REQUEST, true) . "\n",
			3,
			WP_CONTENT_DIR . '/debug.log'
		);
		$data = explode('-', $_POST['nonce']);
		$donation_id = $data[0];
		$donation = new Give_Payment(absint($data[0]));
		if ($_REQUEST['transaction']['transactionStatus'] == "SUCCESS") {
			$donation->update_status('completed');

			wp_clear_scheduled_hook('give_arifpay_set_donation_abandoned', array(absint($donation_id)));

			give_set_payment_transaction_id($donation_id, $_REQUEST['transaction']['transactionId']);
			update_post_meta($donation_id, 'arifpay_donation_response', $_REQUEST);
		} else {
			$donation->update_status('failed');
			$donation->add_note(sprintf(__('Arifpay payment failed (Transaction id: %s)', 'give-arifpay'), $_REQUEST['transaction']['transactionId']));

			wp_clear_scheduled_hook('give_arifpay_set_donation_abandoned', array(absint($donation_id)));

			give_set_payment_transaction_id($donation_id, $_REQUEST['transaction']['transactionId']);
			update_post_meta($donation_id, 'arifpay_donation_response', $_REQUEST);

			give_record_gateway_error(
				esc_html__('Arifpay Error', 'give-arifpay'),
				esc_html__('The Arifpay Gateway returned an error while charging a donation.', 'give-arifpay') . '<br><br>' . sprintf(esc_attr__('Details: %s', 'give-arifpay'), '<br>' . print_r($_REQUEST, true)),
				$donation_id
			);
		}
		return "success";
	}

	/**
	 * Process arifpay failure payment.
	 *
	 * @since  1.0
	 *
	 * @access public
	 *
	 * @param int $donation_id
	 */
	public static function process_failure($donation_id)
	{
		$donation = new Give_Payment($donation_id);
		$donation->update_status('failed');
		$donation->add_note(sprintf(__('Arifpay payment failed (Transaction id: %s)', 'give-arifpay'), $_REQUEST['txnid']));

		wp_clear_scheduled_hook('give_arifpay_set_donation_abandoned', array(absint($donation_id)));

		give_set_payment_transaction_id($donation_id, $_REQUEST['txnid']);
		update_post_meta($donation_id, 'arifpay_donation_response', $_REQUEST);

		give_record_gateway_error(
			esc_html__('Arifpay Error', 'give-arifpay'),
			esc_html__('The Arifpay Gateway returned an error while charging a donation.', 'give-arifpay') . '<br><br>' . sprintf(esc_attr__('Details: %s', 'give-arifpay'), '<br>' . print_r($_REQUEST, true)),
			$donation_id
		);

		wp_redirect(give_get_failed_transaction_uri());
		exit();
	}

	/**
	 * Process arifpay pending payment.
	 *
	 * @since  1.0
	 *
	 * @access public
	 *
	 * @param int $donation_id
	 */
	public static function process_pending($donation_id)
	{
		$donation = new Give_Payment($donation_id);
		$donation->add_note(sprintf(__('Arifpay payment has "%s" status. Check the <a href="%s" target="_blank">Arifpay merchant dashboard</a> for more information or check the <a href="%s" target="_blank">payment gateway error logs</a> for additional details', 'give-arifpay'), $_REQUEST['status'], "https://www.arifpay.com/merchant/dashboard/#/paymentCompleteDetails/{$_REQUEST['apayMoneyId']}", admin_url('edit.php?post_type=give_forms&page=give-tools&tab=logs&section=gateway_errors')));

		wp_clear_scheduled_hook('give_arifpay_set_donation_abandoned', array(absint($donation_id)));

		give_set_payment_transaction_id($donation_id, $_REQUEST['txnid']);
		update_post_meta($donation_id, 'arifpay_donation_response', $_REQUEST);

		give_record_gateway_error(
			esc_html__('Arifpay Error', 'give-arifpay'),
			esc_html__('The Arifpay Gateway returned an error while charging a donation.', 'give-arifpay') . '<br><br>' . sprintf(esc_attr__('Details: %s', 'give-arifpay'), '<br>' . print_r($_REQUEST, true)),
			$donation_id
		);

		give_send_to_success_page();
	}
}

Give_Arifpay_API::get_instance()->setup_params()->setup_hooks();
