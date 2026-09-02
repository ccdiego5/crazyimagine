<?php
/**
 * Plugin Name: Ágora Calendar
 * Description: Calendario base de festivos oficiales. Nager.Date v3 + CSV. Sin page builders.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.3
 * Author: Diego
 * License: GPL-2.0-or-later
 * Text Domain: agora-calendar
 */

defined( 'ABSPATH' ) || exit;

define( 'AGORA_CALENDAR_FILE', __FILE__ );
define( 'AGORA_CALENDAR_DIR', plugin_dir_path( __FILE__ ) );
define( 'AGORA_CALENDAR_URL', plugin_dir_url( __FILE__ ) );
define( 'AGORA_CALENDAR_VERSION', '1.0.0' );

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'Agora\\';
		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}
		$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
		$file     = AGORA_CALENDAR_DIR . 'src/' . $relative . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'Agora\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Agora\\Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Agora\\Plugin', 'instance' ) );
