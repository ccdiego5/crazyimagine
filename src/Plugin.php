<?php

namespace Agora;

use Agora\Admin\SettingsPage;
use Agora\Content\EventType;
use Agora\Frontend\CalendarShortcode;
use Agora\Rest\EventsEndpoint;
use Agora\Source\CsvSource;
use Agora\Source\NagerDateSource;
use Agora\Source\SourceRegistry;
use Agora\Sync\SyncRunner;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static ?self $instance = null;

	public SourceRegistry $sources;
	public SyncRunner $sync;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}

		return self::$instance;
	}

	public static function activate(): void {
		EventType::register();
		flush_rewrite_rules();

		if ( ! wp_next_scheduled( 'agora_daily_sync' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'agora_daily_sync' );
		}

		self::maybe_seed_page();
	}

	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( 'agora_daily_sync' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'agora_daily_sync' );
		}
		flush_rewrite_rules();
	}

	private static function maybe_seed_page(): void {
		$existing = get_page_by_path( 'calendario-agora' );
		if ( $existing ) {
			return;
		}
		wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Calendario Ágora',
				'post_name'    => 'calendario-agora',
				'post_content' => '[agora_calendar]',
			)
		);
	}

	private function boot(): void {
		$this->sources = new SourceRegistry();
		$this->sources->register( new NagerDateSource() );
		$this->sources->register( new CsvSource() );

		$this->sync = new SyncRunner( $this->sources );

		EventType::init();
		SettingsPage::init( $this->sync, $this->sources );
		CalendarShortcode::init();
		EventsEndpoint::init();

		add_action( 'agora_daily_sync', array( $this->sync, 'run_scheduled' ) );
	}
}
