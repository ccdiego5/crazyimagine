<?php
/**
 * Override del bloque de precio. Vive en el child: desactivar el tema
 * vuelve al markup del plugin.
 *
 * @var array $nomade_quote
 * @var int $nomade_product_id
 * @var string[] $nomade_currencies
 */

defined( 'ABSPATH' ) || exit;

$quote      = $nomade_quote;
$product_id = $nomade_product_id;
$currencies = $nomade_currencies;
$current    = (string) $quote['currency'];
$field_id   = 'nomade-currency-' . $product_id;
?>
<aside class="nomade-price nomade-price--theme" data-product="<?php echo esc_attr( (string) $product_id ); ?>">
	<form class="nomade-price__switcher" method="get" action="">
		<label for="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Ver precio en', 'blocksy' ); ?></label>
		<select id="<?php echo esc_attr( $field_id ); ?>" name="currency" onchange="this.form.submit()">
			<?php foreach ( $currencies as $code ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current, $code ); ?>><?php echo esc_html( $code ); ?></option>
			<?php endforeach; ?>
		</select>
		<noscript><button type="submit"><?php esc_html_e( 'Actualizar', 'blocksy' ); ?></button></noscript>
	</form>

	<?php if ( empty( $quote['available'] ) ) : ?>
		<p class="nomade-price__missing">
			<?php esc_html_e( 'Sin tipo para esta moneda. Precio de referencia:', 'blocksy' ); ?>
			<strong>USD <?php echo esc_html( number_format( (float) $quote['usd'], 2, ',', '.' ) ); ?></strong>
		</p>
	<?php else : ?>
		<p class="nomade-price__amount">
			<span class="nomade-price__code"><?php echo esc_html( $quote['currency'] ); ?></span>
			<strong><?php echo esc_html( $quote['formatted'] ); ?></strong>
		</p>
		<p class="nomade-price__meta">
			<?php if ( $quote['rate'] ) : ?>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: rate 2: currency 3: date */
						__( '1 USD = %1$s %2$s · publicado %3$s', 'blocksy' ),
						(string) $quote['rate'],
						$quote['currency'],
						$quote['rate_date'] !== '' ? $quote['rate_date'] : '—'
					)
				);
				?>
			<?php endif; ?>
			<?php if ( ! empty( $quote['overridden'] ) ) : ?>
				<br><span class="nomade-price__note"><?php esc_html_e( 'Redondeado a mano. El tipo del día sigue arriba.', 'blocksy' ); ?></span>
			<?php endif; ?>
		</p>
	<?php endif; ?>
</aside>
