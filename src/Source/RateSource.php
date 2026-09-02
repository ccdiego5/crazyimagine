<?php

namespace Nomade\Source;

defined( 'ABSPATH' ) || exit;

interface RateSource {

	public function id(): string;

	public function label(): string;

	/**
	 * @param string[] $currencies ISO 4217 codes.
	 */
	public function fetch( array $currencies ): FetchResult;
}
