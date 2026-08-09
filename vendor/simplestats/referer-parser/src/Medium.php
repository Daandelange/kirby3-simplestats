<?php

declare(strict_types = 1);

namespace Snowplow\RefererParser;

enum Medium: string
{
	case INVALID  = 'invalid';
	case UNKNOWN  = 'unknown';
	case INTERNAL = 'internal';
	case SEARCH   = 'search';
	case SOCIAL   = 'social';
	case EMAIL    = 'email';
	case PAID     = 'paid';
	case CHATBOT  = 'chatbot';
}
