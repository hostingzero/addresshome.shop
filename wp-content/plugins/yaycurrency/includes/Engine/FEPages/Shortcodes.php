<?php
namespace Yay_Currency\Engine\FEPages;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\Helper;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

	use SingletonTrait;

	public $selected_currency_ID = null;

	protected function __construct() {

		//Dropdown Shortcode
		add_shortcode( 'yaycurrency-switcher', array( $this, 'currency_dropdown_shortcode' ) );
		//Menu Shortcode
		add_shortcode( 'yaycurrency-menu-item-switcher', array( $this, 'menu_item_switcher_shortcode' ) );
		// Convert Price HTML By Currency
		add_shortcode( 'yaycurrency-price-html', array( $this, 'yay_convert_price_html' ) );
	}

	public function currency_dropdown_shortcode( $content = null ) {
		if ( is_checkout() && ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
			return '';
		}
		$is_show_flag            = get_option( 'yay_currency_show_flag_in_switcher', 1 );
		$is_show_currency_name   = get_option( 'yay_currency_show_currency_name_in_switcher', 1 );
		$is_show_currency_symbol = get_option( 'yay_currency_show_currency_symbol_in_switcher', 1 );
		$is_show_currency_code   = get_option( 'yay_currency_show_currency_code_in_switcher', 1 );
		$switcher_size           = get_option( 'yay_currency_switcher_size', 'medium' );

		ob_start();
		require YAY_CURRENCY_PLUGIN_DIR . 'includes/templates/switcher/template.php';
		$content = ob_get_clean();
		return $content;

	}

	public function menu_item_switcher_shortcode( $content = null ) {
		if ( is_checkout() && ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
			return '';
		}
		$is_show_flag            = get_option( 'yay_currency_show_flag_in_menu_item', 1 );
		$is_show_currency_name   = get_option( 'yay_currency_show_currency_name_in_menu_item', 1 );
		$is_show_currency_symbol = get_option( 'yay_currency_show_currency_symbol_in_menu_item', 1 );
		$is_show_currency_code   = get_option( 'yay_currency_show_currency_code_in_menu_item', 1 );
		$switcher_size           = get_option( 'yay_currency_menu_item_size', 'small' );

		ob_start();
		require YAY_CURRENCY_PLUGIN_DIR . 'includes/templates/switcher/template.php';
		$content = ob_get_clean();
		return $content;

	}

	public function yay_convert_price_html( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'price' => '',
			),
			$atts
		);

		ob_start();
		$price          = apply_filters( 'yaycurrency_get_price', $atts['price'] );
		$apply_currency = YayCurrencyHelper::detect_current_currency();
		$price_html     = YayCurrencyHelper::calculate_price_by_currency_html( $apply_currency, $price );
		$price_html     = apply_filters( 'yaycurrency_get_price_html', $price_html, $apply_currency );
		echo wp_kses_post( $price_html );
		return ob_get_clean();
	}

}
