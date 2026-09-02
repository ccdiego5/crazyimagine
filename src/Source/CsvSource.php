<?php

namespace Agora\Source;

defined( 'ABSPATH' ) || exit;

final class CsvSource implements EventSource {

	public function id(): string {
		return 'csv';
	}

	public function label(): string {
		return __( 'CSV local (alcaldía / fallback)', 'agora-calendar' );
	}

	public function fetch( array $countries, array $years ): FetchResult {
		$path = AGORA_CALENDAR_DIR . 'data/holidays.csv';
		if ( ! is_readable( $path ) ) {
			return new FetchResult( false, array(), __( 'No se lee data/holidays.csv.', 'agora-calendar' ) );
		}

		$handle = fopen( $path, 'r' );
		if ( ! $handle ) {
			return new FetchResult( false, array(), __( 'No se abre data/holidays.csv.', 'agora-calendar' ) );
		}

		$header = fgetcsv( $handle );
		unset( $header );

		$wanted_c = array_map( 'strtoupper', $countries );
		$wanted_y = array_map( 'intval', $years );
		$events   = array();

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( count( $row ) < 4 ) {
				continue;
			}
			$country = strtoupper( sanitize_key( (string) $row[0] ) );
			$date    = sanitize_text_field( (string) $row[1] );
			$year    = (int) substr( $date, 0, 4 );
			if ( ! in_array( $country, $wanted_c, true ) || ! in_array( $year, $wanted_y, true ) ) {
				continue;
			}
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
				continue;
			}
			$events[] = array(
				'date'          => $date,
				'official_name' => sanitize_text_field( (string) $row[2] ),
				'local_name'    => sanitize_text_field( (string) $row[3] ),
				'country'       => $country,
				'counties'      => array(),
				'types'         => array( 'public' ),
				'global'        => true,
			);
		}
		fclose( $handle );

		if ( $events === array() ) {
			return new FetchResult( false, array(), __( 'El CSV no tiene festivos para esos países y años.', 'agora-calendar' ) );
		}

		return new FetchResult( true, $events );
	}
}
