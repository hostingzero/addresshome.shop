<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// link plugin : https://creativethemes.com/blocksy/premium/

class BlocksyTheme {
	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( 'blocksy' === wp_get_theme()->template ) {
			$this->apply_currency = YayCurrencyHelper::detect_current_currency();
			add_filter( 'woocommerce_cart_subtotal', array( $this, 'blocksy_custom_cart_subtotal' ), 10, 3 );
		}

	}

	public function blocksy_custom_cart_subtotal( $price, $compound, $cart ) {
		if ( is_checkout() ) {
			return $price;
		}
		$subtotal = 0;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_price = $cart_item['data']->get_price( 'edit' );
			$subtotal     += YayCurrencyHelper::calculate_price_by_currency_cookie( $product_price, true, $this->apply_currency ) * $cart_item['quantity'];
		}
		$price = YayCurrencyHelper::format_price( $subtotal );
		return $price;
	}

}
