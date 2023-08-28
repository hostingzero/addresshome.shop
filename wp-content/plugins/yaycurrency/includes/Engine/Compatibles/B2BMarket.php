<?php

namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://marketpress.com/shop/plugins/woocommerce/b2b-market/

class B2BMarket {

	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( ! class_exists( 'BM' ) ) {
			return;
		}

		$this->apply_currency = YayCurrencyHelper::detect_current_currency();

		add_filter( 'bm_filter_rrp_price', array( $this, 'bm_filter_rrp_price' ), 10, 2 );
		add_filter( 'bm_filter_get_cheapest_price_update_price', array( $this, 'bm_filter_get_cheapest_price_update_price' ), 10, 5 );
		add_filter( 'bm_filter_bulk_price_discount_value', array( $this, 'bm_bulk_prices_discount_value' ), 10, 1 );
		add_filter( 'bm_filter_bulk_price_dynamic_generate_first_row', array( $this, 'bm_filter_bulk_price_dynamic_generate_first_row' ), 10, 5 );
		if ( is_ajax() ) {
			add_filter( 'bm_filter_get_price', array( $this, 'bm_filter_get_price' ), 10, 2 );
			add_filter( 'wc_price_args', array( $this, 'wc_price_args' ), 10, 1 );
		}

	}

	public function bm_filter_get_price( $price, $product ) {
		$price = YayCurrencyHelper::calculate_price_by_currency( $price, false, $this->apply_currency );
		return $price;
	}

	public function bm_filter_rrp_price( $rrp_price, $product_id ) {
		$converted_price = YayCurrencyHelper::calculate_price_by_currency( $rrp_price, false, $this->apply_currency );
		return $converted_price;
	}

	public function bm_filter_get_cheapest_price_update_price( $cheapest_price, $product_price, $product, $group_id, $qty ) {
		if ( $cheapest_price === $product_price ) {
			$converted_price = YayCurrencyHelper::calculate_price_by_currency( $cheapest_price, false, $this->apply_currency );
			return $converted_price;
		} else {
			return $cheapest_price;
		}

	}

	public function bm_bulk_prices_discount_value( $price ) {
		$price = YayCurrencyHelper::calculate_price_by_currency( $price, false, $this->apply_currency );
		return $price;
	}

	public function bm_filter_bulk_price_dynamic_generate_first_row( $temp_price, $price, $product, $group_id, $quantity ) {
		$converted_price = YayCurrencyHelper::calculate_price_by_currency( $temp_price, false, $this->apply_currency );
		return $converted_price;
	}

	public function wc_price_args( $args ) {
		$apply_currency = YayCurrencyHelper::detect_current_currency();
		if ( isset( $apply_currency['currency'] ) ) {
			$args['currency'] = $apply_currency['currency'];
		}
		return $args;
	}
}
