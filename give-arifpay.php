<?php
/**
 * Plugin Name: Give - Arifpay
 * Plugin URI: https://github.com/Arifpay-net/give-arifpay
 * Description: Process online donations via the Arifpay payment gateway.
 * Author: GiveWP
 * Author URI: ba5liel.github.io
 * Version:             1.0.0
 * Requires at least:   5.0
 * Requires PHP:        7.0
 * Text Domain: give-arifpay
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/Arifpay-net/give-arifpay
 */


if ( ! class_exists( 'Give_Arifpay_Gateway' ) ) {
	/**
	 * Class Give_Arifpay_Gateway
	 *
	 * @since 1.0
	 */
	final class Give_Arifpay_Gateway {

		/**
		 * @since  1.0
		 * @access static
		 * @var Give_Arifpay_Gateway $instance
		 */
		static private $instance;

		/**
		 * Notices (array)
		 *
		 * @since 1.0
		 *
		 * @var array
		 */
		public $notices = array();

		/**
		 * Get instance
		 *
		 * @since  1.0
		 * @access static
		 * @return Give_Arifpay_Gateway|static
		 */
		static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
				self::$instance->setup();
			}

			return self::$instance;
		}

		/**
		 * Setup Give Arifpay.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function setup() {

			// Setup constants.
			$this->setup_constants();

			// Give init hook.
			add_action( 'give_init', array( $this, 'init' ), 10 );
			add_action( 'admin_init', array( $this, 'check_environment' ), 999 );
			add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
		}


		/**
		 * Setup constants.
		 *
		 * @since  1.0
		 * @access public
		 * @return Give_Arifpay_Gateway
		 */
		public function setup_constants() {
			// Global Params.
			define( 'GIVE_APAY_VERSION', '1.0.8' );
			define( 'GIVE_APAY_MIN_GIVE_VER', '2.7.0' );
			define( 'GIVE_APAY_BASENAME', plugin_basename( __FILE__ ) );
			define( 'GIVE_APAY_URL', plugins_url( '/', __FILE__ ) );
			define( 'GIVE_APAY_DIR', plugin_dir_path( __FILE__ ) );

			return self::$instance;
		}

		/**
		 * Load files.
		 *
		 * @since  1.0
		 * @access public
		 * @return Give_Arifpay_Gateway
		 */
		public function init() {
			
			if (is_readable(__DIR__ . '/vendor/autoload.php')) {
				require __DIR__ . '/vendor/autoload.php';
			}
			if ( ! $this->get_environment_warning() ) {
				return;
			}

			$this->load_textdomain();
			$this->licensing();
			$this->activation_banner();

			require_once GIVE_APAY_DIR . 'includes/admin/plugin-activation.php';

			// Load helper functions.
			require_once GIVE_APAY_DIR . 'includes/functions.php';

			// Load plugin settings.
			require_once GIVE_APAY_DIR . 'includes/admin/admin-settings.php';

			// Process payments.
			require_once GIVE_APAY_DIR . 'includes/payment-processing.php';

			require_once GIVE_APAY_DIR . 'includes/lib/class-give-arifpay-api.php';

			require_once GIVE_APAY_DIR . 'includes/filters.php';

			require_once GIVE_APAY_DIR . 'includes/actions.php';

			if ( is_admin() ) {
				// Add actions.
				require_once GIVE_APAY_DIR . 'includes/admin/actions.php';
			}

			return self::$instance;
		}

		/**
		 * Load the text domain.
		 *
		 * @access private
		 * @since  1.0
		 *
		 * @return void
		 */
		public function load_textdomain() {

			// Set filter for plugin's languages directory.
			$give_arifpay_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$give_arifpay_lang_dir = apply_filters( 'give_arifpay_languages_directory', $give_arifpay_lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'give-arifpay' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'give-arifpay', $locale );

			// Setup paths to current locale file
			$mofile_local  = $give_arifpay_lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/give-arifpay/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/give-arifpay folder
				load_textdomain( 'give-arifpay', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/give-arifpay/languages/ folder
				load_textdomain( 'give-arifpay', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'give-arifpay', false, $give_arifpay_lang_dir );
			}

		}

		/**
		 * Implement Give Licensing for Give Arifpay Add On.
		 *
		 * @since  1.0.2
		 * @access private
		 */
		private function licensing() {
			if ( class_exists( 'Give_License' ) ) {
				new Give_License( __FILE__, 'Arifpay Gateway', GIVE_APAY_VERSION, 'WordImpress' );
			}
		}

		/**
		 * Check plugin environment.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return bool
		 */
		public function check_environment() {
			// Flag to check whether plugin file is loaded or not.
			$is_working = true;

			// Load plugin helper functions.
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}

			/*
			 Check to see if Give is activated, if it isn't deactivate and show a banner. */
			// Check for if give plugin activate or not.
			$is_give_active = defined( 'GIVE_PLUGIN_BASENAME' ) ? is_plugin_active( GIVE_PLUGIN_BASENAME ) : false;

			if ( empty( $is_give_active ) ) {
				// Show admin notice.
				$this->add_admin_notice( 'prompt_give_activate', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">Give</a> plugin installed and activated for Give - Arifpay to activate.', 'give-arifpay' ), 'https://givewp.com' ) );
				$is_working = false;
			}

			return $is_working;
		}

		/**
		 * Check plugin for Give environment.
		 *
		 * @since  1.1.2
		 * @access public
		 *
		 * @return bool
		 */
		public function get_environment_warning() {
			// Flag to check whether plugin file is loaded or not.
			$is_working = true;

			// Verify dependency cases.
			if (
				defined( 'GIVE_VERSION' )
				&& version_compare( GIVE_VERSION, GIVE_APAY_MIN_GIVE_VER, '<' )
			) {

				/*
				 Min. Give. plugin version. */
				// Show admin notice.
				$this->add_admin_notice( 'prompt_give_incompatible', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%1$s" target="_blank">Give</a> core version %2$s for the Give - Arifpay add-on to activate.', 'give-arifpay' ), 'https://givewp.com', GIVE_APAY_MIN_GIVE_VER ) );

				$is_working = false;
			}

			return $is_working;
		}

		/**
		 * Allow this class and other classes to add notices.
		 *
		 * @since 1.0
		 *
		 * @param $slug
		 * @param $class
		 * @param $message
		 */
		public function add_admin_notice( $slug, $class, $message ) {
			$this->notices[ $slug ] = array(
				'class'   => $class,
				'message' => $message,
			);
		}

		/**
		 * Display admin notices.
		 *
		 * @since 1.0
		 */
		public function admin_notices() {

			$allowed_tags = array(
				'a'      => array(
					'href'  => array(),
					'title' => array(),
					'class' => array(),
					'id'    => array(),
				),
				'br'     => array(),
				'em'     => array(),
				'span'   => array(
					'class' => array(),
				),
				'strong' => array(),
			);

			foreach ( (array) $this->notices as $notice_key => $notice ) {
				echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
				echo wp_kses( $notice['message'], $allowed_tags );
				echo '</p></div>';
			}

		}

		/**
		 * Show activation banner for this add-on.
		 *
		 * @since 1.0
		 */
		public function activation_banner() {

			// Check for activation banner inclusion.
			if (
				! class_exists( 'Give_Addon_Activation_Banner' )
				&& file_exists( GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php' )
			) {
				include GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php';
			}

			// Initialize activation welcome banner.
			if ( class_exists( 'Give_Addon_Activation_Banner' ) ) {

				// Only runs on admin.
				$args = array(
					'file'              => __FILE__,
					'name'              => esc_html__( 'Arifpay Gateway', 'give-arifpay' ),
					'version'           => GIVE_APAY_VERSION,
					'settings_url'      => admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=arifpay' ),
					'documentation_url' => 'http://developer.arifpay.net/docs/wordpress/give',
					'support_url'       => 'https://github.com/Arifpay-net/give-arifpay',
					'testing'           => false, // Never leave true.
				);
				new Give_Addon_Activation_Banner( $args );
			}
		}
	}

	function Give_Arifpay_Gateway() {
		return Give_Arifpay_Gateway::get_instance();
	}

	/**
	 * Returns class object instance.
	 *
	 * @since 1.3
	 *
	 * @return Give_Arifpay_Gateway bool|object
	 */
	Give_Arifpay_Gateway();
}
