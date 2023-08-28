<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\Helper;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

class BeTheme {

	use SingletonTrait;
	private $apply_currency = array();
	public function __construct() {

		if ( 'betheme' !== Helper::get_current_theme() ) {
			return;
		}

		$this->apply_currency = YayCurrencyHelper::detect_current_currency();

		add_filter( 'woocommerce_cart_item_price', array( $this, 'custom_cart_item_price_mini_cart' ), 10, 3 );
		add_filter( 'woocommerce_cart_subtotal', array( $this, 'custom_cart_subtotal_mini_cart' ), 10, 3 );

	}

	public function custom_cart_item_price_mini_cart( $price, $cart_item, $cart_item_key ) {
		if ( wp_doing_ajax() && isset( $_REQUEST['wc-ajax'] ) && 'get_refreshed_fragments' === $_REQUEST['wc-ajax'] ) {
			$product_price = apply_filters( 'yay_currency_get_cart_item_price', 0, $cart_item, $this->apply_currency );
			$price         = YayCurrencyHelper::format_price( $product_price );
		}
		return $price;
	}

	public function custom_cart_subtotal_mini_cart( $cart_subtotal, $compound, $cart ) {
		if ( wp_doing_ajax() && isset( $_REQUEST['wc-ajax'] ) && 'get_refreshed_fragments' === $_REQUEST['wc-ajax'] ) {
			$subtotal      = apply_filters( 'yay_currency_get_cart_subtotal', $cart_subtotal, $this->apply_currency );
			$cart_subtotal = YayCurrencyHelper::format_price( $subtotal );
		}
		return $cart_subtotal;
	}

}
