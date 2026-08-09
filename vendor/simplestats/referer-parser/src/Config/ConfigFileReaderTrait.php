<?php

declare(strict_types = 1);

namespace Snowplow\RefererParser\Config;

use Snowplow\RefererParser\Exception\InvalidArgumentException;

trait ConfigFileReaderTrait
{
	protected string $fileName;
	protected array $referers = [];

	protected function init(string $fileName): void
	{
		if (!file_exists($fileName)) {
			throw InvalidArgumentException::fileNotExists($fileName);
		}

		$this->fileName = $fileName;
		$this->read();
	}

	abstract protected function parse(string $content): array;

	protected function read(): void
	{
		if (!empty($this->referers)) {
			return;
		}

		$content = file_get_contents($this->fileName);
		if ($content === false) {
			throw InvalidArgumentException::fileNotReadable($this->fileName);
		}

		$hash = $this->parse($content);

		foreach ($hash as $medium => $referers) {
			foreach ($referers as $source => $referer) {
				foreach ($referer['domains'] as $domain) {
					$parameters = $referer['parameters'] ?? [];
					$this->addReferer($domain, $source, $medium, $parameters);
				}
			}
		}
	}

	public function addReferer(string $domain, string $source, string $medium, array $parameters = []): void
	{
		$this->referers[$domain] = [
			'source' => $source,
			'medium' => $medium,
			'parameters' => $parameters,
		];
	}

	public function lookup(string $lookupString): ?array
	{
		return $this->referers[$lookupString] ?? null;
	}
}
