<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

class TabbyCheckout {
	use SingletonTrait;

	private $apply_currency = array();

	public function __construct() {

		if ( ! class_exists( '\WC_Tabby' ) ) {
			return;
		}

		add_filter( 'tabby_checkout_tabby_currency', array( $this, 'tabby_checkout_tabby_currency' ), 10, 1 );

	}

	public function tabby_checkout_tabby_currency( $currency ) {
		$apply_currency = YayCurrencyHelper::detect_current_currency();
		if ( isset( $apply_currency['currency'] ) ) {
			return $apply_currency['currency'];
		}
		return $currency;
	}

}
