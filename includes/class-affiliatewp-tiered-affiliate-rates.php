<?php
/**
 * Core: Plugin Bootstrap
 *
 * @package     AffiliateWP Plugin Template
 * @subpackage  Core
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Boots up Tiered Rates.
 *
 * @since 1.0
 */
final class AffiliateWP_Tiered_Rates {

	/**
	 * Holds the instance.
	 *
	 * Ensures that only one instance of AffiliateWP_Affiliate_Portal exists in memory at any one
	 * time and it also prevents needing to define globals all over the place.
	 *
	 * TL;DR This is a static property property that holds the singleton instance.
	 *
	 * @access private
	 * @var    \AffiliateWP_Tiered_Rates
	 * @static
	 *
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * The version number.
	 *
	 * @access private
	 * @since  1.0.0
	 * @var    string
	 */
	private $version = '1.2';

	/**
	 * Main plugin file.
	 *
	 * @since 1.2
	 * @var   string
	 */
	private $file = '';

	/**
	 * Main AffiliateWP_Tiered_Rates Instance
	 *
	 * Insures that only one instance of AffiliateWP_Tiered_Rates exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0.0
	 * @static
	 *
	 * @param string $file Main plugin file.
	 * @return AffiliateWP_Tiered_Rates The one true AffiliateWP_Tiered_Rates Plugin instance.
	 */
	public static function instance( $file = '' ) {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof AffiliateWP_Tiered_Rates ) ) {
			self::$instance = new AffiliateWP_Tiered_Rates;
			self::$instance->file = $file;

			self::$instance->setup_constants();
			self::$instance->load_textdomain();
			self::$instance->includes();
			self::$instance->init();

		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'affiliate-wp-tiered' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'affiliate-wp-tiered' ), '1.0' );
	}

	/**
	 * Sets up plugin constants.
	 *
	 * @since 1.2
	 */
	private function setup_constants() {
		// Plugin version.
		if ( ! defined( 'AFFWP_TR_VERSION' ) ) {
			define( 'AFFWP_TR_VERSION', $this->version );
		}

		// Plugin Folder Path.
		if ( ! defined( 'AFFWP_TR_PLUGIN_DIR' ) ) {
			define( 'AFFWP_TR_PLUGIN_DIR', plugin_dir_path( $this->file ) );
		}

		// Plugin Folder URL.
		if ( ! defined( 'AFFWP_TR_PLUGIN_URL' ) ) {
			define( 'AFFWP_TR_PLUGIN_URL', plugin_dir_url( $this->file ) );
		}

		// Plugin Root File.
		if ( ! defined( 'AFFWP_TR_PLUGIN_FILE' ) ) {
			define( 'AFFWP_TR_PLUGIN_FILE', $this->file );
		}
	}

	/**
	 * Loads the plugin language files
	 *
	 * @since 1.0
	 */
	public function load_textdomain() {

		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( $this->file ) ) . '/languages/';
		$lang_dir = apply_filters( 'aff_wp_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'affiliate-wp-tiered' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'affiliate-wp-tiered', $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/affiliate-wp-tiered/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/affiliate-wp-tiered/ folder
			load_textdomain( 'affiliate-wp-tiered', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/affiliate-wp-tiered/languages/ folder
			load_textdomain( 'affiliate-wp-tiered', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'affiliate-wp-tiered', false, $lang_dir );
		}
	}

	/**
	 * Include required files
	 *
	 * @since 1.0
	 */
	private function includes() {

		require_once AFFWP_TR_PLUGIN_DIR . 'includes/functions.php';
		require_once AFFWP_TR_PLUGIN_DIR . 'includes/class-tiered-rates.php';

		if ( is_admin() ) {
			require_once AFFWP_TR_PLUGIN_DIR . 'includes/admin/rates.php';
		}

	}

	/**
	 * Initialize the bootstrap.
	 *
	 * @since 1.0
	 */
	private function init() {
		AffWP_Tiered_Rates::init();

		if ( is_admin() ) {
			self::$instance->updater();
		}
	}

	/**
	 * Attempts to run the updater script if it exists.
	 *
	 * @since 1.0
	 */
	public function updater() {
		if ( class_exists( 'AffWP_AddOn_Updater' ) ) {
			$updater = new AffWP_AddOn_Updater( 368, $this->file, $this->version );
		}
	}

	/**
	 * Retrieve the tiered rates
	 *
	 * @since 1.0
	 * @since 1.2 Refactored to wrap a new affwp_get_tiered_rates() function
	 *
	 * @return array Tiered rates.
	 */
	public function get_rates() {
		return affwp_get_tiered_rates();
	}

	/**
	 * Removes disabled rates from consideration.
	 *
	 * @since 1.1
	 * @deprecated 1.2 Use AffWP_Tiered_Rates::remove_disabled_rate_tiers() instead
	 *
	 * @param array $rates Rate values.
	 * @return array Filtered rates
	 */
	public function remove_disabled_rates( $rates ) {
		_deprecated_function( __METHOD__, '1.2', 'AffWP_Tiered_Rates::remove_disabled_rate_tiers()' );

		return ( new AffWP_Tiered_Rates )->remove_disabled_rate_tiers( $rates );
	}

	/**
	 * Retrieves the tiered rate for a specific affiliate.
	 *
	 * @since 1.0
	 * @deprecated 1.2 Use AffWP_Tiered_Rates::get_tiered_affiliate_rate() instead
	 *
	 * @return int Tiered affiliate rate.
	 */
	public function get_affiliate_rate( $rate, $affiliate_id, $type ) {
		_deprecated_function( __METHOD__, '1.2', 'AffWP_Tiered_Rates::get_tiered_affiliate_rate()' );

		return ( new AffWP_Tiered_Rates )->get_tiered_affiliate_rate( $rate, $affiliate_id, $type );
	}

	/**
	 * Filters the rate for an affiliate in the affiliates list table.
	 *
	 * @since 1.1.2
	 * @deprecated 1.2 Use AffWP_Tiered_Rates::filter_affiliate_table_rate() instead
	 *
	 * @param int              $rate      The current affiliate rate.
	 * @param \AffWP\Affiliate $affiliate The current affiliate object.
	 *
	 * @return int The filtered affiliate rate
	 */
	public function affiliate_table_rate( $rate, $affiliate ) {
		_deprecated_function( __METHOD__, '1.2', 'AffWP_Tiered_Rates::filter_affiliate_table_rate()' );

		return ( new AffWP_Tiered_Rates )->filter_affiliate_table_rate( $rate, $affiliate );
	}

	/**
	 * Retrieves the paid referrals count for the given affiliate.
	 *
	 * @since 1.1
	 * @deprecated 1.2 Use AffWP_Tiered_Rates::get_paid_referrals_count() instead
	 *
	 * @param string $date         Date period to retrieve the referral count for.
	 * @param int    $affiliate_id Affiliate ID.
	 * @return int Number of paid referrals for the time period (based on now).
	 */
	public function paid_count( $date = '', $affiliate_id = 0 ) {
		_deprecated_function( __METHOD__, '1.2', 'AffWP_Tiered_Rates::get_paid_referrals_count()' );

		return ( new AffWP_Tiered_Rates() )->get_paid_referrals_count( $date, $affiliate_id );
	}

}

/**
 * The main function responsible for returning the one true AffiliateWP_Tiered_Rates
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $AffiliateWP_Tiered_Rates = affiliate_wp_tiers(); ?>
 *
 * @since 1.0
 *
 * @return AffiliateWP_Tiered_Rates The one true plugin instance.
 */
function affiliate_wp_tiers() {
	return AffiliateWP_Tiered_Rates::instance();
}
