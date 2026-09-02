<?php
/**
 * Markup por defecto de la tarjeta. El tema hijo puede overridear
 * templates/event-card.php vía locate_template.
 *
 * @var array $agora_event
 */

defined( 'ABSPATH' ) || exit;

$event = $agora_event;
?>
<article class="agora-card" data-event="<?php echo esc_attr( (string) $event['id'] ); ?>">
	<p class="agora-card__date"><?php echo esc_html( $event['date'] !== '' ? $event['date'] : '—' ); ?></p>
	<h3 class="agora-card__title">
		<a href="<?php echo esc_url( (string) $event['url'] ); ?>"><?php echo esc_html( (string) $event['title'] ); ?></a>
	</h3>
	<p class="agora-card__meta">
		<?php echo esc_html( (string) $event['country'] ); ?>
		<?php if ( ! empty( $event['counties'] ) ) : ?>
			· <?php echo esc_html( sprintf( /* translators: regions */ __( 'ámbito: %s', 'agora-calendar' ), implode( ', ', $event['counties'] ) ) ); ?>
		<?php endif; ?>
	</p>
	<?php if ( ! empty( $event['overridden'] ) ) : ?>
		<p class="agora-card__note"><?php esc_html_e( 'Nombre corregido por el equipo. La fecha y el país siguen siendo los oficiales.', 'agora-calendar' ); ?></p>
	<?php endif; ?>
	<?php if ( ! empty( $event['description'] ) ) : ?>
		<div class="agora-card__desc"><?php echo wp_kses_post( wpautop( (string) $event['description'] ) ); ?></div>
	<?php endif; ?>
</article>
