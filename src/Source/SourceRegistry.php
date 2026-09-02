<?php

namespace Agora\Source;

defined( 'ABSPATH' ) || exit;

final class SourceRegistry {

	/** @var array<string, EventSource> */
	private array $sources = array();

	public function register( EventSource $source ): void {
		$this->sources[ $source->id() ] = $source;
	}

	public function get( string $id ): ?EventSource {
		return $this->sources[ $id ] ?? null;
	}

	/** @return array<string, EventSource> */
	public function all(): array {
		return $this->sources;
	}
}
