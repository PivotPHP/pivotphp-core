# PSR-7 Version Compatibility

> **Update (v2.1.1):** the sections below describing PivotPHP Core as requiring PSR-7 v2.0
> "at runtime" and needing a bridge/adapter for PSR-7 v1.x projects are outdated and were the
> actual bug fixed in v2.1.1. Since v2.1.1, all PSR-7 classes are directly compatible with
> **both** `psr/http-message` v1.1 and v2.0 with no bridge, adapter, or version switch
> required — see the [v2.1.1 changelog entry](../../../CHANGELOG.md#211---2026-07-15---psr-7-20-compatibility-fix)
> for details. The rest of this document is kept for historical context and is being revised;
> treat the "Compatibility with PSR-7 v1.x Projects" section as no longer accurate.

## Overview

PivotPHP Core v1.0.1 is designed with PSR-7 v2.0 but allows installation with either PSR-7 v1.x or v2.x through composer constraints.

## Composer Configuration

```json
{
    "require": {
        "psr/http-message": "^1.1|^2.0"
    }
}
```

## Important Notes

### Current Implementation

PivotPHP Core's HTTP message implementations (`ServerRequest`, `Request`, `Response`, `Stream`, etc.) are built following PSR-7 v2.0 specifications, which include:

- Return type declarations on interface methods
- Parameter type declarations
- Stricter type safety

### Compatibility with PSR-7 v1.x Projects

**As of v2.1.1**, PivotPHP Core's PSR-7 classes declare return types matching the PSR-7 v2.0
interfaces, which is compatible with both PSR-7 v1.x (whose interfaces don't declare return
types, so a stricter implementation is legal) and v2.0 (whose interfaces require those exact
return types). Composer may resolve `psr/http-message` to either `^1.1` or `^2.0` and the
package works correctly either way — no bridge, adapter, or manual switching is required for
normal usage.

Prior to v2.1.1, several classes had signatures that were incompatible with the v2.0
interfaces despite `composer.json` allowing `^2.0`; whenever Composer resolved to v2.0, every
request failed with a fatal `Declaration must be compatible` error. That regression is what
v2.1.1 fixes.

Mixed environments needing a specific PSR-7 v1.x-only library that itself is incompatible
with the *installed* `psr/http-message` version (e.g. some ReactPHP components) may still
need a bridge/adapter layer — that is a constraint of the third-party library, not of
PivotPHP Core.

### ReactPHP Integration Example

If you need to integrate PivotPHP Core with ReactPHP (which uses PSR-7 v1.x), consider:

```php
// Use a PSR-7 bridge to convert between versions
use Acme\Psr7Bridge;

// ReactPHP PSR-7 v1.x request
$reactRequest = $event->getRequest();

// Convert to PSR-7 v2.0 for PivotPHP
$pivotRequest = Psr7Bridge::fromV1ToV2($reactRequest);

// Process with PivotPHP
$pivotResponse = $app->handle($pivotRequest);

// Convert back to PSR-7 v1.x for ReactPHP
$reactResponse = Psr7Bridge::fromV2ToV1($pivotResponse);
```

## Future Considerations

Future versions of PivotPHP Core may include:

1. **Conditional Loading**: Automatic detection and loading of appropriate implementations based on installed PSR-7 version
2. **Built-in Bridge**: Native bridge classes for seamless conversion between PSR-7 versions
3. **Adapter Pattern**: Factory methods that return compatible implementations

## Recommendations

- For new projects: Use PSR-7 v2.0 for better type safety
- For existing projects with PSR-7 v1.x dependencies: Consider using a bridge library or waiting for native dual-version support
- For maximum compatibility: Implement your own PSR-7 adapter layer specific to your needs

## Related Resources

- [PSR-7 HTTP Message Interface](https://www.php-fig.org/psr/psr-7/)
- [PSR-7 v2.0 Migration Guide](https://www.php-fig.org/psr/psr-7/migration-guide/)
- [PivotPHP HTTP Implementation](../http/README.md)