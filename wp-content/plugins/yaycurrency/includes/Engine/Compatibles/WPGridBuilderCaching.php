<?php
namespace Yay_Currency\Engine\Compatibles;

use Yay_Currency\Utils\SingletonTrait;

defined( 'ABSPATH' ) || exit;

// Link plugin: https://www.wpgridbuilder.com

class WPGridBuilderCaching {
	use SingletonTrait;

	public function __construct() {
		if ( ! class_exists( 'WP_Grid_Builder_Caching\Includes\Plugin' ) ) {
			return;
		}
		add_filter( 'wp_grid_builder_caching/bypass', array( $this, 'bypass_grid_builder_caching' ), 10, 2 );
	}

	public function bypass_grid_builder_caching( $is_bypass, $attrs ) {
		return true;
	}

}
