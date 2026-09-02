<?php

namespace Nomade\Sync;

use Nomade\Source\SourceRegistry;

defined( 'ABSPATH' ) || exit;

final class SyncRunner {

	public const OPTION_RATES  = 'nomade_rates';
	public const OPTION_LOG    = 'nomade_sync_log';
	public const OPTION_SOURCE = 'nomade_active_source';
	public const LOCK_KEY      = 'nomade_sync_lock';

	public function __construct( private SourceRegistry $sources ) {}

	public function run_scheduled(): void {
		$this->run( (string) get_option( self::OPTION_SOURCE, 'frankfurter' ), false );
	}

	/**
	 * @return array{ok:bool, message:string, skipped?:bool}
	 */
	public function run( string $source_id, bool $manual ): array {
		if ( get_transient( self::LOCK_KEY ) ) {
			$entry = $this->log( false, $source_id, __( 'Omitido: ya hay un sync en curso.', 'nomade-prices' ), array(), array() );
			return array(
				'ok'      => false,
				'skipped' => true,
				'message' => $entry['message'],
			);
		}

		set_transient( self::LOCK_KEY, 1, 2 * MINUTE_IN_SECONDS );

		try {
			$source = $this->sources->get( $source_id );
			if ( ! $source ) {
				$this->log( false, $source_id, __( 'Fuente desconocida.', 'nomade-prices' ), array(), array() );
				return array(
					'ok'      => false,
					'message' => __( 'Fuente desconocida.', 'nomade-prices' ),
				);
			}

			$currencies = self::active_currencies();
			$result     = $source->fetch( $currencies );

			if ( ! $result->ok ) {
				$this->log( false, $source_id, $result->error, array(), $result->missing );
				return array(
					'ok'      => false,
					'message' => $result->error,
				);
			}

			$previous = get_option( self::OPTION_RATES, array() );
			$stored   = is_array( $previous ) ? $previous : array();
			$merged   = isset( $stored['rates'] ) && is_array( $stored['rates'] ) ? $stored['rates'] : array();

			foreach ( $result->rates as $code => $rate ) {
				$merged[ $code ] = $rate;
			}

			update_option(
				self::OPTION_RATES,
				array(
					'source'     => $source_id,
					'base'       => 'USD',
					'rate_date'  => $result->rate_date,
					'rates'      => $merged,
					'updated_at' => gmdate( 'c' ),
					'partial'    => $result->is_partial(),
				),
				false
			);

			$message = $result->is_partial()
				? sprintf(
					/* translators: 1: ok count 2: missing list */
					__( 'Parcial: %1$d tipos. Faltaron: %2$s.', 'nomade-prices' ),
					count( $result->rates ),
					implode( ', ', $result->missing )
				)
				: sprintf(
					/* translators: %d count */
					__( 'Sync ok: %d tipos.', 'nomade-prices' ),
					count( $result->rates )
				);

			$this->log( true, $source_id, $message, $result->rates, $result->missing );

			/**
			 * Corre al terminar un sync que persistió tipos.
			 *
			 * @param array  $payload   Option nomade_rates.
			 * @param bool   $manual    Si lo disparó el admin.
			 * @param string $source_id Id de la fuente.
			 */
			do_action( 'nomade_sync_completed', get_option( self::OPTION_RATES ), $manual, $source_id );

			return array(
				'ok'      => true,
				'message' => $message,
			);
		} finally {
			delete_transient( self::LOCK_KEY );
		}
	}

	/**
	 * @return string[]
	 */
	public static function active_currencies(): array {
		$default = array( 'MXN', 'COP', 'CLP', 'PEN', 'EUR', 'VES' );
		$saved   = get_option( 'nomade_currencies', $default );
		$list    = is_array( $saved ) ? $saved : $default;

		/**
		 * Monedas activas del catálogo.
		 *
		 * @param string[] $currencies Códigos ISO 4217.
		 */
		$filtered = apply_filters( 'nomade_active_currencies', $list );

		$out = array();
		foreach ( (array) $filtered as $code ) {
			$code = strtoupper( sanitize_key( (string) $code ) );
			if ( preg_match( '/^[A-Z]{3}$/', $code ) ) {
				$out[] = $code;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * @param array<string, float> $rates
	 * @param string[]             $missing
	 * @return array<string, mixed>
	 */
	private function log( bool $ok, string $source_id, string $message, array $rates, array $missing ): array {
		$entry = array(
			'at'      => gmdate( 'c' ),
			'ok'      => $ok,
			'source'  => $source_id,
			'message' => $message,
			'count'   => count( $rates ),
			'missing' => $missing,
		);

		$log = get_option( self::OPTION_LOG, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		array_unshift( $log, $entry );
		update_option( self::OPTION_LOG, array_slice( $log, 0, 10 ), false );

		return $entry;
	}
}
