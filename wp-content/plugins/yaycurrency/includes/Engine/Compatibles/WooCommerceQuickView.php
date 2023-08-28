<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;

use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

class WooCommerceQuickView {
	use SingletonTrait;

	public function __construct() {
		// Compatible with Salient theme Quick view
		if ( 'salient' === wp_get_theme()->template && wp_doing_ajax() ) {
			add_filter( 'woocommerce_get_price_html', array( $this, 'custom_single_product_price_in_quickview' ), 10, 2 );
			add_filter( 'woocommerce_variable_price_html', array( $this, 'custom_variable_price_in_quickview' ), 10, 2 );
		}
	}

	public function custom_single_product_price_in_quickview( $price, $product ) {
		if ( 'simple' === $product->get_type() ) {

			$salient_theme_options = get_option( 'salient_redux' );

			if ( ! empty( $salient_theme_options ) && '1' === $salient_theme_options['product_quick_view'] ) {
				$converted_price = YayCurrencyHelper::calculate_price_by_currency_cookie( $product->get_price() );
				$formatted_price = YayCurrencyHelper::format_price( $converted_price );
				return $formatted_price;
			}
		}

		return $price;
	}

	public function custom_variable_price_in_quickview( $price, $product ) {
		$salient_theme_options = get_option( 'salient_redux' );

		if ( ! empty( $salient_theme_options ) && '1' === $salient_theme_options['product_quick_view'] ) {

			$converted_min_price = YayCurrencyHelper::calculate_price_by_currency_cookie( $product->get_variation_price( 'min', true ) );
			$converted_max_price = YayCurrencyHelper::calculate_price_by_currency_cookie( $product->get_variation_price( 'max', true ) );

			$formatted_min_price = YayCurrencyHelper::format_price( $converted_min_price );
			$formatted_max_price = YayCurrencyHelper::format_price( $converted_max_price );

			$price = $formatted_min_price . ' - ' . $formatted_max_price;

		}

		return $price;
	}

}
