<?php
if ( ! defined( 'WP_DEBUG' ) ) {
	die( 'Direct access forbidden.' );
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'blocksy-child-style',
			get_stylesheet_uri(),
			array( 'ct-main-styles' ),
			wp_get_theme()->get( 'Version' )
		);
	},
	20
);
