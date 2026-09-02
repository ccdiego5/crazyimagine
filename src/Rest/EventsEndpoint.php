<?php

namespace Agora\Rest;

use Agora\Content\EventType;
use Agora\Sync\SyncRunner;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class EventsEndpoint {

	public static function init(): void {
		add_action( 'rest_api_init', array( self::class, 'register' ) );
	}

	public static function register(): void {
		register_rest_route(
			'agora/v1',
			'/events',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'list_events' ),
				'permission_callback' => array( self::class, 'can_read' ),
				'args'                => array(
					'country'  => array(
						'required'          => true,
						'validate_callback' => array( self::class, 'validate_country' ),
						'sanitize_callback' => array( self::class, 'sanitize_country' ),
					),
					'page'     => array(
						'default'           => 1,
						'validate_callback' => static fn( $v ) => is_numeric( $v ) && (int) $v > 0,
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'default'           => 20,
						'validate_callback' => static fn( $v ) => is_numeric( $v ) && (int) $v > 0 && (int) $v <= 50,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	public static function can_read(): bool {
		return true;
	}

	public static function validate_country( mixed $value ): bool {
		return is_string( $value ) && (bool) preg_match( '/^[A-Za-z]{2}$/', $value );
	}

	public static function sanitize_country( mixed $value ): string {
		return strtoupper( sanitize_key( (string) $value ) );
	}

	public static function list_events( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$country = (string) $request->get_param( 'country' );
		if ( ! in_array( $country, SyncRunner::active_countries(), true ) ) {
			return new WP_Error(
				'agora_country_not_covered',
				__( 'Ese país no está en el calendario.', 'agora-calendar' ),
				array( 'status' => 400 )
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => EventType::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $request->get_param( 'per_page' ),
				'paged'          => (int) $request->get_param( 'page' ),
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

		$items = array();
		foreach ( $query->posts as $post ) {
			$view    = EventType::view( (int) $post->ID );
			$items[] = array(
				'id'         => $view['id'],
				'title'      => $view['title'],
				'date'       => $view['date'],
				'country'    => $view['country'],
				'overridden' => $view['overridden'],
			);
		}

		return rest_ensure_response(
			array(
				'events' => $items,
				'total'  => (int) $query->found_posts,
				'pages'  => (int) $query->max_num_pages,
			)
		);
	}
}
