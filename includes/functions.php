<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieves the tiered rates.
 *
 * @since 1.2
 *
 * @return array
 */
function affwp_get_tiered_rates() {
	$rates = affiliate_wp()->settings->get( 'rates', array() );

	/**
	 * Filters tiered rate values.
	 *
	 * @since 1.0
	 *
	 * @param array $rate_values Rate values.
	 */
	return apply_filters( 'affwp_tiered_rates', array_values( $rates ) );
}
