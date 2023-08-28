<?php
namespace Yay_Currency\Helpers;

use Yay_Currency\Utils\SingletonTrait;

class Helper {

	use SingletonTrait;

	protected function __construct() {}
	private static $YAY_CURRENCY_POST_TYPE = 'yay-currency-manage';

	public static function sanitize_array( $var ) {
		if ( is_array( $var ) ) {
			return array_map( 'self::sanitize_array', $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
		}
	}

	public static function get_value_variable( $variable, $default = false ) {
		return isset( $variable ) && ! empty( $variable ) ? $variable : $default;
	}

	public static function sanitize( $var ) {
		return wp_kses_post_deep( $var['data'] );
	}

	public static function get_instance_classes( $engine_classes = array(), $yay_classes = array() ) {
		$last_length = count( $engine_classes );
		foreach ( $yay_classes as $yay_class ) {
			$engine_classes[ $last_length ] = $yay_class;
			$class                          = implode( '\\', $engine_classes );
			$class::get_instance();
		}
	}

	public static function engine_classes() {
		$classes = array(
			'Hooks',
			'Ajax',
		);

		return $classes;
	}

	public static function appearance_classes() {
		$classes = array(
			'MenuDropdown',
			'Widget',
		);

		return $classes;
	}

	public static function backend_classes() {
		$classes = array(
			'WooCommerceFilterReport',
			'Settings',
			'FixedPricesPerProduct',
			'WooCommerceSettingGeneral',
			'WooCommerceOrderAdmin',
		);

		return $classes;
	}

	public static function frontend_classes() {
		$classes = array(
			'WooCommerceCurrency',
			'WooCommerceCheckoutPage',
			'WooCommercePriceFormat',
			'SingleProductDropdown',
			'Shortcodes',
		);

		return $classes;
	}

	public static function compatible_classes() {
		$classes = array(
			// PLUGINS
			'RapydPaymentGateway',
			'WooCommerceSimpleAuctions',
			'AdvancedProductFieldsProWooCommerce',
			'Bookly',
			'B2BMarket',
			'B2BKingPro',
			'Cartflows',
			'Dokan',
			'FlexibleShipping',
			'HivePress',
			'TieredPricingTableForWooCommerce',
			'JetSmartFilters',
			'WooCommerceProductFeed',
			'WooCommercePayForPayment',
			'WooCommercePayments',
			'WooCommercePayPalPayments',
			'WooCommerceQuickView',
			'WooCommerceTableRateShipping',
			'WooDiscountRules',
			'WooCommerceTMExtraProductOptions',
			'WooCommerceProductAddons',
			'WooCommerceProductAddOnsUltimate',
			'WPFunnels',
			'LearnPress',
			'WooCommerceNameYourPrice',
			'WooCommerceSubscriptions',
			'YITHWooCommerceAddOnsExtraPremiumOptions',
			'YITHEasyOrderPageForWooCommerce',
			'YITHPointsAndRewards',
			'YITHWoocommerceGiftCards',
			'YITHWooCommerceSubscription',
			'WooCommerceProductBundles',
			'Measurement_Price_Calculator',
			'PPOM',
			'YayExtra',
			'YayPricing',
			'WooCommerceDeposits',
			'WooCommerceEmailSymbol',
			'WooCommerceBookings',
			'ElementorPro',
			'TranslatePressMultilingual',
			'WooCommerceTeraWallet',
			'WooCommerceRequestAQuote',
			'TabbyCheckout',
			//THEMES
			'BeTheme',
			'BlocksyTheme',
			'JulyTheme',
			'KapeeTheme',
			'WoodmartTheme',
			'SalientTheme',
			'FlatsomeTheme',
			//CACHES
			'WPGridBuilderCaching',
			'WPRocket',

		);

		return $classes;

	}

	public static function get_post_type() {
		return self::$YAY_CURRENCY_POST_TYPE;
	}

	public static function get_yay_currency_by_currency_code( $currency_code = 'USD' ) {
		$currencies = get_posts(
			array(
				'post_type' => self::get_post_type(),
				'title'     => $currency_code,
			)
		);
		return $currencies ? $currencies[0] : false;
	}

	public static function use_yay_currency_params() {
		$yay_currency_use_params = apply_filters( 'yay_currency_use_params', false );
		return $yay_currency_use_params;
	}

	public static function default_currency_code() {
		$default_currency = get_option( 'woocommerce_currency' );
		return $default_currency;
	}

	public static function default_thousand_separator() {
		return get_option( 'woocommerce_price_thousand_sep' );
	}

	public static function default_decimal_separator() {
		$separator = get_option( 'woocommerce_price_decimal_sep' );
		return $separator ? stripslashes( $separator ) : '.';
	}

	public static function default_price_num_decimals() {
		$default_price_num_decimals = get_option( 'woocommerce_price_num_decimals' );
		return wp_kses_post( html_entity_decode( $default_price_num_decimals ) );
	}

	public static function get_currencies_post_type() {

		$currencies = ! is_admin() ? wp_cache_get( 'currency_list', 'yay_currency' ) : false;
		if ( ! $currencies ) {
			$post_type_args = array(
				'posts_per_page' => -1,
				'post_type'      => 'yay-currency-manage',
				'post_status'    => 'publish',
				'order'          => 'ASC',
				'orderby'        => 'menu_order',
			);
			$currencies     = get_posts( $post_type_args );
			$dup_currency   = array();
			foreach ( $currencies as $key => $currency ) {
				if ( in_array( $currency->post_title, $dup_currency ) ) {
					wp_delete_post( $currency->ID );
					unset( $currencies[ $key ] );
				} else {
					array_push( $dup_currency, $currency->post_title );
				}
			};
			if ( ! is_admin() ) {
				wp_cache_set( 'currency_list', $currencies, 'yay_currency' );
			}
		}

		return $currencies;

	}

	public static function count_display_elements_in_switcher( $is_show_flag = true, $is_show_currency_name = true, $is_show_currency_symbol = true, $is_show_currency_code = true ) {
		$display_elements_array = array();
		$is_show_flag ? array_push( $display_elements_array, $is_show_flag ) : null;
		$is_show_currency_name ? array_push( $display_elements_array, $is_show_currency_name ) : null;
		$is_show_currency_symbol ? array_push( $display_elements_array, $is_show_currency_symbol ) : null;
		$is_show_currency_code ? array_push( $display_elements_array, $is_show_currency_code ) : null;
		return count( $display_elements_array );
	}

	public static function get_flag_by_country_code( $country_code = 'us' ) {
		$flag = $country_code;
		switch ( $country_code ) {
			case 'byr':
				$flag = 'by';
				return YAY_CURRENCY_PLUGIN_URL . 'assets/dist/flags/' . $flag . '.svg';
			case 'cuc':
				$flag = 'cu';
				return YAY_CURRENCY_PLUGIN_URL . 'assets/dist/flags/' . $flag . '.svg';
			case 'irt':
				$flag = 'ir';
				return YAY_CURRENCY_PLUGIN_URL . 'assets/dist/flags/' . $flag . '.svg';
			case 'vef':
				$flag = 've';
				return YAY_CURRENCY_PLUGIN_URL . 'assets/dist/flags/' . $flag . '.svg';
			default:
				return YAY_CURRENCY_PLUGIN_URL . 'assets/dist/flags/' . $flag . '.svg';
		}
	}

	public static function currency_code_by_country_code() {
		$countries_code = array(
			'AED' => 'ae',
			'AFN' => 'af',
			'ALL' => 'al',
			'AMD' => 'am',
			'ANG' => 'an',
			'AOA' => 'ao',
			'ARS' => 'ar',
			'AUD' => 'au',
			'AWG' => 'aw',
			'AZN' => 'az',
			'BAM' => 'ba',
			'BBD' => 'bb',
			'BDT' => 'bd',
			'BGN' => 'bg',
			'BHD' => 'bh',
			'BIF' => 'bi',
			'BMD' => 'bm',
			'BND' => 'bn',
			'BOB' => 'bo',
			'BRL' => 'br',
			'BSD' => 'bs',
			'BTN' => 'bt',
			'BTC' => 'btc',
			'BWP' => 'bw',
			'BYN' => 'by',
			'BYR' => 'byr',
			'BZD' => 'bz',
			'CAD' => 'ca',
			'CDF' => 'cd',
			'CHF' => 'ch',
			'CLP' => 'cl',
			'CNY' => 'cn',
			'COP' => 'co',
			'CRC' => 'cr',
			'CUP' => 'cu',
			'CUC' => 'cuc',
			'CVE' => 'cv',
			'CZK' => 'cz',
			'DJF' => 'dj',
			'DKK' => 'dk',
			'DOP' => 'do',
			'DZD' => 'dz',
			'EGP' => 'eg',
			'ERN' => 'er',
			'ETB' => 'et',
			'EUR' => 'eu',
			'FJD' => 'fj',
			'FKP' => 'fk',
			'GBP' => 'gb',
			'GEL' => 'ge',
			'GGP' => 'gg',
			'GHS' => 'gh',
			'GIP' => 'gi',
			'GMD' => 'gm',
			'GNF' => 'gn',
			'GTQ' => 'gt',
			'GYD' => 'gy',
			'HKD' => 'hk',
			'HNL' => 'hn',
			'HRK' => 'hr',
			'HTG' => 'ht',
			'HUF' => 'hu',
			'IDR' => 'id',
			'ILS' => 'il',
			'IMP' => 'im',
			'INR' => 'in',
			'IQD' => 'iq',
			'IRR' => 'ir',
			'IRT' => 'irt',
			'ISK' => 'is',
			'JEP' => 'je',
			'JMD' => 'jm',
			'JOD' => 'jo',
			'JPY' => 'jp',
			'KES' => 'ke',
			'KGS' => 'kg',
			'KHR' => 'kh',
			'KMF' => 'km',
			'KPW' => 'kp',
			'KRW' => 'kr',
			'KWD' => 'kw',
			'KYD' => 'ky',
			'KZT' => 'kz',
			'LAK' => 'la',
			'LBP' => 'lb',
			'LKR' => 'lk',
			'LRD' => 'lr',
			'LSL' => 'ls',
			'LYD' => 'ly',
			'MAD' => 'ma',
			'MDL' => 'md',
			'PRB' => 'mda',
			'MGA' => 'mg',
			'MKD' => 'mk',
			'MMK' => 'mm',
			'MNT' => 'mn',
			'MOP' => 'mo',
			'MRU' => 'mr',
			'MUR' => 'mu',
			'MVR' => 'mv',
			'MWK' => 'mw',
			'MXN' => 'mx',
			'MYR' => 'my',
			'MZN' => 'mz',
			'NAD' => 'na',
			'NGN' => 'ng',
			'NIO' => 'ni',
			'NOK' => 'no',
			'XOF' => 'none',
			'XPF' => 'none1',
			'XCD' => 'none2',
			'XAF' => 'none3',
			'NPR' => 'np',
			'NZD' => 'nz',
			'OMR' => 'om',
			'PAB' => 'pa',
			'PEN' => 'pe',
			'PGK' => 'pg',
			'PHP' => 'ph',
			'PKR' => 'pk',
			'PLN' => 'pl',
			'PYG' => 'py',
			'QAR' => 'qa',
			'RON' => 'ro',
			'RSD' => 'rs',
			'RUB' => 'ru',
			'RWF' => 'rw',
			'SAR' => 'sa',
			'SBD' => 'sb',
			'SCR' => 'sc',
			'SDG' => 'sd',
			'SEK' => 'se',
			'SGD' => 'sg',
			'SHP' => 'sh',
			'SLL' => 'sl',
			'SOS' => 'so',
			'SRD' => 'sr',
			'SSP' => 'ss',
			'STN' => 'st',
			'SYP' => 'sy',
			'SZL' => 'sz',
			'THB' => 'th',
			'TJS' => 'tj',
			'TMT' => 'tm',
			'TND' => 'tn',
			'TOP' => 'to',
			'TRY' => 'tr',
			'TTD' => 'tt',
			'TWD' => 'tw',
			'TZS' => 'tz',
			'UAH' => 'ua',
			'UGX' => 'ug',
			'USD' => 'us',
			'UYU' => 'uy',
			'UZS' => 'uz',
			'VES' => 've',
			'VEF' => 'vef',
			'VND' => 'vn',
			'VUV' => 'vu',
			'WST' => 'ws',
			'YER' => 'ye',
			'ZAR' => 'za',
			'ZMW' => 'zm',
		);
		return $countries_code;
	}

	public static function woo_list_currencies() {
		$list_currencies        = get_woocommerce_currencies();
		$list_currencies['USD'] = 'United States dollar'; // Remove (US) from default
		return $list_currencies;
	}

	public static function convert_currencies_data() {
		$most_traded_currencies_code           = array( 'USD', 'EUR', 'GBP', 'INR', 'AUD', 'CAD', 'SGD', 'CHF', 'MYR', 'JPY' );
		$most_traded_converted_currencies_data = array();
		$converted_currencies_data             = array();

		$currency_code_by_country_code = self::currency_code_by_country_code();
		$woo_list_currencies           = self::woo_list_currencies();

		foreach ( $currency_code_by_country_code as $key => $value ) {
			$currency_data = array(
				'currency'      => isset( $woo_list_currencies[ $key ] ) ? html_entity_decode( $woo_list_currencies[ $key ] ) : 'USD',
				'currency_code' => $key,
				'country_code'  => $value,
			);
			if ( in_array( $key, $most_traded_currencies_code ) ) {
				array_push( $most_traded_converted_currencies_data, $currency_data );
			} else {
				array_push( $converted_currencies_data, $currency_data );
			}
		}
		usort(
			$most_traded_converted_currencies_data,
			function ( $a, $b ) use ( $most_traded_currencies_code ) {
				$pos_a = array_search( $a['currency_code'], $most_traded_currencies_code );
				$pos_b = array_search( $b['currency_code'], $most_traded_currencies_code );
				return $pos_a - $pos_b;
			}
		);
		$result = array_merge( $most_traded_converted_currencies_data, $converted_currencies_data );
		return $result;
	}

	public static function get_woo_current_settings() {
		return array(
			'currentCurrency'       => get_option( 'woocommerce_currency' ),
			'currentCurrencySymbol' => get_woocommerce_currency_symbol(),
			'currencyPosition'      => get_option( 'woocommerce_currency_pos' ),
			'thousandSeparator'     => get_option( 'woocommerce_price_thousand_sep' ),
			'decimalSeparator'      => get_option( 'woocommerce_price_decimal_sep' ),
			'numberDecimals'        => get_option( 'woocommerce_price_num_decimals' ),
		);
	}

	public static function converted_currencies( $currencies = array() ) {
		$converted_currencies = array();
		foreach ( $currencies as $currency ) {
			$currency_meta          = get_post_meta( $currency->ID, '', true );
			$currency_code_position = isset( $currency_meta['currency_code_position'] ) && ! empty( $currency_meta['currency_code_position'][0] ) ? $currency_meta['currency_code_position'][0] : 'not_display';
			$converted_currency     = array(
				'ID'                   => $currency->ID,
				'currency'             => $currency->post_title,
				'currencySymbol'       => html_entity_decode( get_woocommerce_currency_symbol( $currency->post_title ) ),
				'currencyPosition'     => $currency_meta['currency_position'][0],
				'currencyCodePosition' => $currency_code_position,
				'thousandSeparator'    => $currency_meta['thousand_separator'][0],
				'decimalSeparator'     => $currency_meta['decimal_separator'][0],
				'numberDecimal'        => $currency_meta['number_decimal'][0],
				'rate'                 =>
					array(
						'type'  => $currency_meta['rate_type'] && ! empty( $currency_meta['rate_type'][0] ) ? $currency_meta['rate_type'][0] : 'auto',
						'value' => $currency_meta['rate'][0],
					),
				'fee'                  => maybe_unserialize( $currency_meta['fee'][0] ),
				'status'               => $currency_meta['status'][0],
				'paymentMethods'       => maybe_unserialize( $currency_meta['payment_methods'][0] ),
				'countries'            => maybe_unserialize( $currency_meta['countries'][0] ),
				'default'              => get_option( 'woocommerce_currency' ) == $currency->post_title ? true : false,
				'isLoading'            => false,
				'roundingType'         => $currency_meta['rounding_type'][0] ? $currency_meta['rounding_type'][0] : 'disabled',
				'roundingValue'        => $currency_meta['rounding_value'][0] ? $currency_meta['rounding_value'][0] : 1,
				'subtractAmount'       => $currency_meta['subtract_amount'][0] ? $currency_meta['subtract_amount'][0] : 0,
			);
			array_push( $converted_currencies, $converted_currency );
		}
		return $converted_currencies;
	}

	public static function get_default_currency() {
		$woo_current_settings = self::get_woo_current_settings();
		$currentCurrency      = $woo_current_settings['currentCurrency'];
		$symbol               = get_woocommerce_currency_symbol( $currentCurrency );
		$default_currency     = array(
			'currency'             => $currentCurrency,
			'currencySymbol'       => html_entity_decode( $symbol ),
			'currencyPosition'     => $woo_current_settings['currencyPosition'],
			'currencyCodePosition' => 'not_display',
			'thousandSeparator'    => $woo_current_settings['thousandSeparator'],
			'decimalSeparator'     => $woo_current_settings['decimalSeparator'],
			'numberDecimal'        => $woo_current_settings['numberDecimals'],
			'rate'                 => array(
				'type'  => 'auto',
				'value' => '1',
			),
			'fee'                  => array(
				'value' => '0',
				'type'  => 'fixed',
			),
			'status'               => '1',
			'paymentMethods'       => array( 'all' ),
			'countries'            => array( 'default' ),
			'default'              => true,
			'isLoading'            => false,
			'roundingType'         => 'disabled',
			'roundingValue'        => 1,
			'subtractAmount'       => 0,
		);

		return $default_currency;

	}

	public static function create_new_currency( $currentCurrency = '', $is_wc_settings_page = false ) {
		if ( ! $is_wc_settings_page ) {
			$woo_current_settings = self::get_woo_current_settings();
			$currentCurrency      = $woo_current_settings['currentCurrency'];
		}
		$args            = array(
			'post_title'  => $currentCurrency,
			'post_type'   => self::$YAY_CURRENCY_POST_TYPE,
			'post_status' => 'publish',
			'menu_order'  => 0,
		);
		$new_currency_ID = wp_insert_post( $args );
		if ( ! is_wp_error( $new_currency_ID ) ) {
			if ( ! $is_wc_settings_page ) {
				update_post_meta( $new_currency_ID, 'currency_position', $woo_current_settings['currencyPosition'] );
				update_post_meta( $new_currency_ID, 'thousand_separator', $woo_current_settings['thousandSeparator'] );
				update_post_meta( $new_currency_ID, 'decimal_separator', $woo_current_settings['decimalSeparator'] );
				update_post_meta( $new_currency_ID, 'number_decimal', $woo_current_settings['numberDecimals'] );
				update_post_meta( $new_currency_ID, 'currency_code_position', 'not_display' );
			}
			self::update_post_meta_currency( $new_currency_ID );
		}
	}

	public static function update_post_meta_currency( $currency_id = 0, $currency = false ) {
		if ( $currency ) {
			update_post_meta( $currency_id, 'currency_position', $currency['currencyPosition'] );
			$currency_code_position = isset( $currency['currencyCodePosition'] ) ? $currency['currencyCodePosition'] : 'not_display';
			update_post_meta( $currency_id, 'currency_code_position', $currency_code_position );
			update_post_meta( $currency_id, 'thousand_separator', $currency['thousandSeparator'] );
			update_post_meta( $currency_id, 'decimal_separator', $currency['decimalSeparator'] );
			update_post_meta( $currency_id, 'number_decimal', $currency['numberDecimal'] );
		}
		update_post_meta( $currency_id, 'rounding_type', $currency ? $currency['roundingType'] : 'disabled' );
		update_post_meta( $currency_id, 'rounding_value', $currency ? $currency['roundingValue'] : 1 );
		update_post_meta( $currency_id, 'subtract_amount', $currency ? $currency['subtractAmount'] : 0 );
		update_post_meta( $currency_id, 'rate', $currency ? $currency['rate']['value'] : 1 );
		update_post_meta( $currency_id, 'rate_type', $currency ? $currency['rate']['type'] : 'auto' );
		$fee_currency = $currency ? $currency['fee'] : array(
			'value' => '0',
			'type'  => 'fixed',
		);
		update_post_meta( $currency_id, 'fee', $fee_currency );
		update_post_meta( $currency_id, 'status', $currency ? $currency['status'] : '1' );
		update_post_meta( $currency_id, 'payment_methods', $currency ? $currency['paymentMethods'] : array( 'all' ) );
		update_post_meta( $currency_id, 'countries', $currency ? $currency['countries'] : array( 'default' ) );
	}

	public static function get_exchange_rates( $currency_params_template = array() ) {
		$url_template = 'https://query1.finance.yahoo.com/v8/finance/chart/$src$dest=X?interval=2m';
		$url          = strtr( $url_template, $currency_params_template );
		$json_data    = wp_remote_get( $url );
		return $json_data;
	}

	public static function update_exchange_rate_currency( $yay_currencies = array(), $woocommerce_currency = '' ) {
		if ( ! empty( $woocommerce_currency ) && $yay_currencies ) {
			foreach ( $yay_currencies as $currency ) {
				if ( $currency->post_title !== $woocommerce_currency ) {
					$rate_type = get_post_meta( $currency->ID, 'rate_type', true );
					if ( 'auto' === $rate_type || empty( $rate_type ) ) {

						$json_data = self::get_exchange_rates(
							array(
								'$src'  => $woocommerce_currency,
								'$dest' => $currency->post_title,
							)
						);

						if ( isset( $json_data['response']['code'] ) && 200 !== $json_data['response']['code'] ) {
							update_post_meta( $currency->ID, 'rate', 'N/A' );
							continue;
						}

						$decoded_json_data = json_decode( $json_data['body'] );
						$exchange_rate     = 1;

						if ( isset( $decoded_json_data->chart->result[0]->indicators->quote[0]->close ) ) {
							$exchange_rate = $decoded_json_data->chart->result[0]->indicators->quote[0]->close[0];
						} else {
							$exchange_rate = $decoded_json_data->chart->result[0]->meta->previousClose;
						}

						update_post_meta( $currency->ID, 'rate', $exchange_rate );

					}
				} else {
					update_post_meta( $currency->ID, 'rate', 1 );
				}
			}
		}
	}

	public static function get_current_theme() {
		return wp_get_theme()->template;
	}

	public static function change_existing_currency_symbol( $apply_currency = array(), $currency_symbol = '' ) {
		if ( ! $apply_currency ) {
			return $currency_symbol;
		}
		$currency_symbol = isset( $apply_currency['symbol'] ) ? $apply_currency['symbol'] : $currency_symbol;
		return wp_kses_post( html_entity_decode( $currency_symbol ) );
	}

	public static function change_currency_position( $apply_currency = array() ) {
		if ( ! $apply_currency ) {
			return false;
		}
		return $apply_currency['currencyPosition'];
	}

	public static function change_thousand_separator( $apply_currency = array() ) {
		if ( ! $apply_currency ) {
			return;
		}

		if ( self::default_currency_code() === $apply_currency['currency'] ) {
			$apply_currency['thousandSeparator'] = self::default_thousand_separator();
		}

		return wp_kses_post( html_entity_decode( $apply_currency['thousandSeparator'] ) );
	}

	public static function change_decimal_separator( $apply_currency = array() ) {

		if ( ! $apply_currency ) {
			return;
		}

		if ( self::default_currency_code() === $apply_currency['currency'] ) {
			$apply_currency['decimalSeparator'] = self::default_decimal_separator();
		}

		return wp_kses_post( html_entity_decode( $apply_currency['decimalSeparator'] ) );
	}

	public static function change_number_decimals( $apply_currency = array() ) {
		if ( ! $apply_currency ) {
			return;
		}

		if ( self::default_currency_code() === $apply_currency['currency'] || YayCurrencyHelper::disable_fallback_option_in_checkout_page( $apply_currency ) ) {
			return self::default_price_num_decimals();
		}

		return wp_kses_post( html_entity_decode( $apply_currency['numberDecimal'] ) );
	}

	public static function get_current_url() {
		global $wp;
		if ( isset( $_SERVER['QUERY_STRING'] ) && ! empty( $_SERVER['QUERY_STRING'] ) ) {
			$query_string = sanitize_text_field( $_SERVER['QUERY_STRING'] );
			$current_url  = add_query_arg( $query_string, '', home_url( $wp->request ) );
		} else {
			$current_url = add_query_arg( array(), home_url( $wp->request ) );
		}
		return $current_url;
	}

	public static function create_nonce_field( $action = 'yay-currency-check-nonce', $name = 'yay-currency-nonce' ) {
		$name        = esc_attr( $name );
		$request_url = remove_query_arg( '_wp_http_referer' );
		$current_url = self::get_current_url();
		echo '<input type="hidden" class="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_create_nonce( $action ) ) . '" />';
		echo '<input type="hidden" name="_wp_http_referer" value="' . esc_url( $request_url ) . '" />';
		echo '<input type="hidden" name="yay_currency_current_url" value="' . esc_url( $current_url ) . '" />';
	}

	public static function check_custom_orders_table_usage_enabled() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
			if ( \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
				return true;
			}
		}
		return false;
	}
}
