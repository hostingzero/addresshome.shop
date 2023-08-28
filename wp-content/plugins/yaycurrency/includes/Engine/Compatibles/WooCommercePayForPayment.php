<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://cs.wordpress.org/plugins/woocommerce-pay-for-payment/

class WooCommercePayForPayment {

	use SingletonTrait;

	public function __construct() {
		if ( ! function_exists( 'pay4payment_plugin_init' ) ) {
			return;
		}

		add_filter( 'woocommerce_pay4pay_charges_fixed', array( $this, 'custom_fee' ) );
		add_filter( 'woocommerce_pay4pay_charges_minimum', array( $this, 'custom_fee' ) );
		add_filter( 'woocommerce_pay4pay_charges_maximum', array( $this, 'custom_fee' ) );

	}

	public function custom_fee( $fee ) {
		$apply_currency = YayCurrencyHelper::detect_current_currency();
		if ( is_checkout() && ( 0 == get_option( 'yay_currency_checkout_different_currency', 0 ) || 0 == $apply_currency['status'] ) ) {
			return $fee;
		}

		$fee = YayCurrencyHelper::calculate_price_by_currency( $fee, false, $apply_currency );

		return $fee;

	}
}
