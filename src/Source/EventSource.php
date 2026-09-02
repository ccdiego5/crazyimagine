<?php

namespace Agora\Source;

defined( 'ABSPATH' ) || exit;

interface EventSource {

	public function id(): string;

	public function label(): string;

	/**
	 * @param string[] $countries ISO 3166-1 alpha-2.
	 * @param int[]    $years
	 */
	public function fetch( array $countries, array $years ): FetchResult;
}
