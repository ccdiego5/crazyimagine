<?php

namespace Nomade\Pricing;

use Nomade\Sync\SyncRunner;

defined( 'ABSPATH' ) || exit;

final class Calculator {

	/**
	 * Cálculo local. No hace HTTP.
	 *
	 * @return array{
	 *   amount: float|null,
	 *   formatted: string,
	 *   currency: string,
	 *   rate: float|null,
	 *   rate_date: string,
	 *   overridden: bool,
	 *   available: bool
	 * }
	 */
	public function quote( int $product_id, string $currency ): array {
		$currency = strtoupper( sanitize_key( $currency ) );
		$usd      = (float) get_post_meta( $product_id, '_nomade_price_usd', true );
		$override = get_post_meta( $product_id, '_nomade_override_' . $currency, true );
		$bundle   = get_option( SyncRunner::OPTION_RATES, array() );
		$rates    = ( is_array( $bundle ) && isset( $bundle['rates'] ) && is_array( $bundle['rates'] ) ) ? $bundle['rates'] : array();
		$date     = is_array( $bundle ) ? (string) ( $bundle['rate_date'] ?? '' ) : '';

		$overridden = $override !== '' && is_numeric( $override );
		$rate       = isset( $rates[ $currency ] ) && is_numeric( $rates[ $currency ] ) ? (float) $rates[ $currency ] : null;
		$computed   = $rate !== null ? $usd * $rate : null;

		if ( $overridden ) {
			$amount = (float) $override;
		} else {
			$amount = $computed;
		}

		return array(
			'amount'     => $amount,
			'formatted'  => $amount === null ? '' : self::format_amount( $amount, $currency ),
			'currency'   => $currency,
			'rate'       => $rate,
			'rate_date'  => $date,
			'overridden' => $overridden,
			'available'  => $amount !== null,
			'usd'        => $usd,
			'computed'   => $computed,
		);
	}

	public static function format_amount( float $amount, string $currency ): string {
		$zero = array( 'COP', 'CLP', 'VES' );
		$dec  = in_array( $currency, $zero, true ) ? 0 : 2;
		return number_format( $amount, $dec, ',', '.' );
	}
}
