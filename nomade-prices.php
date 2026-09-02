<?php
/**
 * Plugin Name: Nómade Prices
 * Description: Catálogo con precio base en USD y tipos de cambio persistidos. Sin WooCommerce.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.3
 * Author: Diego
 * License: GPL-2.0-or-later
 * Text Domain: nomade-prices
 */

defined( 'ABSPATH' ) || exit;

define( 'NOMADE_PRICES_FILE', __FILE__ );
define( 'NOMADE_PRICES_DIR', plugin_dir_path( __FILE__ ) );
define( 'NOMADE_PRICES_URL', plugin_dir_url( __FILE__ ) );
define( 'NOMADE_PRICES_VERSION', '1.0.0' );

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'Nomade\\';
		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}
		$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
		$file     = NOMADE_PRICES_DIR . 'src/' . $relative . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'Nomade\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Nomade\\Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Nomade\\Plugin', 'instance' ) );
