<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;
use Yay_Currency\Helpers\SupportHelper;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://yithemes.com/themes/plugins/yith-woocommerce-product-add-ons/

class YITHWooCommerceAddOnsExtraPremiumOptions {
	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( ! defined( 'YITH_WAPO' ) ) {
			return;
		}

		$this->apply_currency = YayCurrencyHelper::detect_current_currency();

		add_filter( 'yay_currency_get_price_default_in_checkout_page', array( $this, 'get_price_default_in_checkout_page' ), 10, 2 );
		add_filter( 'yay_currency_get_price_options_by_cart_item', array( $this, 'get_price_options_by_cart_item' ), 10, 5 );
		add_filter( 'yay_currency_get_cart_subtotal_3rd_plugin', array( $this, 'get_cart_subtotal_3rd_plugin' ), 10, 2 );

		add_filter( 'yith_wapo_addon_prices_on_cart', array( $this, 'yith_wapo_addon_prices_on_cart' ), 10, 1 );
		add_filter( 'yay_currency_product_price_3rd_with_condition', array( $this, 'get_price_with_options' ), 10, 2 );

	}

	public function get_price_options_by_cart_item( $price_options, $cart_item, $product_id, $original_price, $apply_currency ) {
		$_product = $cart_item['data'];
		if ( isset( $_product->yay_wapo_price_options ) ) {
			$price_options = (float) $_product->yay_wapo_price_options;
		}

		return $price_options;
	}

	public function get_price_default_in_checkout_page( $price, $product ) {

		$this->get_price_options_by_cart();

		if ( isset( $product->yay_yith_wapo_set_product_price_with_options_default ) ) {
			$price = $product->yay_yith_wapo_set_product_price_with_options_default;
		}

		return $price;

	}

	public function get_cart_subtotal_3rd_plugin( $subtotal, $apply_currency ) {
		$subtotal      = 0;
		$cart_contents = WC()->cart->get_cart_contents();
		foreach ( $cart_contents  as $key => $cart_item ) {
			$product_price    = SupportHelper::calculate_product_price_by_cart_item( $cart_item );
			$price_options    = isset( $cart_item['data']->yay_wapo_price_options ) ? $cart_item['data']->yay_wapo_price_options : 0;
			$product_subtotal = ( $product_price + $price_options ) * $cart_item['quantity'];
			$subtotal         = $subtotal + YayCurrencyHelper::calculate_price_by_currency( $product_subtotal, false, $apply_currency );
		}

		return $subtotal;
	}

	public function yith_wapo_addon_prices_on_cart( $option_price ) {
		if ( YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) ) {
			return $option_price;
		}
		$option_price = YayCurrencyHelper::calculate_price_by_currency( $option_price, false, $this->apply_currency );
		return $option_price;
	}

	public function get_price_options_by_cart() {
		$cart_contents = WC()->cart->get_cart_contents();

		if ( count( $cart_contents ) > 0 ) {
			foreach ( $cart_contents  as $key => $cart_item ) {
				if ( ! empty( $cart_item['yith_wapo_options'] ) ) {

					$first_free_options_count = 0;
					$currency_rate            = YayCurrencyHelper::get_rate_fee( $this->apply_currency );
					$product_id               = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
					$product_price            = SupportHelper::get_product_price( $product_id, $this->apply_currency );

					$addon_id_check = '';
					foreach ( $cart_item['yith_wapo_options'] as $index => $option ) {
						$price_options = 0;
						foreach ( $option as $key => $value ) {
							if ( $key && '' !== $value ) {
								$values = \YITH_WAPO_Premium::get_instance()->split_addon_and_option_ids( $key, $value );

								$addon_id  = $values['addon_id'];
								$option_id = $values['option_id'];

								if ( $addon_id !== $addon_id_check ) {
									$first_free_options_count = 0;
									$addon_id_check           = $addon_id;
								}

								$info = yith_wapo_get_option_info( $addon_id, $option_id );

								if ( ! apply_filters( 'yith_wapo_show_options_grouped_in_cart', true ) && 'addon_title' === $option_id ) {
									$info['addon_type'] = 'hidden';
								}

								if ( 'percentage' === $info['price_type'] ) {
									$option_percentage      = floatval( $info['price'] );
									$option_percentage_sale = floatval( $info['price_sale'] );
									$option_price           = ( $product_price / 100 ) * $option_percentage;
									$option_price_sale      = ( $product_price / 100 ) * $option_percentage_sale;
								} elseif ( 'multiplied' === $info['price_type'] ) {
									$option_price      = floatval( $info['price'] ) * (float) $value * (float) $currency_rate;
									$option_price_sale = floatval( $info['price_sale'] ) * (float) $value * (float) $currency_rate;
								} elseif ( 'characters' === $info['price_type'] ) {
									$remove_spaces     = apply_filters( 'yith_wapo_remove_spaces', false );
									$value             = $remove_spaces ? str_replace( ' ', '', $value ) : $value;
									$option_price      = floatval( $info['price'] ) * strlen( $value ) * (float) $currency_rate;
									$option_price_sale = floatval( $info['price_sale'] ) * strlen( $value ) * (float) $currency_rate;
								} else {
									$option_price      = floatval( $info['price'] ) * (float) $currency_rate;
									$option_price_sale = floatval( $info['price_sale'] ) * (float) $currency_rate;
								}

								// First X free options check.
								if ( 'yes' === $info['addon_first_options_selected'] && $first_free_options_count < $info['addon_first_free_options'] ) {
									$option_price = 0;
									$first_free_options_count ++;
								} else {
									$option_price = $option_price_sale > 0 ? $option_price_sale : $option_price;
								}

								if ( in_array(
									$info['addon_type'],
									array(
										'checkbox',
										'color',
										'label',
										'radio',
										'select',
									),
									true
								) ) {
									$value = ! empty( $info['label'] ) ? $info['label'] : ( isset( $info['tooltip'] ) ? $info['tooltip'] : '' );
								} elseif ( 'product' === $info['addon_type'] ) {
									$option_product_info = explode( '-', $value );
									$option_product_id   = isset( $option_product_info[1] ) ? $option_product_info[1] : '';
									$option_product_qty  = isset( $cart_item['yith_wapo_qty_options'][ $key ] ) ? $cart_item['yith_wapo_qty_options'][ $key ] : 1;
									$option_product      = wc_get_product( $option_product_id );

									if ( $option_product && $option_product instanceof \WC_Product ) {
										// product prices.
										$product_price = $option_product->get_price();
										if ( 'product' === $info['price_method'] ) {
											$option_price = $product_price;
										} elseif ( 'discount' === $info['price_method'] ) {
											$option_discount_value = floatval( $info['price'] );
											if ( 'percentage' === $info['price_type'] ) {
												$option_price = $product_price - ( ( $product_price / 100 ) * $option_discount_value );
											} else {
												$option_price = $product_price - $option_discount_value;
											}
										}

										$option_price = $option_price * $option_product_qty;
									}
								} elseif ( 'number' === $info['addon_type'] ) {
									if ( 'value_x_product' === $info['price_method'] ) {
										$option_price = $value * $product_price;
									} else {
										if ( 'multiplied' === $info['price_type'] ) {
											$option_price = $value * $info['price'];
										}
									}
								}

								if ( 'free' === $info['price_method'] ) {
									$option_price = 0;
								}

								$option_price = '' !== $option_price ? $option_price : 0;
								$option_price = \YITH_WAPO_Premium::get_instance()->calculate_price_depending_on_tax( $option_price );
							}
							// get total price options
							$price_options = $price_options + $option_price;
						}
					}

					$product_obj                                  = $cart_item['data'];
					$cart_item['data']->yay_wapo_price_options    = (float) $price_options / YayCurrencyHelper::get_rate_fee( $this->apply_currency );
					$product_obj->yay_yith_wapo_set_options_price = $price_options;
					$product_obj->yay_yith_wapo_set_product_price_with_options_default = SupportHelper::get_product_price( $product_id ) + (float) $price_options / YayCurrencyHelper::get_rate_fee( $this->apply_currency );
					$product_obj->yay_yith_wapo_set_product_price_with_options         = $product_price + $price_options;

				}
			}
		}

	}

	public function get_price_with_options( $price, $product ) {
		$this->get_price_options_by_cart();
		if ( isset( $product->yay_yith_wapo_set_product_price_with_options ) ) {
			$price = $product->yay_yith_wapo_set_product_price_with_options;
		}
		return $price;
	}

}
