<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\Helper;
use Yay_Currency\Helpers\YayCurrencyHelper;
use WCPay\MultiCurrency\MultiCurrency;
defined( 'ABSPATH' ) || exit;

// Link plugin: https://woocommerce.com/products/woocommerce-payments/

class WooCommercePayments {


	use SingletonTrait;
	private $apply_currency = array();

	public function __construct() {
		if ( ! class_exists( 'WCPay\MultiCurrency\MultiCurrency' ) ) {
			return;
		}

		$this->apply_currency = YayCurrencyHelper::detect_current_currency();

		add_filter( 'yay_currency_woocommerce_currency', array( $this, 'paypal_payments_get_currency' ), 20, 2 );
		add_filter( MultiCurrency::FILTER_PREFIX . 'override_selected_currency', array( $this, 'override_selected_currency' ), 50 );

	}

	public function paypal_payments_get_currency( $currency, $is_dis_checkout_diff_currency ) {
		$payment_methods = isset( WC()->payment_gateways ) ? WC()->payment_gateways->get_available_payment_gateways() : array();
		if ( $is_dis_checkout_diff_currency && array_key_exists( 'paypal', $payment_methods ) ) {
			$currency = Helper::default_currency_code();
		}
		return $currency;
	}

	public function override_selected_currency() {
		$default_currency_code = Helper::default_currency_code();
		if ( YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) ) {
			return $default_currency_code;
		}
		$currency_code = isset( $this->apply_currency['currency'] ) ? $this->apply_currency['currency'] : $default_currency_code;
		return $currency_code;
	}

}
