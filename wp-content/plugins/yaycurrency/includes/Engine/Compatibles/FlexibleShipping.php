<?php

namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://wordpress.org/plugins/flexible-shipping/

class FlexibleShipping {

	use SingletonTrait;

	public function __construct() {
		if ( ! defined( 'FLEXIBLE_SHIPPING_VERSION' ) ) {
			return;
		}

		add_filter( 'flexible_shipping_value_in_currency', array( $this, 'custom_shipping_fee' ), 1 );
	}

	public function custom_shipping_fee( $fee ) {
		$converted_currency = YayCurrencyHelper::converted_currency();
		$apply_currency     = YayCurrencyHelper::get_apply_currency( $converted_currency );
		$fee                = YayCurrencyHelper::calculate_price_by_currency( $fee, false, $apply_currency );
		return $fee;
	}
}
