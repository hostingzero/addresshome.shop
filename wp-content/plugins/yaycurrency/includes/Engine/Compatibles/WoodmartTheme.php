<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

class WoodmartTheme {
	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( 'woodmart' === wp_get_theme()->template ) {
			$this->apply_currency = YayCurrencyHelper::detect_current_currency();
			add_filter( 'yay_currency_get_price_by_currency', array( $this, 'get_round_price_by_currency' ), 10, 3 );
			add_filter( 'yay_currency_calculated_total_again', array( $this, 'yay_currency_calculated_total_again' ) );
			add_filter( 'woocommerce_cart_subtotal', array( $this, 'woocommerce_cart_subtotal' ), 9999, 3 );
			if ( wp_doing_ajax() ) {
				add_filter( 'woocommerce_cart_item_price', array( $this, 'custom_cart_item_price_mini_cart' ), 10, 3 );
				add_filter( 'woocommerce_cart_subtotal', array( $this, 'custom_cart_subtotal_mini_cart' ), 10, 3 );
			}

			add_filter( 'woocommerce_get_price_html', array( $this, 'custom_woocommerce_get_price_html' ), 20, 2 );
			add_filter( 'wc_price_args', array( $this, 'change_wc_price_args' ), 9999, 1 );
			add_filter( 'woodmart_get_product_bundle_discount', array( $this, 'custom_woocommerce_get_product_bundle_price' ), 20, 1 );
			add_filter( 'woodmart_get_product_bundle_old_price', array( $this, 'custom_woocommerce_get_product_bundle_price' ), 20, 1 );
		}

	}

	public function change_wc_price_args( $args ) {
		$data_actions = array( 'woodmart_quick_shop', 'woodmart_update_frequently_bought_price' );
		if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $data_actions, true ) ) {
			$args = array(
				'ex_tax_label'       => false,
				'currency'           => $this->apply_currency['currency'],
				'decimal_separator'  => $this->apply_currency['decimalSeparator'],
				'thousand_separator' => $this->apply_currency['thousandSeparator'],
				'decimals'           => $this->apply_currency['numberDecimal'],
				'price_format'       => YayCurrencyHelper::format_currency_symbol( $this->apply_currency ),
			);
		}
		return $args;
	}


	public function get_round_price( $price ) {
		if ( function_exists( 'round_price_product' ) ) {
			// Return rounded price
			return ceil( $price );
		}

		return $price;
	}

	public function yay_currency_calculated_total_again() {
		return true;
	}

	public function get_round_price_by_currency( $price, $product, $apply_currency ) {
		return $this->get_round_price( $price );
	}

	public function woocommerce_cart_subtotal( $cart_subtotal, $compound, $cart ) {
		WC()->cart->calculate_totals();
		return $cart_subtotal;
	}

	public function custom_cart_item_price_mini_cart( $price, $cart_item, $cart_item_key ) {
		if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'woodmart_ajax_add_to_cart' === $_REQUEST['action'] ) {
			$product_price = apply_filters( 'yay_currency_get_cart_item_price', 0, $cart_item, $this->apply_currency );
			$price         = YayCurrencyHelper::format_price( $product_price );
		}
		return $price;
	}

	public function custom_cart_subtotal_mini_cart( $cart_subtotal, $compound, $cart ) {
		if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'woodmart_ajax_add_to_cart' === $_REQUEST['action'] ) {
			$subtotal      = apply_filters( 'yay_currency_get_cart_subtotal', 0, $this->apply_currency );
			$cart_subtotal = YayCurrencyHelper::format_price( $subtotal );
		}
		return $cart_subtotal;
	}

	public function custom_woocommerce_get_price_html( $price_html, $product ) {
		$data_actions = array( 'woodmart_quick_shop', 'woodmart_update_frequently_bought_price' );
		if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $data_actions, true ) ) {
			$product_price = $product->get_price( 'edit' );
			$product_price = YayCurrencyHelper::calculate_price_by_currency( $product_price, false, $this->apply_currency );
			$price_html    = YayCurrencyHelper::format_price( $product_price );
		}

		return $price_html;

	}

	public function custom_woocommerce_get_product_bundle_price( $price ) {
		$data_actions = array( 'woodmart_update_frequently_bought_price' );
		if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $data_actions, true ) ) {
			$price = YayCurrencyHelper::calculate_price_by_currency( $price, false, $this->apply_currency );
		}

		return $price;
	}
}
