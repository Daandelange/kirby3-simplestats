<?php

declare(strict_types = 1);

namespace Snowplow\RefererParser\Config;

use Snowplow\RefererParser\Exception\InvalidArgumentException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class YamlConfigReader implements ConfigReaderInterface
{
	use ConfigFileReaderTrait {
		ConfigFileReaderTrait::init as public __construct;
	}

	protected function parse(string $content): array
	{
		try {
			$data = Yaml::parse($content);
		} catch (ParseException $e) {
			throw InvalidArgumentException::invalidData($this->fileName, 'Invalid YAML: ' . $e->getMessage());
		}

		if (!is_array($data)) {
			throw InvalidArgumentException::invalidData($this->fileName, 'YAML data must be an array');
		}

		return $data;
	}
}
