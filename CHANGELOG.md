# Changelog

All notable changes to `snowflake-for-laravel` will be documented in this file.

## v1.0.0 - 2026-10-06

### Snowflake for Laravel v1.0.0

Initial public release of **Snowflake for Laravel**.

Generate distributed, time-sortable 64-bit Snowflake IDs while keeping
Laravel integration familiar and application-facing IDs safe as strings.

#### Highlights

##### Distributed Snowflake generation

Uses the classic 64-bit Snowflake layout:

- 41 bits — timestamp
- 10 bits — generator ID
- 12 bits — sequence

This provides:

- 1,024 generator IDs
- 4,096 IDs per generator per millisecond
- approximately 69.7 years of timestamps from the configured epoch

##### Redis coordination

Redis-backed state allocation uses an atomic Lua operation, allowing
multiple Laravel processes sharing a generator ID to coordinate safely.

Redis failures fail explicitly rather than silently falling back to
unsafe local state.

A local in-memory driver is also available for isolated processes,
development, and testing.

##### Laravel-native integration

Create Snowflake columns using familiar schema helpers:

```php
$table->snowflake();
$table->foreignSnowflake('user_id')->constrained();

```
Enable automatic Snowflake IDs on Eloquent models:

```php
use MahmoudTR\Snowflake\Concerns\HasSnowflakeIds;

class User extends Model
{
    use HasSnowflakeIds;
}

```
##### Simple API

```php
use MahmoudTR\Snowflake\Facades\Snowflake;

$id = Snowflake::generate();

$parts = Snowflake::inspect($id);

Snowflake::isValid($id);

```
Or:

```php
snowflake()->generate();
snowflake()->inspect($id);
snowflake()->isValid($id);

```
##### Validation

Laravel validation rule:

```php
'id' => ['required', 'snowflake']

```
Rule object:

```php
new MahmoudTR\Snowflake\Rules\Snowflake()

```
##### Artisan tooling

```bash
php artisan snowflake:generate
php artisan snowflake:inspect <id>
php artisan snowflake:validate <id>
php artisan snowflake:status

```
##### Safe application boundaries

Snowflake IDs are stored using `BIGINT` database columns while being
exposed by the package as decimal strings.

This avoids precision loss in JavaScript while preserving efficient
numeric database storage and ordering.

#### Requirements

- PHP 8.4+
- 64-bit PHP runtime
- Laravel 11, 12, or 13
- Redis for the default distributed state driver

#### Installation

```bash
composer require mahmoudtr/snowflake-for-laravel

```
Publish the configuration:

```bash
php artisan vendor:publish --tag="snowflake-for-laravel-config"

```
Then configure a generator ID:

```dotenv
SNOWFLAKE_GENERATOR_ID=0
SNOWFLAKE_STATE_DRIVER=redis

```
See the README for deployment considerations, Redis durability,
clock rollback behavior, database integration, and full configuration.


---

This is the first public release. Feedback, issues, and contributions
are welcome.
