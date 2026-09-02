<?php

namespace Nomade\Frontend;

use Nomade\Content\PriceBlock;
use Nomade\Content\ProductType;

defined( 'ABSPATH' ) || exit;

final class PriceShortcode {

	public static function init( mixed $calculator = null ): void {
		unset( $calculator );
		add_shortcode( 'nomade_price', array( self::class, 'shortcode' ) );
		add_shortcode( 'nomade_catalog', array( self::class, 'catalog' ) );
	}

	/**
	 * @param array<string, string>|string $atts
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'       => get_the_ID(),
				'currency' => '',
			),
			$atts,
			'nomade_price'
		);

		$id = absint( $atts['id'] );
		if ( $id <= 0 || get_post_type( $id ) !== ProductType::SLUG ) {
			return '';
		}

		$currency = $atts['currency'] !== '' ? $atts['currency'] : null;
		return PriceBlock::render( $id, $currency );
	}

	public static function catalog(): string {
		$q = new \WP_Query(
			array(
				'post_type'      => ProductType::SLUG,
				'posts_per_page' => 20,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( ! $q->have_posts() ) {
			return '<p>' . esc_html__( 'No hay productos.', 'nomade-prices' ) . '</p>';
		}

		$html = '<div class="nomade-catalog">';
		while ( $q->have_posts() ) {
			$q->the_post();
			$html .= '<article class="nomade-catalog__item">';
			$html .= '<h3><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
			$html .= PriceBlock::render( get_the_ID() );
			$html .= '</article>';
		}
		wp_reset_postdata();
		$html .= '</div>';

		return $html;
	}
}
