<?php
/**
 * Markup por defecto del bloque de precio. El tema hijo puede overridear
 * templates/price-block.php vía locate_template.
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
?>
<div class="nomade-price" data-product="<?php echo esc_attr( (string) $product_id ); ?>">
	<form class="nomade-price__switcher" method="get">
		<label for="nomade-currency-<?php echo esc_attr( (string) $product_id ); ?>">
			<?php esc_html_e( 'Moneda', 'nomade-prices' ); ?>
		</label>
		<select id="nomade-currency-<?php echo esc_attr( (string) $product_id ); ?>" name="currency" onchange="this.form.submit()">
			<?php foreach ( $currencies as $code ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current, $code ); ?>><?php echo esc_html( $code ); ?></option>
			<?php endforeach; ?>
		</select>
		<noscript><button type="submit"><?php esc_html_e( 'Ver', 'nomade-prices' ); ?></button></noscript>
	</form>

	<?php if ( empty( $quote['available'] ) ) : ?>
		<p class="nomade-price__missing">
			<?php esc_html_e( 'No hay tipo para esta moneda. Se muestra el precio en USD.', 'nomade-prices' ); ?>
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
						/* translators: 1 rate 2 date */
						__( 'Tipo: 1 USD = %1$s %2$s · fecha %3$s', 'nomade-prices' ),
						(string) $quote['rate'],
						$quote['currency'],
						$quote['rate_date'] !== '' ? $quote['rate_date'] : '—'
					)
				);
				?>
			<?php endif; ?>
			<?php if ( ! empty( $quote['overridden'] ) ) : ?>
				<br><em><?php esc_html_e( 'Precio ajustado a mano por el equipo. El tipo del día sigue visible arriba.', 'nomade-prices' ); ?></em>
			<?php endif; ?>
		</p>
	<?php endif; ?>
</div>
