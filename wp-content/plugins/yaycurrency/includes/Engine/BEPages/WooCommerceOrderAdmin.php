<?php

namespace Yay_Currency\Engine\BEPages;

use Yay_Currency\Utils\SingletonTrait;

use Yay_Currency\Helpers\Helper;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

class WooCommerceOrderAdmin {
	use SingletonTrait;

	public $yay_currency_price_format = null;
	private $converted_currency       = array();
	private $apply_currency           = array();

	public function __construct() {
		add_action( 'current_screen', array( $this, 'get_current_screen' ) );
	}

	public function get_current_screen() {
		$screen = get_current_screen();
		if ( 'shop_order' === $screen->id ) {

			$order_id = isset( $_GET['post'] ) ? sanitize_key( $_GET['post'] ) : null;

			if ( $order_id ) {
				$order_data                     = wc_get_order( $order_id );
				$yay_currency_checkout_currency = $order_data->get_currency();
				$this->converted_currency       = YayCurrencyHelper::converted_currency();
				$apply_currency                 = array();
				foreach ( $this->converted_currency as $key => $value ) {
					if ( $value['currency'] == $yay_currency_checkout_currency ) {
						$apply_currency = $value;
					}
				}
				if ( $apply_currency ) {
					$this->apply_currency = $apply_currency;
					add_filter( 'woocommerce_currency_symbol', array( $this, 'change_existing_currency_symbol' ), 10, 2 );
					add_filter( 'pre_option_woocommerce_currency_pos', array( $this, 'change_currency_position' ) );
					add_filter( 'wc_get_price_thousand_separator', array( $this, 'change_thousand_separator' ) );
					add_filter( 'wc_get_price_decimal_separator', array( $this, 'change_decimal_separator' ) );
					add_filter( 'wc_get_price_decimals', array( $this, 'change_number_decimals' ) );

				}
			}
		}
	}

	public function change_existing_currency_symbol( $currency_symbol, $currency ) {
		return Helper::change_existing_currency_symbol( $this->apply_currency, $currency_symbol );
	}

	public function change_currency_position() {
		return Helper::change_currency_position( $this->apply_currency );
	}

	public function change_thousand_separator() {
		return Helper::change_thousand_separator( $this->apply_currency );
	}

	public function change_decimal_separator() {
		return Helper::change_decimal_separator( $this->apply_currency );
	}

	public function change_number_decimals() {
		return Helper::change_number_decimals( $this->apply_currency );
	}


}
