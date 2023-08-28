<?php
/*
Plugin Name: WP Menu Image
Plugin URI: 
description: Can add custom images in menu in Wordpress
Version: 2.0
Author: Yudiz Solutions Ltd.
Author URI: https://www.yudiz.com/
*/
?>
<?php
define( 'WMI_MENU_IMG', __FILE__ );
define( 'WMI_MENU_IMG_DIR', plugin_dir_path( __FILE__ ) );
define( 'WMI_MENU_IMG_URL', plugin_dir_url( WMI_MENU_IMG_DIR ) . basename( dirname( __FILE__ ) ) . '/' );
define( 'WMI_MENU_IMG_BASENAME', plugin_basename( WMI_MENU_IMG ) );
require_once(WMI_MENU_IMG_DIR.'init/wmi-functions.php');