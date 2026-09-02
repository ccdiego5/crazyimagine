<?php

namespace Agora\Sync;

use Agora\Content\EventType;
use Agora\Source\SourceRegistry;

defined( 'ABSPATH' ) || exit;

final class SyncRunner {

	public const OPTION_LOG    = 'agora_sync_log';
	public const OPTION_SOURCE = 'agora_active_source';
	public const LOCK_KEY      = 'agora_sync_lock';

	public function __construct( private SourceRegistry $sources ) {}

	public function run_scheduled(): void {
		$this->run( (string) get_option( self::OPTION_SOURCE, 'nager' ), false );
	}

	/**
	 * @return array{ok:bool, message:string, skipped?:bool}
	 */
	public function run( string $source_id, bool $manual ): array {
		if ( get_transient( self::LOCK_KEY ) ) {
			$entry = $this->log( false, $source_id, __( 'Omitido: ya hay un sync en curso.', 'agora-calendar' ), 0, array() );
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
				$this->log( false, $source_id, __( 'Fuente desconocida.', 'agora-calendar' ), 0, array() );
				return array(
					'ok'      => false,
					'message' => __( 'Fuente desconocida.', 'agora-calendar' ),
				);
			}

			$result = $source->fetch( self::active_countries(), self::sync_years() );

			if ( ! $result->ok ) {
				$this->log( false, $source_id, $result->error, 0, $result->failed );
				return array(
					'ok'      => false,
					'message' => $result->error,
				);
			}

			$upserted = 0;
			foreach ( $result->events as $row ) {
				if ( EventType::upsert( $row ) ) {
					++$upserted;
				}
			}

			$message = $result->is_partial()
				? sprintf(
					/* translators: 1: count 2: failed list */
					__( 'Parcial: %1$d festivos. Fallaron: %2$s.', 'agora-calendar' ),
					$upserted,
					implode( ', ', $result->failed )
				)
				: sprintf(
					/* translators: %d count */
					__( 'Sync ok: %d festivos (sin duplicar, sin pisar a Marta).', 'agora-calendar' ),
					$upserted
				);

			$this->log( true, $source_id, $message, $upserted, $result->failed );

			do_action( 'agora_sync_completed', $upserted, $manual, $source_id );

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
	public static function active_countries(): array {
		$default = array( 'ES', 'MX', 'CO', 'CL', 'PE', 'VE' );
		$saved   = get_option( 'agora_countries', $default );
		$list    = is_array( $saved ) ? $saved : $default;

		$filtered = apply_filters( 'agora_active_countries', $list );

		$out = array();
		foreach ( (array) $filtered as $code ) {
			$code = strtoupper( sanitize_key( (string) $code ) );
			if ( preg_match( '/^[A-Z]{2}$/', $code ) ) {
				$out[] = $code;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * @return int[]
	 */
	public static function sync_years(): array {
		$year  = (int) gmdate( 'Y' );
		$years = array( $year );
		if ( (int) gmdate( 'n' ) >= 11 ) {
			$years[] = $year + 1;
		}
		return $years;
	}

	/**
	 * @param string[] $failed
	 * @return array<string, mixed>
	 */
	private function log( bool $ok, string $source_id, string $message, int $count, array $failed ): array {
		$entry = array(
			'at'      => gmdate( 'c' ),
			'ok'      => $ok,
			'source'  => $source_id,
			'message' => $message,
			'count'   => $count,
			'failed'  => $failed,
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
