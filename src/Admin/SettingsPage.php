<?php

namespace Agora\Admin;

use Agora\Source\SourceRegistry;
use Agora\Sync\SyncRunner;

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
			'edit.php?post_type=agora_event',
			__( 'Sincronizar festivos', 'agora-calendar' ),
			__( 'Sincronizar', 'agora-calendar' ),
			'manage_options',
			'agora-sync',
			array( self::class, 'render' )
		);
	}

	public static function handle(): void {
		if ( ! isset( $_POST['agora_sync_action'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['agora_sync_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['agora_sync_nonce'] ) ), 'agora_sync' ) ) {
			return;
		}

		$action = sanitize_key( (string) wp_unslash( $_POST['agora_sync_action'] ) );
		$source = isset( $_POST['agora_active_source'] ) ? sanitize_key( (string) wp_unslash( $_POST['agora_active_source'] ) ) : 'nager';

		$codes = array();
		if ( isset( $_POST['agora_countries'] ) && is_array( $_POST['agora_countries'] ) ) {
			foreach ( wp_unslash( $_POST['agora_countries'] ) as $code ) {
				$code = strtoupper( sanitize_key( (string) $code ) );
				if ( preg_match( '/^[A-Z]{2}$/', $code ) ) {
					$codes[] = $code;
				}
			}
		}
		if ( $codes === array() ) {
			$codes = array( 'ES', 'MX', 'CO', 'CL', 'PE', 'VE' );
		}

		update_option( 'agora_countries', $codes, false );
		update_option( SyncRunner::OPTION_SOURCE, $source, false );

		$redirect = add_query_arg(
			array(
				'post_type'   => 'agora_event',
				'page'        => 'agora-sync',
				'agora_saved' => 1,
			),
			admin_url( 'edit.php' )
		);

		if ( $action === 'sync' && self::$sync ) {
			$result   = self::$sync->run( $source, true );
			$redirect = add_query_arg(
				array(
					'agora_sync' => $result['ok'] ? 'ok' : 'err',
					'agora_msg'  => rawurlencode( $result['message'] ),
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

		$active  = SyncRunner::active_countries();
		$all     = array( 'ES', 'MX', 'CO', 'CL', 'PE', 'VE' );
		$source  = (string) get_option( SyncRunner::OPTION_SOURCE, 'nager' );
		$log     = get_option( SyncRunner::OPTION_LOG, array() );
		$sources = self::$sources ? self::$sources->all() : array();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Calendario base Ágora', 'agora-calendar' ) . '</h1>';
		echo '<p>' . esc_html__( 'Trae festivos oficiales por país. No hay filtro por ciudad: Nager no lo tiene. El botón no borra las correcciones de Marta.', 'agora-calendar' ) . '</p>';

		if ( isset( $_GET['agora_sync'] ) ) {
			$ok  = sanitize_key( (string) wp_unslash( $_GET['agora_sync'] ) ) === 'ok';
			$msg = isset( $_GET['agora_msg'] ) ? sanitize_text_field( rawurldecode( (string) wp_unslash( $_GET['agora_msg'] ) ) ) : '';
			echo '<div class="notice notice-' . ( $ok ? 'success' : 'error' ) . '"><p>' . esc_html( $msg ) . '</p></div>';
		} elseif ( isset( $_GET['agora_saved'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Ajustes guardados.', 'agora-calendar' ) . '</p></div>';
		}

		echo '<form method="post">';
		wp_nonce_field( 'agora_sync', 'agora_sync_nonce' );

		echo '<h2>' . esc_html__( 'Países activos', 'agora-calendar' ) . '</h2>';
		foreach ( $all as $code ) {
			$checked = in_array( $code, $active, true ) ? ' checked' : '';
			echo '<label style="margin-right:1em"><input type="checkbox" name="agora_countries[]" value="' . esc_attr( $code ) . '"' . $checked . '> ' . esc_html( $code ) . '</label>';
		}

		echo '<h2>' . esc_html__( 'Fuente', 'agora-calendar' ) . '</h2>';
		echo '<select name="agora_active_source">';
		foreach ( $sources as $id => $obj ) {
			echo '<option value="' . esc_attr( $id ) . '"' . selected( $source, $id, false ) . '>' . esc_html( $obj->label() ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Nager.Date v3 (sin clave) o CSV local. Una fuente por sync. No se mezclan.', 'agora-calendar' ) . '</p>';

		echo '<p>';
		echo '<button class="button" name="agora_sync_action" value="save">' . esc_html__( 'Guardar', 'agora-calendar' ) . '</button> ';
		echo '<button class="button button-primary" name="agora_sync_action" value="sync">' . esc_html__( 'Sincronizar ahora', 'agora-calendar' ) . '</button>';
		echo '</p>';
		echo '<p class="description">' . esc_html__( 'Sincronizar vuelve a traer el año en curso. No borra nombres que Marta haya corregido.', 'agora-calendar' ) . '</p>';
		echo '</form>';

		echo '<h2>' . esc_html__( 'Rastro de sync', 'agora-calendar' ) . '</h2>';
		if ( ! is_array( $log ) || $log === array() ) {
			echo '<p>' . esc_html__( 'Sin corridas todavía.', 'agora-calendar' ) . '</p>';
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
