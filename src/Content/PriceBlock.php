<?php

namespace Nomade\Content;

use Nomade\Plugin;
use Nomade\Sync\SyncRunner;

defined( 'ABSPATH' ) || exit;

final class PriceBlock {

	public static function init(): void {
		add_action( 'init', array( self::class, 'persist_requested_currency' ) );
	}

	public static function persist_requested_currency(): void {
		if ( headers_sent() || empty( $_GET['currency'] ) ) {
			return;
		}

		$code    = strtoupper( sanitize_key( wp_unslash( (string) $_GET['currency'] ) ) );
		$allowed = SyncRunner::active_currencies();
		if ( ! in_array( $code, $allowed, true ) ) {
			return;
		}

		$path = defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
		setcookie( 'nomade_currency', $code, time() + YEAR_IN_SECONDS, $path, '', is_ssl(), true );
		$_COOKIE['nomade_currency'] = $code;
	}

	public static function render( int $product_id, ?string $currency = null ): string {
		$currencies = SyncRunner::active_currencies();
		if ( $currencies === array() ) {
			return '';
		}

		$requested = $currency ?? self::requested_currency( $currencies );
		$quote     = Plugin::instance()->calculator->quote( $product_id, $requested );

		$theme = locate_template( 'templates/price-block.php' );
		$path  = $theme !== '' ? $theme : NOMADE_PRICES_DIR . 'templates/price-block.php';

		ob_start();
		$nomade_quote      = $quote;
		$nomade_product_id = $product_id;
		$nomade_currencies = $currencies;
		require $path;
		return (string) ob_get_clean();
	}

	/**
	 * @param string[] $allowed
	 */
	public static function requested_currency( array $allowed ): string {
		$from_query = isset( $_GET['currency'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['currency'] ) ) ) : '';
		if ( $from_query && in_array( $from_query, $allowed, true ) ) {
			return $from_query;
		}

		$from_cookie = isset( $_COOKIE['nomade_currency'] ) ? strtoupper( sanitize_key( wp_unslash( $_COOKIE['nomade_currency'] ) ) ) : '';
		if ( $from_cookie && in_array( $from_cookie, $allowed, true ) ) {
			return $from_cookie;
		}

		return $allowed[0];
	}
}
