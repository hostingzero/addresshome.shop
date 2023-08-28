<?php
namespace Yay_Currency\Engine;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;
use Yay_Currency\Helpers\SupportHelper;

defined( 'ABSPATH' ) || exit;

class Hooks {
	use SingletonTrait;

	public function __construct() {

		// ADD FILTER PRIORITY
		add_filter( 'yay_currency_filters_priority', array( $this, 'get_filters_priority' ), 9, 1 );

		add_filter( 'yay_currency_get_cart_item_price', array( $this, 'get_cart_item_price' ), 10, 3 );
		add_filter( 'yay_currency_get_cart_subtotal', array( $this, 'get_cart_subtotal' ), 10, 2 );

		// ADD FILTER GET PRICE WITH CONDITIONS
		add_filter( 'yay_currency_get_price_with_conditions', array( $this, 'get_price_with_conditions' ), 10, 3 );
		// ADD FILTER GET PRICE EXCEPT CLASS PLUGINS
		add_filter( 'yay_currency_get_price_except_class_plugins', array( $this, 'get_price_except_class_plugins' ), 10, 3 );

		add_filter( 'yay_currency_stripe_request_amount', array( $this, 'custom_stripe_request_amount' ), 10, 3 );
		add_action( 'yay_currency_redirect_to_url', array( $this, 'yay_currency_redirect_to_url' ), 10, 2 );

	}

	public function get_filters_priority( $priority ) {

		// Compatible with B2B Wholesale Suite, Price by Country, B2BKing
		if ( class_exists( 'B2bwhs' ) || class_exists( 'CBP_Country_Based_Price' ) || class_exists( 'B2bkingcore' ) || class_exists( 'BM' ) ) {
			$priority = 100000;
		}

		return $priority;

	}

	public function get_cart_item_price( $product_price, $cart_item, $apply_currency ) {
		$product_price = SupportHelper::calculate_product_price_by_cart_item( $cart_item, $apply_currency );
		return $product_price;
	}

	public function get_cart_subtotal( $subtotal, $apply_currency ) {
		$subtotal = apply_filters( 'yay_currency_get_cart_subtotal_3rd_plugin', $subtotal, $apply_currency );
		if ( $subtotal ) {
			return $subtotal;
		}
		$subtotal = SupportHelper::calculate_cart_subtotal( $apply_currency );
		return $subtotal;
	}

	public function get_price_with_conditions( $price, $product, $apply_currency ) {
		// YayPricing

		$is_ydp_adjust_price = false;
		$caculate_price      = YayCurrencyHelper::calculate_price_by_currency( $price, false, $apply_currency );

		if ( class_exists( '\YayPricing\FrontEnd\ProductPricing' ) ) {
			$is_ydp_adjust_price = apply_filters( 'ydp_check_adjust_price', false );
		}

		if ( class_exists( '\YayPricing\FrontEnd\ProductPricing' ) && $is_ydp_adjust_price ) {
			return $caculate_price;
		}

		$price_3rd_plugin = apply_filters( 'yay_currency_product_price_3rd_with_condition', false, $product );

		return $price_3rd_plugin;

	}

	public function get_price_except_class_plugins( $price, $product, $apply_currency ) {
		if ( class_exists( '\BM_Conditionals' ) ) {
			$group_id = \BM_Conditionals::get_validated_customer_group();
			if ( false !== $group_id ) {
				return $price;
			}
		}
		$calculate_price      = YayCurrencyHelper::calculate_price_by_currency( $price, false, $apply_currency );
		$except_class_plugins = array(
			'WC_Measurement_Price_Calculator',
			'\WP_Grid_Builder\Includes\Plugin',
			'WCPA', // Woocommerce Custom Product Addons
			'\Acowebs\WCPA\Main', // Woocommerce Custom Product Addons
			'WoonpCore', // Name Your Price for WooCommerce
			'Webtomizer\\WCDP\\WC_Deposits', // WooCommerce Deposits
			'\WC_Product_Price_Based_Country', // Price Per Country
			'\JET_APB\Plugin', // Jet Appointments Booking
		);
		$except_class_plugins = apply_filters( 'yay_currency_except_class_plugin', $except_class_plugins );
		foreach ( $except_class_plugins as $class ) {
			if ( class_exists( $class ) ) {
				return $calculate_price;
			}
		}
		return false;
	}

	public function custom_stripe_request_amount( $request, $api, $apply_currency ) {
		global $wpdb;
		if ( isset( $request['currency'] ) && isset( $request['metadata'] ) && isset( $request['metadata']['order_id'] ) ) {
			$array_zero_decimal_currencies = array(
				'BIF',
				'CLP',
				'DJF',
				'GNF',
				'JPY',
				'KMF',
				'KRW',
				'MGA',
				'PYG',
				'RWF',
				'UGX',
				'VND',
				'VUV',
				'XAF',
				'XOF',
				'XPF',
			);
			if ( in_array( strtoupper( $request['currency'] ), $array_zero_decimal_currencies ) ) {
				$orderID = $request['metadata']['order_id'];

				$result = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT meta_value FROM {$wpdb->postmeta} WHERE (post_id = %d AND meta_key = '_order_total')",
						$orderID
					)
				);

				if ( empty( $result ) ) {
					return $request;
				}

				$order_total = $result;

				$request['amount'] = (int) $order_total;
			}
		}
		return $request;
	}

	public function yay_currency_redirect_to_url( $current_url, $currency_ID ) {
		$current_currency = YayCurrencyHelper::get_currency_by_ID( $currency_ID );
		$current_url      = add_query_arg( array( 'yay-currency' => $current_currency['currency'] ), $current_url );
		if ( wp_safe_redirect( $current_url ) ) {
			exit;
		}

	}

}
