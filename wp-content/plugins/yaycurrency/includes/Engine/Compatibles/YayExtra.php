<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Helpers\Helper;
use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;
use Yay_Currency\Helpers\SupportHelper;
use YayExtra\Helper\Utils;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://yaycommerce.com/yayextra-woocommerce-extra-product-options/

class YayExtra {
	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( ! defined( 'YAYE_VERSION' ) ) {
			return;
		}

		$this->apply_currency = YayCurrencyHelper::detect_current_currency();
		add_filter( 'yay_currency_get_price_options_by_cart_item', array( $this, 'get_price_options_by_cart_item' ), 10, 5 );
		// Define filter get price default (when disable Checkout in different currency option)
		add_filter( 'yay_currency_get_price_default_in_checkout_page', array( $this, 'get_price_default_in_checkout_page' ), 10, 2 );
		add_filter( 'yay_currency_product_price_3rd_with_condition', array( $this, 'get_price_with_options' ), 10, 2 );
		add_filter( 'yay_currency_get_product_price_by_3rd_plugin', array( $this, 'get_product_price_by_3rd_plugin' ), 10, 3 );
		if ( YayCurrencyHelper::enable_rounding_currency( $this->apply_currency ) ) {
			add_filter( 'yaye_option_cost_display_orders_and_emails', array( $this, 'yaye_option_cost_display_cart_checkout' ), 10, 5 );
			// Change Option Cost again with type is percentage
			add_filter( 'yaye_option_cost_display_cart_checkout', array( $this, 'yaye_option_cost_display_cart_checkout' ), 10, 5 );
			add_filter( 'yaye_option_cost_display_orders_and_emails', array( $this, 'yaye_option_cost_display_cart_checkout' ), 10, 5 );
		}

	}

	public function get_addition_cost_by_option_selected( $option_meta, $option_val, $product_price_by_currency ) {
		$cost    = 0;
		$percent = false;
		if ( isset( $option_meta['optionValues'] ) && ! empty( $option_meta['optionValues'] ) ) {
			foreach ( $option_meta['optionValues'] as $option_value ) {
				if ( $option_value['value'] !== $option_val ) {
					continue;
				}
				$additional_cost = $option_value['additionalCost'];
				if ( $additional_cost['isEnabled'] && ! empty( $additional_cost['value'] ) ) {
					if ( 'fixed' === $additional_cost['costType']['value'] ) { // fixed.
						$cost = floatval( $additional_cost['value'] );
					} else { // percentage.
						$percent = true;
						$cost    = $product_price_by_currency * ( $additional_cost['value'] / 100 );
					}
				}
			}
		}

		$addition_cost = array(
			'percent' => $percent,
			'cost'    => $cost,
		);
		return $addition_cost;
	}

	public function caculate_option_again( $option_field_data, $product_price_by_currency ) {
		$addition_cost = false;
		foreach ( $option_field_data as $option_set_id => $option ) {
			if ( ! empty( $option ) ) {
				foreach ( $option as $option_id => $option_args ) {
					$option_meta = false;
					if ( class_exists( '\YayExtra\Init\CustomPostType' ) ) {
						$option_meta = \YayExtra\Init\CustomPostType::get_option( (int) $option_set_id, $option_id );
					}

					if ( $option_meta ) {

						$option_args = isset( $option_args['option_value'] ) ? array_shift( $option_args['option_value'] ) : false;
						$option_val  = $option_args ? $option_args['option_val'] : false;
						if ( $option_val ) {
							$option_has_addtion_cost_list = array( 'checkbox', 'radio', 'button', 'button_multi', 'dropdown', 'swatches', 'swatches_multi' );
							if ( in_array( $option_meta['type']['value'], $option_has_addtion_cost_list, true ) ) {
								$addition_cost = $this->get_addition_cost_by_option_selected( $option_meta, $option_val, $product_price_by_currency );
							}
						}
					}
				}
			}
		}
		return $addition_cost;
	}

	public function get_price_options_by_cart() {
		if ( ! YayCurrencyHelper::enable_rounding_currency( $this->apply_currency ) ) {
			return false;
		}
		$cart_contents = WC()->cart->get_cart_contents();
		if ( count( $cart_contents ) > 0 ) {
			foreach ( $cart_contents  as $key => $value ) {
				if ( isset( $value['yaye_total_option_cost'] ) && ! empty( $value['yaye_total_option_cost'] ) ) {
					$product_obj                    = $value['data'];
					$product_price_default_currency = (float) $value['yaye_product_price_original'];
					$product_price_by_currency      = YayCurrencyHelper::calculate_price_by_currency( $product_price_default_currency, false, $this->apply_currency );

					$addition_cost_details     = $this->caculate_option_again( $value['yaye_custom_option'], $product_price_by_currency );
					$total_option_cost_default = Utils::cal_total_option_cost_on_cart_item_static( $value['yaye_custom_option'], $product_price_default_currency );

					if ( $addition_cost_details && isset( $addition_cost_details['percent'] ) && $addition_cost_details['percent'] ) {
						$options_price = $addition_cost_details['cost'];
					} else {
						$options_price = YayCurrencyHelper::calculate_price_by_currency( $total_option_cost_default, false, $this->apply_currency );
					}

					$product_obj->yay_currency_extra_price_options_default          = (float) $total_option_cost_default;
					$product_obj->yay_currency_extra_set_price_with_options_default = $product_price_default_currency + $total_option_cost_default;

					$product_obj->yay_currency_extra_price_options          = (float) $options_price;
					$product_obj->yay_currency_extra_set_price_with_options = (float) $product_price_by_currency + $options_price;

				}
			}
			return true;
		}

	}

	public function get_price_options_by_cart_item( $price_options, $cart_item, $product_id, $original_price, $apply_currency ) {

		if ( isset( $cart_item['data']->yay_currency_extra_price_options ) ) {
			return $cart_item['data']->yay_currency_extra_price_options;
		}

		if ( isset( $cart_item['yaye_total_option_cost'] ) ) {
			$price_options = (float) YayCurrencyHelper::calculate_price_by_currency( $cart_item['yaye_total_option_cost'], false, $apply_currency );
		}

		return $price_options;
	}

	public function yaye_option_cost_display_cart_checkout( $option_cost, $option_cost_value, $cost_type, $product_price_original, $product_id ) {

		if ( 'percentage' === $cost_type ) {
			$flag = Helper::default_currency_code() === $this->apply_currency['currency'] || YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency );
			if ( $flag ) {
				$option_cost = $product_price_original * ( $option_cost_value / 100 );
				return $option_cost;
			}

			$product_price_currency = YayCurrencyHelper::calculate_price_by_currency( $product_price_original, false, $this->apply_currency );
			return $product_price_currency * ( $option_cost_value / 100 );

		}

		return $option_cost;
	}

	public function get_price_default_in_checkout_page( $price, $product ) {
		$flag = $this->get_price_options_by_cart();

		if ( $flag && isset( $product->yay_currency_extra_set_price_with_options_default ) ) {
			return $product->yay_currency_extra_set_price_with_options_default;

		}

		return $product->get_price( 'edit' );
	}

	public function get_price_with_options( $price, $product ) {
		$flag = $this->get_price_options_by_cart();

		if ( $flag && isset( $product->yay_currency_extra_set_price_with_options ) ) {
			return $product->yay_currency_extra_set_price_with_options;
		}

		return $product->get_price( 'edit' );
	}

	public function get_product_price_by_3rd_plugin( $product_price, $product, $apply_currency ) {

		$flag = $this->get_price_options_by_cart();

		if ( $flag && isset( $product->yay_currency_extra_set_price_with_options ) ) {
			return $product->yay_currency_extra_set_price_with_options;
		}

		return $product_price;

	}
}
