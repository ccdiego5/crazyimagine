<?php

namespace Nomade;

use Nomade\Admin\SettingsPage;
use Nomade\Content\ProductType;
use Nomade\Content\PriceBlock;
use Nomade\Frontend\PriceShortcode;
use Nomade\Pricing\Calculator;
use Nomade\Rest\PriceEndpoint;
use Nomade\Seed\ProductSeeder;
use Nomade\Source\CsvSource;
use Nomade\Source\FrankfurterSource;
use Nomade\Source\SourceRegistry;
use Nomade\Sync\SyncRunner;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static ?self $instance = null;

	public SourceRegistry $sources;
	public SyncRunner $sync;
	public Calculator $calculator;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}

		return self::$instance;
	}

	public static function activate(): void {
		ProductType::register();
		flush_rewrite_rules();

		if ( ! wp_next_scheduled( 'nomade_daily_sync' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'nomade_daily_sync' );
		}

		if ( false === get_option( 'nomade_rates', false ) ) {
			$plugin = self::instance();
			$plugin->sync->run( 'csv', false );
		}

		ProductSeeder::seed_if_empty();
	}

	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( 'nomade_daily_sync' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'nomade_daily_sync' );
		}
		flush_rewrite_rules();
	}

	private function boot(): void {
		$this->sources    = new SourceRegistry();
		$this->sources->register( new FrankfurterSource() );
		$this->sources->register( new CsvSource() );

		$this->sync       = new SyncRunner( $this->sources );
		$this->calculator = new Calculator();

		ProductType::init();
		PriceBlock::init();
		SettingsPage::init( $this->sync, $this->sources );
		PriceShortcode::init( $this->calculator );
		PriceEndpoint::init();

		add_action( 'nomade_daily_sync', array( $this->sync, 'run_scheduled' ) );
	}
}
