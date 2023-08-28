<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

class WooCommerceBookings {
	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( class_exists( 'WC_Bookings' ) ) {
			$this->apply_currency = YayCurrencyHelper::detect_current_currency();
			add_filter( 'woocommerce_product_get_display_cost', array( $this, 'woocommerce_product_get_display_cost' ), 10, 2 );
			add_filter( 'woocommerce_bookings_calculated_booking_cost', array( $this, 'woocommerce_bookings_calculated_booking_cost' ), 10, 3 );
			add_filter( 'woocommerce_currency_symbol', array( $this, 'yay_currency_woocommerce_currency_symbol' ), 10, 2 );

		}
	}

	public function woocommerce_product_get_display_cost( $price, $product ) {
		$price = YayCurrencyHelper::calculate_price_by_currency( $price, false, $this->apply_currency );
		return $price;
	}
	public function woocommerce_bookings_calculated_booking_cost( $booking_cost, $product, $data ) {
		$booking_cost = YayCurrencyHelper::calculate_price_by_currency( $booking_cost, false, $this->apply_currency );
		return $booking_cost;
	}

	public function yay_currency_woocommerce_currency_symbol( $currency_symbol, $apply_currency ) {
		if ( wp_doing_ajax() ) {
			if ( isset( $_REQUEST['action'] ) && 'wc_bookings_calculate_costs' === $_REQUEST['action'] ) {
				$currency_symbol = wp_kses_post( html_entity_decode( $this->apply_currency['symbol'] ) );
			}
		}
		return $currency_symbol;
	}
}
