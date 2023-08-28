<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;


defined( 'ABSPATH' ) || exit;

class YayPricing {
	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( ! defined( 'YAYDP_VERSION' ) ) {
			return;
		}

		$this->apply_currency = YayCurrencyHelper::detect_current_currency();

		add_filter( 'yay_currency_get_cart_item_price_3rd_plugin', array( $this, 'yay_currency_get_cart_item_price_3rd_plugin' ), 30, 3 );

	}

	public function yay_currency_get_cart_item_price_3rd_plugin( $product_price, $cart_item, $apply_currency ) {

		if ( isset( $cart_item['modifiers'] ) && ! empty( $cart_item['modifiers'] ) ) {
			if ( isset( $cart_item['yaydp_custom_data']['price'] ) && ! empty( $cart_item['yaydp_custom_data']['price'] ) ) {
				$product_price = YayCurrencyHelper::calculate_price_by_currency( $cart_item['yaydp_custom_data']['price'], false, $this->apply_currency );
			}
		}

		return $product_price;
	}

}
