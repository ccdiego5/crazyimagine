<?php

namespace Nomade\Source;

defined( 'ABSPATH' ) || exit;

final class CsvSource implements RateSource {

	public function id(): string {
		return 'csv';
	}

	public function label(): string {
		return __( 'CSV local (banco / fallback)', 'nomade-prices' );
	}

	public function fetch( array $currencies ): FetchResult {
		$path = NOMADE_PRICES_DIR . 'data/rates.csv';
		if ( ! is_readable( $path ) ) {
			return new FetchResult( false, array(), '', __( 'No se lee data/rates.csv.', 'nomade-prices' ) );
		}

		$handle = fopen( $path, 'r' );
		if ( ! $handle ) {
			return new FetchResult( false, array(), '', __( 'No se abre data/rates.csv.', 'nomade-prices' ) );
		}

		$header = fgetcsv( $handle );
		$rates  = array();
		$date   = '';

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( count( $row ) < 2 ) {
				continue;
			}
			$code = strtoupper( sanitize_key( (string) $row[0] ) );
			if ( $code === '' || ! is_numeric( $row[1] ) ) {
				continue;
			}
			$rates[ $code ] = (float) $row[1];
			if ( isset( $row[2] ) && $row[2] !== '' ) {
				$date = sanitize_text_field( (string) $row[2] );
			}
		}
		fclose( $handle );

		$wanted  = array_map( 'strtoupper', $currencies );
		$kept    = array();
		$missing = array();
		foreach ( $wanted as $code ) {
			if ( isset( $rates[ $code ] ) ) {
				$kept[ $code ] = $rates[ $code ];
			} else {
				$missing[] = $code;
			}
		}

		if ( $kept === array() ) {
			return new FetchResult( false, array(), $date, __( 'El CSV no tiene las monedas activas.', 'nomade-prices' ), $missing );
		}

		return new FetchResult( true, $kept, $date, '', $missing );
	}
}
