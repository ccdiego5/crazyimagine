<?php

namespace Nomade\Content;

use Nomade\Sync\SyncRunner;

defined( 'ABSPATH' ) || exit;

final class ProductType {

	public const SLUG = 'nomade_product';

	public static function init(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'add_meta_boxes', array( self::class, 'meta_boxes' ) );
		add_action( 'save_post_' . self::SLUG, array( self::class, 'save' ), 10, 2 );
		add_filter( 'the_content', array( self::class, 'append_price_block' ) );
	}

	public static function register(): void {
		register_post_type(
			self::SLUG,
			array(
				'labels'       => array(
					'name'          => __( 'Productos Nómade', 'nomade-prices' ),
					'singular_name' => __( 'Producto', 'nomade-prices' ),
					'add_new_item'  => __( 'Añadir producto', 'nomade-prices' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-tag',
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
				'rewrite'      => array( 'slug' => 'catalogo' ),
			)
		);
	}

	public static function meta_boxes(): void {
		add_meta_box(
			'nomade_price',
			__( 'Precio Nómade', 'nomade-prices' ),
			array( self::class, 'render_box' ),
			self::SLUG,
			'side'
		);
	}

	public static function render_box( \WP_Post $post ): void {
		wp_nonce_field( 'nomade_save_price', 'nomade_price_nonce' );
		$usd = get_post_meta( $post->ID, '_nomade_price_usd', true );
		echo '<p><label for="nomade_price_usd"><strong>' . esc_html__( 'Precio base (USD)', 'nomade-prices' ) . '</strong></label></p>';
		echo '<p><input type="number" step="0.01" min="0" class="widefat" id="nomade_price_usd" name="nomade_price_usd" value="' . esc_attr( (string) $usd ) . '" /></p>';
		echo '<p class="description">' . esc_html__( 'Abajo está el precio de hoy. Cámbialo solo si quieres redondear. Si lo dejas igual, sigue el tipo.', 'nomade-prices' ) . '</p>';

		$calc   = \Nomade\Plugin::instance()->calculator;
		$bundle = get_option( SyncRunner::OPTION_RATES, array() );
		$date   = is_array( $bundle ) ? (string) ( $bundle['rate_date'] ?? '' ) : '';

		if ( ! is_array( $bundle ) || empty( $bundle['rates'] ) ) {
			echo '<p class="description"><a href="' . esc_url( admin_url( 'edit.php?post_type=' . self::SLUG . '&page=nomade-rates' ) ) . '">' . esc_html__( 'Aún no hay tipos. Sincroniza Frankfurter.', 'nomade-prices' ) . '</a></p>';
		}

		foreach ( SyncRunner::active_currencies() as $code ) {
			$quote     = $calc->quote( $post->ID, $code );
			$computed  = $quote['computed'];
			$shown     = $quote['overridden'] ? $quote['amount'] : $computed;
			$step      = in_array( $code, array( 'COP', 'CLP', 'VES' ), true ) ? '1' : '0.01';
			$input_val = $shown === null ? '' : (string) ( in_array( $code, array( 'COP', 'CLP', 'VES' ), true ) ? round( (float) $shown ) : round( (float) $shown, 2 ) );

			echo '<p style="margin-bottom:2px"><label for="nomade_override_' . esc_attr( $code ) . '"><strong>' . esc_html( $code ) . '</strong></label></p>';
			if ( $quote['rate'] ) {
				echo '<p class="description" style="margin:0 0 4px">';
				echo esc_html(
					sprintf(
						/* translators: 1: rate 2: currency 3: date */
						__( 'Tasa hoy: 1 USD = %1$s %2$s · %3$s', 'nomade-prices' ),
						(string) $quote['rate'],
						$code,
						$date !== '' ? $date : '—'
					)
				);
				echo '</p>';
			}
			echo '<p><input type="number" step="' . esc_attr( $step ) . '" min="0" class="widefat" id="nomade_override_' . esc_attr( $code ) . '" name="nomade_override_' . esc_attr( $code ) . '" value="' . esc_attr( $input_val ) . '" /></p>';
			if ( $quote['overridden'] && $computed !== null ) {
				echo '<p class="description">' . esc_html( sprintf( /* translators: price */ __( 'Calculado por tipo: %s (este campo está redondeado a mano).', 'nomade-prices' ), \Nomade\Pricing\Calculator::format_amount( (float) $computed, $code ) ) ) . '</p>';
			}
		}
	}

	public static function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['nomade_price_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nomade_price_nonce'] ) ), 'nomade_save_price' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$usd = isset( $_POST['nomade_price_usd'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['nomade_price_usd'] ) ) : 0;
		update_post_meta( $post_id, '_nomade_price_usd', $usd );

		$calc = \Nomade\Plugin::instance()->calculator;
		foreach ( SyncRunner::active_currencies() as $code ) {
			$key = 'nomade_override_' . $code;
			$raw = isset( $_POST[ $key ] ) ? trim( sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) ) ) : '';
			if ( $raw === '' || ! is_numeric( $raw ) ) {
				delete_post_meta( $post_id, '_nomade_override_' . $code );
				continue;
			}

			$quote    = $calc->quote( $post_id, $code );
			$computed = $quote['computed'];
			$entered  = (float) $raw;
			$zero_dec = in_array( $code, array( 'COP', 'CLP', 'VES' ), true );
			$same     = $computed !== null && (
				$zero_dec
					? (int) round( $entered ) === (int) round( (float) $computed )
					: abs( $entered - (float) $computed ) < 0.009
			);

			if ( $same ) {
				delete_post_meta( $post_id, '_nomade_override_' . $code );
				continue;
			}

			update_post_meta( $post_id, '_nomade_override_' . $code, $entered );
		}
	}

	public static function append_price_block( string $content ): string {
		if ( ! is_singular( self::SLUG ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		return $content . PriceBlock::render( get_the_ID() );
	}
}
