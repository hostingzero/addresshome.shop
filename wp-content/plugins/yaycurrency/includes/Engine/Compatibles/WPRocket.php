<?php

namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\YayCurrencyHelper;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://wp-rocket.me/

class WPRocket {
	use SingletonTrait;

	public function __construct() {
		if ( ! defined( 'WP_ROCKET_VERSION' ) ) {
			return;
		}
		// Add cookie ID to cookkies for dynamic caches.
		add_filter( 'rocket_cache_dynamic_cookies', array( $this, 'custom_cache_dynamic_cookie' ) );
		add_filter( 'rocket_cache_mandatory_cookies', array( $this, 'custom_cache_mandatory_cookie' ) );
		add_filter( 'rocket_htaccess_mod_rewrite', '__return_false' );
	}

	public function custom_cache_dynamic_cookie( $cookies ) {
		$cookies[] = YayCurrencyHelper::get_cookie_name();
		return $cookies;
	}

	public function custom_cache_mandatory_cookie( $cookies ) {
		$cookies[] = YayCurrencyHelper::get_cookie_name();
		return $cookies;
	}

}
