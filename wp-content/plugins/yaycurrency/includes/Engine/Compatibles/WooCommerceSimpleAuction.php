<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;

use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

class WooCommerceSimpleAuction {

	use SingletonTrait;

	public function __construct() {
		if ( ! class_exists( 'WooCommerce_simple_auction' ) ) {
			return;
		}
		add_filter( 'woocommerce_simple_auctions_get_current_bid', array( $this, 'custom_woocommerce_simple_auction_price' ), 10, 2 );
		add_filter( 'woocommerce_place_bid_bid', array( $this, 'custom_woocommerce_place_bid_bid' ), 10, 1 );
		add_filter( 'woocommerce_simple_auctions_minimal_bid_value', array( $this, 'woocommerce_simple_auctions_minimal_bid_value' ), 10, 2 );
	}

	public function custom_woocommerce_simple_auction_price( $price, $product ) {
		$converted_price = YayCurrencyHelper::calculate_price_by_currency_cookie( $price );
		return $converted_price;
	}

	public function custom_woocommerce_place_bid_bid( $bid ) {
		$converted_price = YayCurrencyHelper::reverse_calculate_price_by_currency( $bid );
		return $converted_price;
	}

	public function woocommerce_simple_auctions_minimal_bid_value( $bid_value, $product_data ) {
		$converted_price = YayCurrencyHelper::reverse_calculate_price_by_currency( $bid_value );
		return $converted_price;
	}
}
