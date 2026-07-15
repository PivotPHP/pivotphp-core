<?php

/**
 * Compatibility Aliases for PivotPHP\Core\PerformanceTools
 *
 * Este arquivo permite que código legado que usa classes do Core continue funcionando
 * ao importar automaticamente do pacote PerformanceTools separado.
 *
 * Nota: Este arquivo será descontinuado em v3.0.0
 *
 * @deprecated 2.2.0 Use PivotPHP\PerformanceTools directly
 */

declare(strict_types=1);

// Aliases para manter compatibilidade até v3.0.0
if (class_exists('PivotPHP\\PerformanceTools\\Http\\Psr7\\Pool\\Psr7Pool')) {
    class_alias(
        'PivotPHP\\PerformanceTools\\Http\\Psr7\\Pool\\Psr7Pool',
        'PivotPHP\\Core\\Http\\Pool\\Psr7Pool'
    );
}

if (class_exists('PivotPHP\\PerformanceTools\\Http\\Psr7\\Pool\\PoolManager')) {
    class_alias(
        'PivotPHP\\PerformanceTools\\Http\\Psr7\\Pool\\PoolManager',
        'PivotPHP\\Core\\Http\\Psr7\\Pool\\PoolManager'
    );
}

if (class_exists('PivotPHP\\PerformanceTools\\Http\\Psr7\\Pool\\ResponsePool')) {
    class_alias(
        'PivotPHP\\PerformanceTools\\Http\\Psr7\\Pool\\ResponsePool',
        'PivotPHP\\Core\\Http\\Psr7\\Pool\\ResponsePool'
    );
}

if (class_exists('PivotPHP\\PerformanceTools\\Http\\Psr7\\Pool\\HeaderPool')) {
    class_alias(
        'PivotPHP\\PerformanceTools\\Http\\Psr7\\Pool\\HeaderPool',
        'PivotPHP\\Core\\Http\\Psr7\\Pool\\HeaderPool'
    );
}

if (class_exists('PivotPHP\\PerformanceTools\\Http\\Psr7\\Pool\\EnhancedStreamPool')) {
    class_alias(
        'PivotPHP\\PerformanceTools\\Http\\Psr7\\Pool\\EnhancedStreamPool',
        'PivotPHP\\Core\\Http\\Psr7\\Pool\\EnhancedStreamPool'
    );
}

if (class_exists('PivotPHP\\PerformanceTools\\Http\\Psr7\\Cache\\OperationsCache')) {
    class_alias(
        'PivotPHP\\PerformanceTools\\Http\\Psr7\\Cache\\OperationsCache',
        'PivotPHP\\Core\\Http\\Psr7\\Cache\\OperationsCache'
    );
}

if (class_exists('PivotPHP\\PerformanceTools\\Json\\Pool\\JsonBufferPool')) {
    class_alias(
        'PivotPHP\\PerformanceTools\\Json\\Pool\\JsonBufferPool',
        'PivotPHP\\Core\\Json\\Pool\\JsonBufferPool'
    );
}

if (class_exists('PivotPHP\\PerformanceTools\\Json\\Pool\\JsonBuffer')) {
    class_alias(
        'PivotPHP\\PerformanceTools\\Json\\Pool\\JsonBuffer',
        'PivotPHP\\Core\\Json\\Pool\\JsonBuffer'
    );
}

if (class_exists('PivotPHP\\PerformanceTools\\Middleware\\MiddlewarePipelineCompiler')) {
    class_alias(
        'PivotPHP\\PerformanceTools\\Middleware\\MiddlewarePipelineCompiler',
        'PivotPHP\\Core\\Middleware\\MiddlewarePipelineCompiler'
    );
}

if (class_exists('PivotPHP\\PerformanceTools\\Utils\\SerializationCache')) {
    class_alias(
        'PivotPHP\\PerformanceTools\\Utils\\SerializationCache',
        'PivotPHP\\Core\\Utils\\SerializationCache'
    );
}
