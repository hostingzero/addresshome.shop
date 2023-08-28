<?php

namespace Yay_Currency\Engine\FEPages;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\Helper;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

class WooCommercePriceFormat {
	use SingletonTrait;

	private $converted_currency = array();
	private $apply_currency     = array();

	private $is_dis_checkout_diff_currency = false;

	public function __construct() {
		add_action( 'init', array( $this, 'wp_load' ) );
	}

	public function wp_load() {
		if ( ! is_admin() ) {
			$this->converted_currency            = YayCurrencyHelper::converted_currency();
			$this->apply_currency                = YayCurrencyHelper::get_apply_currency( $this->converted_currency );
			$is_checkout_different_currency      = get_option( 'yay_currency_checkout_different_currency', 0 );
			$this->is_dis_checkout_diff_currency = YayCurrencyHelper::is_dis_checkout_diff_currency( $is_checkout_different_currency, $this->apply_currency['status'] );
			$priority                            = apply_filters( 'yay_currency_woocommerce_currency_priority', 10 );
			add_filter( 'woocommerce_currency', array( $this, 'change_woocommerce_currency' ), $priority, 1 );
			add_filter( 'woocommerce_currency_symbol', array( $this, 'change_existing_currency_symbol' ), $priority, 2 );
			add_filter( 'pre_option_woocommerce_currency_pos', array( $this, 'change_currency_position' ), $priority );
			add_filter( 'wc_get_price_thousand_separator', array( $this, 'change_thousand_separator' ), $priority );
			add_filter( 'wc_get_price_decimal_separator', array( $this, 'change_decimal_separator' ), $priority );
			add_filter( 'wc_get_price_decimals', array( $this, 'change_number_decimals' ), $priority );

		}
	}

	public function change_woocommerce_currency( $currency ) {

		if ( ! $this->apply_currency || ( is_checkout() && $this->is_dis_checkout_diff_currency ) ) {
			$currency = apply_filters( 'yay_currency_woocommerce_currency', $currency, $this->is_dis_checkout_diff_currency );
			return $currency;
		}

		if ( isset( $this->apply_currency['currency'] ) ) {
			$currency = $this->apply_currency['currency'];
		}
		$currency = apply_filters( 'yay_currency_woocommerce_currency', $currency, $this->is_dis_checkout_diff_currency );
		return $currency;
	}

	public function change_existing_currency_symbol( $currency_symbol, $currency ) {
		if ( ! $this->apply_currency || ( is_checkout() && ( $this->is_dis_checkout_diff_currency || ! empty( is_wc_endpoint_url( 'order-received' ) ) ) ) || ( function_exists( 'is_account_page' ) && is_account_page() ) ) {
			$currency_symbol = apply_filters( 'yay_currency_woocommerce_currency_symbol', $currency_symbol, $this->apply_currency );
			return $currency_symbol;
		}

		if ( isset( $this->apply_currency['currency'] ) ) {
			$currency_symbol = wp_kses_post( html_entity_decode( $this->apply_currency['symbol'] ) );
			$currency_symbol = apply_filters( 'yay_currency_woocommerce_currency_symbol', $currency_symbol, $this->apply_currency );
		}

		return $currency_symbol;
	}

	public function change_currency_position() {
		return Helper::change_currency_position( $this->apply_currency );
	}

	public function change_thousand_separator() {
		return Helper::change_thousand_separator( $this->apply_currency );
	}

	public function change_decimal_separator() {
		return Helper::change_decimal_separator( $this->apply_currency );
	}

	public function change_number_decimals() {
		return Helper::change_number_decimals( $this->apply_currency );
	}


}
