<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// link plugin : https://thimpress.com/learnpress/

class LearnPress {
	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( class_exists( '\LP_Admin_Assets' ) ) {
			$this->apply_currency = YayCurrencyHelper::detect_current_currency();
			add_filter( 'learn-press/course/price', array( $this, 'custom_course_price' ), 10, 2 );
			add_filter( 'learn-press/course/regular-price', array( $this, 'custom_course_regular_price' ), 10, 2 );
			add_filter( 'learn_press_currency_symbol', array( $this, 'learn_press_currency_symbol' ), 10, 2 );
		}
	}

	public function learn_press_currency_symbol( $currency_symbol, $currency ) {
		if ( isset( $this->apply_currency['symbol'] ) ) {
			$currency_symbol = $this->apply_currency['symbol'];
		}
		return $currency_symbol;
	}

	public function custom_course_price( $price, $product_id ) {
		$price = YayCurrencyHelper::calculate_price_by_currency_cookie( $price, true, $this->apply_currency );
		$price = (float) number_format( $price, (int) $this->apply_currency['numberDecimal'], $this->apply_currency['decimalSeparator'], $this->apply_currency['thousandSeparator'] );
		return $price;
	}

	public function custom_course_regular_price( $price, $product_id ) {
		$has_symbol = ! is_numeric( $price );
		$price      = YayCurrencyHelper::calculate_price_by_currency_cookie( $price, true, $this->apply_currency );
		$price      = (float) number_format( $price, (int) $this->apply_currency['numberDecimal'], $this->apply_currency['decimalSeparator'], $this->apply_currency['thousandSeparator'] );
		if ( $has_symbol && function_exists( 'learn_press_format_price' ) ) {
			return learn_press_format_price( $price, true );
		}
		return $price;
	}
}
