<?php

namespace Yay_Currency\Engine;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\Helper;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

class Ajax {

	use SingletonTrait;
	public $exchange_rate_api;
	private $converted_currencies = array();
	public function __construct() {
		add_action( 'wp_ajax_yayCurrency_get_all_data', array( $this, 'get_all_data' ) );
		add_action( 'wp_ajax_yayCurrency_set_all_data', array( $this, 'set_all_data' ) );
		add_action( 'wp_ajax_yayCurrency_update_exchange_rate', array( $this, 'update_exchange_rate' ) );
		add_action( 'wp_ajax_yayCurrency_delete_currency', array( $this, 'delete_currency' ) );
	}


	public function get_currency_manage_tab_data( $woo_current_settings ) {
		$post_type_args = array(
			'numberposts' => -1,
			'post_type'   => 'yay-currency-manage',
			'post_status' => 'publish',
			'order'       => 'ASC',
			'orderby'     => 'menu_order',
		);

		$currencies = get_posts( $post_type_args );

		if ( $currencies ) {
			foreach ( $currencies as $currency ) {
				$currency_meta = get_post_meta( $currency->ID, '', true );

				$converted_currency = array(
					'ID'                => $currency->ID,
					'currency'          => $currency->post_title,
					'currencySymbol'    => html_entity_decode( get_woocommerce_currency_symbol( $currency->post_title ) ),
					'currencyPosition'  => $currency_meta['currency_position'][0],
					'thousandSeparator' => $currency_meta['thousand_separator'][0],
					'decimalSeparator'  => $currency_meta['decimal_separator'][0],
					'numberDecimal'     => $currency_meta['number_decimal'][0],
					'rate'              =>
						array(
							'type'  => $currency_meta['rate_type'] && ! empty( $currency_meta['rate_type'][0] ) ? $currency_meta['rate_type'][0] : 'auto',
							'value' => $currency_meta['rate'][0],
						),
					'fee'               => maybe_unserialize( $currency_meta['fee'][0] ),
					'status'            => $currency_meta['status'][0],
					'paymentMethods'    => maybe_unserialize( $currency_meta['payment_methods'][0] ),
					'countries'         => maybe_unserialize( $currency_meta['countries'][0] ),
					'default'           => get_option( 'woocommerce_currency' ) == $currency->post_title ? true : false,
					'isLoading'         => false,
					'roundingType'      => $currency_meta['rounding_type'][0] ? $currency_meta['rounding_type'][0] : 'disabled',
					'roundingValue'     => $currency_meta['rounding_value'][0] ? $currency_meta['rounding_value'][0] : 1,
					'subtractAmount'    => $currency_meta['subtract_amount'][0] ? $currency_meta['subtract_amount'][0] : 0,
				);
				array_push( $this->converted_currencies, $converted_currency );
			}
		} else {
			$default_currency = array(
				'currency'          => $woo_current_settings['currentCurrency'],
				'currencySymbol'    => html_entity_decode( get_woocommerce_currency_symbol( $woo_current_settings['currentCurrency'] ) ),
				'currencyPosition'  => $woo_current_settings['currencyPosition'],
				'thousandSeparator' => $woo_current_settings['thousandSeparator'],
				'decimalSeparator'  => $woo_current_settings['decimalSeparator'],
				'numberDecimal'     => $woo_current_settings['numberDecimals'],
				'rate'              => array(
					'type'  => 'auto',
					'value' => '1',
				),
				'fee'               => array(
					'value' => '0',
					'type'  => 'fixed',
				),
				'status'            => '1',
				'paymentMethods'    => array( 'all' ),
				'countries'         => array( 'default' ),
				'default'           => true,
				'isLoading'         => false,
				'roundingType'      => 'disabled',
				'roundingValue'     => 1,
				'subtractAmount'    => 0,
			);
			array_push( $this->converted_currencies, $default_currency );
		}
		$is_checkout_different_currency           = get_option( 'yay_currency_checkout_different_currency', 0 );
		$is_show_on_single_product_page           = get_option( 'yay_currency_show_single_product_page', 1 );
		$switcher_position_on_single_product_page = get_option( 'yay_currency_switcher_position_on_single_product_page', 'after_description' );
		$is_show_flag_in_switcher                 = get_option( 'yay_currency_show_flag_in_switcher', 1 );
		$is_show_currency_name_in_switcher        = get_option( 'yay_currency_show_currency_name_in_switcher', 1 );
		$is_show_currency_symbol_in_switcher      = get_option( 'yay_currency_show_currency_symbol_in_switcher', 1 );
		$is_show_currency_code_in_switcher        = get_option( 'yay_currency_show_currency_code_in_switcher', 1 );
		$switcher_size                            = get_option( 'yay_currency_switcher_size', 'medium' );
		$is_wpml_compatible                       = get_option( 'yay_currency_wpml_compatible', 0 );
		$is_polylang_compatible                   = get_option( 'yay_currency_polylang_compatible', 0 );
		$paymentMethodsOptions                    = array();
		$installed_payment_methods                = WC()->payment_gateways->get_available_payment_gateways();
		foreach ( $installed_payment_methods as $key => $value ) {
			$paymentMethodsOptions[ $key ] = $value->title;
		}
		return array(
			'isCheckoutDifferentCurrency'         => $is_checkout_different_currency,
			'isShowOnSingleProductPage'           => $is_show_on_single_product_page,
			'switcherPositionOnSingleProductPage' => $switcher_position_on_single_product_page,
			'isShowFlagInSwitcher'                => $is_show_flag_in_switcher,
			'isShowCurrencyNameInSwitcher'        => $is_show_currency_name_in_switcher,
			'isShowCurrencySymbolInSwitcher'      => $is_show_currency_symbol_in_switcher,
			'isShowCurrencyCodeInSwitcher'        => $is_show_currency_code_in_switcher,
			'switcherSize'                        => $switcher_size,
			'isWPMLCompatible'                    => $is_wpml_compatible,
			'isPolylangCompatible'                => $is_polylang_compatible,
			'currencies'                          => $this->converted_currencies,
			'paymentMethods'                      => $paymentMethodsOptions,
		);
	}


	public function get_all_data() {
		check_ajax_referer( 'yay-currency-nonce', 'nonce', true );
		$woo_current_settings     = Helper::get_woo_current_settings();
		$currency_manage_tab_data = $this->get_currency_manage_tab_data( $woo_current_settings );
		wp_send_json(
			apply_filters(
				'yay_currency_wpml_polylang_compatible',
				array(
					'list_countries'           => WC()->countries->countries,
					'woo_current_settings'     => $woo_current_settings,
					'currency_manage_tab_data' => $currency_manage_tab_data,
				)
			)
		);
	}

	public function set_all_data() {
		check_ajax_referer( 'yay-currency-nonce', 'nonce', true );
		if ( isset( $_POST['data'] ) ) {
			$all_currencies_settings_data = Helper::sanitize( $_POST );
			$this->set_currency_manage_settings( $all_currencies_settings_data['currencies'] );
			$this->set_checkout_options_settings( $all_currencies_settings_data );
			$this->set_display_options_settings( $all_currencies_settings_data );

			\WC_Cache_Helper::get_transient_version( 'product', true ); // Update product price (currency) after change value.
		}
		$this->get_all_data();
	}

	public function update_exchange_rate() {
		check_ajax_referer( 'yay-currency-nonce', 'nonce', true );
		if ( isset( $_POST['data'] ) ) {
			$currency_object = Helper::sanitize( $_POST );
			$exchange_rate   = array();
			try {
				if ( 'all' === $currency_object['type'] ) {
					$currencies       = $currency_object['currencies'];
					$default_currency = get_option( 'woocommerce_currency' );
					foreach ( $currencies as $currency ) {
						if ( $default_currency !== $currency ) {
							if ( '' === $currency ) {
								array_push( $exchange_rate, 'N/A' );
							} else {
								$currency_params_template = array(
									'$src'  => get_option( 'woocommerce_currency' ),
									'$dest' => $currency,
								);
								$json_data                = Helper::get_exchange_rates( $currency_params_template );
								if ( 200 !== $json_data['response']['code'] ) {
									array_push( $exchange_rate, 'N/A' );
									continue;
								}
								$decoded_json_data = json_decode( $json_data['body'] );
								if ( isset( $decoded_json_data->chart->result[0]->indicators->quote[0]->close ) ) {
									array_push( $exchange_rate, $decoded_json_data->chart->result[0]->indicators->quote[0]->close[0] );
								} else {
									array_push( $exchange_rate, $decoded_json_data->chart->result[0]->meta->previousClose );
								}
							}
						} else {
							array_push( $exchange_rate, 1 );
						}
					}
					wp_send_json_success(
						array(
							'success'      => true,
							'exchangeRate' => $exchange_rate,
						)
					);
				}
				$currency_params_template = array(
					'$src'  => $currency_object['srcCurrency'],
					'$dest' => $currency_object['destCurrency'],
				);
				$json_data                = Helper::get_exchange_rates( $currency_params_template );
				if ( 200 !== $json_data['response']['code'] ) {
					wp_send_json_error();
				}
				$decoded_json_data = json_decode( $json_data['body'] );
				if ( isset( $decoded_json_data->chart->result[0]->indicators->quote[0]->close ) ) {
					$exchange_rate = $decoded_json_data->chart->result[0]->indicators->quote[0]->close[0];
				} else {
					$exchange_rate = $decoded_json_data->chart->result[0]->meta->previousClose;
				}
				wp_send_json_success(
					array(
						'exchangeRate' => $exchange_rate,
					)
				);
			} catch ( \Exception $e ) {
				wp_send_json_error( $e );
			}
		}
	}


	public function set_currency_manage_settings( $currencies ) {
		$currencies_array = Helper::sanitize_array( $currencies );
		foreach ( $currencies_array as $key => $currency ) {
			if ( isset( $currency['ID'] ) ) {
				$update_currency = array(
					'ID'         => $currency['ID'],
					'post_title' => $currency['currency'],
					'menu_order' => $key,
				);
				wp_update_post( $update_currency );
				Helper::update_post_meta_currency( $currency['ID'], $currency );
			} else {
				$new_currency    = array(
					'post_title'  => $currency['currency'],
					'post_type'   => Helper::get_post_type(),
					'post_status' => 'publish',
					'menu_order'  => $key,
				);
				$new_currency_ID = wp_insert_post( $new_currency );
				if ( ! is_wp_error( $new_currency_ID ) ) {
					Helper::update_post_meta_currency( $new_currency_ID, $currency );
				}
			}
		}
	}

	public function set_checkout_options_settings( $all_currencies_settings_data ) {
		$currencies_array               = Helper::sanitize_array( $all_currencies_settings_data['currencies'] );
		$is_checkout_different_currency = sanitize_text_field( $all_currencies_settings_data['isCheckoutDifferentCurrency'] ) === '1' ? 1 : 0;
		update_option( 'yay_currency_checkout_different_currency', $is_checkout_different_currency );
		foreach ( $currencies_array as $currency ) {
			if ( isset( $currency['ID'] ) ) {
				update_post_meta( $currency['ID'], 'status', '1' === $currency['status'] ? 1 : 0 );
				update_post_meta( $currency['ID'], 'payment_methods', $currency['paymentMethods'] );
			}
		}
	}

	public function set_display_options_settings( $all_currencies_settings_data ) {
		$is_show_on_single_product_page           = sanitize_text_field( $all_currencies_settings_data['isShowOnSingleProductPage'] ) === '1' ? 1 : 0;
		$switcher_position_on_single_product_page = sanitize_text_field( $all_currencies_settings_data['switcherPositionOnSingleProductPage'] );
		$is_show_flag_in_switcher                 = sanitize_text_field( $all_currencies_settings_data['isShowFlagInSwitcher'] ) === '1' ? 1 : 0;
		$is_show_currency_name_in_switcher        = sanitize_text_field( $all_currencies_settings_data['isShowCurrencyNameInSwitcher'] ) === '1' ? 1 : 0;
		$is_show_currency_symbol_in_switcher      = sanitize_text_field( $all_currencies_settings_data['isShowCurrencySymbolInSwitcher'] ) === '1' ? 1 : 0;
		$is_show_currency_code_in_switcher        = sanitize_text_field( $all_currencies_settings_data['isShowCurrencyCodeInSwitcher'] ) === '1' ? 1 : 0;
		$switcher_size                            = sanitize_text_field( $all_currencies_settings_data['switcherSize'] );
		update_option( 'yay_currency_show_single_product_page', $is_show_on_single_product_page );
		update_option( 'yay_currency_switcher_position_on_single_product_page', $switcher_position_on_single_product_page );
		update_option( 'yay_currency_show_flag_in_switcher', $is_show_flag_in_switcher );
		update_option( 'yay_currency_show_currency_name_in_switcher', $is_show_currency_name_in_switcher );
		update_option( 'yay_currency_show_currency_symbol_in_switcher', $is_show_currency_symbol_in_switcher );
		update_option( 'yay_currency_show_currency_code_in_switcher', $is_show_currency_code_in_switcher );
		update_option( 'yay_currency_switcher_size', $switcher_size );
	}

	public function delete_currency() {
		check_ajax_referer( 'yay-currency-nonce', 'nonce', true );
		if ( isset( $_POST['data'] ) ) {
			$currency_ID = isset( $_POST['data']['ID'] ) ? sanitize_text_field( $_POST['data']['ID'] ) : false;
			if ( ! $currency_ID ) {
				$currency_code = isset( $_POST['data']['currency'] ) ? sanitize_text_field( $_POST['data']['currency'] ) : false;
				$currency_data = YayCurrencyHelper::get_currency_by_currency_code( $currency_code );
				$currency_ID   = $currency_data ? $currency_data['ID'] : false;
			}
			$is_deleted = $currency_ID ? wp_delete_post( $currency_ID ) : false;
			wp_send_json(
				array(
					'status' => $is_deleted,
				)
			);
		}
	}

}
