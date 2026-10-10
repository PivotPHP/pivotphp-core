# Environment Detection

The `Environment` class provides centralized environment detection and management for the PivotPHP framework.

## Overview

The `Environment` class centralizes all environment-related logic that was previously scattered across different classes. It provides consistent methods for detecting development mode, environment settings, and debug state.

## Basic Usage

```php
use PivotPHP\Core\Core\Environment;

// Check environment
if (Environment::isDevelopment()) {
    // Development-specific code
}

if (Environment::isProduction()) {
    // Production-specific code
}

// Get environment variables
$dbHost = Environment::get('DB_HOST', 'localhost');
$apiKey = Environment::get('API_KEY');
```

## Environment Detection

### Development Mode Detection

The framework detects development mode using multiple indicators:

```php
Environment::isDevelopment(); // true if any of:
// - APP_ENV=development
// - APP_DEBUG=true
// - display_errors=1  
// - PIVOTPHP_DEBUG constant is true
```

### Environment Types

```php
Environment::getEnvironment();  // Returns: production, development, testing
Environment::isProduction();    // APP_ENV=production (default)
Environment::isDevelopment();   // APP_ENV=development OR debug indicators
Environment::isTesting();       // APP_ENV=testing
Environment::isDebug();         // APP_DEBUG=true/1/yes
```

### Runtime Detection

```php
Environment::isCli();  // Running in command line
Environment::isWeb();  // Running in web context
```

## Environment Variables

### Getting Variables

```php
// Get with fallback
$value = Environment::get('MY_VAR', 'default_value');

// Check existence
if (Environment::has('MY_VAR')) {
    $value = Environment::get('MY_VAR');
}
```

The class checks variables in this order:
1. `$_ENV` array
2. `$_SERVER` array  
3. `getenv()` function

### Environment Variable Sources

```php
// These are all checked automatically:
$_ENV['APP_ENV']           // Preferred
$_SERVER['APP_ENV']        // Fallback
getenv('APP_ENV')          // Final fallback
```

## Caching

The Environment class caches results for performance:

```php
// Results are cached automatically
$isDev1 = Environment::isDevelopment(); // Checks environment
$isDev2 = Environment::isDevelopment(); // Returns cached result

// Clear cache when needed (primarily for testing)
Environment::clearCache();
```

## Configuration Examples

### Development Environment

```bash
# .env file
APP_ENV=development
APP_DEBUG=true
```

### Production Environment

```bash
# .env file  
APP_ENV=production
APP_DEBUG=false
```

### Testing Environment

```bash
# .env file
APP_ENV=testing
APP_DEBUG=true
```

## Integration with Exceptions

The Environment class integrates with the enhanced exception system:

```php
use PivotPHP\Core\Exceptions\Enhanced\ContextualException;

// Exceptions automatically use Environment for debug detection
$exception = new ContextualException(500, 'Error', $context, $suggestions);

// Debug info only shown in development
$array = $exception->toArray();
// In development: includes context, suggestions, stack trace
// In production: only basic error info
```

## Migration from Legacy Code

### Before (Scattered Logic)

```php
// Old scattered approach
private function isDevelopmentMode(): bool
{
    return (
        ($_ENV['APP_ENV'] ?? '') === 'development' ||
        ($_ENV['APP_DEBUG'] ?? false) === true ||
        ini_get('display_errors') === '1' ||
        defined('PIVOTPHP_DEBUG') && PIVOTPHP_DEBUG === true
    );
}
```

### After (Centralized)

```php
// New centralized approach
use PivotPHP\Core\Core\Environment;

if (Environment::isDevelopment()) {
    // Development logic
}
```

## Debugging

Get comprehensive environment information:

```php
$debug = Environment::getDebugInfo();
// Returns:
[
    'environment' => 'development',
    'is_development' => true,
    'is_debug' => true,
    'is_production' => false,
    'is_testing' => false,
    'is_cli' => false,
    'is_web' => true,
    'display_errors' => '1',
    'pivotphp_debug_defined' => false,
    'pivotphp_debug_value' => null,
]
```

## Best Practices

### 1. Use Centralized Detection

```php
// ✅ Good - Centralized
if (Environment::isDevelopment()) {
    $this->enableVerboseLogging();
}

// ❌ Bad - Scattered logic
if ($_ENV['APP_ENV'] === 'development' || $_ENV['APP_DEBUG']) {
    $this->enableVerboseLogging();
}
```

### 2. Environment-Specific Configuration

```php
// Debug output only outside production
$app->getConfig()->set('app.debug', Environment::isDevelopment());
```

## Performance Considerations

- Results are cached for performance
- Environment detection happens only once per request
- Minimal overhead after initial detection
- Cache can be cleared for testing scenarios

## Security Notes

- Production mode automatically hides debug information
- Stack traces and sensitive data are only shown in development
- Environment variables are checked securely
- No sensitive information is logged in production mode