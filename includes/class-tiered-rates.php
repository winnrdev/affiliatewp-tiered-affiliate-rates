<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Tiered Rates business logic.
 *
 * @since 1.2
 */
class AffWP_Tiered_Rates {

	/**
	 * Registers callbacks.
	 *
	 * @since 1.2
	 */
	public static function init() {
		$instance = new self();

		if ( is_admin() ) {
			add_filter( 'affwp_affiliate_table_rate', array( $instance, 'filter_affiliate_table_rate' ), 10, 2 );
		}

		add_filter( 'affwp_get_affiliate_rate', array( $instance, 'get_tiered_affiliate_rate'  ), 10, 3 );
		add_filter( 'affwp_tiered_rates',       array( $instance, 'remove_disabled_rate_tiers' )        );
	}

	/**
	 * Retrieve the rate for a specific affiliate.
	 *
	 * @since 1.2
	 *
	 * @return int Affiliate rate as it corresponds to tiered rates.
	 */
	public function get_tiered_affiliate_rate( $rate, $affiliate_id, $type ) {

		$has_tiered_rate = false;

		$rates          = affwp_get_tiered_rates();
		$affiliate_rate = affiliate_wp()->affiliates->get_column( 'rate', $affiliate_id );

		$tiers_expire = affiliate_wp()->settings->get( 'rate-expiration', null );
		$tiers_expire = isset( $tiers_expire );

		if ( ! empty( $rates ) && empty( $affiliate_rate ) ) {
			// Start with highest tiers
			$rates = array_reverse( $rates );

			if ( $tiers_expire ) {
				$earnings  = affiliate_wp()->referrals->paid_earnings( 'month', $affiliate_id, false );
				$referrals = $this->get_paid_referrals_count( 'month', $affiliate_id );
			} else {
				$earnings  = affwp_get_affiliate_earnings( $affiliate_id, false );
				$referrals = affwp_get_affiliate_referral_count( $affiliate_id );
			}

			// Loop through the rates to see which applies to this affiliate
			foreach( $rates as $tiered_rate ) {

				if( empty( $tiered_rate['threshold'] ) || empty( $tiered_rate['rate'] ) ) {
					continue;
				}

				if( 'earnings' == $tiered_rate['type'] ) {

					if( $earnings >= affwp_sanitize_amount( $tiered_rate['threshold'] ) ) {

						$rate            = $tiered_rate['rate'];
						$has_tiered_rate = true;
						break;

					}

				} else {

					if( $referrals >= $tiered_rate['threshold'] ) {

						$rate            = $tiered_rate['rate'];
						$has_tiered_rate = true;
						break;

					}

				}

			}

			if ( $has_tiered_rate && 'percentage' == $type ) {
				// Sanitize the rate and ensure it's in the proper format
				if ( $rate > 0 ) {
					$rate = $rate / 100;
				}
			}

		}

		return $rate;
	}

	/**
	 * Filters the rate for an affiliate in the affiliates list table.
	 *
	 * @since 1.2
	 *
	 * @param int              $rate      The current affiliate rate.
	 * @param \AffWP\Affiliate $affiliate The current affiliate object.
	 *
	 * @return int The filtered affiliate rate.
	 */
	public function filter_affiliate_table_rate( $rate, $affiliate ) {

		// Get the default rate set instead of the passed rate.
		$rate = affiliate_wp()->settings->get( 'referral_rate', 20 );
		$rate = affwp_abs_number_round( $rate );

		$rates          = affwp_get_tiered_rates();
		$affiliate_rate = affiliate_wp()->affiliates->get_column( 'rate', $affiliate->affiliate_id );

		// Get the referral rate type.
		$type = affwp_get_affiliate_rate_type( $affiliate->affiliate_id );

		$tiers_expire = affiliate_wp()->settings->get( 'rate-expiration', null );
		$tiers_expire = isset( $tiers_expire );

		if ( ! empty( $rates ) && empty( $affiliate_rate ) ) {
			// Start with highest tiers
			$rates = array_reverse( $rates );

			if ( $tiers_expire ) {
				$earnings  = affiliate_wp()->referrals->paid_earnings( 'month', $affiliate->affiliate_id, false );
				$referrals = $this->get_paid_referrals_count( 'month', $affiliate->affiliate_id );
			} else {
				$earnings  = affwp_get_affiliate_earnings( $affiliate->affiliate_id, false );
				$referrals = affwp_get_affiliate_referral_count( $affiliate->affiliate_id );
			}

			// Loop through the rates to see which applies to this affiliate
			foreach( $rates as $tiered_rate ) {

				if( empty( $tiered_rate['threshold'] ) || empty( $tiered_rate['rate'] ) ) {
					continue;
				}

				if( 'earnings' == $tiered_rate['type'] ) {

					if( $earnings >= affwp_sanitize_amount( $tiered_rate['threshold'] ) ) {
						$rate = $tiered_rate['rate'];
						break;

					}

				} else {

					if( $referrals >= $tiered_rate['threshold'] ) {

						$rate = $tiered_rate['rate'];
						break;

					}

				}

			}

		} else {

			$affiliate_rate = affwp_abs_number_round( $affiliate_rate );

			$rate = ( null !== $affiliate_rate ) ? $affiliate_rate : $rate;

		}

		// Format percentage rates.
		$rate = ( 'percentage' === $type ) ? $rate / 100 : $rate;

		// Format the rate based on the type.
		$rate = affwp_format_rate( $rate, $type );

		return $rate;

	}

	/**
	 * Removes disabled rates tiers from consideration.
	 *
	 * @since 1.2
	 *
	 * @param array $rates Rate values.
	 * @return array Filtered rates
	 */
	public function remove_disabled_rate_tiers( $rates ) {

		$tab = empty( $_REQUEST['tab'] ) ? '' : sanitize_text_field( $_REQUEST['tab'] );

		// Bail if on rates edit screen.
		if ( is_admin() && ( ! empty( $_REQUEST['page'] ) || ! empty( $tab ) || 'affiliate-wp-settings' != $tab || 'rates' != $tab ) ) {
			return $rates;
		}

		foreach ( $rates as $index => $rate ) {
			if ( isset( $rate['disabled'] ) && 'on' === $rate['disabled'] ) {
				unset( $rates[ $index ] );
			}
		}

		return $rates;
	}

	/**
	 * Retrieves the paid referrals count for the given affiliate.
	 *
	 * @since 1.1
	 * @access public
	 *
	 * @param string $date         Date period to retrieve the referral count for.
	 * @param int    $affiliate_id Affiliate ID.
	 * @return int Number of paid referrals for the time period (based on now).
	 */
	public function get_paid_referrals_count( $date = '', $affiliate_id = 0 ) {
		$args = array(
			'affiliate_id' => absint( $affiliate_id ),
			'status'       => 'paid',
		);

		if ( ! empty( $date ) ) {
			switch ( $date ) {
				case 'month' :
					$date = array(
						'start' => date( 'Y-m-d H:i:s', strtotime( 'first day of', current_time( 'timestamp' ) ) ),
						'end'   => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
					);
					break;
			}
			$args['date'] = $date;
		}

		return affiliate_wp()->referrals->count( $args );
	}


}
