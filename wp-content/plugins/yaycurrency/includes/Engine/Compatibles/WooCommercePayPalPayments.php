<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\Helper;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://woocommerce.com/products/woocommerce-paypal-payments/

class WooCommercePayPalPayments {

	use SingletonTrait;
	private $apply_currency = array();
	private $is_dis_checkout_diff_currency;
	public function __construct() {

		if ( ! class_exists( 'WooCommerce\PayPalCommerce\PPCP' ) ) {
			return;
		}

		$this->apply_currency = YayCurrencyHelper::detect_current_currency();

		if ( ! $this->apply_currency ) {
			return;
		}

		$is_checkout_different_currency      = get_option( 'yay_currency_checkout_different_currency', 0 );
		$this->is_dis_checkout_diff_currency = YayCurrencyHelper::is_dis_checkout_diff_currency( $is_checkout_different_currency, $this->apply_currency['status'] );

		add_filter( 'yay_currency_woocommerce_currency', array( $this, 'paypal_payments_get_currency' ), 10, 2 );
		add_filter( 'woocommerce_paypal_args', array( $this, 'custom_request_paypal' ), 10, 2 );

		if ( $this->is_dis_checkout_diff_currency ) {
			add_filter( 'woocommerce_calculated_total', array( $this, 'convert_cart_total_to_default' ), 9999, 2 );
			add_filter( 'body_class', array( $this, 'add_class_hide_paypal_button' ) );
		}

	}

	public function paypal_payments_get_currency( $currency, $is_dis_checkout_diff_currency ) {

		if ( $is_dis_checkout_diff_currency ) {
			if ( ( wp_doing_ajax() && isset( $_REQUEST['wc-ajax'] ) && 'ppc-create-order' === $_REQUEST['wc-ajax'] ) || is_cart() ) {
				$currency = Helper::default_currency_code();
			}
		}

		return $currency;

	}

	public function custom_request_paypal( $args, $order ) {
		if ( $this->is_dis_checkout_diff_currency ) {
			$currency_code = Helper::default_currency_code();
		} else {
			$currency_code = isset( $this->apply_currency['currency'] ) ? $this->apply_currency['currency'] : Helper::default_currency_code();
		}
		$args['currency_code'] = $currency_code;
		return $args;
	}

	public function convert_cart_total_to_default( $cart_total, $cart ) {
		$flag = apply_filters( 'yay_currency_calculated_total_again', false );
		if ( wp_doing_ajax() && ! $flag ) {
			$args_ajax = array( 'wc_stripe_get_cart_details', 'get_refreshed_fragments' );
			if ( isset( $_REQUEST['wc-ajax'] ) && in_array( $_REQUEST['wc-ajax'], $args_ajax ) ) {
				$cart_total = $cart_total / YayCurrencyHelper::get_rate_fee( $this->apply_currency );
			}
		}
		return $cart_total;
	}

	public function add_class_hide_paypal_button( $classes ) {
		if ( is_product() || is_singular( 'product' ) ) {
			$classes[] = 'yay-currency-product-page';
		}
		return $classes;
	}

}
