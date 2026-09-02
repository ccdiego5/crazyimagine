<?php

namespace Agora\Source;

defined( 'ABSPATH' ) || exit;

final class NagerDateSource implements EventSource {

	private const ENDPOINT = 'https://date.nager.at/api/v3/PublicHolidays';

	public function id(): string {
		return 'nager';
	}

	public function label(): string {
		return __( 'Nager.Date v3 (oficial por país)', 'agora-calendar' );
	}

	public function fetch( array $countries, array $years ): FetchResult {
		$events = array();
		$failed = array();

		foreach ( $years as $year ) {
			$year = (int) $year;
			if ( $year < 2000 || $year > 2100 ) {
				continue;
			}
			foreach ( $countries as $code ) {
				$code = strtoupper( sanitize_key( (string) $code ) );
				if ( ! preg_match( '/^[A-Z]{2}$/', $code ) ) {
					continue;
				}

				$url      = self::ENDPOINT . '/' . $year . '/' . $code;
				$response = wp_remote_get(
					$url,
					array(
						'timeout'    => 8,
						'user-agent' => 'AgoraCalendar/' . AGORA_CALENDAR_VERSION . '; ' . home_url( '/' ),
					)
				);

				if ( is_wp_error( $response ) ) {
					$failed[] = $code . '/' . $year;
					continue;
				}

				$status = (int) wp_remote_retrieve_response_code( $response );
				$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

				if ( $status !== 200 || ! is_array( $body ) ) {
					$failed[] = $code . '/' . $year;
					continue;
				}

				foreach ( $body as $row ) {
					$parsed = $this->parse_row( $row, $code );
					if ( $parsed !== null ) {
						$events[] = $parsed;
					}
				}
			}
		}

		if ( $events === array() ) {
			return new FetchResult( false, array(), __( 'Nager no trajo festivos usables.', 'agora-calendar' ), $failed );
		}

		return new FetchResult( true, $events, '', $failed );
	}

	/**
	 * @param mixed $row
	 * @return array<string, mixed>|null
	 */
	private function parse_row( mixed $row, string $fallback_country ): ?array {
		if ( ! is_array( $row ) || empty( $row['date'] ) || empty( $row['name'] ) ) {
			return null;
		}

		$date = sanitize_text_field( (string) $row['date'] );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return null;
		}

		$country = ! empty( $row['countryCode'] ) ? strtoupper( sanitize_key( (string) $row['countryCode'] ) ) : $fallback_country;
		$counties = array();
		if ( isset( $row['counties'] ) && is_array( $row['counties'] ) ) {
			foreach ( $row['counties'] as $county ) {
				$counties[] = sanitize_text_field( (string) $county );
			}
		}

		$types = array();
		if ( isset( $row['types'] ) && is_array( $row['types'] ) ) {
			foreach ( $row['types'] as $type ) {
				$types[] = sanitize_key( (string) $type );
			}
		}

		return array(
			'date'          => $date,
			'official_name' => sanitize_text_field( (string) $row['name'] ),
			'local_name'    => sanitize_text_field( (string) ( $row['localName'] ?? $row['name'] ) ),
			'country'       => $country,
			'counties'      => $counties,
			'types'         => $types,
			'global'        => ! empty( $row['global'] ),
		);
	}
}
