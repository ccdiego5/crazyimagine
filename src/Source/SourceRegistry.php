<?php

namespace Nomade\Source;

defined( 'ABSPATH' ) || exit;

final class SourceRegistry {

	/** @var array<string, RateSource> */
	private array $sources = array();

	public function register( RateSource $source ): void {
		$this->sources[ $source->id() ] = $source;
	}

	public function get( string $id ): ?RateSource {
		return $this->sources[ $id ] ?? null;
	}

	/** @return array<string, RateSource> */
	public function all(): array {
		return $this->sources;
	}
}
