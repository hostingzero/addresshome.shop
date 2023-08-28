<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://woocommerce.com/products/request-a-quote-plugin-for-woocommerce/

class WooCommerceRequestAQuote {

	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( ! class_exists( 'Addify_Request_For_Quote' ) ) {
			return;
		}

		$this->apply_currency = YayCurrencyHelper::detect_current_currency();

		add_filter( 'addify_quote_item_price', array( $this, 'addify_quote_item_price' ), 10, 3 );
		add_filter( 'addify_quote_item_subtotal', array( $this, 'addify_quote_item_subtotal' ), 10, 3 );
		add_filter( 'addify_rfq_quote_totals', array( $this, 'addify_rfq_quote_totals' ), 10, 1 );
		add_filter( 'woocommerce_currency_symbol', array( $this, 'change_existing_currency_symbol' ), 20, 2 );

	}

	public function change_existing_currency_symbol( $currency_symbol, $currency ) {
		if ( wp_doing_ajax() ) {
			$currency_symbol = wp_kses_post( html_entity_decode( $this->apply_currency['symbol'] ) );
		}
		return $currency_symbol;
	}


	public function addify_quote_item_price( $price, $quote_item, $quote_item_key ) {
		if ( wp_doing_ajax() ) {
			$product_data = $quote_item['data'];
			$price        = $product_data->get_price( 'edit' );
			$price        = YayCurrencyHelper::calculate_price_by_currency( $price, false, $this->apply_currency );
			$price        = YayCurrencyHelper::format_price( $price );
		}
		return $price;
	}

	public function addify_quote_item_subtotal( $subtotal, $quote_item, $quote_item_key ) {
		if ( wp_doing_ajax() ) {
			$product_data = $quote_item['data'];
			$subtotal     = $product_data->get_price( 'edit' ) * $quote_item['quantity'];
			$subtotal     = YayCurrencyHelper::calculate_price_by_currency( $subtotal, false, $this->apply_currency );
			$subtotal     = YayCurrencyHelper::format_price( $subtotal );
		}
		return $subtotal;
	}

	public function addify_rfq_quote_totals( $quote_totals ) {
		if ( wp_doing_ajax() ) {
			$quote_totals['_subtotal']       = YayCurrencyHelper::calculate_price_by_currency( $quote_totals['_subtotal'], false, $this->apply_currency );
			$quote_totals['_offered_total']  = YayCurrencyHelper::calculate_price_by_currency( $quote_totals['_offered_total'], false, $this->apply_currency );
			$quote_totals['_tax_total']      = YayCurrencyHelper::calculate_price_by_currency( $quote_totals['_tax_total'], false, $this->apply_currency );
			$quote_totals['_shipping_total'] = YayCurrencyHelper::calculate_price_by_currency( $quote_totals['_shipping_total'], false, $this->apply_currency );
			$quote_totals['_total']          = YayCurrencyHelper::calculate_price_by_currency( $quote_totals['_total'], false, $this->apply_currency );
		}
		return $quote_totals;
	}

}
