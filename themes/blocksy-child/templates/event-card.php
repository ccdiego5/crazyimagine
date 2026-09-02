<?php
/**
 * Override de la tarjeta de festivo. Desactivar el child vuelve al plugin.
 *
 * @var array $agora_event
 */

defined( 'ABSPATH' ) || exit;

$event = $agora_event;
?>
<article class="agora-card agora-card--theme" data-event="<?php echo esc_attr( (string) $event['id'] ); ?>">
	<p class="agora-card__date"><?php echo esc_html( $event['date'] !== '' ? $event['date'] : '—' ); ?></p>
	<h3 class="agora-card__title">
		<a href="<?php echo esc_url( (string) $event['url'] ); ?>"><?php echo esc_html( (string) $event['title'] ); ?></a>
	</h3>
	<p class="agora-card__meta">
		<?php echo esc_html( (string) $event['country'] ); ?>
		<?php if ( ! empty( $event['counties'] ) ) : ?>
			· <?php echo esc_html( 'ámbito: ' . implode( ', ', $event['counties'] ) ); ?>
		<?php endif; ?>
	</p>
	<?php if ( ! empty( $event['overridden'] ) ) : ?>
		<p class="agora-card__note"><?php esc_html_e( 'Corregido por el equipo. Fecha y país oficiales.', 'blocksy' ); ?></p>
	<?php endif; ?>
	<?php if ( ! empty( $event['description'] ) ) : ?>
		<div class="agora-card__desc"><?php echo wp_kses_post( wpautop( (string) $event['description'] ) ); ?></div>
	<?php endif; ?>
</article>
