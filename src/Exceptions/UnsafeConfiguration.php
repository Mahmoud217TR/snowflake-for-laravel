<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Exceptions;

/**
 * Required generator configuration is missing/malformed or the state driver is unsupported.
 */
final class UnsafeConfiguration extends SnowflakeException {}
