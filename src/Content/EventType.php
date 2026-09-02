<?php

namespace Agora\Content;

defined( 'ABSPATH' ) || exit;

final class EventType {

	public const SLUG = 'agora_event';
	public const TAX  = 'agora_country';

	public static function init(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'add_meta_boxes', array( self::class, 'meta_boxes' ) );
		add_action( 'save_post_' . self::SLUG, array( self::class, 'save' ), 10, 2 );
		add_filter( 'the_content', array( self::class, 'append_card' ) );
	}

	public static function register(): void {
		register_post_type(
			self::SLUG,
			array(
				'labels'       => array(
					'name'          => __( 'Festivos Ágora', 'agora-calendar' ),
					'singular_name' => __( 'Festivo', 'agora-calendar' ),
					'add_new_item'  => __( 'Añadir festivo', 'agora-calendar' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-calendar-alt',
				'supports'     => array( 'title', 'editor' ),
				'rewrite'      => array( 'slug' => 'calendario' ),
			)
		);

		register_taxonomy(
			self::TAX,
			self::SLUG,
			array(
				'labels'       => array(
					'name'          => __( 'Países', 'agora-calendar' ),
					'singular_name' => __( 'País', 'agora-calendar' ),
				),
				'public'       => true,
				'hierarchical' => false,
				'show_in_rest' => true,
				'rewrite'      => array( 'slug' => 'pais' ),
			)
		);
	}

	/**
	 * @param array<string, mixed> $row
	 */
	public static function upsert( array $row ): bool {
		$country = strtoupper( (string) ( $row['country'] ?? '' ) );
		$date    = (string) ( $row['date'] ?? '' );
		$official = (string) ( $row['official_name'] ?? '' );
		if ( $country === '' || $date === '' || $official === '' ) {
			return false;
		}

		$key = $country . '|' . $date . '|' . $official;
		$existing = get_posts(
			array(
				'post_type'      => self::SLUG,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_agora_sync_key',
				'meta_value'     => $key,
			)
		);

		$local = (string) ( $row['local_name'] ?? $official );
		$id    = $existing ? (int) $existing[0] : 0;
		$overridden = $id && get_post_meta( $id, '_agora_override_title', true ) !== '';

		$payload = array(
			'post_type'   => self::SLUG,
			'post_status' => 'publish',
			'post_title'  => $overridden ? get_the_title( $id ) : $local,
		);

		if ( $id ) {
			$payload['ID'] = $id;
			$saved         = wp_update_post( $payload, true );
		} else {
			$saved = wp_insert_post( $payload, true );
		}

		if ( is_wp_error( $saved ) || ! $saved ) {
			return false;
		}

		$id = (int) $saved;
		update_post_meta( $id, '_agora_sync_key', $key );
		update_post_meta( $id, '_agora_country', $country );
		update_post_meta( $id, '_agora_date', $date );
		update_post_meta( $id, '_agora_official_name', $official );
		update_post_meta( $id, '_agora_local_name', $local );
		update_post_meta( $id, '_agora_counties', isset( $row['counties'] ) && is_array( $row['counties'] ) ? $row['counties'] : array() );
		update_post_meta( $id, '_agora_types', isset( $row['types'] ) && is_array( $row['types'] ) ? $row['types'] : array() );
		update_post_meta( $id, '_agora_global', ! empty( $row['global'] ) ? '1' : '' );

		wp_set_object_terms( $id, $country, self::TAX, false );

		return true;
	}

	public static function meta_boxes(): void {
		add_meta_box(
			'agora_event_meta',
			__( 'Festivo Ágora', 'agora-calendar' ),
			array( self::class, 'render_box' ),
			self::SLUG,
			'side'
		);
	}

	public static function render_box( \WP_Post $post ): void {
		wp_nonce_field( 'agora_save_event', 'agora_event_nonce' );
		$official = (string) get_post_meta( $post->ID, '_agora_official_name', true );
		$local    = (string) get_post_meta( $post->ID, '_agora_local_name', true );
		$date     = (string) get_post_meta( $post->ID, '_agora_date', true );
		$country  = (string) get_post_meta( $post->ID, '_agora_country', true );
		$override = (string) get_post_meta( $post->ID, '_agora_override_title', true );
		$counties = get_post_meta( $post->ID, '_agora_counties', true );

		echo '<p><strong>' . esc_html__( 'Oficial (Nager)', 'agora-calendar' ) . '</strong><br>';
		echo esc_html( $date !== '' ? $date : '—' ) . ' · ' . esc_html( $country !== '' ? $country : '—' ) . '<br>';
		echo '<em>' . esc_html( $official !== '' ? $official : '—' ) . '</em></p>';
		echo '<p class="description">' . esc_html__( 'Nombre local de la API:', 'agora-calendar' ) . ' ' . esc_html( $local !== '' ? $local : '—' ) . '</p>';

		if ( is_array( $counties ) && $counties !== array() ) {
			echo '<p class="description">' . esc_html__( 'Ámbito (región, no ciudad):', 'agora-calendar' ) . ' ' . esc_html( implode( ', ', $counties ) ) . '</p>';
		}

		echo '<p><label for="agora_override_title"><strong>' . esc_html__( 'Nombre para el público', 'agora-calendar' ) . '</strong></label></p>';
		echo '<p><input type="text" class="widefat" id="agora_override_title" name="agora_override_title" value="' . esc_attr( $override !== '' ? $override : $local ) . '" /></p>';
		echo '<p class="description">' . esc_html__( 'Si lo dejas igual al de la API, no se guarda corrección. El sync no pisa una corrección tuya.', 'agora-calendar' ) . '</p>';
		echo '<p><label><input type="checkbox" name="agora_reset_official" value="1"> ' . esc_html__( 'Restaurar el nombre oficial', 'agora-calendar' ) . '</label></p>';
	}

	public static function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['agora_event_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['agora_event_nonce'] ) ), 'agora_save_event' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! empty( $_POST['agora_reset_official'] ) ) {
			delete_post_meta( $post_id, '_agora_override_title' );
			$local = (string) get_post_meta( $post_id, '_agora_local_name', true );
			if ( $local !== '' ) {
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $local,
					)
				);
			}
			return;
		}

		$entered = isset( $_POST['agora_override_title'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['agora_override_title'] ) ) : '';
		$local   = (string) get_post_meta( $post_id, '_agora_local_name', true );
		if ( $entered === '' || $entered === $local ) {
			delete_post_meta( $post_id, '_agora_override_title' );
			$title = $local !== '' ? $local : $post->post_title;
		} else {
			update_post_meta( $post_id, '_agora_override_title', $entered );
			$title = $entered;
		}

		remove_action( 'save_post_' . self::SLUG, array( self::class, 'save' ), 10 );
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => $title,
			)
		);
		add_action( 'save_post_' . self::SLUG, array( self::class, 'save' ), 10, 2 );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function view( int $post_id ): array {
		$official   = (string) get_post_meta( $post_id, '_agora_official_name', true );
		$local      = (string) get_post_meta( $post_id, '_agora_local_name', true );
		$override   = (string) get_post_meta( $post_id, '_agora_override_title', true );
		$counties   = get_post_meta( $post_id, '_agora_counties', true );
		$overridden = $override !== '';

		return array(
			'id'            => $post_id,
			'title'         => $overridden ? $override : ( $local !== '' ? $local : get_the_title( $post_id ) ),
			'official_name' => $official,
			'local_name'    => $local,
			'date'          => (string) get_post_meta( $post_id, '_agora_date', true ),
			'country'       => (string) get_post_meta( $post_id, '_agora_country', true ),
			'counties'      => is_array( $counties ) ? $counties : array(),
			'overridden'    => $overridden,
			'description'   => get_post_field( 'post_content', $post_id ),
			'url'           => get_permalink( $post_id ),
		);
	}

	public static function append_card( string $content ): string {
		if ( ! is_singular( self::SLUG ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		return $content . EventCard::render( get_the_ID() );
	}
}
