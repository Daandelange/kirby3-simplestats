<?php

declare(strict_types = 1);

namespace Snowplow\RefererParser\Config;

use Snowplow\RefererParser\Exception\InvalidArgumentException;

final class JsonConfigReader implements ConfigReaderInterface
{
	use ConfigFileReaderTrait {
		ConfigFileReaderTrait::init as public __construct;
	}

	protected function parse(string $content): array
	{
		try {
			$data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {
			throw InvalidArgumentException::invalidData($this->fileName, 'Invalid JSON: ' . $e->getMessage());
		}

		if (!is_array($data)) {
			throw InvalidArgumentException::invalidData($this->fileName, 'JSON data must be an array');
		}

		return $data;
	}
}
