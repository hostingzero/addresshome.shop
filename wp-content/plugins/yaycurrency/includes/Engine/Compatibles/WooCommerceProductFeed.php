<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\Helper;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://marketpress.com/shop/plugins/google-product-feed/

class WooCommerceProductFeed {

	use SingletonTrait;

	public function __construct() {

		if ( ! defined( 'WOO_FEED_PRO_VERSION' ) ) {
			return;
		}

		add_filter( 'woo_feed_filter_product_regular_price', array( $this, 'woo_feed_filter_regular_price_for_yay_currency' ), 10, 3 );
		add_filter( 'woo_feed_filter_product_price', array( $this, 'woo_feed_filter_sale_price_for_yay_currency' ), 10, 3 );
		add_filter( 'woo_feed_filter_product_sale_price', array( $this, 'woo_feed_filter_sale_price_for_yay_currency' ), 10, 3 );
		add_filter( 'woo_feed_filter_product_regular_price_with_tax', array( $this, 'woo_feed_filter_regular_price_for_yay_currency' ), 10, 3 );
		add_filter( 'woo_feed_filter_product_price_with_tax', array( $this, 'woo_feed_filter_sale_price_for_yay_currency' ), 10, 3 );
		add_filter( 'woo_feed_filter_product_sale_price_with_tax', array( $this, 'woo_feed_filter_sale_price_for_yay_currency' ), 10, 3 );
		add_filter( 'woo_feed_parsed_rules', array( $this, 'woo_feed_parsed_rules' ), 10, 2 );
		// add_filter( 'woo_feed_schema_product_currency', array( $this, 'woo_feed_schema_product_currency' ), 10, 3 );
		add_filter( 'woocommerce_structured_data_product', array( $this, 'woo_feed_after_wc_product_structured_data' ), 10, 2 );
		add_filter( 'woo_feed_after_wc_product_structured_data', array( $this, 'woo_feed_after_wc_product_structured_data' ), 10, 2 );
	}

	public function woo_feed_after_wc_product_structured_data( $markup, $product ) {

		if ( function_exists( 'Yay_Currency\\plugin_init' ) ) {
			$yay_currencies = Helper::get_currencies_post_type();
			$i              = 0;
			if ( $yay_currencies ) {
				foreach ( $yay_currencies as $currency ) {
					$currency_name                   = $currency->post_title;
					$converted_price                 = $product->get_price();
					$markup['offers'][ $i ]['@type'] = 'Offer';
					$markup['offers'][ $i ]['price'] = $converted_price;
					$markup['offers'][ $i ]['priceSpecification']['price']         = $converted_price;
					$markup['offers'][ $i ]['priceSpecification']['priceCurrency'] = $currency_name;
					$markup['offers'][ $i ]['priceCurrency']                       = $currency_name;

					if ( isset( $markup['offers'][0] ) && is_array( $markup['offers'][0] ) ) {
						$markup['offers'][ $i ]['priceValidUntil'] = isset( $markup['offers'][0]['priceValidUntil'] ) ? $markup['offers'][0]['priceValidUntil'] : null;
						$markup['offers'][ $i ]['availability']    = $markup['offers'][0]['availability'];
						$markup['offers'][ $i ]['url']             = $markup['offers'][0]['url'];
						$markup['offers'][ $i ]['priceSpecification']['valueAddedTaxIncluded'] = isset( $markup['offers'][0]['priceSpecification']['valueAddedTaxIncluded'] ) ? $markup['offers'][0]['priceSpecification']['valueAddedTaxIncluded'] : false;
					}

					$i ++;
				}
			}
		}

		return $markup;

	}

	public function woo_feed_parsed_rules( $rules, $context ) {
		$key = array_search( 'price', $rules['mattributes'] );
		if ( false !== $key ) {
			if ( ! empty( $rules['suffix'][ $key ] ) ) {
				$currency              = str_replace( ' ', '', $rules['suffix'][ $key ] );
				$rules['feedCurrency'] = $currency;
			}
		}
		return $rules;
	}

	public function woo_feed_filter_regular_price_for_yay_currency( $price, $product, $config ) {
		$key = array_search( 'price', $config['mattributes'] );
		if ( false !== $key ) {
			if ( ! empty( $config['suffix'][ $key ] ) ) {
				$currency = str_replace( ' ', '', $config['suffix'][ $key ] );
				$price    = $this->convert_price( $price, $config, $currency );
			}
		}
		return $price;
	}

	public function woo_feed_filter_sale_price_for_yay_currency( $price, $product, $config ) {
		$key = array_search( 'sale_price', $config['mattributes'] );
		if ( false !== $key ) {
			if ( ! empty( $config['suffix'][ $key ] ) ) {
				$currency = str_replace( ' ', '', $config['suffix'][ $key ] );
				$price    = $this->convert_price( $price, $config, $currency );
			}
		}
		return $price;
	}

	public function convert_price( $price, $config, $currency ) {
		$currency_data = YayCurrencyHelper::get_currency_by_currency_code( $currency );
		if ( ! empty( $currency_data ) ) {

			if ( ! empty( $price ) ) {
				$price = YayCurrencyHelper::calculate_price_by_currency( $price, false, $currency_data );
			}
		}
		return $price;
	}
}
