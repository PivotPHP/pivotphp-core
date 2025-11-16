<?php

/**
 * Aliases de compatibilidade - PivotPHP Core v2.0.0
 *
 * Apenas aliases essenciais para modular routing system (v2.0.0)
 * Aliases v1.x removidos - breaking change
 *
 * @package PivotPHP\Core
 */

declare(strict_types=1);

// ============================================================================
// v2.0.0 Modular Routing - Backward Compatibility Aliases
// ============================================================================
// These aliases redirect old PivotPHP\Core\Routing\* classes to the new
// modular routing system from pivotphp/core-routing package

// Router - Main routing class
class_alias(
    'PivotPHP\Routing\Router\Router',
    'PivotPHP\Core\Routing\Router'
);

// Route Collection
class_alias(
    'PivotPHP\Routing\Router\RouteCollection',
    'PivotPHP\Core\Routing\RouteCollection'
);

// Route
class_alias(
    'PivotPHP\Routing\Router\Route',
    'PivotPHP\Core\Routing\Route'
);

// Cache Strategy (File-based)
class_alias(
    'PivotPHP\Routing\Cache\FileCacheStrategy',
    'PivotPHP\Core\Routing\RouteCache'
);

// Memory Manager (Memory-based caching)
class_alias(
    'PivotPHP\Routing\Cache\MemoryCacheStrategy',
    'PivotPHP\Core\Routing\RouteMemoryManager'
);

// Static File Manager
class_alias(
    'PivotPHP\Routing\Router\StaticFileManager',
    'PivotPHP\Core\Routing\StaticFileManager'
);

// Simple Static File Manager
class_alias(
    'PivotPHP\Routing\Router\SimpleStaticFileManager',
    'PivotPHP\Core\Routing\SimpleStaticFileManager'
);

// Router Instance (Singleton pattern)
class_alias(
    'PivotPHP\Routing\Router\RouterInstance',
    'PivotPHP\Core\Routing\RouterInstance'
);
