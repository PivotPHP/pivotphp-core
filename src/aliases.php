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
if (class_exists('PivotPHP\Routing\Router\Router')) {
    class_alias(
        'PivotPHP\Routing\Router\Router',
        'PivotPHP\Core\Routing\Router'
    );
}

// Route Collection
if (class_exists('PivotPHP\Routing\Router\RouteCollection')) {
    class_alias(
        'PivotPHP\Routing\Router\RouteCollection',
        'PivotPHP\Core\Routing\RouteCollection'
    );
}

// Route
if (class_exists('PivotPHP\Routing\Router\Route')) {
    class_alias(
        'PivotPHP\Routing\Router\Route',
        'PivotPHP\Core\Routing\Route'
    );
}

// Cache Strategy (File-based)
if (class_exists('PivotPHP\Routing\Cache\FileCacheStrategy')) {
    class_alias(
        'PivotPHP\Routing\Cache\FileCacheStrategy',
        'PivotPHP\Core\Routing\RouteCache'
    );
}

// Memory Manager (Memory-based caching)
if (class_exists('PivotPHP\Routing\Cache\MemoryCacheStrategy')) {
    class_alias(
        'PivotPHP\Routing\Cache\MemoryCacheStrategy',
        'PivotPHP\Core\Routing\RouteMemoryManager'
    );
}

// Static File Manager
if (class_exists('PivotPHP\Routing\Router\StaticFileManager')) {
    class_alias(
        'PivotPHP\Routing\Router\StaticFileManager',
        'PivotPHP\Core\Routing\StaticFileManager'
    );
}

// Simple Static File Manager
if (class_exists('PivotPHP\Routing\Router\SimpleStaticFileManager')) {
    class_alias(
        'PivotPHP\Routing\Router\SimpleStaticFileManager',
        'PivotPHP\Core\Routing\SimpleStaticFileManager'
    );
}

// Router Instance (Singleton pattern)
if (class_exists('PivotPHP\Routing\Router\RouterInstance')) {
    class_alias(
        'PivotPHP\Routing\Router\RouterInstance',
        'PivotPHP\Core\Routing\RouterInstance'
    );
}
