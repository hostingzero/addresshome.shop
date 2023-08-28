<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;

use Yay_Currency\Helpers\YayCurrencyHelper;


defined( 'ABSPATH' ) || exit;

class JulyTheme {

	use SingletonTrait;

	public function __construct() {
		// Compatible with July theme
		if ( 'july' === wp_get_theme()->template ) {
			add_filter( 'woocommerce_cart_get_subtotal', array( $this, 'custom_cart_subtotal' ), 1000 );
			add_filter( 'woocommerce_variable_price_html', array( $this, 'custom_variable_price' ), 10, 2 );
			add_filter( 'formatted_woocommerce_price', array( $this, 'formatted_woocommerce_price' ), 10, 6 );
		}

	}

	public function custom_cart_subtotal( $subtotal ) {
		$subtotal = 0;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$subtotal += YayCurrencyHelper::calculate_price_by_currency_cookie( $cart_item['data']->get_price( 'edit' ) ) * $cart_item['quantity'];
		}
		return $subtotal;
	}

	public function custom_variable_price( $price, $product ) {
		$converted_price = YayCurrencyHelper::calculate_price_by_currency_cookie( $product->get_price( 'edit' ) );
		$formatted_price = YayCurrencyHelper::format_price( $converted_price );
		return $formatted_price;
	}

	public function formatted_woocommerce_price( $price_format, $price, $decimals, $decimal_separator, $thousand_separator, $original_price ) {
		$apply_currency = YayCurrencyHelper::detect_current_currency();
		if ( isset( $apply_currency['currency'] ) ) {
			return number_format( $price, $apply_currency['numberDecimal'], $apply_currency['decimalSeparator'], $apply_currency['thousandSeparator'] );
		}
		return $price_format;
	}

}
