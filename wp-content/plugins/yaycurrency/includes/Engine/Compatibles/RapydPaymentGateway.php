<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\Helper;

defined( 'ABSPATH' ) || exit;

// link plugin : https://woocommerce.com/document/rapyd-payments-plugin-for-woocommerce/

class RapydPaymentGateway {
	use SingletonTrait;

	public function __construct() {

		if ( ! class_exists( 'WC_Rapyd_Payment_Gateway' ) ) {
			return;
		}

		add_filter( 'woocommerce_order_get_total', array( $this, 'custom_get_order_total' ), 10, 2 );

	}

	public function custom_get_order_total( $total, $order ) {
		if ( Helper::check_custom_orders_table_usage_enabled() ) {
			$get_total = $order->get_total();
		} else {
			$get_total = get_post_meta( $order->get_id(), '_order_total', true );
		}
		if ( ! empty( $get_total ) ) {
			return $get_total;
		}
		return $total;
	}

}
