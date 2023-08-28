<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;

defined( 'ABSPATH' ) || exit;

// link plugin: https://woocommerce.com/products/table-rate-shipping/

class WooCommerceTableRateShipping {
	use SingletonTrait;

	public function __construct() {

		if ( ! class_exists( 'WC_Table_Rate_Shipping ' ) ) {
			return;
		}

		add_filter( 'woocommerce_table_rate_package_row_base_price', array( $this, 'custom_table_rate_shipping_plugin_row_base_price' ), 10, 3 );

	}

	public function custom_table_rate_shipping_plugin_row_base_price( $row_base_price, $_product, $qty ) {
		$row_base_price = $_product->get_data()['price'] * $qty;
		return $row_base_price;
	}

}
