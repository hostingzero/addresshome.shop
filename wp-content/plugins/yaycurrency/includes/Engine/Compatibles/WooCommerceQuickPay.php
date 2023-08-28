<?php

namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;
use Yay_Currency\Helpers\Helper;

defined( 'ABSPATH' ) || exit;

class WooCommerceQuickPay {
	use SingletonTrait;

	private $apply_currency     = array();
	private $converted_currency = array();

	public function __construct() {
		if ( ! defined( 'WCQP_VERSION' ) ) {
			return;
		}
		$this->apply_currency     = YayCurrencyHelper::detect_current_currency();
		$this->converted_currency = YayCurrencyHelper::converted_currency();
		add_action( 'woocommerce_email_order_details', array( $this, 'set_order_id' ), 9, 4 );
		add_filter( 'yay_currency_woocommerce_currency_symbol', array( $this, 'get_currency_symbol' ), 10, 2 );

	}

	public function set_order_id( $order, $sent_to_admin, $plain_text, $email ) {
		$order_id                                = $order->get_id();
		$_REQUEST['yay_currency_email_order_id'] = $order_id;
	}

	public function get_currency_symbol( $currency_symbol, $currency ) {
		if ( doing_action( 'woocommerce_email_order_details' ) && isset( $_REQUEST['yay_currency_email_order_id'] ) ) {
			$order_id = sanitize_text_field( $_REQUEST['yay_currency_email_order_id'] );
			$order_id = intval( $order_id );
			if ( $order_id ) {
				if ( Helper::check_custom_orders_table_usage_enabled() ) {
					$order         = wc_get_order( $order_id );
					$currency_code = $order->get_currency();
				} else {
					$currency_code = get_post_meta( $order_id, '_order_currency', true );
				}
				$apply_currency  = YayCurrencyHelper::get_currency_by_currency_code( $currency_code, $this->converted_currency );
				$currency_symbol = wp_kses_post( html_entity_decode( $apply_currency['symbol'] ) );
			}
		}
		return $currency_symbol;
	}

}
