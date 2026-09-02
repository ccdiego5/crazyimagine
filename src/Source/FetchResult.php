<?php

namespace Agora\Source;

defined( 'ABSPATH' ) || exit;

final class FetchResult {

	/**
	 * @param list<array<string, mixed>> $events
	 * @param string[]                   $failed
	 */
	public function __construct(
		public bool $ok,
		public array $events,
		public string $error = '',
		public array $failed = array(),
	) {}

	public function is_partial(): bool {
		return $this->ok && $this->failed !== array();
	}
}
