<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\SupportHelper;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://pluginrepublic.com/wordpress-plugins/woocommerce-product-add-ons-ultimate/

class WooCommerceProductAddOnsUltimate {
	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( ! defined( 'PEWC_PLUGIN_VERSION' ) ) {
			return;
		}
		$this->apply_currency = YayCurrencyHelper::detect_current_currency();
		add_filter( 'pewc_after_add_cart_item_data', array( $this, 'pewc_after_add_cart_item_data' ), 10, 1 );
		add_filter( 'yay_currency_product_price_3rd_with_condition', array( $this, 'get_price_with_options' ), 10, 2 );
		add_filter( 'yay_currency_get_price_default_in_checkout_page', array( $this, 'get_price_default_in_checkout_page' ), 10, 2 );
	}

	public function pewc_after_add_cart_item_data( $cart_item_data ) {
		$product_extras = isset( $cart_item_data['product_extras'] ) && ! empty( $cart_item_data['product_extras'] ) ? $cart_item_data['product_extras'] : false;
		if ( $product_extras ) {
			$cart_item_data['product_extras']['yay_currency'] = $this->apply_currency['currency'];
		}
		return $cart_item_data;
	}

	public function get_price_options_by_cart() {
		$cart_contents = WC()->cart->get_cart_contents();
		foreach ( $cart_contents  as $key => $cart_item ) {
			$product_extras = isset( $cart_item['product_extras'] ) ? $cart_item['product_extras'] : false;
			if ( $product_extras && isset( $product_extras['yay_currency'] ) ) {
				$product_id                = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
				$price_with_extras_default = SupportHelper::get_product_price( $product_id );
				$price_with_extras         = $product_extras['yay_currency'] === $this->apply_currency['currency'] ? $product_extras['price_with_extras'] : YayCurrencyHelper::calculate_price_by_currency( $price_with_extras_default, false, $this->apply_currency );
				$cart_item['data']->yay_currency_product_price_with_extras_by_currency = $price_with_extras;
				$cart_item['data']->yay_currency_product_price_with_extras_by_default  = $price_with_extras_default;
			}
		}

	}

	public function get_price_with_options( $price, $product ) {
		$this->get_price_options_by_cart();
		if ( isset( $product->yay_currency_product_price_with_extras_by_currency ) ) {
			$price = $product->yay_currency_product_price_with_extras_by_currency;
		}
		return $price;
	}

	public function get_price_default_in_checkout_page( $price, $product ) {
		$this->get_price_options_by_cart();
		if ( isset( $product->yay_currency_product_price_with_extras_by_default ) ) {
			$price = $product->yay_currency_product_price_with_extras_by_default;
		}
		return $price;
	}

}
