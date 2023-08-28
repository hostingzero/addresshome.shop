<?php

namespace Yay_Currency\Engine\FEPages;

use Yay_Currency\Helpers\YayCurrencyHelper;
use Yay_Currency\Helpers\SupportHelper;
use Yay_Currency\Helpers\Helper;

use Yay_Currency\Utils\SingletonTrait;

defined( 'ABSPATH' ) || exit;
class WooCommerceCurrency {
	use SingletonTrait;

	private $converted_currency = array();
	private $apply_currency     = null;
	private $is_checkout_different_currency;
	private $is_dis_checkout_diff_currency = false;
	private $current_theme;

	private $fee_cost = 0;

	public function __construct() {
		add_action( 'init', array( $this, 'add_woocommerce_filters' ) );
	}

	public function add_woocommerce_filters() {
		$this->converted_currency = YayCurrencyHelper::converted_currency();
		$this->apply_currency     = YayCurrencyHelper::get_apply_currency( $this->converted_currency );

		$this->is_checkout_different_currency = get_option( 'yay_currency_checkout_different_currency', 0 );
		$this->is_dis_checkout_diff_currency  = YayCurrencyHelper::is_dis_checkout_diff_currency( $this->is_checkout_different_currency, $this->apply_currency['status'] );
		$this->current_theme                  = wp_get_theme()->template;

		if ( ! is_admin() ) {
			YayCurrencyHelper::set_cookies( $this->apply_currency );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			$price_filters_priority = apply_filters( 'yay_currency_filters_priority', 10 );

			add_filter( 'woocommerce_product_get_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_product_get_regular_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_product_get_sale_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );

			add_filter( 'woocommerce_product_variation_get_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );

			add_filter( 'woocommerce_variation_prices_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );
			add_filter( 'woocommerce_variation_prices_sale_price', array( $this, 'custom_raw_price' ), $price_filters_priority, 2 );

			add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'custom_variation_price_hash' ), $price_filters_priority, 1 );

			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'conditional_payment_gateways' ), 10, 1 );

			add_action( 'woocommerce_before_mini_cart', array( $this, 'custom_mini_cart_price' ), 10 );

			if ( $this->is_dis_checkout_diff_currency ) {
				add_filter( 'woocommerce_cart_product_subtotal', array( $this, 'custom_checkout_product_subtotal' ), 10, 4 );
				add_action( 'woocommerce_before_checkout_form', array( $this, 'add_notice_checkout_payment_methods' ), 1000 );
				add_filter( 'woocommerce_cart_subtotal', array( $this, 'custom_checkout_order_subtotal' ), 10, 3 );
				add_filter( 'woocommerce_cart_total', array( $this, 'custom_checkout_order_total' ) );
				add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'custom_shipping_fee' ), 10, 2 );
				add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'custom_discount_coupon' ), 10, 3 );
				add_filter( 'woocommerce_cart_tax_totals', array( $this, 'custom_total_tax' ), 10, 2 );
				add_filter( 'woocommerce_cart_get_taxes', array( $this, 'custom_cart_taxes' ), 10, 1 );
				add_filter( 'woocommerce_cart_totals_fee_html', array( $this, 'custom_cart_totals_fee_html' ), 10, 2 );
			}

			add_action( 'woocommerce_checkout_create_order', array( $this, 'custom_checkout_create_order' ), 10, 2 );
			add_action( 'woocommerce_checkout_create_order_shipping_item', array( $this, 'custom_checkout_create_order_shipping_item' ), 10, 4 );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'add_order_currency_meta' ), 10, 2 );

		}

		add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'change_format_order_line_subtotal' ), 10, 3 );// frontend
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'change_format_order_item_totals' ), 10, 3 );// frontend
		// add_filter( 'woocommerce_get_formatted_order_total', array( $this, 'get_formatted_order_total' ), 10, 4 ); // admin & frontend
		add_filter( 'woocommerce_order_subtotal_to_display', array( $this, 'get_formatted_order_subtotal' ), 10, 3 ); // frontend

		add_filter( 'woocommerce_order_shipping_to_display', array( $this, 'get_formatted_order_shipping' ), 10, 3 );
		add_filter( 'woocommerce_order_discount_to_display', array( $this, 'get_formatted_order_discount' ), 10, 2 );
		add_filter( 'woocommerce_package_rates', array( $this, 'change_shipping_cost' ), 10, 2 );

		// Filter to Coupon Min/Max
		add_filter( 'woocommerce_coupon_get_amount', array( $this, 'change_coupon_amount' ), 10, 2 );
		add_filter( 'woocommerce_coupon_get_minimum_amount', array( $this, 'change_coupon_amount' ), 10, 2 );
		add_filter( 'woocommerce_coupon_get_maximum_amount', array( $this, 'change_coupon_amount' ), 10, 2 );

		add_filter( 'woocommerce_stripe_request_body', array( $this, 'custom_stripe_request_total_amount' ), 10, 2 );

		// Free shipping with minimum amount
		add_filter( 'woocommerce_shipping_free_shipping_instance_option', array( $this, 'custom_free_shipping_min_amount' ), 10, 3 );
		add_filter( 'woocommerce_shipping_free_shipping_option', array( $this, 'custom_free_shipping_min_amount' ), 10, 3 );

		// Custom price fees
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'recalculate_cart_fees' ), 10, 1 );

	}

	public function enqueue_scripts() {
		wp_register_script( 'yay-currency-frontend-script', YAY_CURRENCY_PLUGIN_URL . 'src/script.js', array(), YAY_CURRENCY_VERSION, true );
		wp_localize_script(
			'yay-currency-frontend-script',
			'yayCurrency',
			array(
				'admin_url'               => admin_url( 'admin.php?page=wc-settings' ),
				'ajaxurl'                 => admin_url( 'admin-ajax.php' ),
				'nonce'                   => wp_create_nonce( 'yay-currency-nonce' ),
				'isShowOnMenu'            => get_option( 'yay_currency_show_menu', 0 ),
				'shortCode'               => do_shortcode( '[yaycurrency-menu-item-switcher]' ),
				'isPolylangCompatible'    => get_option( 'yay_currency_polylang_compatible', 0 ),
				'isDisplayFlagInSwitcher' => get_option( 'yay_currency_show_flag_in_switcher', 1 ),
				'yayCurrencyPluginURL'    => YAY_CURRENCY_PLUGIN_URL,
				'converted_currency'      => $this->converted_currency,
			)
		);

		wp_enqueue_style(
			'yay-currency-frontend-style',
			YAY_CURRENCY_PLUGIN_URL . 'src/styles.css',
			array(),
			YAY_CURRENCY_VERSION
		);

		wp_enqueue_script( 'yay-currency-frontend-script' );

	}

	public function custom_checkout_create_order( $order ) {
		if ( 0 != $this->is_checkout_different_currency && 0 == $this->apply_currency['status'] ) {
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] != $currencies_data['fallback_currency']['currency'] ) {
				$order_items    = $order->get_items();
				$subtotal       = $order->get_subtotal();
				$total          = $order->get_total();
				$discount_total = $order->get_discount_total();
				$shipping_total = $order->get_shipping_total();
				$cart_tax       = $order->get_cart_tax();
				$shipping_tax   = $order->get_shipping_tax();

				$apply_currency = $currencies_data['fallback_currency'];
				foreach ( $order_items as $item ) {
					$item_subtotal     = $item->get_subtotal();
					$item_total        = $item->get_total();
					$item_subtotal_tax = $item->get_subtotal_tax();

					$item->set_subtotal( YayCurrencyHelper::calculate_price_by_currency( $item_subtotal, false, $apply_currency ) );
					$item->set_total( YayCurrencyHelper::calculate_price_by_currency( $item_total, false, $apply_currency ) );
					$item->set_subtotal_tax( YayCurrencyHelper::calculate_price_by_currency( $item_subtotal_tax, false, $apply_currency ) );
					$item->save();
				}

				$order->subtotal = YayCurrencyHelper::calculate_price_by_currency( $subtotal, false, $apply_currency );
				$order->set_currency( $currencies_data['fallback_currency']['currency'] );
				$order->set_total( YayCurrencyHelper::calculate_price_by_currency( $total, false, $apply_currency ) );
				$order->set_discount_total( YayCurrencyHelper::calculate_price_by_currency( $discount_total, false, $apply_currency ) );
				$order->set_shipping_total( YayCurrencyHelper::calculate_price_by_currency( $shipping_total, false, $apply_currency ) );
				$order->set_cart_tax( YayCurrencyHelper::calculate_price_by_currency( $cart_tax, false, $apply_currency ) );
				$order->set_shipping_tax( YayCurrencyHelper::calculate_price_by_currency( $shipping_tax, false, $apply_currency ) );

			}
		}
	}

	public function custom_checkout_create_order_shipping_item( $item, $package_key, $package, $order ) {
		if ( 0 != $this->is_checkout_different_currency && 0 == $this->apply_currency['status'] ) {
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] != $currencies_data['fallback_currency']['currency'] ) {
				$shipping_item_total = $item->get_total();
				$apply_currency      = $currencies_data['fallback_currency'];
				$item->set_total( YayCurrencyHelper::calculate_price_by_currency( $shipping_item_total, false, $apply_currency ) );
				$item->save();
				$this->apply_currency = $currencies_data['current_currency'];
			}
		}
	}

	public function recalculate_cart_fees( $cart ) {

		if ( is_admin() && ! defined( 'DOING_AJAX' ) || class_exists( 'Woocommerce_Conditional_Product_Fees_For_Checkout_Pro' ) || class_exists( 'TaxamoClass' ) ) { // Fix compatible WooCommerce Conditional Product Fees + Taxamo
			return;
		} else {

			if ( YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) ) {
				return;
			}

			foreach ( $cart->get_fees() as $fee ) {
				$amount      = YayCurrencyHelper::calculate_price_by_currency( $fee->amount, false, $this->apply_currency );
				$fee->amount = $amount;
			}
		}

	}

	public function custom_free_shipping_min_amount( $option, $key, $method ) {
		if ( YayCurrencyHelper::is_dis_checkout_diff_currency( $this->apply_currency ) ) {
			return $option;
		}
		if ( 'min_amount' !== $key || ! is_numeric( $option ) ) {
			return $option;
		}

		$converted_min_amount = YayCurrencyHelper::calculate_price_by_currency( $option, false, $this->apply_currency );

		return $converted_min_amount;

	}

	public function custom_stripe_request_total_amount( $request, $api ) {
		$request = apply_filters( 'yay_currency_stripe_request_amount', $request, $api, $this->apply_currency );
		return $request;
	}

	// Change currency when send mail start

	public function change_format_order_item_totals( $total_rows, $order, $tax_display ) {
		if ( isset( $_GET['action'] ) && 'generate_wpo_wcpdf' === $_GET['action'] ) {
			return $total_rows;
		}
		if ( Helper::check_custom_orders_table_usage_enabled() ) {
			$yay_currency_checkout_currency = $order->get_currency();
		} else {
			$yay_currency_checkout_currency = get_post_meta( $order->get_id(), '_order_currency', true );
		}
		if ( ! empty( $yay_currency_checkout_currency ) ) {
			$convert_currency = YayCurrencyHelper::get_convert_currency_by_checkout_currency( $this->converted_currency, $this->apply_currency, $yay_currency_checkout_currency );
			// Fee
			$fees = $order->get_fees();
			if ( $fees ) {
				foreach ( $fees as $id => $fee ) {
					if ( apply_filters( 'woocommerce_get_order_item_totals_excl_free_fees', empty( $fee['line_total'] ) && empty( $fee['line_tax'] ), $id ) ) {
						continue;
					}
					$price_format                          = 'excl' === $tax_display ? $fee->get_total() : $fee->get_total() + $fee->get_total_tax();
					$total_rows[ 'fee_' . $fee->get_id() ] = array(
						'label' => $fee->get_name() . ':',
						'value' => YayCurrencyHelper::get_formatted_total_by_convert_currency( $price_format, $convert_currency, $yay_currency_checkout_currency ),
					);

				}
			}
			// Tax for tax exclusive prices.
			if ( 'excl' === $tax_display && wc_tax_enabled() ) {
				if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
					foreach ( $order->get_tax_totals() as $code => $tax ) {
						$formatted_tax_amount                  = YayCurrencyHelper::get_formatted_total_by_convert_currency( $tax->amount, $convert_currency, $yay_currency_checkout_currency );
						$total_rows[ sanitize_title( $code ) ] = array(
							'label' => $tax->label . ':',
							'value' => $formatted_tax_amount, // $tax->formatted_amount
						);
					}
				} else {
					$total_rows['tax'] = array(
						'label' => WC()->countries->tax_or_vat() . ':',
						'value' => YayCurrencyHelper::get_formatted_total_by_convert_currency( $order->get_total_tax(), $convert_currency, $yay_currency_checkout_currency ),
					);
				}
			}
			// Refund
			if ( method_exists( $order, 'get_refunds' ) ) {
				$refunds = $order->get_refunds();
				if ( $refunds ) {
					foreach ( $refunds as $id => $refund ) {
						$total_rows[ 'refund_' . $id ] = array(
							'label' => $refund->get_reason() ? $refund->get_reason() : __( 'Refund', 'woocommerce' ) . ':',
							'value' => YayCurrencyHelper::get_formatted_total_by_convert_currency( '-' . $refund->get_amount(), $convert_currency, $yay_currency_checkout_currency ),
						);
					}
				}
			}
		}
		return $total_rows;
	}

	public function get_formatted_order_total( $formatted_total, $order, $tax_display ) {
		if ( ( isset( $_GET['action'] ) && 'generate_wpo_wcpdf' === $_GET['action'] ) || ( isset( $_GET['_fs_blog_admin'] ) && 'true' === $_GET['_fs_blog_admin'] ) ) {
			return $formatted_total;
		}
		if ( Helper::check_custom_orders_table_usage_enabled() ) {
			$yay_currency_checkout_currency = $order->get_currency();
		} else {
			$yay_currency_checkout_currency = get_post_meta( $order->get_id(), '_order_currency', true );
		}
		if ( ! empty( $yay_currency_checkout_currency ) ) {

			$converted_currency = YayCurrencyHelper::converted_currency();
			$convert_currency   = YayCurrencyHelper::get_convert_currency_by_checkout_currency( $converted_currency, array(), $yay_currency_checkout_currency );

			if ( ! $convert_currency ) {
				return $formatted_total;
			}

			if ( Helper::check_custom_orders_table_usage_enabled() ) {
				$total = $order->get_total();
			} else {
				$total = get_post_meta( $order->get_id(), '_order_total', true );
			}
			$formatted_total = YayCurrencyHelper::get_formatted_total_by_convert_currency( $total, $convert_currency, $yay_currency_checkout_currency );
			if ( wc_tax_enabled() && 'incl' === $tax_display ) {
				$formatted_tax   = YayCurrencyHelper::get_formatted_total_by_convert_currency( $order->get_total_tax(), $convert_currency, $yay_currency_checkout_currency );
				$formatted_total = $formatted_total . ' (includes ' . $formatted_tax . ' Tax)';
			}
		}
		return $formatted_total;
	}

	public function change_format_order_line_subtotal( $subtotal, $item, $order ) {
		// WooCommerce Product Bundles
		if ( class_exists( 'WC_Bundles' ) && wc_pb_is_bundle_container_order_item( $item ) ) {
			return $subtotal;
		}

		if ( Helper::check_custom_orders_table_usage_enabled() ) {
			$yay_currency_checkout_currency = $order->get_currency();
		} else {
			$yay_currency_checkout_currency = get_post_meta( $order->get_id(), '_order_currency', true );
		}
		if ( ! empty( $yay_currency_checkout_currency ) ) {
			$convert_currency = YayCurrencyHelper::get_convert_currency_by_checkout_currency( $this->converted_currency, $this->apply_currency, $yay_currency_checkout_currency );
			$tax_display      = get_option( 'woocommerce_tax_display_cart' );
			if ( 'excl' === $tax_display ) {
				$ex_tax_label = $order->get_prices_include_tax() ? 1 : 0;
				$subtotal     = YayCurrencyHelper::get_formatted_total_by_convert_currency( $order->get_line_subtotal( $item ), $convert_currency, $yay_currency_checkout_currency, $ex_tax_label );
			} else {
				$subtotal = YayCurrencyHelper::get_formatted_total_by_convert_currency( $order->get_line_subtotal( $item, true ), $convert_currency, $yay_currency_checkout_currency );
			}
		}
		return $subtotal;
	}

	public function get_formatted_order_subtotal( $subtotal, $compound, $order ) {
		if ( Helper::check_custom_orders_table_usage_enabled() ) {
			$yay_currency_checkout_currency = $order->get_currency();
		} else {
			$yay_currency_checkout_currency = get_post_meta( $order->get_id(), '_order_currency', true );
		}
		if ( ! empty( $yay_currency_checkout_currency ) ) {
			$convert_currency = YayCurrencyHelper::get_convert_currency_by_checkout_currency( $this->converted_currency, $this->apply_currency, $yay_currency_checkout_currency );
			$tax_display      = get_option( 'woocommerce_tax_display_cart' );
			$subtotal         = YayCurrencyHelper::get_cart_subtotal_for_order( $order );

			if ( ! $compound ) {
				if ( 'incl' === $tax_display ) {
					$subtotal_taxes = 0;
					foreach ( $order->get_items() as $item ) {
						$subtotal_taxes += YayCurrencyHelper::round_line_tax( $item->get_subtotal_tax(), false );
					}
					$subtotal += wc_round_tax_total( $subtotal_taxes );
				}
				$subtotal = YayCurrencyHelper::get_formatted_total_by_convert_currency( $subtotal, $convert_currency, $yay_currency_checkout_currency );
				if ( 'excl' === $tax_display && $order->get_prices_include_tax() && wc_tax_enabled() ) {
					$subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
				}
			} else {
				if ( 'incl' === $tax_display ) {
					return '';
				}
				// Add Shipping Costs.
				$subtotal += $order->get_shipping_total();
				// Remove non-compound taxes.
				foreach ( $order->get_taxes() as $tax ) {
					if ( $tax->is_compound() ) {
						continue;
					}
					$subtotal = $subtotal + $tax->get_tax_total() + $tax->get_shipping_tax_total();
				}
				// Remove discounts.
				$subtotal = $subtotal - $order->get_total_discount();
				$subtotal = YayCurrencyHelper::get_formatted_total_by_convert_currency( $subtotal, $convert_currency, $yay_currency_checkout_currency );
			}
		}
		return $subtotal;
	}

	public function get_formatted_order_shipping( $shipping, $order, $tax_display ) {
		if ( Helper::check_custom_orders_table_usage_enabled() ) {
			$yay_currency_checkout_currency = $order->get_currency();
		} else {
			$yay_currency_checkout_currency = get_post_meta( $order->get_id(), '_order_currency', true );
		}
		if ( ! empty( $yay_currency_checkout_currency ) ) {
			$convert_currency = YayCurrencyHelper::get_convert_currency_by_checkout_currency( $this->converted_currency, $this->apply_currency, $yay_currency_checkout_currency );
			$tax_display      = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_cart' );

			if ( 0 < abs( (float) $order->get_shipping_total() ) ) {
				if ( 'excl' === $tax_display ) {
					// Show shipping excluding tax.
					$shipping = YayCurrencyHelper::get_formatted_total_by_convert_currency( $order->get_shipping_total(), $convert_currency, $yay_currency_checkout_currency );
					if ( (float) $order->get_shipping_tax() > 0 && $order->get_prices_include_tax() ) {
						$shipping .= apply_filters( 'woocommerce_order_shipping_to_display_tax_label', '&nbsp;<small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>', $order, $tax_display );
					}
				} else {
					// Show shipping including tax.
					$shipping = YayCurrencyHelper::get_formatted_total_by_convert_currency( $order->get_shipping_total() + $order->get_shipping_tax(), $convert_currency, $yay_currency_checkout_currency );
					if ( (float) $order->get_shipping_tax() > 0 && ! $order->get_prices_include_tax() ) {
						$shipping .= apply_filters( 'woocommerce_order_shipping_to_display_tax_label', '&nbsp;<small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>', $order, $tax_display );
					}
				}
				/* translators: %s: method */
				$shipping .= apply_filters( 'woocommerce_order_shipping_to_display_shipped_via', '&nbsp;<small class="shipped_via">' . sprintf( __( 'via %s', 'woocommerce' ), $order->get_shipping_method() ) . '</small>', $order );
			} elseif ( $order->get_shipping_method() ) {
				$shipping = $order->get_shipping_method();
			} else {
				$shipping = __( 'Free!', 'woocommerce' );
			}
		}
		return $shipping;
	}

	public function get_formatted_order_discount( $tax_display, $order ) {
		if ( Helper::check_custom_orders_table_usage_enabled() ) {
			$yay_currency_checkout_currency = $order->get_currency();
		} else {
			$yay_currency_checkout_currency = get_post_meta( $order->get_id(), '_order_currency', true );
		}
		if ( ! empty( $yay_currency_checkout_currency ) ) {
			$convert_currency = YayCurrencyHelper::get_convert_currency_by_checkout_currency( $this->converted_currency, $this->apply_currency, $yay_currency_checkout_currency );
			// $price_format     = $order->get_total_discount( 'excl' === $tax_display && 'excl' === get_option( 'woocommerce_tax_display_cart' ) );
			$price_format = $order->get_total_discount();
			$tax_display  = YayCurrencyHelper::get_formatted_total_by_convert_currency( $price_format, $convert_currency, $yay_currency_checkout_currency );
		}
		return $tax_display;
	}
	protected function evaluate_cost( $sum, $args = array(), $caculate_checkout_fallback = false ) {
		if ( ! is_array( $args ) || ! array_key_exists( 'qty', $args ) || ! array_key_exists( 'cost', $args ) ) {
			wc_doing_it_wrong( __FUNCTION__, '$args must contain `cost` and `qty` keys.', '4.0.1' );
		}

		include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';
		$args           = apply_filters( 'woocommerce_evaluate_shipping_cost_args', $args, $sum, $this );
		$locale         = localeconv();
		$decimals       = array( wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',' );
		$this->fee_cost = $args['cost'];
		add_shortcode( 'fee', array( $this, 'fee' ) );
		if ( $caculate_checkout_fallback ) {
			add_shortcode( 'fee_checkout_fallback', array( $this, 'fee_checkout_fallback' ) );
			$sum = str_replace( '[fee', '[fee_checkout_fallback', $sum );
		}
		$sum = do_shortcode(
			str_replace(
				array(
					'[qty]',
					'[cost]',
				),
				array(
					$args['qty'],
					$args['cost'],
				),
				$sum
			)
		);
		remove_shortcode( 'fee', array( $this, 'fee' ) );
		if ( $caculate_checkout_fallback ) {
			remove_shortcode( 'fee_checkout_fallback', array( $this, 'fee_checkout_fallback' ) );
		}
		$sum = preg_replace( '/\s+/', '', $sum );
		$sum = str_replace( $decimals, '.', $sum );
		$sum = rtrim( ltrim( $sum, "\t\n\r\0\x0B+*/" ), "\t\n\r\0\x0B+-*/" );
		return $sum ? \WC_Eval_Math::evaluate( $sum ) : 0;
	}
	public function caculate_cost_fee( $atts = array(), $checkout_fallback = false ) {
		$min_fee                = $atts['min_fee'];
		$max_fee                = $atts['max_fee'];
		$caculate_with_currency = false;

		if ( 1 != $this->apply_currency['rate'] ) {
			if ( $checkout_fallback ) {
				$caculate_with_currency = true;
			} elseif ( ! YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) ) {
				$caculate_with_currency = true;
			}
		}

		if ( $caculate_with_currency ) {
			$min_fee = YayCurrencyHelper::calculate_price_by_currency( $min_fee, false, $this->apply_currency );
			$max_fee = YayCurrencyHelper::calculate_price_by_currency( $max_fee, false, $this->apply_currency );
		}

		$calculated_fee = 0;

		if ( $atts['percent'] ) {
			$calculated_fee = $this->fee_cost * ( floatval( $atts['percent'] ) / 100 );
		}

		if ( $min_fee && $calculated_fee < $min_fee ) {
			$calculated_fee = $min_fee;
		}

		if ( $max_fee && $calculated_fee > $max_fee ) {
			$calculated_fee = $max_fee;
		}

		if ( $caculate_with_currency ) {
			$calculated_fee = (float) ( $calculated_fee / YayCurrencyHelper::get_rate_fee( $this->apply_currency ) );
		}

		return $calculated_fee;
	}

	public function fee( $atts ) {
		$atts           = shortcode_atts(
			array(
				'percent' => '',
				'min_fee' => '',
				'max_fee' => '',
			),
			$atts,
			'fee'
		);
		$calculated_fee = $this->caculate_cost_fee( $atts );
		return $calculated_fee;
	}

	public function fee_checkout_fallback( $atts ) {
		$atts           = shortcode_atts(
			array(
				'percent' => '',
				'min_fee' => '',
				'max_fee' => '',
			),
			$atts,
			'fee'
		);
		$calculated_fee = $this->caculate_cost_fee( $atts, true );
		return $calculated_fee;
	}

	// Shipping
	public function change_shipping_cost( $methods, $package ) {
		if ( count( array_filter( $methods ) ) ) {
			foreach ( $methods as $key => $method ) {
				if ( 'betrs_shipping' == $method->method_id || 'printful_shipping' == $method->method_id || 'easyship' == $method->method_id ) {
					continue;
				}
				if ( 'flat_rate' == $method->method_id ) {
					$shipping = new \WC_Shipping_Flat_Rate( $method->instance_id );
					// Calculate the costs.
					$rate = array(
						'id'      => $method->id,
						'label'   => $method->label,
						'cost'    => 0,
						'package' => $package,
					);

					$has_costs = false; // True when a cost is set. False if all costs are blank strings.
					$cost      = $shipping->get_option( 'cost' );

					if ( '' !== $cost ) {
						$has_costs    = true;
						$rate['cost'] = $this->evaluate_cost(
							$cost,
							array(
								'qty'  => $shipping->get_package_item_qty( $package ),
								'cost' => $package['contents_cost'],
							)
						);
					}

					$shipping_classes = WC()->shipping->get_shipping_classes();

					if ( ! empty( $shipping_classes ) ) {
						$product_shipping_classes = $shipping->find_shipping_classes( $package );
						$shipping_classes_cost    = 0;

						foreach ( $product_shipping_classes as $shipping_class => $products ) {
							$shipping_class_term = get_term_by( 'slug', $shipping_class, 'product_shipping_class' );
							$class_cost_string   = $shipping_class_term && $shipping_class_term->term_id ? $shipping->get_option( 'class_cost_' . $shipping_class_term->term_id, $shipping->get_option( 'class_cost_' . $shipping_class, '' ) ) : $shipping->get_option( 'no_class_cost', '' );

							if ( '' === $class_cost_string ) {
								continue;
							}

							$has_costs  = true;
							$class_cost = $this->evaluate_cost(
								$class_cost_string,
								array(
									'qty'  => array_sum( wp_list_pluck( $products, 'quantity' ) ),
									'cost' => array_sum( wp_list_pluck( $products, 'line_total' ) ),
								)
							);

							if ( 'class' === $shipping->type ) {
								$rate['cost'] += $class_cost;
							} else {
								$shipping_classes_cost = $class_cost > $shipping_classes_cost ? $class_cost : $shipping_classes_cost;
							}
						}

						if ( 'order' === $shipping->type && $shipping_classes_cost ) {
							$rate['cost'] += $shipping_classes_cost;
						}
					}
					if ( $has_costs ) {
						if ( is_checkout() && $this->is_dis_checkout_diff_currency ) {
							$method->set_cost( $rate['cost'] );
						} else {
							$rate['cost'] = YayCurrencyHelper::calculate_price_by_currency( $rate['cost'], false, $this->apply_currency );
							$method->set_cost( $rate['cost'] );
						}
					}
				} elseif ( 'printful_shipping_STANDARD' === $method->method_id ) {
					continue;
				} else {
					$special_shipping_methods = array( 'table_rate', 'per_product', 'tree_table_rate', 'wf_fedex_woocommerce_shipping' );
					if ( in_array( $method->method_id, $special_shipping_methods ) ) {
						if ( is_checkout() && $this->is_dis_checkout_diff_currency ) {
							return $methods;
						}
						$method->cost = YayCurrencyHelper::calculate_price_by_currency( $method->cost, false, $this->apply_currency );
						return $methods;
					}
					if ( YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) ) {
						return $methods;
					}
					$data = get_option( 'woocommerce_' . $method->method_id . '_' . $method->instance_id . '_settings' );
					$method->set_cost( isset( $data['cost'] ) ? YayCurrencyHelper::calculate_price_by_currency( $data['cost'], false, $this->apply_currency ) : YayCurrencyHelper::calculate_price_by_currency( $method->get_cost(), false, $this->apply_currency ) );
				}

				// Set tax for shipping method
				if ( count( $method->get_taxes() ) ) {
					if ( YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) ) {
						return $methods;
					}
					$tax_new = array();
					foreach ( $method->get_taxes() as $key => $tax ) {
						$tax_currency = YayCurrencyHelper::calculate_price_by_currency( $tax, true, $this->apply_currency );
						// caculate again with rounding
						if ( 'disabled' !== $this->apply_currency['roundingType'] ) {
							$tax_currency = YayCurrencyHelper::round_price_by_currency( $tax_currency * 10, $this->apply_currency );
							$tax_currency = $tax_currency / 10;
						}
						if ( 'flat_rate' == $method->method_id && isset( $cost ) && ! is_numeric( $cost ) ) {
							$tax_caculate    = \WC_Tax::calc_shipping_tax( $rate['cost'], \WC_Tax::get_shipping_tax_rates() );
							$tax_new[ $key ] = is_array( $tax_caculate ) ? array_shift( $tax_caculate ) : $tax_currency;
						} else {
							$tax_new[ $key ] = $tax_currency;
						}
					}
					$method->set_taxes( $tax_new );
				}
			}
		}

		return $methods;
	}

	// Coupon
	public function change_coupon_amount( $price, $coupon ) {

		// Check coupon type is percent return default price
		if ( $coupon->is_type( array( 'percent' ) ) ) {
			return $price;
		}

		if ( YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) ) {
			return $price;
		}

		// Coupon type != 'percent' calculate price
		$converted_coupon_price = YayCurrencyHelper::calculate_price_by_currency( $price, false, $this->apply_currency );
		$converted_coupon_price = apply_filters( 'yay_currency_converted_coupon_price', $converted_coupon_price, $coupon, $price, $this->apply_currency );
		return $converted_coupon_price;
	}

	public function custom_raw_price( $price, $product ) {

		if ( YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) ) {
			$price = apply_filters( 'yay_currency_get_price_default_in_checkout_page', $price, $product );
			return $price;
		}

		if ( function_exists( 'b2bking' ) || class_exists( 'HivePress\Core' ) || ! $this->apply_currency || empty( $price ) || ! is_numeric( $price ) || YayCurrencyHelper::is_wc_json_products() ) {
			return $price;
		}

		if ( class_exists( 'WC_Bookings' ) && 'booking' === $product->get_type() ) {
			return $price;
		}

		// Fix for manual renewal subscription product and still keep old code works well
		if ( is_checkout() || is_cart() || wp_doing_ajax() ) {

			$price_with_conditions = apply_filters( 'yay_currency_get_price_with_conditions', $price, $product, $this->apply_currency );
			if ( $price_with_conditions ) {
				return $price_with_conditions;
			}

			$price_exist_class_plugins = apply_filters( 'yay_currency_get_price_except_class_plugins', $price, $product, $this->apply_currency );
			if ( $price_exist_class_plugins ) {
				return $price_exist_class_plugins;
			}

			$price = YayCurrencyHelper::calculate_price_by_currency( $price, false, $this->apply_currency );
			$price = apply_filters( 'yay_currency_get_price_by_currency', $price, $product, $this->apply_currency );
			return $price;
		}

		$price = YayCurrencyHelper::calculate_price_by_currency( $price, false, $this->apply_currency );
		$price = apply_filters( 'yay_currency_get_price_by_currency', $price, $product, $this->apply_currency );
		return $price;

	}

	public function custom_variation_price_hash( $price_hash ) {
		$cookie_name = YayCurrencyHelper::get_cookie_name();
		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			$price_hash[] = (int) sanitize_key( $_COOKIE[ $cookie_name ] );
		}
		return $price_hash;
	}

	public function add_notice_checkout_payment_methods() {
		$currencies_data        = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
		$notice_payment_methods = apply_filters( 'yay_currency_checkout_notice_payment_methods', true, $this->apply_currency );

		if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] || ! $notice_payment_methods ) {
			return;
		}

		do_action( 'yay_currency_before_notice_checkout_payment_methods' );

		if ( current_user_can( 'manage_options' ) ) {
			// only for admin
			echo "<div class='yay-currency-checkout-notice yay-currency-with-" . esc_attr( $this->current_theme ) . "'><span>" . esc_html__( 'The current payment method for ', 'yay-currency' ) . '<strong>' . wp_kses_post( html_entity_decode( esc_html__( $currencies_data['current_currency']['currency'], 'yay-currency' ) ) ) . '</strong></span><span>' . esc_html__( ' is not supported in your location. ', 'yay-currency' ) . '</span><span>' . esc_html__( 'So your payment will be recorded in ', 'yay-currency' ) . '</span><strong>' . wp_kses_post( html_entity_decode( esc_html__( $currencies_data['fallback_currency']['currency'], 'yay-currency' ) ) ) . '.</strong></span></div>';
			echo "<div class='yay-currency-checkout-notice-admin yay-currency-with-" . esc_attr( $this->current_theme ) . "'><span>" . esc_html__( 'Are you the admin? You can change the checkout options for payment methods ', 'yay-currency' ) . '<a href=' . esc_url( admin_url( '/admin.php?page=yay_currency&tabID=1' ) ) . '>' . esc_html__( 'here', 'yay-currency' ) . '</a>.</span><br><span><i>' . esc_html__( '(Only logged in admin can see this.)', 'yay-currency' ) . '</i></span></div>';
		} else {
			echo "<div class='yay-currency-checkout-notice user yay-currency-with-" . esc_attr( $this->current_theme ) . "'><span>" . esc_html__( 'The current payment method for ', 'yay-currency' ) . '<strong>' . wp_kses_post( html_entity_decode( esc_html__( $currencies_data['current_currency']['currency'], 'yay-currency' ) ) ) . '</strong></span><span>' . esc_html__( ' is not supported in your location. ', 'yay-currency' ) . '</span><span>' . esc_html__( 'So your payment will be recorded in ', 'yay-currency' ) . '</span><strong>' . wp_kses_post( html_entity_decode( esc_html__( $currencies_data['fallback_currency']['currency'], 'yay-currency' ) ) ) . '.</strong></span></div>';

		}

		do_action( 'yay_currency_after_notice_checkout_payment_methods' );

	}

	public function conditional_payment_gateways( $available_gateways ) {

		if ( ! $this->apply_currency ) {
			return $available_gateways;
		}

		if ( YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) ) {
			$currencies_data    = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			$available_gateways = YayCurrencyHelper::filter_payment_methods_by_currency( $currencies_data['fallback_currency'], $available_gateways );
			return $available_gateways;
		}

		$available_gateways = YayCurrencyHelper::filter_payment_methods_by_currency( $this->apply_currency, $available_gateways );
		$available_gateways = apply_filters( 'yay_currency_available_gateways', $available_gateways, $this->apply_currency );
		return $available_gateways;

	}

	public function custom_mini_cart_price() {

		if ( YayCurrencyHelper::disable_fallback_option_in_checkout_page( $this->apply_currency ) || is_cart() || is_checkout() ) {
			return false;
		}
		WC()->cart->calculate_totals();

	}

	public function custom_checkout_product_subtotal( $product_subtotal, $product, $quantity, $cart ) {
		if ( is_checkout() ) {
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] ) {
				return $product_subtotal;
			}

			$product_price             = $product->get_price();
			$original_product_subtotal = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['fallback_currency'], $product_price, $quantity );
			$converted_approximately   = apply_filters( 'yay_currency_checkout_converted_approximately', true, $this->apply_currency );
			if ( ! $converted_approximately ) {
				return $original_product_subtotal;
			}

			$converted_product_subtotal = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['current_currency'], $product_price, $quantity );

			if ( class_exists( 'WC_Subscriptions' ) && class_exists( 'WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
				$original_price = (float) $product->get_price( 'edit' );
				$original_price = apply_filters( 'yay_currency_checkout_get_original_price_wc_subscriptions', $original_price, $product );
				$sign_up_price  = SupportHelper::get_price_sign_up_fee_by_wc_subscriptions( $this->apply_currency, $product );

				if ( $sign_up_price ) {
					$sign_up_price = (float) $product->get_price();
					if ( $sign_up_price && $original_price !== $sign_up_price ) {
						return $converted_product_subtotal;
					}
				}
			}

			$product_price_3rd = apply_filters( 'yay_currency_get_product_price_by_3rd_plugin', false, $product, $this->apply_currency );

			if ( $product_price_3rd ) {
				$converted_product_subtotal = YayCurrencyHelper::format_price( $product_price_3rd * $quantity );
			}

			$converted_product_subtotal_html = YayCurrencyHelper::converted_approximately_html( $converted_product_subtotal );
			$product_subtotal                = $original_product_subtotal . $converted_product_subtotal_html;

		}
		return $product_subtotal;
	}

	public function custom_checkout_order_subtotal( $cart_subtotal ) {
		if ( is_checkout() ) {
			$subtotal_price  = apply_filters( 'yay_currency_checkout_get_subtotal_price', (float) WC()->cart->get_displayed_subtotal(), $this->apply_currency );
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] ) {
				return $cart_subtotal;
			}

			$original_subtotal       = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['fallback_currency'], $subtotal_price );
			$converted_approximately = apply_filters( 'yay_currency_checkout_converted_approximately', true, $this->apply_currency );
			if ( ! $converted_approximately ) {
				return $original_subtotal;
			}

			$converted_subtotal = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['current_currency'], $subtotal_price );

			if ( YayCurrencyHelper::enable_rounding_currency( $this->apply_currency ) ) {
				$converted_subtotal = apply_filters( 'yay_currency_checkout_converted_cart_subtotal', $converted_subtotal, $this->apply_currency );
			}

			$converted_product_subtotal_html = YayCurrencyHelper::converted_approximately_html( $converted_subtotal );
			$cart_subtotal                   = $original_subtotal . $converted_product_subtotal_html;

		}
		return $cart_subtotal;
	}

	public function custom_checkout_order_total( $cart_total ) {
		if ( is_checkout() ) {
			$total_price     = apply_filters( 'yay_currency_checkout_get_total_price', (float) WC()->cart->total );
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] ) {
				return $cart_total;
			}

			$original_total          = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['fallback_currency'], $total_price );
			$converted_approximately = apply_filters( 'yay_currency_checkout_converted_approximately', true, $this->apply_currency );
			if ( ! $converted_approximately ) {
				return $original_total;
			}
			$converted_total = YayCurrencyHelper::calculate_price_by_currency_html( $this->apply_currency, $total_price );

			if ( YayCurrencyHelper::enable_rounding_currency( $this->apply_currency ) ) {
				$converted_total = apply_filters( 'yay_currency_checkout_converted_cart_total', $converted_total, $total_price, $this->apply_currency );
			}

			$converted_total_html = YayCurrencyHelper::converted_approximately_html( $converted_total );
			$cart_total           = $original_total . $converted_total_html;

		}
		return $cart_total;
	}

	public function custom_shipping_fee( $label, $method ) {
		if ( is_checkout() ) {
			if ( 'Free shipping' === $label ) {
				return $label;
			}
			$shipping_fee    = (float) $method->cost;
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] ) {
				return $label;
			}
			$label = apply_filters( 'yay_currency_checkout_converted_shipping_method_full_label', $label, $method->label, $shipping_fee, $currencies_data['fallback_currency'], $this->apply_currency );
		}
		return $label;
	}

	public function custom_discount_coupon( $coupon_html, $coupon, $discount_amount_html ) {
		if ( is_checkout() ) {
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] ) {
				return $coupon_html;
			}
			$coupon_html = apply_filters( 'yay_currency_checkout_converted_cart_coupon_totals_html', $coupon_html, $coupon, $currencies_data['fallback_currency'], $this->apply_currency );
		}
		return $coupon_html;
	}

	public function custom_total_tax( $tax_display ) {
		if ( count( $tax_display ) > 0 && is_checkout() ) {
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] ) {
				return $tax_display;
			}
			foreach ( $tax_display as $tax_info ) {
				$tax_info->formatted_amount = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['fallback_currency'], $tax_info->amount );
				$converted_approximately    = apply_filters( 'yay_currency_checkout_converted_approximately', true, $this->apply_currency );
				if ( $converted_approximately ) {
					$converted_tax_amount           = YayCurrencyHelper::calculate_price_by_currency( $tax_info->amount, false, $this->apply_currency );
					$formatted_converted_tax_amount = YayCurrencyHelper::format_price( $converted_tax_amount );
					if ( YayCurrencyHelper::enable_rounding_currency( $this->apply_currency ) ) {
						$formatted_converted_tax_amount = apply_filters( 'yay_currency_checkout_converted_tax_amount', $formatted_converted_tax_amount, $tax_info, $this->apply_currency );
					}
					$formatted_converted_tax_amount_html = YayCurrencyHelper::converted_approximately_html( $formatted_converted_tax_amount );
					$tax_info->formatted_amount         .= $formatted_converted_tax_amount_html;
				}
			}
		}
		return $tax_display;
	}

	public function custom_cart_taxes( $taxes ) {
		if ( is_checkout() ) {
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] !== $currencies_data['fallback_currency']['currency'] ) {
				$apply_currency = $currencies_data['fallback_currency'];
				if ( count( $taxes ) > 0 ) {
					foreach ( $taxes as &$tax ) {
						$tax = YayCurrencyHelper::calculate_price_by_currency( $tax, false, $apply_currency );
					}
				}
			}
		}
		return $taxes;
	}

	public function custom_cart_totals_fee_html( $cart_totals_fee_html, $fee ) {
		if ( is_checkout() ) {
			$converted_approximately = apply_filters( 'yay_currency_checkout_converted_approximately', true, $this->apply_currency );
			if ( ! $converted_approximately ) {
				return $cart_totals_fee_html;
			}
			$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
			if ( $currencies_data['current_currency']['currency'] !== $currencies_data['fallback_currency']['currency'] ) {
				$fee_amount              = $fee->amount;
				$fee_amount_html         = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['fallback_currency'], $fee_amount );
				$convert_fee_amount_html = YayCurrencyHelper::calculate_price_by_currency_html( $currencies_data['current_currency'], $fee_amount );
				$cart_totals_fee_html    = $fee_amount_html . YayCurrencyHelper::converted_approximately_html( $convert_fee_amount_html );
			}
		}
		return $cart_totals_fee_html;
	}

	public function add_order_currency_meta( $order_id, $data ) {
		if ( 0 == $this->is_checkout_different_currency ) {
			if ( function_exists( 'wcpay_init' ) ) {
				$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
				if ( Helper::check_custom_orders_table_usage_enabled() ) {
					$order_data = wc_get_order( $order_id );
					$order_data->update_meta_data( '_order_currency', $currencies_data['fallback_currency']['currency'] );
					$order_data->save();
				} else {
					update_post_meta( $order_id, '_order_currency', $currencies_data['fallback_currency']['currency'] );
				}
			}
			return;
		}
		$order_data  = wc_get_order( $order_id );
		$order_total = $order_data->get_total();

		$currencies_data = YayCurrencyHelper::get_current_and_fallback_currency( $this->apply_currency, $this->converted_currency );
		if ( 0 == $currencies_data['current_currency']['status'] ) {
			if ( $currencies_data['current_currency']['currency'] == $currencies_data['fallback_currency']['currency'] ) {
				return;
			}
			$original_total = YayCurrencyHelper::reverse_calculate_price_by_currency( $order_total, $currencies_data['fallback_currency'] );
			if ( Helper::check_custom_orders_table_usage_enabled() ) {
				$order_data->update_meta_data( 'yay_currency_checkout_original_total', $original_total );
				$order_data->save();
			} else {
				update_post_meta( $order_id, 'yay_currency_checkout_original_total', $original_total );
			}
			return;
		}

		$converted_order_total = YayCurrencyHelper::reverse_calculate_price_by_currency( $order_total );

		if ( Helper::check_custom_orders_table_usage_enabled() ) {
			$order_data->update_meta_data( 'yay_currency_checkout_original_total', $this->apply_currency['currency'] );
			$order_data->update_meta_data( 'yay_currency_checkout_original_total', $converted_order_total );
			$order_data->save();
		} else {
			update_post_meta( $order_id, '_order_currency', $this->apply_currency['currency'] );
			update_post_meta( $order_id, 'yay_currency_checkout_original_total', $converted_order_total );
		}

	}

}
