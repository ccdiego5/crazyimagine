<?php

namespace Nomade\Admin;

use Nomade\Source\SourceRegistry;
use Nomade\Sync\SyncRunner;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

	private static ?SyncRunner $sync = null;
	private static ?SourceRegistry $sources = null;

	public static function init( SyncRunner $sync, SourceRegistry $sources ): void {
		self::$sync    = $sync;
		self::$sources = $sources;
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_init', array( self::class, 'handle' ) );
	}

	public static function menu(): void {
		add_submenu_page(
			'edit.php?post_type=nomade_product',
			__( 'Tipos de cambio', 'nomade-prices' ),
			__( 'Tipos de cambio', 'nomade-prices' ),
			'manage_options',
			'nomade-rates',
			array( self::class, 'render' )
		);
	}

	public static function handle(): void {
		if ( ! isset( $_POST['nomade_rates_action'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['nomade_rates_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nomade_rates_nonce'] ) ), 'nomade_rates' ) ) {
			return;
		}

		$action = sanitize_key( (string) wp_unslash( $_POST['nomade_rates_action'] ) );
		$source = isset( $_POST['nomade_active_source'] ) ? sanitize_key( (string) wp_unslash( $_POST['nomade_active_source'] ) ) : 'frankfurter';

		$codes = array();
		if ( isset( $_POST['nomade_currencies'] ) && is_array( $_POST['nomade_currencies'] ) ) {
			foreach ( wp_unslash( $_POST['nomade_currencies'] ) as $code ) {
				$code = strtoupper( sanitize_key( (string) $code ) );
				if ( preg_match( '/^[A-Z]{3}$/', $code ) ) {
					$codes[] = $code;
				}
			}
		}
		if ( $codes === array() ) {
			$codes = array( 'MXN', 'COP', 'CLP', 'PEN', 'EUR', 'VES' );
		}

		update_option( 'nomade_currencies', $codes, false );
		update_option( SyncRunner::OPTION_SOURCE, $source, false );

		$redirect = add_query_arg(
			array(
				'post_type'    => 'nomade_product',
				'page'         => 'nomade-rates',
				'nomade_saved' => 1,
			),
			admin_url( 'edit.php' )
		);

		if ( $action === 'sync' && self::$sync ) {
			$result   = self::$sync->run( $source, true );
			$redirect = add_query_arg(
				array(
					'nomade_sync' => $result['ok'] ? 'ok' : 'err',
					'nomade_msg'  => rawurlencode( $result['message'] ),
				),
				$redirect
			);
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active   = SyncRunner::active_currencies();
		$all      = array( 'MXN', 'COP', 'CLP', 'PEN', 'EUR', 'VES' );
		$source   = (string) get_option( SyncRunner::OPTION_SOURCE, 'frankfurter' );
		$bundle   = get_option( SyncRunner::OPTION_RATES, array() );
		$log      = get_option( SyncRunner::OPTION_LOG, array() );
		$sources  = self::$sources ? self::$sources->all() : array();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Tipos de cambio Nómade', 'nomade-prices' ) . '</h1>';

		if ( isset( $_GET['nomade_sync'] ) ) {
			$ok  = sanitize_key( (string) wp_unslash( $_GET['nomade_sync'] ) ) === 'ok';
			$msg = isset( $_GET['nomade_msg'] ) ? sanitize_text_field( rawurldecode( (string) wp_unslash( $_GET['nomade_msg'] ) ) ) : '';
			echo '<div class="notice notice-' . ( $ok ? 'success' : 'error' ) . '"><p>' . esc_html( $msg ) . '</p></div>';
		} elseif ( isset( $_GET['nomade_saved'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Ajustes guardados.', 'nomade-prices' ) . '</p></div>';
		}

		echo '<form method="post">';
		wp_nonce_field( 'nomade_rates', 'nomade_rates_nonce' );

		echo '<h2>' . esc_html__( 'Monedas activas', 'nomade-prices' ) . '</h2>';
		foreach ( $all as $code ) {
			$checked = in_array( $code, $active, true ) ? ' checked' : '';
			echo '<label style="margin-right:1em"><input type="checkbox" name="nomade_currencies[]" value="' . esc_attr( $code ) . '"' . $checked . '> ' . esc_html( $code ) . '</label>';
		}

		echo '<h2>' . esc_html__( 'Fuente', 'nomade-prices' ) . '</h2>';
		echo '<select name="nomade_active_source">';
		foreach ( $sources as $id => $obj ) {
			echo '<option value="' . esc_attr( $id ) . '"' . selected( $source, $id, false ) . '>' . esc_html( $obj->label() ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Una fuente por sync. Frankfurter (sin clave) para las seis monedas, o CSV local. No se mezclan en la misma corrida.', 'nomade-prices' ) . '</p>';

		echo '<p>';
		echo '<button class="button" name="nomade_rates_action" value="save">' . esc_html__( 'Guardar', 'nomade-prices' ) . '</button> ';
		echo '<button class="button button-primary" name="nomade_rates_action" value="sync">' . esc_html__( 'Sincronizar ahora', 'nomade-prices' ) . '</button>';
		echo '</p>';
		echo '</form>';

		echo '<h2>' . esc_html__( 'Últimos tipos persistidos', 'nomade-prices' ) . '</h2>';
		if ( ! is_array( $bundle ) || empty( $bundle['rates'] ) ) {
			echo '<p>' . esc_html__( 'Todavía no hay tipos. Sincroniza o se usará el CSV al activar.', 'nomade-prices' ) . '</p>';
		} else {
			echo '<p>' . esc_html(
				sprintf(
					/* translators: 1 date 2 source */
					__( 'Fecha del tipo: %1$s · fuente: %2$s', 'nomade-prices' ),
					(string) ( $bundle['rate_date'] ?? '—' ),
					(string) ( $bundle['source'] ?? '—' )
				)
			) . '</p>';
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Moneda', 'nomade-prices' ) . '</th><th>' . esc_html__( '1 USD', 'nomade-prices' ) . '</th></tr></thead><tbody>';
			foreach ( $bundle['rates'] as $code => $rate ) {
				echo '<tr><td>' . esc_html( (string) $code ) . '</td><td>' . esc_html( (string) $rate ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h2>' . esc_html__( 'Rastro de sync', 'nomade-prices' ) . '</h2>';
		if ( ! is_array( $log ) || $log === array() ) {
			echo '<p>' . esc_html__( 'Sin corridas todavía.', 'nomade-prices' ) . '</p>';
		} else {
			echo '<ol>';
			foreach ( $log as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				echo '<li>' . esc_html( (string) ( $row['at'] ?? '' ) ) . ' · ' . esc_html( (string) ( $row['source'] ?? '' ) ) . ' · ' . esc_html( (string) ( $row['message'] ?? '' ) ) . '</li>';
			}
			echo '</ol>';
		}

		echo '</div>';
	}
}
