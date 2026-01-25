# Performance Tools Migration Guide

## Overview

A partir da versão 2.2.0, os componentes de performance do PivotPHP foram migrados para um pacote separado: **`pivotphp/performance-tools`**.

Esta mudança permite:
- ✅ **Modularização**: Performance tools como pacote independente
- ✅ **Compatibilidade BC**: Aliases automáticos até v3.0.0
- ✅ **Injeção de Dependências**: Interfaces e adapters para flexibilidade
- ✅ **Manutenção**: Separação clara de responsabilidades

## Migrated Components

| Component | Location | New Package |
|-----------|----------|-------------|
| Psr7Pool | `PivotPHP\Core\Http\Pool` | `PivotPHP\PerformanceTools\Http\Psr7\Pool` |
| PoolManager | `PivotPHP\Core\Http\Psr7\Pool` | `PivotPHP\PerformanceTools\Http\Psr7\Pool` |
| ResponsePool | `PivotPHP\Core\Http\Psr7\Pool` | `PivotPHP\PerformanceTools\Http\Psr7\Pool` |
| HeaderPool | `PivotPHP\Core\Http\Psr7\Pool` | `PivotPHP\PerformanceTools\Http\Psr7\Pool` |
| EnhancedStreamPool | `PivotPHP\Core\Http\Psr7\Pool` | `PivotPHP\PerformanceTools\Http\Psr7\Pool` |
| OperationsCache | `PivotPHP\Core\Http\Psr7\Cache` | `PivotPHP\PerformanceTools\Http\Psr7\Cache` |
| JsonBufferPool | `PivotPHP\Core\Json\Pool` | `PivotPHP\PerformanceTools\Json\Pool` |
| JsonBuffer | `PivotPHP\Core\Json\Pool` | `PivotPHP\PerformanceTools\Json\Pool` |
| MiddlewarePipelineCompiler | `PivotPHP\Core\Middleware` | `PivotPHP\PerformanceTools\Middleware` |
| SerializationCache | `PivotPHP\Core\Utils` | `PivotPHP\PerformanceTools\Utils` |

## Backward Compatibility (v2.2.0 - v2.9.x)

**No breaking changes!** O PivotPHP Core v2.2.0+ inclui aliases automáticos que redirecionam as classes antigas para o novo pacote.

### Old Code (Still Works)

```php
use PivotPHP\Core\Http\Pool\Psr7Pool;
use PivotPHP\Core\Http\Psr7\Pool\PoolManager;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

// Funciona exatamente como antes
$pool = Psr7Pool::getServerRequest(...);
```

### New Code (Recommended)

```php
use PivotPHP\PerformanceTools\Http\Psr7\Pool\Psr7Pool;
use PivotPHP\PerformanceTools\Http\Psr7\Pool\PoolManager;
use PivotPHP\PerformanceTools\Json\Pool\JsonBufferPool;

// Mesmo funcionamento, namespaces claros
$pool = Psr7Pool::getServerRequest(...);
```

## Installation

### For Development/Optional Performance

```bash
# Install pivotphp/performance-tools if not already included
composer require pivotphp/performance-tools
```

### For Production

O PivotPHP Core mantém cópias injetáveis das interfaces para permitir que aplicações funcionem sem o pacote performance-tools. Para máxima performance, instale o pacote:

```bash
composer require pivotphp/performance-tools
```

## Interfaces & Adapters

O Core v2.2.0 introduz interfaces e adapters para permitir injeção de dependências sem criar dependências rígidas:

### Interfaces (em `src/Contracts/`)

- `JsonOptimizerInterface`
- `Psr7PoolInterface`
- `ResponsePoolInterface`
- `HeaderPoolInterface`
- `StreamPoolInterface`
- `OperationsCacheInterface`
- `SerializationCacheInterface`
- `MiddlewarePipelineCompilerInterface`

### Adapters (em `src/Http/Adapters/`, `src/Json/Adapters/`, etc.)

Os adapters implementam as interfaces e delegam para as classes do PerformanceTools quando disponíveis, ou caem de volta para implementações simples quando o pacote não está instalado.

## Architecture Changes

### Before (v2.1.x)

```
Core
├── Http/Pool/Psr7Pool
├── Http/Psr7/Pool/PoolManager
├── Json/Pool/JsonBufferPool
├── Middleware/MiddlewarePipelineCompiler
└── Utils/SerializationCache
```

### After (v2.2.0+)

```
Core
├── Contracts/ (Interfaces)
├── Adapters/ (Delegates to PerformanceTools)
└── aliases-performance-tools.php (BC)

PerformanceTools (Separate Package)
├── Http/Psr7/Pool/ (Psr7Pool, PoolManager, ResponsePool, etc.)
├── Http/Psr7/Cache/ (OperationsCache)
├── Json/Pool/ (JsonBufferPool, JsonBuffer)
├── Middleware/ (MiddlewarePipelineCompiler)
└── Utils/ (SerializationCache)
```

## Dependency Injection

A injeção de dependências agora funciona via interfaces e adapters:

### Example: Custom Psr7Pool Implementation

```php
use PivotPHP\Core\Contracts\Psr7PoolInterface;
use PivotPHP\Core\Http\Facades\HttpPoolFacade;

class CustomPool implements Psr7PoolInterface {
    // Implementação customizada
}

// Injetar no façade
HttpPoolFacade::setPool(new CustomPool());

// Ou via PoolManager
PoolManager::setResponsePool(...);
PoolManager::setHeaderPool(...);
```

## Migration v2.x → v3.0.0

Na v3.0.0, as aliases serão removidas. Para preparar seu código:

### Step 1: Update Imports

```php
// Old
use PivotPHP\Core\Http\Pool\Psr7Pool;

// New
use PivotPHP\PerformanceTools\Http\Psr7\Pool\Psr7Pool;
```

### Step 2: Ensure performance-tools is Installed

```bash
composer require pivotphp/performance-tools
```

### Step 3: Test Your Application

```bash
composer test
```

## Deprecation Timeline

| Version | Status | Notes |
|---------|--------|-------|
| v2.1.x | ✅ Current | All in Core |
| v2.2.0 - v2.9.x | ✅ Supported | Aliases available, new package recommended |
| v3.0.0 | ⚠️ Upcoming | Aliases removed, performance-tools required |

## Support

For issues or questions about the migration:

1. Check the [PerformanceTools documentation](../pivotphp-performance-tools/README.md)
2. Review [Core Contracts](src/Contracts/)
3. Open an issue on GitHub

## See Also

- [PerformanceTools README](../pivotphp-performance-tools/README.md)
- [Architecture Decision Record: Performance Tools Package Separation](./docs/adr-001-performance-tools-separation.md)
