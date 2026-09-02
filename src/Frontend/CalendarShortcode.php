<?php

namespace Agora\Frontend;

use Agora\Content\EventType;
use Agora\Sync\SyncRunner;

defined( 'ABSPATH' ) || exit;

final class CalendarShortcode {

	private const LABELS = array(
		'ES' => 'España',
		'MX' => 'México',
		'CO' => 'Colombia',
		'CL' => 'Chile',
		'PE' => 'Perú',
		'VE' => 'Venezuela',
	);

	public static function init(): void {
		add_shortcode( 'agora_calendar', array( self::class, 'shortcode' ) );
	}

	/**
	 * @param array<string, string>|string $atts
	 */
	public static function shortcode( $atts ): string {
		unset( $atts );
		$countries = SyncRunner::active_countries();
		if ( $countries === array() ) {
			return '<p>' . esc_html__( 'No hay países activos.', 'agora-calendar' ) . '</p>';
		}

		$current = self::requested_country( $countries );
		$year    = (int) gmdate( 'Y' );
		$by_date = self::events_by_date( $current );

		$theme = locate_template( 'templates/calendar.php' );
		$path  = $theme !== '' ? $theme : AGORA_CALENDAR_DIR . 'templates/calendar.php';

		ob_start();
		$agora_countries = $countries;
		$agora_current   = $current;
		$agora_labels    = self::LABELS;
		$agora_year      = $year;
		$agora_by_date   = $by_date;
		require $path;
		return (string) ob_get_clean();
	}

	/**
	 * @param string[] $allowed
	 */
	private static function requested_country( array $allowed ): string {
		$from_query = isset( $_GET['country'] ) ? strtoupper( sanitize_key( wp_unslash( (string) $_GET['country'] ) ) ) : '';
		if ( $from_query && in_array( $from_query, $allowed, true ) ) {
			return $from_query;
		}

		return $allowed[0];
	}

	/**
	 * @return array<string, list<array<string, mixed>>>
	 */
	private static function events_by_date( string $country ): array {
		$q = new \WP_Query(
			array(
				'post_type'      => EventType::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'tax_query'      => array(
					array(
						'taxonomy' => EventType::TAX,
						'field'    => 'slug',
						'terms'    => strtolower( $country ),
					),
				),
				'meta_key'       => '_agora_date',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
			)
		);

		$out = array();
		foreach ( $q->posts as $post ) {
			$view = EventType::view( (int) $post->ID );
			$date = (string) $view['date'];
			if ( $date === '' ) {
				continue;
			}
			if ( ! isset( $out[ $date ] ) ) {
				$out[ $date ] = array();
			}
			$out[ $date ][] = $view;
		}

		return $out;
	}
}
