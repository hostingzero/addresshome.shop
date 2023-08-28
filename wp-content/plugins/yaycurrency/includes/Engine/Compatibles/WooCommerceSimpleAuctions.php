<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// link plugin : https://codecanyon.net/item/woocommerce-simple-auctions-wordpress-auctions/6811382

class WooCommerceSimpleAuctions {
	use SingletonTrait;
	private $apply_currency = array();
	public function __construct() {

		if ( ! class_exists( 'WooCommerce_simple_auction' ) ) {
			return;
		}

		$this->apply_currency = YayCurrencyHelper::detect_current_currency();

		add_filter( 'woocommerce_simple_auctions_get_current_bid', array( $this, 'woocommerce_simple_auctions_get_current_bid' ), 10, 2 );
		add_filter( 'woocommerce_place_bid_bid', array( $this, 'woocommerce_place_bid_bid' ), 10, 1 );
		add_filter( 'woocommerce_simple_auctions_minimal_bid_value', array( $this, 'woocommerce_simple_auctions_minimal_bid_value' ), 10, 2 );

	}

	public function woocommerce_simple_auctions_get_current_bid( $price, $product ) {
		$converted_price = YayCurrencyHelper::calculate_price_by_currency_cookie( $price, true, $this->apply_currency );
		return $converted_price;
	}

	public function woocommerce_place_bid_bid( $bid ) {
		$converted_price = YayCurrencyHelper::reverse_calculate_price_by_currency( $bid, $this->apply_currency );
		return $converted_price;
	}

	public function woocommerce_simple_auctions_minimal_bid_value( $bid_value, $product_data ) {
		$converted_price = YayCurrencyHelper::reverse_calculate_price_by_currency( $bid_value, $this->apply_currency );
		return $converted_price;
	}

}
