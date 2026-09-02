<?php

namespace Nomade\Source;

defined( 'ABSPATH' ) || exit;

final class FetchResult {

	/**
	 * @param array<string, float> $rates
	 * @param string[]             $missing
	 */
	public function __construct(
		public bool $ok,
		public array $rates,
		public string $rate_date,
		public string $error = '',
		public array $missing = array(),
	) {}

	public function is_partial(): bool {
		return $this->ok && $this->missing !== array();
	}
}
