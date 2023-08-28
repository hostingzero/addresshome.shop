<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;

use Yay_Currency\Helpers\YayCurrencyHelper;
use Yay_Currency\Helpers\SupportHelper;
use Yay_Currency\Helpers\Helper;

defined( 'ABSPATH' ) || exit;

class WooCommerceSubscriptions {
	use SingletonTrait;
	private $converted_currency;
	private $apply_currency;
	private $is_dis_checkout_diff_currency;

	public function __construct() {

		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			return;
		}

		$this->converted_currency = YayCurrencyHelper::converted_currency();
		$this->apply_currency     = YayCurrencyHelper::get_apply_currency( $this->converted_currency );
		if ( ! $this->apply_currency ) {
			return;
		}
		$is_checkout_different_currency      = get_option( 'yay_currency_checkout_different_currency', 0 );
		$this->is_dis_checkout_diff_currency = YayCurrencyHelper::is_dis_checkout_diff_currency( $is_checkout_different_currency, $this->apply_currency['status'] );

		add_filter( 'yay_currency_get_price_default_in_checkout_page', array( $this, 'get_price_default_in_checkout_page' ), 10, 2 );

		add_filter( 'yay_currency_get_cart_item_price_3rd_plugin', array( $this, 'get_cart_item_price_3rd_plugin' ), 10, 3 );

		add_filter( 'woocommerce_subscriptions_product_sign_up_fee', array( $this, 'custom_subscription_sign_up_fee' ), 10, 2 );
		add_filter( 'woocommerce_subscriptions_product_price_string', array( $this, 'custom_subscription_price_string' ), 10, 3 );
		add_filter( 'woocommerce_subscriptions_price_string', array( $this, 'custom_subscription_price_string' ), 10, 3 );

		if ( $this->is_dis_checkout_diff_currency ) {
			// Recurring cart shipping
			add_filter( 'wcs_cart_totals_shipping_method', array( $this, 'wcs_cart_totals_shipping_method' ), 10, 3 );
			add_filter( 'wcs_cart_totals_shipping_method_price_label', array( $this, 'wcs_cart_totals_shipping_price_label' ), 10, 3 );
			// Recurring cart subtotal
			add_filter( 'woocommerce_cart_subscription_string_details', array( $this, 'woocommerce_cart_subscription_string_details' ), 10, 2 );
			// Recurring cart total tax
			add_filter( 'wcs_recurring_cart_itemized_tax_totals_html', array( $this, 'wcs_recurring_cart_itemized_tax_totals_html' ), 10, 4 );
			// Recurring cart total
			add_filter( 'wcs_cart_totals_order_total_html', array( $this, 'woocommerce_cart_totals_order_total_html' ), 10, 2 );
		}
		//Excute Renew now & Resubscribe
		add_filter( 'yay_currency_product_price_3rd_with_condition', array( $this, 'subscription_get_price_renew' ), 10, 2 );
	}

	public function get_price_default_in_checkout_page( $price, $product ) {

		$this->get_product_contains_renew_by_cart();

		if ( isset( $product->subscription_renewal_price_original_default ) ) {
			return $product->subscription_renewal_price_original_default;
		}

		if ( isset( $product->subscription_resubscribe_price_original_default ) ) {
			return $product->subscription_resubscribe_price_original_default;
		}

		if ( isset( $product->awdr_discount_price ) ) {
			return $product->awdr_discount_price;
		}

		return $price;

	}

	public function get_sign_up_fee_by_cart_item( $cart_item, $apply_currency ) {
		if ( class_exists( 'WC_Subscriptions_Product' ) ) {
			$sign_up_fee = \WC_Subscriptions_Product::get_sign_up_fee( $cart_item['data'] );
			if ( $sign_up_fee > 0 ) {
				return (float) $sign_up_fee;
			}
		}
		return 0;
	}

	public function get_cart_item_price_3rd_plugin( $product_price, $cart_item, $apply_currency ) {
		$product_price = SupportHelper::calculate_product_price_by_cart_item( $cart_item );
		$product_price = YayCurrencyHelper::calculate_price_by_currency( $product_price, false, $apply_currency );
		$sign_up_fee   = $this->get_sign_up_fee_by_cart_item( $cart_item, $apply_currency );
		return $sign_up_fee ? $product_price + YayCurrencyHelper::calculate_price_by_currency( $sign_up_fee, false, $apply_currency ) : $product_price;
	}

	public function get_product_contains_renew_by_cart() {
		$cart_contents = WC()->cart->get_cart_contents();
		if ( count( $cart_contents ) > 0 ) {
			foreach ( $cart_contents  as $key => $cart_item ) {
				$product_obj = $cart_item['data'];
				$product_id  = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
				$wc_product  = wc_get_product( $product_id );
				if ( isset( $cart_item['subscription_renewal'] ) ) {
					$order_id = $cart_item['subscription_renewal']['renewal_order_id'];
					if ( Helper::check_custom_orders_table_usage_enabled() ) {
						$order         = wc_get_order( $order_id );
						$currency_code = ! empty( $order->get_currency() ) ? $order->get_currency() : get_option( 'woocommerce_currency' );
					} else {
						$currency_code = get_post_meta( $order_id, '_order_currency', true ) ? get_post_meta( $order_id, '_order_currency', true ) : get_option( 'woocommerce_currency' );
					}
					if ( empty( $currency_code ) ) {
						$currency_code = get_option( 'woocommerce_currency' );
					}
					$order_currency = YayCurrencyHelper::filtered_by_currency_code( $currency_code, $this->converted_currency );
					if ( 1 != $order_currency['rate'] && ( $currency_code != $this->apply_currency['currency'] || YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) ) ) {
						$product_price                                    = $wc_product->get_price( 'edit' );
						$product_price_by_currency                        = YayCurrencyHelper::calculate_price_by_currency( $product_price, false, $this->apply_currency );
						$product_obj->subscription_renewal_price_original = $product_price_by_currency;
						$product_obj->subscription_renewal_price_original_default = $product_price;
					}
					$product_obj->subscription_renewal_price_currency = $currency_code;
				}
				if ( isset( $cart_item['subscription_resubscribe'] ) ) {
					$order_id = $cart_item['subscription_resubscribe']['subscription_id'];
					if ( Helper::check_custom_orders_table_usage_enabled() ) {
						$order         = wc_get_order( $order_id );
						$currency_code = ! empty( $order->get_currency() ) ? $order->get_currency() : get_option( 'woocommerce_currency' );
					} else {
						$currency_code = get_post_meta( $order_id, '_order_currency', true ) ? get_post_meta( $order_id, '_order_currency', true ) : get_option( 'woocommerce_currency' );
					}
					if ( empty( $currency_code ) ) {
						$currency_code = get_option( 'woocommerce_currency' );
					}
					$order_currency = YayCurrencyHelper::filtered_by_currency_code( $currency_code, $this->converted_currency );
					if ( 1 != $order_currency['rate'] && ( $currency_code != $this->apply_currency['currency'] || YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) ) ) {
						$product_price                                        = $wc_product->get_price( 'edit' );
						$product_price_by_currency                            = YayCurrencyHelper::calculate_price_by_currency( $product_price, false, $this->apply_currency );
						$product_obj->subscription_resubscribe_price_original = $product_price_by_currency;
						$product_obj->subscription_resubscribe_price_original_default = $product_price;
					}
					$product_obj->subscription_resubscribe_price_currency = $currency_code;
				}
			}
		}
	}

	public function subscription_get_price_renew( $price, $product ) {
		$this->get_product_contains_renew_by_cart();
		if ( isset( $product->subscription_renewal_price_currency ) ) {
			if ( $product->subscription_renewal_price_currency === $this->apply_currency['currency'] ) {
				return $product->get_price( 'edit' );
			} else {
				if ( isset( $product->subscription_renewal_price_original ) ) {
					return $product->subscription_renewal_price_original;
				}
			}
		}

		if ( isset( $product->subscription_resubscribe_price_currency ) ) {
			if ( $product->subscription_resubscribe_price_currency === $this->apply_currency['currency'] ) {
				return $product->get_price( 'edit' );
			} else {
				if ( isset( $product->subscription_resubscribe_price_original ) ) {
					return $product->subscription_resubscribe_price_original;
				}
			}
		}

		return $price;
	}

	public function get_period_string( $cart_item_key ) {
		if ( str_contains( $cart_item_key, 'daily' ) ) {
			return 'day';
		}
		if ( str_contains( $cart_item_key, 'weekly' ) ) {
			return 'week';
		}
		if ( str_contains( $cart_item_key, 'yearly' ) ) {
			return 'year';
		}
		if ( str_contains( $cart_item_key, 'monthly' ) ) {
			return 'month';
		}
	}

	public function custom_subscription_sign_up_fee( $sign_up_fee ) {
		if ( is_checkout() && $this->is_dis_checkout_diff_currency ) {
			return $sign_up_fee;
		}
		$converted_sign_up_fee = YayCurrencyHelper::calculate_price_by_currency( $sign_up_fee, false, $this->apply_currency );
		return $converted_sign_up_fee;
	}

	public function custom_subscription_price_string( $price_string, $product, $args ) {

		if ( is_checkout() ) {
			return $price_string;
		}

		$quantity = 1;

		if ( is_cart() ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {

				$item = $cart_item['data'];

				if ( ! empty( $item ) ) {
						$quantity = $cart_item['quantity'];
				}
			}
		}

		$signup_fee_original  = get_post_meta( $product->get_id(), '_subscription_sign_up_fee', true );
		$signup_fee_original  = $signup_fee_original ? $signup_fee_original : 0;
		$converted_signup_fee = YayCurrencyHelper::calculate_price_by_currency( $signup_fee_original, false, $this->apply_currency ) * $quantity;
		$formatted_signup_fee = YayCurrencyHelper::format_price( $converted_signup_fee );

		$custom_sign_up_fee = ( isset( $args['sign_up_fee'] ) && 0 != $signup_fee_original ) ? __( ' and a ' . wp_kses_post( $formatted_signup_fee ) . ' sign-up fee', 'woocommerce' ) : '';

		if ( in_array( $product->get_type(), array( 'variable-subscription' ) ) ) {
			$min_price = $product->get_variation_price( 'min', true );

			$formatted_price            = YayCurrencyHelper::format_price( $min_price );
			$price_string_no_html       = strip_tags( $price_string );
			$price_string_no_fee_string = substr( $price_string_no_html, 0, strpos( $price_string_no_html, 'and' ) ); // remove default sign-up fee string
			$start_index_to_cut_string  = strpos( $price_string_no_html, ' /' ) ? strpos( $price_string_no_html, ' /' ) : ( strpos( $price_string_no_html, ' every' ) ? strpos( $price_string_no_html, ' every' ) : strpos( $price_string_no_html, ' for' ) );
			$interval_subscrition       = substr( empty( $price_string_no_fee_string ) ? $price_string_no_html : $price_string_no_fee_string, $start_index_to_cut_string ); // get default interval subscrition (ex: /month or every x days...)
			$price_string               = __( 'From: ', 'woocommerce' ) . $formatted_price . $interval_subscrition . $custom_sign_up_fee;
		}

		return $price_string;
	}

	public function wcs_cart_totals_shipping_method( $label, $method, $cart ) {
		if ( is_checkout() ) {
			if ( 'Free shipping' === $label ) {
				return $label;
			}

			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] ) {
				return $label;
			}
			$shipping_fee                             = (float) $method->cost;
			$converted_shipping_fee                   = YayCurrencyHelper::calculate_price_by_currency( $shipping_fee, false, $this->apply_currency );
			$formatted_shipping_fee                   = YayCurrencyHelper::format_price( $converted_shipping_fee );
			$shipping_method_label                    = $method->label;
			$formatted_fallback_currency_shipping_fee = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['fallback_currency'], $shipping_fee );

			$converted_approximately = apply_filters( 'yay_currency_checkout_converted_approximately', true, $this->apply_currency );
			if ( ! $converted_approximately ) {
				$label = '' . $shipping_method_label . ': ' . $formatted_fallback_currency_shipping_fee . ' / ' . $this->get_period_string( $cart->recurring_cart_key );
			} else {
				$formatted_shipping_fee_html = YayCurrencyHelper::converted_approximately_html( $formatted_shipping_fee );
				$label                       = '' . $shipping_method_label . ': ' . $formatted_fallback_currency_shipping_fee . $formatted_shipping_fee_html . ' / ' . $this->get_period_string( $cart->recurring_cart_key );
			}
		}
		return $label;
	}

	public function wcs_cart_totals_shipping_price_label( $price_label, $method, $cart ) {
		if ( is_checkout() && 0 < $method->cost ) {
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] ) {
				return $price_label;
			}
			$display_prices_include_tax = wcs_is_woocommerce_pre( '3.3' ) ? ( 'incl' === WC()->cart->tax_display_cart ) : WC()->cart->display_prices_including_tax();
			if ( ! $display_prices_include_tax ) {
				$shipping_fee = (float) $method->cost;
			} else {
				$shipping_fee = (float) $method->cost + $method->get_shipping_tax();
			}
			$converted_shipping_fee = YayCurrencyHelper::calculate_price_by_currency( $shipping_fee, false, $this->apply_currency );
			$formatted_shipping_fee = YayCurrencyHelper::format_price( $converted_shipping_fee );

			$formatted_fallback_currency_shipping_fee = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['fallback_currency'], $shipping_fee );

			$converted_approximately = apply_filters( 'yay_currency_checkout_converted_approximately', true, $this->apply_currency );
			if ( ! $converted_approximately ) {
				$price_label = $formatted_fallback_currency_shipping_fee . ' / ' . $this->get_period_string( $cart->recurring_cart_key );
			} else {
				$formatted_shipping_fee_html = YayCurrencyHelper::converted_approximately_html( $formatted_shipping_fee );
				$price_label                 = $formatted_fallback_currency_shipping_fee . $formatted_shipping_fee_html . ' / ' . $this->get_period_string( $cart->recurring_cart_key );
			}

			if ( $method->get_shipping_tax() > 0 && ! $cart->prices_include_tax ) {
				$price_label .= ' <small>' . WC()->countries->inc_tax_or_vat() . '</small>';
			}
		}

		return $price_label;
	}

	public function woocommerce_cart_subscription_string_details( $data, $cart ) {
		if ( is_checkout() ) {
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] ) {
				return $data;
			}
			$recurring_cart_amount                   = $cart->get_displayed_subtotal();
			$convert_recurring_cart_amount           = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['fallback_currency'], $recurring_cart_amount );
			$subtotal                                = $this->get_subtotal_price_sign_up_fee( $this->apply_currency );
			$formatted_convert_recurring_cart_amount = YayCurrencyHelper::format_price( $subtotal );

			$converted_approximately = apply_filters( 'yay_currency_checkout_converted_approximately', true, $this->apply_currency );
			if ( ! $converted_approximately ) {
				$data['recurring_amount'] = $convert_recurring_cart_amount;
			} else {
				$formatted_convert_recurring_cart_amount_html = YayCurrencyHelper::converted_approximately_html( $formatted_convert_recurring_cart_amount );
				$data['recurring_amount']                     = $convert_recurring_cart_amount . $formatted_convert_recurring_cart_amount_html;
			}
		}
		return $data;

	}

	public function get_subtotal_price_sign_up_fee( $apply_currency, $recurring_cart_tax = false ) {
		$subtotal      = 0;
		$cart_contents = WC()->cart->get_cart_contents();
		foreach ( $cart_contents  as $key => $cart_item ) {
			$product = $cart_item['data'];
			if ( ! class_exists( 'WC_Subscriptions_Product' ) || ! \WC_Subscriptions_Product::is_subscription( $product ) ) {
				continue;
			}
			$price_options = SupportHelper::get_price_options_by_3rd_plugin( $product );
			remove_filter( 'yay_currency_get_cart_item_price_3rd_plugin', array( $this, 'get_cart_item_price_3rd_plugin' ), 10, 3 );
			$product_price = YayCurrencyHelper::calculate_price_by_currency( $product->get_price( 'edit' ), false, $apply_currency ) + $price_options;
			$product_price = apply_filters( 'yay_currency_get_cart_item_price_3rd_plugin', $product_price, $cart_item, $apply_currency );
			$subtotal      = $subtotal + $product_price * $cart_item['quantity'];
		}

		return $subtotal;
	}


	public function get_recurring_shiping_total() {
		$recurring_total = 0;
		if ( isset( WC()->cart->recurring_carts ) && ! empty( WC()->cart->recurring_carts ) ) {
			foreach ( WC()->cart->recurring_carts as $cart ) {
				$recurring_total += $cart->shipping_total;
			}
		}

		return $recurring_total;
	}

	public function get_recurring_cart_total_tax( $recurring_tax ) {
		$total_tax         = 0;
		$subtotal          = $this->get_subtotal_price_sign_up_fee( $this->apply_currency );
		$tax_rate          = \WC_Tax::_get_tax_rate( $recurring_tax->tax_rate_id );
		$shipping_total    = YayCurrencyHelper::calculate_price_by_currency( $this->get_recurring_shiping_total(), false, $this->apply_currency );
		$tax_rate_shipping = isset( $tax_rate['tax_rate_shipping'] ) ? (int) $tax_rate['tax_rate_shipping'] : false;
		$tax_amount        = (float) $tax_rate['tax_rate'];
		if ( $tax_rate_shipping ) {
			$total_tax = ( $subtotal + $shipping_total ) * $tax_amount / 100;
		} else {
			$total_tax = $subtotal * $tax_amount / 100;
		}
		return $total_tax;
	}

	public function wcs_recurring_cart_itemized_tax_totals_html( $amount_html, $recurring_cart, $recurring_code, $recurring_tax ) {
		if ( is_checkout() ) {
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] ) {
				return $amount_html;
			}
			$amount    = $recurring_tax->amount;
			$total_tax = YayCurrencyHelper::calculate_price_by_currency( $amount, false, $this->apply_currency );
			if ( 'disabled' !== $this->apply_currency['roundingType'] ) {
				$total_tax = $this->get_recurring_cart_total_tax( $recurring_tax );
			}
			$converted_tax_amount           = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['fallback_currency'], $amount );
			$formatted_converted_tax_amount = YayCurrencyHelper::format_price( $total_tax );

			$converted_approximately = apply_filters( 'yay_currency_checkout_converted_approximately', true, $this->apply_currency );
			if ( ! $converted_approximately ) {
				$amount_html = $converted_tax_amount . ' / ' . $this->get_period_string( $recurring_cart->recurring_cart_key );
			} else {
				$formatted_converted_tax_amount_html = YayCurrencyHelper::converted_approximately_html( $formatted_converted_tax_amount );
				$amount_html                         = $converted_tax_amount . $formatted_converted_tax_amount_html . ' / ' . $this->get_period_string( $recurring_cart->recurring_cart_key );
			}
		}
		return $amount_html;
	}

	public function get_recurring_cart_total() {
		$recurring_total = 0;

		if ( ! empty( WC()->cart->recurring_carts ) ) {
			foreach ( WC()->cart->recurring_carts as $recurring_cart ) {
				$recurring_total += $recurring_cart->total;
			}
		}

		return $recurring_total;
	}

	public function get_recurring_cart_total_include_tax_shipping() {
		$total_tax      = 0;
		$subtotal       = $this->get_subtotal_price_sign_up_fee( $this->apply_currency );
		$shipping_total = $this->get_recurring_shiping_total();
		foreach ( WC()->cart->get_taxes() as $tax_id => $tax_total ) {
			foreach ( WC()->cart->recurring_carts as $recurring_cart_key => $recurring_cart ) {
				foreach ( $recurring_cart->get_tax_totals() as $recurring_code => $recurring_tax ) {
					if ( ! isset( $recurring_tax->tax_rate_id ) || $recurring_tax->tax_rate_id !== $tax_id ) {
						continue;
					}
					$total_tax += $this->get_recurring_cart_total_tax( $recurring_tax );
				}
			}
		}
		return $subtotal + $total_tax + $shipping_total;
	}

	public function woocommerce_cart_totals_order_total_html( $order_total_html, $cart ) {
		if ( is_checkout() ) {
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] ) {
				return $order_total_html;
			}
			$recurring_total         = $this->get_recurring_cart_total();
			$convert_recurring_total = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['fallback_currency'], $recurring_total );
			if ( $this->apply_currency && 'disabled' !== $this->apply_currency['roundingType'] ) {
				$recurring_total_apply_currency = $this->get_recurring_cart_total_include_tax_shipping();
			} else {
				$recurring_total_apply_currency = YayCurrencyHelper::calculate_price_by_currency( $recurring_total, true, $this->apply_currency );
			}

			$formatted_convert_recurring_cart_amount = YayCurrencyHelper::format_price( $recurring_total_apply_currency );

			$converted_approximately = apply_filters( 'yay_currency_checkout_converted_approximately', true, $this->apply_currency );
			if ( ! $converted_approximately ) {
				$order_total_html = '<strong>' . $convert_recurring_total . '</strong> / ' . $this->get_period_string( $cart->recurring_cart_key );
			} else {
				$formatted_convert_recurring_cart_amount_html = YayCurrencyHelper::converted_approximately_html( $formatted_convert_recurring_cart_amount );
				$order_total_html                             = '<strong>' . $convert_recurring_total . $formatted_convert_recurring_cart_amount_html . '</strong> / ' . $this->get_period_string( $cart->recurring_cart_key );
			}
		}

		return $order_total_html;
	}
}
