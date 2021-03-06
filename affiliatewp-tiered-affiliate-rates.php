<?php
/**
 * Plugin Name: AffiliateWP - Tiered Rates
 * Plugin URI: http://affiliatewp.com/addons/tiered-affiliate-rates/
 * Description: Tiered affiliate rates for AffiliateWP
 * Author: Sandhills Development, LLC
 * Author URI: https://sandhillsdev.com
 * Version: 1.2
 * Text Domain: affiliate-wp-tiered
 * Domain Path: languages
 *
 * AffiliateWP is distributed under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * AffiliateWP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AffiliateWP. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package AffiliateWP Tiered Rates
 * @category Core
 * @author Pippin Williamson
 * @version 1.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AffiliateWP_Requirements_Check' ) ) {
	require_once dirname( __FILE__ ) . '/includes/lib/affwp/class-affiliatewp-requirements-check.php';
}

/**
 * Class used to check requirements for and bootstrap the plugin.
 *
 * @since 1.2
 *
 * @see Affiliate_WP_Requirements_Check
 */
class AffiliateWP_TR_Requirements_Check extends AffiliateWP_Requirements_Check {

	/**
	 * Plugin slug.
	 *
	 * @since 1.2
	 * @var   string
	 */
	protected $slug = 'affiliatewp-tiered-affiliate-rates';

	/**
	 * Add-on requirements.
	 *
	 * @since 1.2
	 * @var   array[]
	 */
	protected $addon_requirements = array(
		// AffiliateWP.
		'affwp' => array(
			'minimum' => '2.6',
			'name'    => 'AffiliateWP',
			'exists'  => true,
			'current' => false,
			'checked' => false,
			'met'     => false
		),
	);

	/**
	 * Bootstrap everything.
	 *
	 * @since 1.2
	 */
	public function bootstrap() {
		if ( ! class_exists( 'Affiliate_WP' ) ) {

			if ( ! class_exists( 'AffiliateWP_Activation' ) ) {
				require_once 'includes/lib/affwp/class-affiliatewp-activation.php';
			}

			// AffiliateWP activation
			if ( ! class_exists( 'Affiliate_WP' ) ) {
				$activation = new AffiliateWP_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
				$activation = $activation->run();
			}
		} else {
			\AffiliateWP_Tiered_Rates::instance( __FILE__ );
		}
	}

	/**
	 * Loads the add-on.
	 *
	 * @since 1.2
	 */
	protected function load() {
		// Maybe include the bundled bootstrapper.
		if ( ! class_exists( 'AffiliateWP_Tiered_Rates' ) ) {
			require_once dirname( __FILE__ ) . '/includes/class-affiliatewp-tiered-affiliate-rates.php';
		}

		// Maybe hook-in the bootstrapper.
		if ( class_exists( 'AffiliateWP_Tiered_Rates' ) ) {

			$affwp_version = get_option( 'affwp_version' );

			if ( version_compare( $affwp_version, '2.7', '<' ) ) {
				add_action( 'plugins_loaded', array( $this, 'bootstrap' ), 100 );
			} else {
				add_action( 'affwp_plugins_loaded', array( $this, 'bootstrap' ), 100 );
			}

			// Register the activation hook.
			register_activation_hook( __FILE__, array( $this, 'install' ) );
		}
	}

	/**
	 * Install, usually on an activation hook.
	 *
	 * @since 1.2
	 */
	public function install() {
		// Bootstrap to include all of the necessary files
		$this->bootstrap();

		if ( defined( 'AFFWP_TR_VERSION' ) ) {
			update_option( 'affwp_tr_version', AFFWP_TR_VERSION );
		}
	}

	/**
	 * Plugin-specific aria label text to describe the requirements link.
	 *
	 * @since 1.2
	 *
	 * @return string Aria label text.
	 */
	protected function unmet_requirements_label() {
		return esc_html__( 'AffiliateWP - Tiered Affiliate Rates Requirements', 'affiliate-wp-tiered' );
	}

	/**
	 * Plugin-specific text used in CSS to identify attribute IDs and classes.
	 *
	 * @since 1.2
	 *
	 * @return string CSS selector.
	 */
	protected function unmet_requirements_name() {
		return 'affiliatewp-tiered-affiliate-rates-requirements';
	}

	/**
	 * Plugin specific URL for an external requirements page.
	 *
	 * @since 1.2
	 *
	 * @return string Unmet requirements URL.
	 */
	protected function unmet_requirements_url() {
		return 'https://docs.affiliatewp.com/article/2361-minimum-requirements-roadmaps';
	}

}

$requirements = new AffiliateWP_TR_Requirements_Check( __FILE__ );

$requirements->maybe_load();

function calc_per_order_referral_amount( $referral_amount, $affiliate_id, $amount, $reference, $product_id, $context ){

	
	//get_product_category
	$pro_cat = null;
	$cat = wp_get_object_terms( $product_id, 'product_cat' , array( 'fields' => 'slugs' ) ) ;
	if( !empty( $cat ) ){
		foreach ($cat as $key => $value) {
			$pro_cat = $value;
			break;
		}
	}

	//current user
	$current_user = affwp_get_affiliate( $affiliate_id ) -> user_id;

	//rates
	$rates = affwp_get_tiered_rates();

	$selected_cat = [];
	$ranges = [];
	foreach ($rates as $key => $value) {
		$selected_cat[] = $value['product'];
		$ranges[ $value['product'] ][ $key ] = $value['threshold'];
	}
	
	// create range of threshold
	foreach( $ranges as $k => $v ){
		
		$min = 0;
		foreach( $v as $kn => $kv ){

			$max = $kv;
			
			$rates[$kn]['min_th'] = $min;
			$rates[$kn]['max_th'] = $max;

			$min = $kv;
		}
	}

	

	if( in_array( $pro_cat, $selected_cat ) ){
		$total_refer = get_user_meta( $current_user, "affwp_tiered_".$value['product'], true );
		if( !empty( $total_refer ) && is_numeric( $total_refer ) ){
			$total_refer = $total_refer + 1;
		}else{
			$total_refer = 1;	
		}
		
		update_user_meta( $current_user, "affwp_tiered_".$value['product'], $total_refer );

		if( !empty( $rates ) && is_array( $rates ) ){ 
			foreach ($rates as $key => $value) {
				if( $value['type'] == "referrals" && $value['product'] == $pro_cat && empty( $value['disabled'] ) && empty($value['disabled']) && ( $total_refer > $value['min_th'] && $total_refer <= $value['max_th'] ) ){					
					if( !empty( $value['rate'] ) &&  is_numeric( $value['rate'] ) && !empty( $amount ) && is_numeric( $amount ) ){
						$referral_amount =  $amount / $value['rate'] ;	
						pr( $value );
					}
				}
			}
		}
	}
	
	return $referral_amount;

}