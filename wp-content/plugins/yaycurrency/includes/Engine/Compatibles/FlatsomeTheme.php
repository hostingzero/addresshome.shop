<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// link theme : https://themeforest.net/item/flatsome-multipurpose-responsive-woocommerce-theme/5484319

class FlatsomeTheme {
	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( 'flatsome' === wp_get_theme()->template ) {
			$this->apply_currency = YayCurrencyHelper::detect_current_currency();
			add_filter( 'woocommerce_get_price_html', array( $this, 'custom_woocommerce_get_price_html' ), 9999, 2 );
		}
	}

	public function custom_woocommerce_get_price_html( $price_html, $product ) {

		$data_actions = array( 'flatsome_ajax_search_products' );
		if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $data_actions, true ) ) {
			$product_price = $product->get_price( 'edit' );
			if ( function_exists( 'thenga_customer_specific_pricing' ) ) {
				$product_price = thenga_customer_specific_pricing( $product_price );
			}
			$product_price = YayCurrencyHelper::calculate_price_by_currency( $product_price, false, $this->apply_currency );
			if ( function_exists( 'filtering_product_prices' ) ) {
				$product_price = filtering_product_prices( $product_price, $product );
			}
			$price_html = YayCurrencyHelper::format_price( $product_price );
		}

		return $price_html;

	}

}
