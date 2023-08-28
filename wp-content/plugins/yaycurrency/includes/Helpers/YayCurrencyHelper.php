<?php
namespace Yay_Currency\Helpers;

use Yay_Currency\Utils\SingletonTrait;
class YayCurrencyHelper {

	use SingletonTrait;

	private static $COOKIE_NAME = 'yay_currency_widget';

	protected function __construct() {}

	public static function get_cookie_name() {
		return self::$COOKIE_NAME;
	}

	public static function get_symbol_by_currency_code( $currency = 'USD' ) {
		$symbols               = get_woocommerce_currency_symbols();
		$default_currency_code = get_option( 'woocommerce_currency' );
		$currency_symbol       = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : get_woocommerce_currency_symbol( $default_currency_code );
		return $currency_symbol;
	}

	public static function is_dis_checkout_diff_currency( $is_checkout_different_currency = '1', $status_current_currency = '1' ) {

		if ( ! (int) $is_checkout_different_currency || ! (int) $status_current_currency ) {
			return true;
		}
		return false;

	}

	public static function disable_fallback_option_in_checkout_page( $apply_currency = array() ) {
		$is_checkout_different_currency = get_option( 'yay_currency_checkout_different_currency', 0 );
		$status                         = isset( $apply_currency['status'] ) ? $apply_currency['status'] : 0;
		$is_dis_checkout_diff_currency  = self::is_dis_checkout_diff_currency( $is_checkout_different_currency, $status );
		return is_checkout() && $is_dis_checkout_diff_currency;
	}

	public static function set_cookies( $apply_currency = array() ) {
		if ( ! $apply_currency || headers_sent() ) {
			return;
		}

		$cookie_value = $apply_currency['ID'];

		if ( isset( $_COOKIE[ self::$COOKIE_NAME ] ) ) {
			$currency_ID = intval( sanitize_key( $_COOKIE[ self::$COOKIE_NAME ] ) );
			if ( $currency_ID === $cookie_value ) {
				return;
			}
		}

		setcookie( self::$COOKIE_NAME, $cookie_value, time() + ( 86400 * 30 ), '/' );

		$_COOKIE[ self::$COOKIE_NAME ] = $cookie_value;

	}

	public static function get_id_selected_currency() {
		$selected_currency_ID = 0;
		if ( isset( $_COOKIE[ self::$COOKIE_NAME ] ) ) {
			$selected_currency_ID = (int) sanitize_key( $_COOKIE[ self::$COOKIE_NAME ] );
		}

		if ( isset( $_REQUEST['yay-currency-nonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['yay-currency-nonce'] ), 'yay-currency-check-nonce' ) ) {
			if ( isset( $_POST['currency'] ) ) {
				$selected_currency_ID = (int) sanitize_key( wp_unslash( $_POST['currency'] ) );
			}
		}

		return $selected_currency_ID;

	}

	public static function converted_currency( $currencies = false ) {
		$yay_list_currencies = $currencies ? $currencies : Helper::get_currencies_post_type();
		$converted_currency  = array();
		if ( $yay_list_currencies ) {
			foreach ( $yay_list_currencies as $currency ) {
				$currency_meta       = get_post_meta( $currency->ID, '', true );
				$is_default_currency = Helper::default_currency_code() === $currency->post_title ? true : false;
				$currency_symbol     = self::get_symbol_by_currency_code( $currency->post_title );
				array_push(
					$converted_currency,
					array(
						'ID'                   => $currency->ID,
						'currency'             => $currency->post_title,
						'currencyPosition'     => $currency_meta['currency_position'][0],
						'currencyCodePosition' => isset( $currency_meta['currency_code_position'] ) && ! empty( $currency_meta['currency_code_position'][0] ) ? $currency_meta['currency_code_position'][0] : 'not_display',
						'thousandSeparator'    => $is_default_currency ? Helper::default_thousand_separator() : $currency_meta['thousand_separator'][0],
						'decimalSeparator'     => $is_default_currency ? Helper::default_decimal_separator() : $currency_meta['decimal_separator'][0],
						'numberDecimal'        => $is_default_currency ? Helper::default_price_num_decimals() : $currency_meta['number_decimal'][0],
						'rate'                 => $currency_meta['rate'][0],
						'fee'                  => maybe_unserialize( $currency_meta['fee'][0] ),
						'status'               => $currency_meta['status'][0],
						'paymentMethods'       => maybe_unserialize( $currency_meta['payment_methods'][0] ),
						'countries'            => maybe_unserialize( $currency_meta['countries'][0] ),
						'symbol'               => $currency_symbol,
						'roundingType'         => $currency_meta['rounding_type'][0],
						'roundingValue'        => $currency_meta['rounding_value'][0],
						'subtractAmount'       => $currency_meta['subtract_amount'][0],
					)
				);
			}
		}
		return $converted_currency;
	}

	// GET CURRENT CURRENCY & APPLY CURRENCY
	public static function get_currency_by_currency_code( $currency_code = '', $converted_currency_available = false ) {
		$converted_currency = $converted_currency_available ? $converted_currency_available : self::converted_currency();
		foreach ( $converted_currency as $currency ) {
			if ( $currency['currency'] === $currency_code ) {
				return $currency;
			}
		}
	}

	// get currency by curreny_code
	public static function filtered_by_currency_code( $currency_code = '', $converted_currency = array() ) {
		$args = array_filter(
			$converted_currency,
			function ( $currency ) use ( $currency_code ) {
				if ( $currency['currency'] === $currency_code ) {
					return true;
				}
				return false;
			}
		);
		return $args ? array_shift( $args ) : false;
	}


	public static function get_currency_by_ID( $currency_ID = 0 ) {
		$currency = get_post( $currency_ID );
		if ( empty( $currency ) || Helper::get_post_type() !== $currency->post_type ) {
			$default_currency_code = get_option( 'woocommerce_currency' );
			$default_currency      = self::get_currency_by_currency_code( $default_currency_code );
			return $default_currency;
		}

		$currency_meta = get_post_meta( $currency_ID, '', true );

		$converted_currency = array(
			'ID'                   => $currency->ID,
			'currency'             => $currency->post_title,
			'currencyPosition'     => $currency_meta['currency_position'][0],
			'currencyCodePosition' => isset( $currency_meta['currency_code_position'] ) && ! empty( $currency_meta['currency_code_position'][0] ) ? $currency_meta['currency_code_position'][0] : 'not_display',
			'thousandSeparator'    => $currency_meta['thousand_separator'][0],
			'decimalSeparator'     => $currency_meta['decimal_separator'][0],
			'numberDecimal'        => $currency_meta['number_decimal'][0],
			'roundingType'         => $currency_meta['rounding_type'][0],
			'roundingValue'        => $currency_meta['rounding_value'][0],
			'subtractAmount'       => $currency_meta['subtract_amount'][0],
			'rate'                 => $currency_meta['rate'][0],
			'fee'                  => maybe_unserialize( $currency_meta['fee'][0] ),
			'status'               => $currency_meta['status'][0],
			'paymentMethods'       => maybe_unserialize( $currency_meta['payment_methods'][0] ),
			'countries'            => maybe_unserialize( $currency_meta['countries'][0] ),
			'symbol'               => get_woocommerce_currency_symbol( $currency->post_title ),
		);
		return $converted_currency;
	}

	public static function get_currency_change_switcher( $apply_currency = array() ) {
		if ( isset( $_REQUEST['yay-currency-nonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['yay-currency-nonce'] ), 'yay-currency-check-nonce' ) ) {
			if ( isset( $_POST['currency'] ) ) {
				$currency_ID    = sanitize_text_field( $_POST['currency'] );
				$apply_currency = self::get_currency_by_ID( $currency_ID );
			}
			if ( isset( $_POST['yay_currency'] ) && isset( $_POST['yay_currency_current_url'] ) ) {
				$selected_currency_ID = sanitize_text_field( $_POST['yay_currency'] );
				$current_url          = sanitize_text_field( $_POST['yay_currency_current_url'] );
				do_action( 'yay_currency_redirect_to_url', $current_url, $selected_currency_ID );
			}
		}

		// CHANGE CURRENCY ON URL --- ?yay-currency=EUR
		$yay_currency_use_params = Helper::use_yay_currency_params();
		if ( $yay_currency_use_params && isset( $_REQUEST['yay-currency'] ) && ! empty( $_REQUEST['yay-currency'] ) ) {
			$currency_code          = sanitize_text_field( $_REQUEST['yay-currency'] );
			$currency_code          = strtoupper( $currency_code );
			$apply_currency_by_code = self::filtered_by_currency_code( $currency_code, self::converted_currency() );
			$apply_currency         = $apply_currency_by_code ? $apply_currency_by_code : $apply_currency;
		}

		$apply_currency = apply_filters( 'yay_currency_apply_currency', $apply_currency );
		return $apply_currency;
	}

	public static function get_apply_currency( $converted_currency = array() ) {
		$found_key      = array_search( get_option( 'woocommerce_currency' ), array_column( $converted_currency, 'currency' ) );
		$apply_currency = $converted_currency[ $found_key ];

		if ( isset( $_COOKIE[ self::$COOKIE_NAME ] ) ) {
			$currency_ID    = sanitize_key( $_COOKIE[ self::$COOKIE_NAME ] );
			$apply_currency = self::get_currency_by_ID( $currency_ID ) ? self::get_currency_by_ID( $currency_ID ) : reset( $converted_currency );
		}

		$apply_currency = self::get_currency_change_switcher( $apply_currency );

		return $apply_currency;
	}

	public static function detect_current_currency() {
		$currency_ID = self::get_id_selected_currency();
		if ( $currency_ID ) {
			$apply_currency = self::get_currency_by_ID( $currency_ID );
		} else {
			$converted_currency = self::converted_currency();
			$apply_currency     = self::get_apply_currency( $converted_currency );
		}
		return $apply_currency;
	}

	public static function format_price( $price = 0 ) {
		if ( isset( $_COOKIE[ self::$COOKIE_NAME ] ) ) {
			$currency_ID     = sanitize_key( $_COOKIE[ self::$COOKIE_NAME ] );
			$formatted_price = wc_price( $price );
			if ( ! empty( $currency_ID ) ) {
				$apply_currency  = self::get_currency_by_ID( $currency_ID );
				$formatted_price = wc_price(
					$price,
					self::get_apply_currency_format_info( $apply_currency )
				);
			}
			return $formatted_price;
		}
	}

	public static function format_sale_price( $regular_price, $sale_price ) {
		if ( isset( $_COOKIE[ self::$COOKIE_NAME ] ) ) {
			$currency_ID     = sanitize_key( $_COOKIE[ self::$COOKIE_NAME ] );
			$formatted_price = '<del aria-hidden="true">' . ( is_numeric( $regular_price ) ? wc_price( $regular_price ) : $regular_price ) . '</del> <ins>' . ( is_numeric( $sale_price ) ? wc_price( $sale_price ) : $sale_price ) . '</ins>';

			if ( ! empty( $currency_ID ) ) {
				$apply_currency = self::get_currency_by_ID( $currency_ID );
				$format         = self::get_apply_currency_format_info( $apply_currency );
				$regular_price  = wc_price( $regular_price, $format );
				$sale_price     = wc_price( $sale_price, $format );

				$formatted_price = '<del aria-hidden="true">' . ( is_numeric( $regular_price ) ? $regular_price : $regular_price ) . '</del> <ins>' . ( is_numeric( $sale_price ) ? $sale_price : $sale_price ) . '</ins>';

			}
			return $formatted_price;
		}
	}

	public static function get_apply_currency_format_info( $apply_currency = array() ) {
		$currency_code              = $apply_currency['currency'];
		$format                     = self::format_currency_position( $apply_currency['currencyPosition'] );
		$apply_currency_format_info = array(
			'currency'           => $currency_code,
			'decimal_separator'  => $apply_currency['decimalSeparator'],
			'thousand_separator' => $apply_currency['thousandSeparator'],
			'decimals'           => Helper::default_currency_code() === $currency_code ? Helper::default_price_num_decimals() : $apply_currency['numberDecimal'],
			'price_format'       => $format,
		);
		return $apply_currency_format_info;

	}

	public static function format_currency_position( $currency_position = 'left' ) {
		$format = '%1$s%2$s';
		switch ( $currency_position ) {
			case 'left':
				$format = '%1$s%2$s';
				break;
			case 'right':
				$format = '%2$s%1$s';
				break;
			case 'left_space':
				$format = '%1$s&nbsp;%2$s';
				break;
			case 'right_space':
				$format = '%2$s&nbsp;%1$s';
				break;
		}
		return $format;
	}

	public static function format_currency_code_position( $format_currency_position = '', $currency_info = array() ) {
		$format = $format_currency_position;

		if ( isset( $currency_info['currencyCodePosition'] ) ) {

			switch ( $currency_info['currencyCodePosition'] ) {
				case 'left':
					$format = $currency_info['currency'] . $format_currency_position;
					break;
				case 'right':
					$format = $format_currency_position . $currency_info['currency'];
					break;
				case 'left_space':
					$format = $currency_info['currency'] . ' ' . $format_currency_position;
					break;
				case 'right_space':
					$format = $format_currency_position . ' ' . $currency_info['currency'];
					break;
				case 'not_display':
					$format = $format_currency_position;
					break;
			}
		}

		return $format;

	}

	public static function get_symbol_by_currency( $currency_name = '', $converted_currency = array() ) {

		foreach ( $converted_currency as $key => $currency ) {
			if ( $currency['currency'] == $currency_name ) {
				return $currency['symbol'];
			}
		}

		return '';
	}

	public static function get_rate_fee( $currency = array() ) {
		if ( 'percentage' === $currency['fee']['type'] ) {
			$rate_after_fee = (float) $currency['rate'] + ( (float) $currency['rate'] * ( (float) $currency['fee']['value'] / 100 ) );
		} else {
			$rate_after_fee = (float) $currency['rate'] + (float) $currency['fee']['value'];
		}
		return $rate_after_fee;
	}

	public static function enable_rounding_currency( $apply_currency = array() ) {
		return $apply_currency && 'disabled' !== $apply_currency['roundingType'];
	}

	public static function round_price_by_currency( $price = 0, $apply_currency = array() ) {
		if ( 'disabled' !== $apply_currency['roundingType'] ) {

			$rounding_type   = $apply_currency['roundingType'];
			$rounding_value  = $apply_currency['roundingValue'];
			$subtract_amount = $apply_currency['subtractAmount'];

			switch ( $rounding_type ) {
				case 'up':
					$price = ceil( $price / $rounding_value ) * $rounding_value - $subtract_amount;
					return $price;
				case 'down':
					$price = floor( $price / $rounding_value ) * $rounding_value - $subtract_amount;
					return $price;
				case 'nearest':
					$price = round( $price / $rounding_value ) * $rounding_value - $subtract_amount;
					return $price;
				default:
					return;
			}
		}

		return $price;

	}

	public static function calculate_price_by_currency( $price = 0, $exclude = false, $apply_currency = array() ) {
		if ( ! empty( $apply_currency ) ) {
			$rate_after_fee = self::get_rate_fee( $apply_currency );
			$price          = (float) $price * $rate_after_fee;

			if ( $exclude ) {
				return $price;
			}

			$price = self::round_price_by_currency( $price, $apply_currency );

		}
		return $price;
	}

	public static function calculate_price_by_currency_html( $currency = array(), $original_price = 0, $quantity = 1 ) {

		$rate_after_fee  = self::get_rate_fee( $currency );
		$price           = $original_price * $rate_after_fee;
		$price           = self::round_price_by_currency( $price, $currency );
		$format          = self::format_currency_position( $currency['currencyPosition'] );
		$formatted_price = wc_price(
			$price * $quantity,
			array(
				'currency'           => $currency['currency'],
				'decimal_separator'  => $currency['decimalSeparator'],
				'thousand_separator' => $currency['thousandSeparator'],
				'decimals'           => Helper::default_currency_code() === $currency['currency'] ? Helper::default_price_num_decimals() : $currency['numberDecimal'],
				'price_format'       => $format,
			)
		);
		return $formatted_price;

	}

	public static function calculate_custom_price_by_currency_html( $apply_currency = array(), $price = 0 ) {
		$price           = self::round_price_by_currency( $price, $apply_currency );
		$formatted_price = self::get_formatted_total_by_convert_currency( $price, $apply_currency, $apply_currency['currency'] );
		return $formatted_price;
	}

	public static function converted_approximately_html( $price_html = '', $class = 'yay-currency-checkout-converted-approximately' ) {
		$html = " <span class='" . esc_attr( $class ) . "'>(~$price_html)</span>";
		return $html;
	}

	public static function calculate_price_by_currency_cookie( $price = 0, $exclude = false ) {
		if ( isset( $_COOKIE[ self::$COOKIE_NAME ] ) ) {
			$currency_ID    = sanitize_key( $_COOKIE[ self::$COOKIE_NAME ] );
			$apply_currency = self::get_currency_by_ID( $currency_ID );
			$price          = self::calculate_price_by_currency( $price, $exclude, $apply_currency );
			return $price;
		}
		return $price;
	}

	public static function reverse_calculate_price_by_currency( $price = 0, $currency = array() ) {
		if ( $currency ) {
			$apply_currency = $currency;
		} elseif ( isset( $_COOKIE[ self::$COOKIE_NAME ] ) ) {
			$currency_ID    = sanitize_key( $_COOKIE[ self::$COOKIE_NAME ] );
			$apply_currency = self::get_currency_by_ID( $currency_ID );
		}
		$currency_fee = self::get_rate_fee( $apply_currency );
		return (float) ( $price / $currency_fee );
	}

	public static function get_current_currency_ID( $apply_currency = array() ) {
		$current_currency_ID = false;
		if ( isset( $_COOKIE[ self::$COOKIE_NAME ] ) ) {
			$current_currency_ID = sanitize_key( $_COOKIE[ self::$COOKIE_NAME ] );
		} else {
			$current_currency_ID = isset( $apply_currency['ID'] ) ? $apply_currency['ID'] : false;
		}
		return $current_currency_ID;
	}

	public static function is_checkout_in_fallback() {
		$default_currency_code              = get_option( 'woocommerce_currency' );
		$fallback_currency_code             = get_option( 'yay_currency_checkout_fallback_currency', $default_currency_code );
		$checkout_different_currency_enable = get_option( 'yay_currency_checkout_different_currency', 0 );
		return $checkout_different_currency_enable && $default_currency_code !== $fallback_currency_code ? true : false;
	}

	public static function get_fallback_currency_by_default( $converted_currency = array() ) {
		$default_currency_code = get_option( 'woocommerce_currency' );
		$fallback_currency     = self::filtered_by_currency_code( $default_currency_code, $converted_currency );
		return $fallback_currency;
	}

	public static function get_fallback_currency( $converted_currency = array() ) {

		$fallback_currency = self::get_fallback_currency_by_default( $converted_currency );

		if ( 0 == $fallback_currency['status'] && self::is_checkout_in_fallback() ) {
			$fallback_currency_code = get_option( 'yay_currency_checkout_fallback_currency' );
			$fallback_currency      = self::filtered_by_currency_code( $fallback_currency_code, $converted_currency );
		}

		return $fallback_currency;
	}

	public static function get_current_and_fallback_currency( $apply_currency = array(), $converted_currency = array() ) {

		$current_currency_ID = self::get_current_currency_ID( $apply_currency );
		$current_currency    = self::get_currency_by_ID( $current_currency_ID );
		$fallback_currency   = self::get_fallback_currency( $converted_currency );

		return array(
			'current_currency'  => $current_currency,
			'fallback_currency' => $fallback_currency,
		);
	}

	public static function round_line_tax( $value = 0, $in_cents = true ) {
		$round_at_subtotal = 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ? true : false;
		if ( ! $round_at_subtotal ) {
			$precision = $in_cents ? 0 : null;
			$value     = wc_round_tax_total( $value, $precision );
		}
		return $value;
	}

	public static function get_convert_currency_by_checkout_currency( $converted_currency = array(), $apply_currency = array(), $yay_currency_checkout_currency = '' ) {
		$convert_currency = $apply_currency;
		foreach ( $converted_currency as $key => $value ) {
			if ( $value['currency'] == $yay_currency_checkout_currency ) {
				$convert_currency = $value;
				break;
			}
		}
		return $convert_currency;
	}

	public static function get_values_for_total( $field, $order ) {
		$items = array_map(
			function ( $item ) use ( $field ) {
				return wc_add_number_precision( $item[ $field ], false );
			},
			array_values( $order->get_items() )
		);
		return $items;
	}

	public static function format_currency_symbol( $currency_info = array() ) {
		$format_currency_position = isset( $currency_info['currencyPosition'] ) ? self::format_currency_position( $currency_info['currencyPosition'] ) : '';
		$format                   = self::format_currency_code_position( $format_currency_position, $currency_info );
		return $format;
	}

	public static function get_formatted_total_by_convert_currency( $price = 0, $convert_currency = array(), $yay_currency = '', $ex_tax_label = false ) {
		$format              = self::format_currency_symbol( $convert_currency );
		$thousand_separator  = get_option( 'woocommerce_price_thousand_sep' ) ? get_option( 'woocommerce_price_thousand_sep' ) : '.';
		$decimal_separator   = get_option( 'woocommerce_price_decimal_sep' ) ? get_option( 'woocommerce_price_decimal_sep' ) : '.';
		$is_default_currency = Helper::default_currency_code() === $yay_currency;

		$args = array(
			'ex_tax_label'       => $ex_tax_label,
			'currency'           => $yay_currency,
			'decimal_separator'  => $is_default_currency ? $decimal_separator : $convert_currency['decimalSeparator'],
			'thousand_separator' => $is_default_currency ? $thousand_separator : $convert_currency['thousandSeparator'],
			'decimals'           => $is_default_currency ? Helper::default_price_num_decimals() : $convert_currency['numberDecimal'],
			'price_format'       => $format,
		);

		$formatted_total = wc_price( $price, $args );
		return $formatted_total;

	}

	public static function get_cart_subtotal_for_order( $order ) {
		return wc_remove_number_precision(
			$order->get_rounded_items_total( self::get_values_for_total( 'subtotal', $order ) )
		);
	}

	// PAYMENT
	public static function filter_payment_methods_by_currency( $currency = array(), $available_gateways = array() ) {
		if ( ! $currency || array( 'all' ) === $currency['paymentMethods'] ) {
			return $available_gateways;
		}
		$allowed_payment_methods = $currency['paymentMethods'];
		$filtered                = array_filter(
			$available_gateways,
			function ( $key ) use ( $allowed_payment_methods ) {
				return in_array( $key, $allowed_payment_methods );
			},
			ARRAY_FILTER_USE_KEY
		);
		$available_gateways      = $filtered;
		return $available_gateways;
	}

	// return price default (wp-json/wc/v3/products?consumer_key=&consumer_secret=&per_page=&page=
	public static function is_wc_json_products() {
		return isset( $_REQUEST['consumer_key'] ) && isset( $_REQUEST['consumer_secret'] );
	}

}
