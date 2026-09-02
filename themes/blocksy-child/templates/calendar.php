<?php
/**
 * Calendario anual (look del child).
 *
 * @var string[] $agora_countries
 * @var string $agora_current
 * @var array<string, string> $agora_labels
 * @var int $agora_year
 * @var array<string, list<array<string, mixed>>> $agora_by_date
 */

defined( 'ABSPATH' ) || exit;

$months = array(
	1  => 'Enero',
	2  => 'Febrero',
	3  => 'Marzo',
	4  => 'Abril',
	5  => 'Mayo',
	6  => 'Junio',
	7  => 'Julio',
	8  => 'Agosto',
	9  => 'Septiembre',
	10 => 'Octubre',
	11 => 'Noviembre',
	12 => 'Diciembre',
);
$dows = array( 'L', 'M', 'X', 'J', 'V', 'S', 'D' );
?>
<div class="agora-calendar agora-calendar--theme">
	<form class="agora-calendar__filter" method="get" action="">
		<label for="agora-country"><?php esc_html_e( 'País', 'blocksy' ); ?></label>
		<select id="agora-country" name="country" onchange="this.form.submit()">
			<?php foreach ( $agora_countries as $code ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $agora_current, $code ); ?>>
					<?php echo esc_html( $agora_labels[ $code ] ?? $code ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<noscript><button type="submit"><?php esc_html_e( 'Ver', 'blocksy' ); ?></button></noscript>
	</form>

	<p class="agora-calendar__lede">
		<?php echo esc_html( sprintf( 'Festivos %d · %s', $agora_year, $agora_labels[ $agora_current ] ?? $agora_current ) ); ?>
	</p>

	<div class="agora-year">
		<?php
		for ( $month = 1; $month <= 12; $month++ ) {
			$start_w = (int) gmdate( 'N', gmmktime( 0, 0, 0, $month, 1, $agora_year ) );
			$days_in = (int) gmdate( 't', gmmktime( 0, 0, 0, $month, 1, $agora_year ) );
			?>
			<section class="agora-month">
				<h3 class="agora-month__name"><?php echo esc_html( $months[ $month ] ); ?></h3>
				<div class="agora-month__grid">
					<?php foreach ( $dows as $dow ) : ?>
						<span class="agora-month__dow"><?php echo esc_html( $dow ); ?></span>
					<?php endforeach; ?>
					<?php for ( $pad = 1; $pad < $start_w; $pad++ ) : ?>
						<span class="agora-month__day is-empty"></span>
					<?php endfor; ?>
					<?php
					for ( $day = 1; $day <= $days_in; $day++ ) {
						$key  = sprintf( '%04d-%02d-%02d', $agora_year, $month, $day );
						$hits = $agora_by_date[ $key ] ?? array();
						$cls  = 'agora-month__day' . ( $hits !== array() ? ' is-holiday' : '' );
						$tip  = array();
						foreach ( $hits as $hit ) {
							$tip[] = (string) $hit['title'];
						}
						echo '<span class="' . esc_attr( $cls ) . '"' . ( $tip !== array() ? ' title="' . esc_attr( implode( ' · ', $tip ) ) . '"' : '' ) . '>';
						echo '<span class="agora-month__num">' . esc_html( (string) $day ) . '</span>';
						foreach ( $hits as $hit ) {
							echo '<a class="agora-month__hol" href="' . esc_url( (string) $hit['url'] ) . '">' . esc_html( (string) $hit['title'] ) . '</a>';
						}
						echo '</span>';
					}
					?>
				</div>
			</section>
			<?php
		}
		?>
	</div>
</div>
