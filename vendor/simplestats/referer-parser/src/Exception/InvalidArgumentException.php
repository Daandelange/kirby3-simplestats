<?php

declare(strict_types = 1);

namespace Snowplow\RefererParser\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;

final class InvalidArgumentException extends BaseInvalidArgumentException
{
	/**
	 * Thrown when data file does not exist
	 */
	public static function fileNotExists(string $fileName): self
	{
		return new self(sprintf('File "%s" does not exist', $fileName));
	}

	/**
	 * Thrown when data file is not readable or cannot be accessed
	 */
	public static function fileNotReadable(string $fileName): self
	{
		return new self(sprintf('File "%s" is not readable', $fileName));
	}

	/**
	 * Thrown when data file contains invalid content
	 */
	public static function invalidData(string $fileName, string $reason): self
	{
		return new self(sprintf('Invalid config in file "%s": %s', $fileName, $reason));
	}
}
