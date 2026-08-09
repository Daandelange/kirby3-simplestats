<?php

declare(strict_types = 1);

namespace Snowplow\RefererParser\Config;

interface ConfigReaderInterface
{
	public function lookup(string $lookupString): array|null;
}
