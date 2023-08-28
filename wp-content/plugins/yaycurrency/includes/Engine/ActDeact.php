<?php
namespace Yay_Currency\Engine;

use Yay_Currency\Utils\SingletonTrait;
use Yay_Currency\Helpers\Helper;

/**
 * Activate and deactive method of the plugin and relates.
 */
class ActDeact {

	use SingletonTrait;

	protected function __construct() {}

	public static function install_yaycurrency_admin_notice() {
		/* translators: %s: Woocommerce link */
		echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'YayCurrency is enabled but not effective. It requires %s in order to work', 'yay-currency' ), '<a href="' . esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ) . '">WooCommerce</a>' ) . '</strong></p></div>';
		return false;
	}

	public static function activate() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		// Create new Currency when empty
		$currencies = Helper::get_currencies_post_type();
		if ( ! $currencies ) {
			Helper::create_new_currency();
		}
		// WP Rocket Active
		if ( defined( 'WP_ROCKET_VERSION' ) ) {
			add_filter( 'rocket_htaccess_mod_rewrite', '__return_false' );
			add_filter( 'rocket_cache_dynamic_cookies', array( self::class, 'wp_rocket_cache_dynamic_cookie' ) );
			add_filter( 'rocket_cache_mandatory_cookies', array( self::class, 'wp_rocket_cache_mandatory_cookie' ) );
			self::yay_currency_flush_wp_rocket();
		}
	}

	public static function deactivate() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		// WP Rocket Active
		if ( defined( 'WP_ROCKET_VERSION' ) ) {
			remove_filter( 'rocket_htaccess_mod_rewrite', '__return_false' );
			remove_filter( 'rocket_cache_dynamic_cookies', array( self::class, 'wp_rocket_cache_dynamic_cookie' ) );
			remove_filter( 'rocket_cache_mandatory_cookies', array( self::class, 'wp_rocket_cache_mandatory_cookie' ) );
			self::yay_currency_flush_wp_rocket();
		}
		do_action( 'yay_currency_deactivate' );
	}

	public static function wp_rocket_cache_dynamic_cookie( $cookies ) {
		$cookies[] = 'yay_currency_widget';
		return $cookies;
	}

	public static function wp_rocket_cache_mandatory_cookie( $cookies ) {
		$cookies[] = 'yay_currency_widget';
		return $cookies;
	}

	public static function yay_currency_flush_wp_rocket() {

		if ( ! function_exists( 'flush_rocket_htaccess' )
		  || ! function_exists( 'rocket_generate_config_file' ) ) {
			return false;
		}

		// Update WP Rocket .htaccess rules.
		flush_rocket_htaccess();

		// Regenerate WP Rocket config file.
		rocket_generate_config_file();

	}

}
