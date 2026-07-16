# PSR-7 Dual Version Support

> **Update (v2.1.1):** genuine dual-version support (both `psr/http-message` v1.1 and v2.0
> working correctly out of the box, with no source rewriting) was fixed and verified in
> v2.1.1 — see the [v2.1.1 changelog entry](../../../CHANGELOG.md#211---2026-07-15---psr-7-20-compatibility-fix).
> Before that fix, `composer.json` already declared `"psr/http-message": "^1.1|^2.0"` but the
> classes were not actually type-compatible with the v2.0 interfaces, so resolving to v2.0
> caused fatal errors on every request. The `switch-psr7-version.php` workflow described
> below (physically rewriting method signatures to add/remove return types) is no longer
> necessary for basic v1.x/v2.x compatibility, since the checked-in implementation now works
> with both simultaneously — return types matching the v2.0 interface are also legal under
> the v1.x interface, which declares no return types at all. The script may still be useful
> for other purposes but should not be treated as a prerequisite for switching PSR-7
> versions.

## Overview

PivotPHP Core v1.0.1 now supports both PSR-7 v1.x and v2.x through a flexible implementation that can adapt to either version.

## How It Works

### 1. Version Detection

The framework includes a `Psr7VersionDetector` class that automatically detects which version of PSR-7 is installed:

```php
use PivotPHP\Core\Http\Psr7\Factory\Psr7VersionDetector;

// Check installed version
$version = Psr7VersionDetector::getVersion(); // "1.x" or "2.x"

// Check specific versions
if (Psr7VersionDetector::isV1()) {
    // PSR-7 v1.x is installed
}
```

### 2. Switching Between Versions

A utility script is provided to switch between PSR-7 versions:

```bash
# Switch to PSR-7 v1.x (for ReactPHP compatibility)
php scripts/utils/switch-psr7-version.php 1
composer update psr/http-message

# Switch to PSR-7 v2.x (for modern projects)
php scripts/utils/switch-psr7-version.php 2
composer update psr/http-message
```

### 3. What Changes

When switching to PSR-7 v1.x:
- Return type declarations are removed from interface methods
- PHPDoc `@return` annotations are added for IDE support
- composer.json is updated to require `^1.1`

When switching to PSR-7 v2.x:
- Return type declarations are added to interface methods
- composer.json is updated to require `^2.0`

## Use Cases

### ReactPHP Integration

If you need to integrate with ReactPHP (which uses PSR-7 v1.x):

```bash
# Switch to PSR-7 v1.x
php scripts/utils/switch-psr7-version.php 1
composer update psr/http-message

# Install ReactPHP
composer require react/http
```

Now you can use PivotPHP with ReactPHP:

```php
use React\Http\HttpServer;
use React\Http\Message\Response as ReactResponse;
use React\EventLoop\Loop;
use PivotPHP\Core\Core\Application;

$app = new Application();

// Define PivotPHP routes
$app->get('/api/users', function ($req, $res) {
    return $res->json(['users' => ['John', 'Jane']]);
});

// Create ReactPHP server
$server = new HttpServer(function ($request) use ($app) {
    // Convert ReactPHP request to PivotPHP request
    $pivotRequest = convertReactRequest($request);
    
    // Process with PivotPHP
    $pivotResponse = $app->handle($pivotRequest);
    
    // Convert back to ReactPHP response
    return convertPivotResponse($pivotResponse);
});

$socket = new \React\Socket\SocketServer('127.0.0.1:8080');
$server->listen($socket);

Loop::run();
```

### Modern PHP Projects

For new projects or those using modern PHP packages:

```bash
# Switch to PSR-7 v2.x
php scripts/utils/switch-psr7-version.php 2
composer update psr/http-message
```

This provides:
- Full type safety with return type declarations
- Better IDE support
- Compatibility with modern PSR-7 implementations

## Implementation Details

### File Structure

The PSR-7 implementations are located in:
- `src/Http/Psr7/Message.php`
- `src/Http/Psr7/Request.php`
- `src/Http/Psr7/Response.php`
- `src/Http/Psr7/ServerRequest.php`
- `src/Http/Psr7/Stream.php`
- `src/Http/Psr7/Uri.php`
- `src/Http/Psr7/UploadedFile.php`

### Compatibility Script

The `scripts/utils/switch-psr7-version.php` script:
1. Modifies method signatures in all PSR-7 implementation files
2. Updates composer.json to require the appropriate PSR-7 version
3. Adds PHPDoc annotations when using v1.x for IDE support

### Testing

Both versions are tested to ensure compatibility:

```bash
# Test with PSR-7 v1.x
php scripts/utils/switch-psr7-version.php 1
composer update
composer test

# Test with PSR-7 v2.x
php scripts/utils/switch-psr7-version.php 2
composer update
composer test
```

## Best Practices

1. **Choose Based on Your Ecosystem**
   - Use v1.x if integrating with older libraries (ReactPHP, etc.)
   - Use v2.x for new projects or modern packages

2. **Document Your Choice**
   - Specify in your README which PSR-7 version your project uses
   - Include switching instructions if flexibility is needed

3. **Type Safety**
   - When using v1.x, rely on PHPDoc annotations
   - When using v2.x, leverage native return types

4. **CI/CD Considerations**
   - Test with both versions in your CI pipeline if supporting both
   - Lock the version in production to avoid surprises

## Limitations

1. **Runtime Switching**: You cannot switch versions at runtime; it requires running the script and updating dependencies
2. **Mixed Environments**: You cannot use both versions simultaneously in the same project
3. **Custom Extensions**: If you extend PivotPHP's HTTP classes, you may need to update your code when switching versions

## Future Enhancements

Potential improvements for future versions:
- Automatic version detection during composer install
- Separate packages for v1.x and v2.x support
- Bridge classes for seamless conversion between versions