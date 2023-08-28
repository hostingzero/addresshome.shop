<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://elementor.com/

class ElementorPro {

	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return;
		}

		$this->apply_currency = YayCurrencyHelper::detect_current_currency();
		// Menu Cart Elementor Pro
		add_filter( 'woocommerce_cart_item_price', array( $this, 'custom_cart_item_price_mini_cart' ), 10, 3 );
		add_filter( 'woocommerce_cart_subtotal', array( $this, 'woocommerce_cart_subtotal' ), 10, 3 );

	}

	public function detect_ajax_menu_mini_cart() {
		$refreshed_fragments           = isset( $_REQUEST['wc-ajax'] ) && 'get_refreshed_fragments' === $_REQUEST['wc-ajax'];
		$elementor_menu_cart_fragments = isset( $_REQUEST['action'] ) && 'elementor_menu_cart_fragments' === $_REQUEST['action'];
		return $refreshed_fragments || $elementor_menu_cart_fragments;
	}

	public function custom_cart_item_price_mini_cart( $price, $cart_item, $cart_item_key ) {
		if ( wp_doing_ajax() && $this->detect_ajax_menu_mini_cart() ) {
			$product_price = apply_filters( 'yay_currency_get_cart_item_price', 0, $cart_item, $this->apply_currency );
			$price         = YayCurrencyHelper::format_price( $product_price );
		}
		return $price;
	}

	public function woocommerce_cart_subtotal( $cart_subtotal, $compound, $cart ) {
		if ( wp_doing_ajax() && $this->detect_ajax_menu_mini_cart() ) {
			$subtotal      = apply_filters( 'yay_currency_get_cart_subtotal', 0, $this->apply_currency );
			$cart_subtotal = YayCurrencyHelper::format_price( $subtotal );
		}
		return $cart_subtotal;
	}
}
