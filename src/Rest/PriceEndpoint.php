<?php

namespace Nomade\Rest;

use Nomade\Content\ProductType;
use Nomade\Plugin;
use Nomade\Sync\SyncRunner;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class PriceEndpoint {

	public static function init(): void {
		add_action( 'rest_api_init', array( self::class, 'register' ) );
	}

	public static function register(): void {
		register_rest_route(
			'nomade/v1',
			'/products/(?P<id>\d+)/price',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'get_price' ),
				'permission_callback' => array( self::class, 'can_read' ),
				'args'                => array(
					'id'       => array(
						'required'          => true,
						'validate_callback' => array( self::class, 'validate_id' ),
						'sanitize_callback' => 'absint',
					),
					'currency' => array(
						'required'          => true,
						'validate_callback' => array( self::class, 'validate_currency' ),
						'sanitize_callback' => array( self::class, 'sanitize_currency' ),
					),
				),
			)
		);
	}

	/**
	 * El precio ya es público en la ficha. Este GET no dispara sync.
	 */
	public static function can_read(): bool {
		return true;
	}

	public static function validate_id( mixed $value ): bool {
		return is_numeric( $value ) && (int) $value > 0;
	}

	public static function validate_currency( mixed $value ): bool {
		return is_string( $value ) && (bool) preg_match( '/^[A-Za-z]{3}$/', $value );
	}

	public static function sanitize_currency( mixed $value ): string {
		return strtoupper( sanitize_key( (string) $value ) );
	}

	public static function get_price( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id       = (int) $request->get_param( 'id' );
		$currency = (string) $request->get_param( 'currency' );
		$post     = get_post( $id );

		if ( ! $post || $post->post_type !== ProductType::SLUG || $post->post_status !== 'publish' ) {
			return new WP_Error(
				'nomade_product_not_found',
				__( 'Ese producto no existe o no está publicado.', 'nomade-prices' ),
				array( 'status' => 404 )
			);
		}

		if ( ! in_array( $currency, SyncRunner::active_currencies(), true ) ) {
			return new WP_Error(
				'nomade_currency_not_covered',
				__( 'Esa moneda no está en el catálogo.', 'nomade-prices' ),
				array( 'status' => 404 )
			);
		}

		$quote = Plugin::instance()->calculator->quote( $id, $currency );

		if ( empty( $quote['available'] ) ) {
			return new WP_Error(
				'nomade_rate_missing',
				__( 'No hay tipo persistido para esa moneda. No se inventa un precio.', 'nomade-prices' ),
				array( 'status' => 404 )
			);
		}

		$zero_dec = in_array( $currency, array( 'COP', 'CLP', 'VES' ), true );
		$amount   = $zero_dec ? (float) round( (float) $quote['amount'] ) : round( (float) $quote['amount'], 2 );
		$rate     = $quote['rate'] === null ? null : round( (float) $quote['rate'], 6 );

		return rest_ensure_response(
			array(
				'amount'     => $amount,
				'currency'   => $quote['currency'],
				'rate'       => $rate,
				'rate_date'  => $quote['rate_date'],
				'overridden' => (bool) $quote['overridden'],
			)
		);
	}
}
