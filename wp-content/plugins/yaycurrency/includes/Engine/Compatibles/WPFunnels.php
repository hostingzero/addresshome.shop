<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Helpers\Helper;
use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

class WPFunnels {


	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {
		if ( ! defined( 'WPFNL_VERSION' ) ) {
			return;
		}

		$this->apply_currency = YayCurrencyHelper::detect_current_currency();

		add_filter( 'wpfunnels/order_bump_settings', array( $this, 'order_bump_settings' ), 10, 3 );

		add_filter( 'yay_currency_product_price_3rd_with_condition', array( $this, 'yay_wpfunnels_get_price' ), 9, 2 );

		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'woocommerce_cart_item_subtotal' ), 99, 3 );

		add_filter( 'woocommerce_cart_subtotal', array( $this, 'woocommerce_cart_subtotal' ), 10, 3 );

	}

	public function order_bump_settings( $ob_settings, $funnel_id, $checkout_id ) {
		$discount_price                  = $ob_settings[0]['discountPrice'];
		$ob_settings[0]['discountPrice'] = YayCurrencyHelper::calculate_price_by_currency( $discount_price, false, $this->apply_currency );
		return $ob_settings;
	}

	public function get_price_options_by_cart() {
		$cart_contents = WC()->cart->get_cart_contents();
		if ( count( $cart_contents ) > 0 ) {
			foreach ( $cart_contents  as $key => $cart_item ) {
				$product_obj   = $cart_item['data'];
				$product_price = $product_obj->get_price( 'edit' );
				if ( isset( $cart_item['wpfnl_order_bump'] ) && $cart_item['wpfnl_order_bump'] && isset( $cart_item['custom_price'] ) ) {
					$custom_price  = $cart_item['custom_price'];
					$product_price = YayCurrencyHelper::calculate_price_by_currency( $custom_price, false, $this->apply_currency );
				} else {
					$product_price = YayCurrencyHelper::calculate_price_by_currency( $product_price, false, $this->apply_currency );
				}
				$product_obj->yay_wpfunnels_price = $product_price;
			}
		}
	}

	public function yay_wpfunnels_get_price( $price, $product ) {
		$this->get_price_options_by_cart();
		if ( isset( $product->yay_wpfunnels_price ) ) {
			$price = $product->yay_wpfunnels_price;
		}
		return $price;
	}

	public function woocommerce_cart_item_subtotal( $product_subtotal, $cart_item, $cart_item_key ) {
		$product_obj      = $cart_item['data'];
		$wpfnl_order_bump = Helper::get_value_variable( $cart_item['wpfnl_order_bump'] );
		$custom_price     = Helper::get_value_variable( $cart_item['custom_price'] );
		$quantity         = $cart_item['quantity'];
		$line_subtotal    = ( $product_obj->get_price( 'edit' ) ) * $quantity;
		$line_subtotal    = YayCurrencyHelper::calculate_price_by_currency( $line_subtotal, false, $this->apply_currency );
		if ( $wpfnl_order_bump && $custom_price ) {
			$line_subtotal = YayCurrencyHelper::calculate_price_by_currency( $custom_price * $quantity, false, $this->apply_currency );
		}
		$product_subtotal = YayCurrencyHelper::calculate_custom_price_by_currency_html( $this->apply_currency, $line_subtotal );

		return $product_subtotal;
	}

	public function caculate_cart_subtotal( $cart_contents ) {
		$subtotal = 0;
		foreach ( $cart_contents  as $key => $cart_item ) {
			$product_obj      = $cart_item['data'];
			$product_price    = $product_obj->get_price( 'edit' );
			$wpfnl_order_bump = Helper::get_value_variable( $cart_item['wpfnl_order_bump'] );
			$custom_price     = Helper::get_value_variable( $cart_item['custom_price'] );
			$quantity         = $cart_item['quantity'];
			if ( is_checkout() ) {
				if ( $wpfnl_order_bump && $custom_price ) {
					$subtotal += YayCurrencyHelper::calculate_price_by_currency( $custom_price * $quantity, false, $this->apply_currency );
				} else {
					$subtotal += YayCurrencyHelper::calculate_price_by_currency( $product_price * $quantity, false, $this->apply_currency );
				}
			} else {
				if ( $wpfnl_order_bump && $custom_price ) {
					$subtotal += YayCurrencyHelper::calculate_price_by_currency( $custom_price * $quantity, false, $this->apply_currency );
				} else {
					$subtotal += YayCurrencyHelper::calculate_price_by_currency( $product_price * $quantity, false, $this->apply_currency );
				}
			}
		}
		return $subtotal;
	}

	public function woocommerce_cart_subtotal( $cart_subtotal, $compound, $cart ) {
		$cart_contents = WC()->cart->get_cart_contents();
		if ( count( $cart_contents ) > 0 ) {
			$subtotal      = $this->caculate_cart_subtotal( $cart_contents );
			$cart_subtotal = YayCurrencyHelper::calculate_custom_price_by_currency_html( $this->apply_currency, $subtotal );
		}
		return $cart_subtotal;
	}

}
