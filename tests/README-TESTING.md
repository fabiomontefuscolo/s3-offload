# Testing Infrastructure

## WP_CLI Mock

The test suite includes a centralized WP_CLI mock that supports both:

1. **Command Registration Testing** - For testing that CLI commands are properly registered
2. **Command Execution Testing** - For testing the actual command logic and output

### Location
`tests/helpers/class-wp-cli-mock.php`

### Features

#### Command Registration
```php
WP_CLI::add_command('command-name', CommandClass::class);
WP_CLI::has_command('command-name'); // Check if registered
WP_CLI::get_command('command-name'); // Get registered command
```

#### Command Output Testing
```php
// Capture log messages
WP_CLI::$__log = function($message) use (&$log_messages) {
    $log_messages[] = $message;
};

// Capture success messages
WP_CLI::$__success = function($message) use (&$success_messages) {
    $success_messages[] = $message;
};

// Capture error messages
WP_CLI::$__error = function($message) use (&$error_messages) {
    $error_messages[] = $message;
};
```

#### Clean Up
```php
WP_CLI::reset(); // Reset all commands and callbacks
```

## Test Files

### Core Tests
- `test-plugin.php` - Plugin initialization and hook registration tests
- `test-uploader.php` - S3 upload functionality and URL filtering tests
- `test-settings-page.php` - Admin settings page tests
- `test-plugin-config.php` - Configuration management tests

### CLI Tests
- `test-cli-commands.php` - WP-CLI command tests
  - Sync command with various scenarios
  - Connection testing
  - Error handling
  - Clean up verification

## Running Tests

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/test-cli-commands.php

# Run with coverage
./run-tests-coverage.sh

# Run with coverage report
vendor/bin/phpunit --coverage-html coverage/
```

## Writing New CLI Tests

When writing tests for CLI commands:

1. Reset WP_CLI mock in `set_up()`:
```php
public function set_up(): void {
    parent::set_up();
    if (class_exists('WP_CLI') && method_exists('WP_CLI', 'reset')) {
        WP_CLI::reset();
    }
}
```

2. Set up callback handlers:
```php
$log_messages = array();
WP_CLI::$__log = function($message) use (&$log_messages) {
    $log_messages[] = $message;
};
```

3. Execute command:
```php
$commands = new Commands();
$commands->sync(array(), array());
```

4. Assert results:
```php
$this->assertContains('Expected message', $log_messages);
```

5. Clean up in `tear_down()`:
```php
public function tear_down(): void {
    parent::tear_down();
    if (class_exists('WP_CLI') && method_exists('WP_CLI', 'reset')) {
        WP_CLI::reset();
    }
}
```

## Best Practices

1. Always reset WP_CLI mock between tests
2. Use dependency injection for testing (pass callbacks)
3. Test both success and failure scenarios
4. Verify clean up of temporary resources
5. Test edge cases (empty results, missing data, etc.)
