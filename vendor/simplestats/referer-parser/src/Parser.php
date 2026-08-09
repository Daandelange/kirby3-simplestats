<?php

declare(strict_types = 1);

namespace Snowplow\RefererParser;

use Snowplow\RefererParser\Config\ConfigReaderInterface;
use Snowplow\RefererParser\Config\JsonConfigReader;

final class Parser
{
	protected readonly ConfigReaderInterface $configReader;
	protected array $internalHosts = [];

	public function __construct(?ConfigReaderInterface $configReader = null, array $internalHosts = [])
	{
		$this->configReader = $configReader ?? static::createDefaultConfigReader();
		$this->internalHosts = $internalHosts;
	}

	public function parse(?string $refererUrl, ?string $pageUrl = null): Referer
	{
		$refererParts = self::parseUrl($refererUrl);
		if ($refererParts === null) {
			return Referer::createInvalid();
		}

		$pageUrlParts = self::parseUrl($pageUrl);

		if (($pageUrlParts !== null && $pageUrlParts['host'] === $refererParts['host']) || in_array($refererParts['host'], $this->internalHosts, true)) {
			return Referer::createInternal();
		}

		$referer = $this->lookup($refererParts['host'], $refererParts['path']);

		if ($referer === null) {
			return Referer::createUnknown();
		}

		$searchTerm = null;
		if (!empty($referer['parameters'])) {
			parse_str($refererParts['query'] ?? '', $queryParts);
			foreach ($referer['parameters'] as $parameter) {
				$searchTerm = $queryParts[$parameter] ?? $searchTerm;
			}
		}

		return Referer::createKnown(Medium::from($referer['medium']), $referer['source'], $searchTerm);
	}

	protected static function parseUrl(?string $url): ?array
	{
		if ($url === null) {
			return null;
		}

		$parts = parse_url($url);
		if ($parts === false || !isset($parts['scheme'], $parts['host']) || !in_array(strtolower($parts['scheme']), ['http', 'https', 'android-app'], true)) {
			return null;
		}

		return array_merge(['query' => null, 'path' => '/'], $parts);
	}

	protected function lookup(string $host, string $path): ?array
	{
		return $this->lookupPath($host, $path) ?? $this->lookupHost($host);
	}

	protected function lookupPath(string $host, string $path): ?array
	{
		$referer = $this->lookupHost($host, $path);
		if ($referer !== null) {
			return $referer;
		}

		$pos = strrpos($path, '/');
		if ($pos === false || $pos === 0) {
			return null;
		}

		return $this->lookupPath($host, substr($path, 0, $pos));
	}

	protected function lookupHost(string $host, ?string $path = null): ?array
	{
		do {
			$referer = $this->configReader->lookup($host . ($path ?? ''));
			$dotPos = strpos($host, '.');
			if ($dotPos === false) {
				break;
			}
			$host = substr($host, $dotPos + 1);
		} while ($referer === null && substr_count($host, '.') > 0);

		return $referer;
	}

	protected static function createDefaultConfigReader(): ConfigReaderInterface
	{
		return new JsonConfigReader(__DIR__ . '/../data/referers.json');
	}
}
