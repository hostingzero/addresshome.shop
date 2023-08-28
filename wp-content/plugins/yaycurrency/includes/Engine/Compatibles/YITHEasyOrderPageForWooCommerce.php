<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;


defined( 'ABSPATH' ) || exit;

// Link plugin: https://yithemes.com/themes/plugins/yith-easy-order-page-for-woocommerce/

class YITHEasyOrderPageForWooCommerce {

	use SingletonTrait;

	private $cookie_name;

	public function __construct() {

		if ( ! function_exists( 'yith_wceop_init' ) ) {
			return;
		}

		$this->cookie_name = YayCurrencyHelper::get_cookie_name();
		if ( wp_doing_ajax() ) {
			add_filter( 'woocommerce_cart_product_price', array( $this, 'custom_cart_product_price' ), 10, 2 );
			add_filter( 'woocommerce_cart_get_subtotal', array( $this, 'custom_cart_get_subtotal' ), 10, 1 );
		}
	}

	public function custom_cart_get_subtotal( $subtotal ) {
		$subtotal = 0;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$subtotal += YayCurrencyHelper::calculate_price_by_currency_cookie( $cart_item['data']->get_price( 'edit' ) ) * $cart_item['quantity'];
		}
		return $subtotal;
	}

	public function custom_cart_product_price( $price, $product ) {
		$converted_price = YayCurrencyHelper::calculate_price_by_currency_cookie( $product->get_price( 'edit' ) );
		$formatted_price = YayCurrencyHelper::format_price( $converted_price );
		return $formatted_price;
	}
}
