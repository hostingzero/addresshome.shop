<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

class Bookly {
	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( ! class_exists( 'BooklyPro\Lib\Plugin' ) ) {
			return;
		}

		$this->apply_currency = YayCurrencyHelper::detect_current_currency();

		add_filter( 'woocommerce_cart_item_price', array( $this, 'custom_cart_item_price_mini_cart' ), 10000, 3 );

	}

	public function custom_cart_item_price_mini_cart( $price, $cart_item, $cart_item_key ) {

		if ( isset( $cart_item['bookly'] ) && ! empty( $cart_item['bookly'] ) ) {

			if ( class_exists( 'Bookly\Lib\UserBookingData' ) ) {
				$userData = new \Bookly\Lib\UserBookingData( null );
				$userData->fillData( $cart_item['bookly'] );
				$userData->cart->setItemsData( $cart_item['bookly']['items'] );
				$cart_info = $userData->cart->getInfo();

				if ( 'excl' === get_option( 'woocommerce_tax_display_cart' ) && \Bookly\Lib\Config::taxesActive() ) {
						$product_price = $cart_info->getPayNow() - $cart_info->getPayTax();
				} else {
						$product_price = $cart_info->getPayNow();
				}

				$price = YayCurrencyHelper::calculate_price_by_currency( $product_price, false, $this->apply_currency );
				$price = YayCurrencyHelper::format_price( $price );
			}
		}

		return $price;

	}

}
