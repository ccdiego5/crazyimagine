<?php

namespace Nomade\Source;

defined( 'ABSPATH' ) || exit;

final class FrankfurterSource implements RateSource {

	private const ENDPOINT = 'https://api.frankfurter.dev/v2/rates';

	public function id(): string {
		return 'frankfurter';
	}

	public function label(): string {
		return __( 'Frankfurter (bancos centrales)', 'nomade-prices' );
	}

	public function fetch( array $currencies ): FetchResult {
		$wanted = array();
		foreach ( $currencies as $code ) {
			$code = strtoupper( sanitize_key( (string) $code ) );
			if ( preg_match( '/^[A-Z]{3}$/', $code ) && $code !== 'USD' ) {
				$wanted[] = $code;
			}
		}

		$url      = add_query_arg(
			array(
				'base'   => 'USD',
				'quotes' => implode( ',', $wanted ),
			),
			self::ENDPOINT
		);
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 8,
				'user-agent' => 'NomadePrices/' . NOMADE_PRICES_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new FetchResult( false, array(), '', $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 || ! is_array( $body ) ) {
			return new FetchResult( false, array(), '', sprintf( /* translators: %d status */ __( 'HTTP %d o JSON inválido.', 'nomade-prices' ), $code ) );
		}

		$parsed = $this->parse_body( $body );
		if ( $parsed['rates'] === array() ) {
			return new FetchResult( false, array(), $parsed['date'], __( 'Frankfurter no trajo tipos usables.', 'nomade-prices' ) );
		}

		$rates   = array();
		$missing = array();
		foreach ( $wanted as $iso ) {
			if ( isset( $parsed['rates'][ $iso ] ) ) {
				$rates[ $iso ] = $parsed['rates'][ $iso ];
			} else {
				$missing[] = $iso;
			}
		}

		if ( $rates === array() ) {
			return new FetchResult( false, array(), $parsed['date'], __( 'Ninguna moneda activa vino en la respuesta.', 'nomade-prices' ), $missing );
		}

		return new FetchResult( true, $rates, $parsed['date'], '', $missing );
	}

	/**
	 * v2 puede devolver lista de filas o un objeto clásico { date, rates }.
	 *
	 * @param array<int|string, mixed> $body
	 * @return array{date: string, rates: array<string, float>}
	 */
	private function parse_body( array $body ): array {
		$date  = '';
		$rates = array();

		if ( array_is_list( $body ) ) {
			foreach ( $body as $row ) {
				if ( ! is_array( $row ) || empty( $row['quote'] ) || ! isset( $row['rate'] ) || ! is_numeric( $row['rate'] ) ) {
					continue;
				}
				$rates[ strtoupper( (string) $row['quote'] ) ] = (float) $row['rate'];
				if ( ! empty( $row['date'] ) ) {
					$date = sanitize_text_field( (string) $row['date'] );
				}
			}
			return array(
				'date'  => $date,
				'rates' => $rates,
			);
		}

		if ( ! empty( $body['date'] ) ) {
			$date = sanitize_text_field( (string) $body['date'] );
		}

		$bucket = array();
		if ( isset( $body['rates'] ) && is_array( $body['rates'] ) ) {
			$bucket = $body['rates'];
		} elseif ( isset( $body['quotes'] ) && is_array( $body['quotes'] ) ) {
			$bucket = $body['quotes'];
		}

		foreach ( $bucket as $iso => $rate ) {
			if ( is_numeric( $rate ) ) {
				$rates[ strtoupper( (string) $iso ) ] = (float) $rate;
			}
		}

		return array(
			'date'  => $date,
			'rates' => $rates,
		);
	}
}
