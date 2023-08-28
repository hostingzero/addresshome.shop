<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;

use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://themeforest.net/item/kapee-fashion-store-woocommerce-theme/24187521
class KapeeTheme {

	use SingletonTrait;
	private $apply_currency = array();
	public function __construct() {

		if ( 'kapee' === wp_get_theme()->template ) {
			$this->apply_currency = YayCurrencyHelper::detect_current_currency();
			add_filter( 'woocommerce_get_price_html', array( $this, 'custom_price_html' ), 10, 2 );
			add_filter( 'woocommerce_cart_item_price', array( $this, 'recalculate_mini_cart' ), 10000, 3 );
		}

	}

	public function custom_price_html( $price, $product ) {
		$converted_price = YayCurrencyHelper::calculate_price_by_currency_cookie( $product->get_price( 'edit' ) );
		$formatted_price = YayCurrencyHelper::format_price( $converted_price );
		return $formatted_price;
	}

	public function recalculate_mini_cart( $price, $cart_item, $cart_item_key ) {

		if ( wp_doing_ajax() ) {
			if ( is_cart() ) {
				return $price;
			}

			$product_price = wc_get_product( $cart_item['product_id'] )->get_price( 'edit' );
			$price         = YayCurrencyHelper::calculate_price_by_currency( $product_price, false, $this->apply_currency );

			$price = YayCurrencyHelper::format_price( $price );

		}

		return $price;
	}

}
