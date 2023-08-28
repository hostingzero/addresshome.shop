<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// link plugin : https://creativethemes.com/blocksy/premium/

class SalientTheme {
	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( 'salient' === wp_get_theme()->template && wp_doing_ajax() ) {
			$this->apply_currency = YayCurrencyHelper::detect_current_currency();
			add_filter( 'woocommerce_get_price_html', array( $this, 'custom_single_product_price_in_quickview' ), 10, 2 );
			add_filter( 'woocommerce_variable_price_html', array( $this, 'custom_variable_price_in_quickview' ), 10, 2 );
		}
	}

	public function custom_single_product_price_in_quickview( $price, $product ) {
		if ( 'simple' === $product->get_type() ) {

			$salient_theme_options = get_option( 'salient_redux' );

			if ( ! empty( $salient_theme_options ) && '1' === $salient_theme_options['product_quick_view'] ) {
				$converted_price = YayCurrencyHelper::calculate_price_by_currency( $product->get_price( 'edit' ), false, $this->apply_currency );
				$formatted_price = YayCurrencyHelper::format_price( $converted_price );
				return $formatted_price;
			}
		}

		return $price;
	}

	public function custom_variable_price_in_quickview( $price, $product ) {
		$salient_theme_options = get_option( 'salient_redux' );

		if ( ! empty( $salient_theme_options ) && '1' === $salient_theme_options['product_quick_view'] ) {
			$min_price           = $product->get_variation_price( 'min', true );
			$max_price           = $product->get_variation_price( 'max', true );
			$converted_min_price = YayCurrencyHelper::calculate_price_by_currency( $min_price, false, $this->apply_currency );
			$converted_max_price = YayCurrencyHelper::calculate_price_by_currency( $max_price, false, $this->apply_currency );

			$formatted_min_price = YayCurrencyHelper::format_price( $converted_min_price );
			$formatted_max_price = YayCurrencyHelper::format_price( $converted_max_price );

			$price = $formatted_min_price . ' - ' . $formatted_max_price;

		}

		return $price;
	}

}
