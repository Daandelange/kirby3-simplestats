<?php

declare(strict_types = 1);

namespace Snowplow\RefererParser;

final readonly class Referer
{
	protected function __construct(
		public readonly Medium $medium,
		public readonly ?string $source = null,
		public readonly ?string $searchTerm = null,
	) {
	}

	public static function createKnown(Medium $medium, string $source, ?string $searchTerm = null): self
	{
		return new self($medium, $source, $searchTerm);
	}

	public static function createUnknown(): self
	{
		return new self(Medium::UNKNOWN);
	}

	public static function createInternal(): self
	{
		return new self(Medium::INTERNAL);
	}

	public static function createInvalid(): self
	{
		return new self(Medium::INVALID);
	}

	public function isValid(): bool
	{
		return $this->medium !== Medium::INVALID;
	}

	public function isKnown(): bool
	{
		return !in_array($this->medium, [Medium::UNKNOWN, Medium::INTERNAL, Medium::INVALID], true);
	}

	// Backward-compatible getters
	public function getMedium(): string
	{
		return $this->medium->value;
	}

	public function getSource(): ?string
	{
		return $this->source;
	}

	public function getSearchTerm(): ?string
	{
		return $this->searchTerm;
	}
}
