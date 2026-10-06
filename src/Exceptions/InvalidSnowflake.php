<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Exceptions;

use InvalidArgumentException;

/**
 * An ID cannot be parsed or assigned as a non-negative signed 64-bit Snowflake.
 *
 * Invalid input is an argument error, not a SnowflakeException generation failure.
 */
final class InvalidSnowflake extends InvalidArgumentException {}
