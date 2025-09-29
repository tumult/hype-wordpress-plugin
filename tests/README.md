# Tumult Hype Animations – Tests

This plugin ships with a PHPUnit test suite that exercises the Gutenberg block integration using WordPress' official testing tools.

## Prerequisites
- PHP 8.0+
- A MySQL service the WordPress test suite can access
- Composer dependencies installed (`composer install`)

## One-time WordPress test scaffold
Run the bundled helper to download WordPress, the test library, and bootstrap configuration. Adjust the database credentials as needed for your local setup.

```bash
bin/install-wp-tests.sh <db-name> <db-user> <db-password> <db-host>
```

The script installs into the system temporary directory by default. Update `wp-tests-config.php` if your environment requires custom paths or credentials.

## Running the tests

```bash
vendor/bin/phpunit
```

By default the suite boots from `tests/bootstrap.php`, loads the plugin, and executes any files in `tests/php/` with the `test-*.php` naming convention.

### Block testing helpers
To keep block rendering tests fast and deterministic, the plugin exposes three filters:

- `hypeanimations_render_block_shortcode_atts` – adjust or inspect the shortcode attributes generated from block props.
- `hypeanimations_render_block_shortcode_handler` – swap in a lightweight callable when you need to bypass database-backed shortcode logic during tests.
- `hypeanimations_render_block_output` – modify rendered markup before assertions.

These hooks allow tests (and custom code) to replace heavy dependencies with simple stubs while preserving coverage of the block-to-shortcode mapping.

