<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://hivepress.io/

class HivePress {
	use SingletonTrait;

	public function __construct() {
		add_filter( 'hivepress/v1/fields/currency/display_value', array( $this, 'custom_hivepress_price' ), 10, 2 );
	}

	public function custom_hivepress_price( $price, $data ) {
		$apply_currency = YayCurrencyHelper::detect_current_currency();

		$converted_number_from_price = YayCurrencyHelper::calculate_price_by_currency( $data->get_value(), false, $apply_currency );

		$formatted_price = wc_price(
			$converted_number_from_price,
			YayCurrencyHelper::get_apply_currency_format_info( $apply_currency )
		);

		return $formatted_price;
	}
}
