<?php

namespace Nomade\Seed;

use Nomade\Content\ProductType;

defined( 'ABSPATH' ) || exit;

final class ProductSeeder {

	public static function seed_if_empty(): void {
		$existing = get_posts(
			array(
				'post_type'      => ProductType::SLUG,
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);
		if ( $existing ) {
			return;
		}

		$path = NOMADE_PRICES_DIR . 'data/products.csv';
		if ( ! is_readable( $path ) ) {
			return;
		}

		$handle = fopen( $path, 'r' );
		if ( ! $handle ) {
			return;
		}

		$header = fgetcsv( $handle );
		unset( $header );

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( count( $row ) < 3 ) {
				continue;
			}
			$title = sanitize_text_field( (string) $row[0] );
			$usd   = (float) $row[1];
			$body  = wp_kses_post( (string) $row[2] );
			$id    = wp_insert_post(
				array(
					'post_type'    => ProductType::SLUG,
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_content' => $body,
				),
				true
			);
			if ( ! is_wp_error( $id ) ) {
				update_post_meta( (int) $id, '_nomade_price_usd', $usd );
			}
		}

		fclose( $handle );
	}
}
