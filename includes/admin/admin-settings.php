<?php

/**
 * Class Give_Arifpay_Gateway_Settings
 *
 * @since 1.0
 */
class Give_Arifpay_Gateway_Settings {
	/**
	 * @since  1.0
	 * @access static
	 * @var Give_Arifpay_Gateway_Settings $instance
	 */
	static private $instance;

	/**
	 * @since  1.0
	 * @access private
	 * @var string $section_id
	 */
	private $section_id;

	/**
	 * @since  1.0
	 * @access private
	 * @var string $section_label
	 */
	private $section_label;

	/**
	 * Give_Arifpay_Gateway_Settings constructor.
	 */
	private function __construct() {
	}

	/**
	 * get class object.
	 *
	 * @since 1.0
	 * @return Give_Arifpay_Gateway_Settings
	 */
	static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Setup hooks.
	 *
	 * @since 1.0
	 */
	public function setup_hooks() {
		$this->section_id    = 'arifpay';
		$this->section_label = __( 'Arifpay', 'give-arifpay' );

		// Add payment gateway to payment gateways list.
		add_filter( 'give_payment_gateways', array( $this, 'add_gateways' ) );

		if ( is_admin() ) {

			// Add section to payment gateways tab.
			add_filter( 'give_get_sections_gateways', array( $this, 'add_section' ) );

			// Add section settings.
			add_filter( 'give_get_settings_gateways', array( $this, 'add_settings' ) );
		}
	}

	/**
	 * Add payment gateways to gateways list.
	 *
	 * @since 1.0
	 *
	 * @param array $gateways array of payment gateways.
	 *
	 * @return array
	 */
	public function add_gateways( $gateways ) {
		$gateways[ $this->section_id ] = array(
			'admin_label'    => __( 'Arifpay - India', 'give-arifpay' ),
			'checkout_label' => give_arifpay_get_payment_method_label(),
			'admin_tooltip'  => __( 'Only ETH currency is supported by Arifpay. Hence, the Indian Arifpay accounts are only supported.', 'give-arifpay' ),
		);

		return $gateways;
	}

	/**
	 * Add setting section.
	 *
	 * @since 1.0
	 *
	 * @param array $sections Array of section.
	 *
	 * @return array
	 */
	public function add_section( $sections ) {
		$sections[ $this->section_id ] = $this->section_label;

		return $sections;
	}

	/**
	 * Add plugin settings.
	 *
	 * @since 1.0
	 *
	 * @param array $settings Array of setting fields.
	 *
	 * @return array
	 */
	public function add_settings( $settings ) {
		$current_section = give_get_current_setting_section();

		if ( $this->section_id === $current_section ) {
			$settings = array(
				array(
					'id'   => 'give_arifpay_payments_setting',
					'type' => 'title',
				),
				array(
					'title'       => __( 'Evniroment', 'give-arifpay' ),
					'id'          => 'give_arifpay_environment',
					'type'        => 'radio_inline',
					'options'     => array(
						'Production' => esc_html__( 'production', 'give-arifpay' ),
						'Sandbox'   => esc_html__( 'sandbox', 'give-arifpay' ),
					),
					'default'     => 'sandbox',
					'description' => __( 'This option will help you select the enviroment you\'re planing to use.', 'give-arifpay' ),
				),
				array(
					'title' => __( 'Production API Key', 'give-arifpay' ),
					'id'    => 'arifpay_production_api_key',
					'type'  => 'text',
					'desc'  => __( 'The LIVE Merchant ID provided by arifpay. Required for testing donation payments in LIVE mode.', 'give-arifpay' ),
				),
				array(
					'title' => __( 'Live Merchant Salt', 'give-arifpay' ),
					'id'    => 'arifpay_live_salt_key',
					'type'  => 'api_key',
					'desc'  => __( 'The LIVE Merchant Salt provided by arifpay. Required for testing donation payments in LIVE mode.', 'give-arifpay' ),
				),
				array(
					'title' => __( 'Sandbox Merchant Key', 'give-arifpay' ),
					'id'    => 'arifpay_sandbox_api_key',
					'type'  => 'text',
					'desc'  => __( 'The TEST Merchant Key provided by arifpay. Required for testing donation payments in TEST mode.', 'give-arifpay' ),
				),
				array(
					'title' => __( 'Sandbox Merchant Salt', 'give-arifpay' ),
					'id'    => 'arifpay_sandbox_salt_key',
					'type'  => 'api_key',
					'desc'  => __( 'The TEST Merchant Salt provided by arifpay. Required for testing donation payments in TEST mode.', 'give-arifpay' ),
				),
				array(
					'title'       => __( 'Collect Billing Details', 'give-arifpay' ),
					'id'          => 'arifpay_billing_details',
					'type'        => 'radio_inline',
					'options'     => array(
						'enabled'  => esc_html__( 'Enabled', 'give-arifpay' ),
						'disabled' => esc_html__( 'Disabled', 'give-arifpay' ),
					),
					'default'     => 'disabled',
					'description' => __( 'This option will enable the billing details section for Arifpay which requires the donor\'s address to complete the donation. These fields are not required by Arifpay to process the transaction, but you may have the need to collect the data.', 'give-arifpay' ),
				),
				array(
					'title'   => __( 'Show Phone Field', 'give-arifpay' ),
					'id'      => 'arifpay_phone_field',
					'type'    => 'radio_inline',
					'desc'    => __( 'This setting will display a phone number field on the donation form (NOTE: this field is necessary for Arifpay).', 'give-arifpay' ),
					'default' => 'enabled',
					'options' => array(
						'enabled'  => __( 'Enabled', 'give-arifpay' ),
						'disabled' => __( 'Disabled', 'give-arifpay' ),
					),
				),
				array(
					'id'   => 'give_arifpay_payments_setting',
					'type' => 'sectionend',
				),
			);
		}// End if().

		return $settings;
	}
}

Give_Arifpay_Gateway_Settings::get_instance()->setup_hooks();
