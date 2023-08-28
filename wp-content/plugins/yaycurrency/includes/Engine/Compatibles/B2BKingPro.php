<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Helpers\YayCurrencyHelper;
use Yay_Currency\Utils\SingletonTrait;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://codecanyon.net/item/b2bking-the-ultimate-woocommerce-b2b-plugin/26689576

class B2BKingPro {

	use SingletonTrait;
	private $apply_currency = array();
	public function __construct() {

		if ( ! function_exists( 'b2bking' ) ) {
			return;
		}

		add_action( 'init', array( $this, 'add_woocommerce_filters' ) );

	}

	public function add_woocommerce_filters() {
		if ( ! is_admin() ) {
			$this->apply_currency   = YayCurrencyHelper::detect_current_currency();
			$price_filters_priority = 100000;

			add_filter( 'woocommerce_product_get_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_product_get_regular_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_product_get_sale_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );

			add_filter( 'woocommerce_product_variation_get_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );

			add_filter( 'woocommerce_variation_prices_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_variation_prices_sale_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'custom_variation_price_hash' ), $price_filters_priority, 1 );
		}
	}

	public function custom_variation_price_hash( $price_hash ) {
		if ( apply_filters( 'b2bking_clear_wc_products_cache', true ) ) {
			\WC_Cache_Helper::get_transient_version( 'product', true );
		}

		return $price_hash;
	}

	public function get_price_b2b_account_login( $product ) {
		if ( is_user_logged_in() ) {
			$user_id      = get_current_user_id();
			$account_type = get_user_meta( $user_id, 'b2bking_account_type', true );
			if ( 'subaccount' === $account_type ) {
				$parent_user_id = get_user_meta( $user_id, 'b2bking_account_parent', true );
				$user_id        = $parent_user_id;
			}
			$is_b2b_user          = get_user_meta( $user_id, 'b2bking_b2buser', true );
			$currentusergroupidnr = b2bking()->get_user_group( $user_id );
			if ( 'yes' === $is_b2b_user ) {
				// Search if there is a specific price set for the user's group
				$b2b_price     = b2bking()->tofloat( get_post_meta( $product->get_id(), 'b2bking_regular_product_price_group_' . $currentusergroupidnr, true ) );
				$b2b_saleprice = b2bking()->tofloat( get_post_meta( $product->get_id(), 'b2bking_sale_product_price_group_' . $currentusergroupidnr, true ) );
				return ! empty( $b2b_saleprice ) ? $b2b_saleprice : $b2b_price;
			}
		}
		return false;
	}

	public function custom_raw_price( $price, $product ) {
		if ( ! $this->apply_currency || empty( $price ) ) {
			return $price;
		}

		if ( YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) ) {
			$b2b_price = $this->get_price_b2b_account_login( $product );
			return $b2b_price ? $b2b_price * 0.8 : $price;
		}

		$b2b_price = $this->get_price_b2b_account_login( $product );
		if ( $b2b_price ) {

			if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
				 $price_res =
				wc_get_price_including_tax(
					$product,
					array(
						'qty'   => 1,
						'price' => $b2b_price,
					)
				);
			} else {
				$price_res =
				wc_get_price_excluding_tax(
					$product,
					array(
						'qty'   => 1,
						'price' => $b2b_price,
					)
				);
			}
			$per_cent          = $price_res / $b2b_price;
			$b2b_price_convert = YayCurrencyHelper::calculate_price_by_currency( $price * $per_cent, false, $this->apply_currency );
			return $b2b_price_convert / $per_cent;

		}

		// $price = YayCurrencyHelper::calculate_price_by_currency( $price, false, $this->apply_currency );

		return $price;

	}

}
