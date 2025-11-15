<?php

/**
 * 📁 PivotPHP v2.0.0 - Static Files Serving
 *
 * Demonstrates static file serving using StaticFileManager from core-routing.
 *
 * 🚀 How to run:
 * php -S localhost:8000 examples/02-routing/static-files.php
 *
 * 🧪 How to test:
 * # Static files (served from disk)
 * curl http://localhost:8000/public/test.json      # File serving
 * curl http://localhost:8000/assets/app.css        # CSS file
 * curl http://localhost:8000/docs/readme.txt       # Text file
 *
 * 📝 Note: $app->static() was removed in v2.0.0
 * Use regular routes with HTTP caching headers for static responses instead.
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

// 🎯 Create Application
$app = new Application();

// 📁 Create some test static files for demonstration
$staticDir = __DIR__ . '/static-demo';
if (!is_dir($staticDir)) {
    mkdir($staticDir, 0755, true);
    mkdir($staticDir . '/css', 0755, true);
    mkdir($staticDir . '/js', 0755, true);
    mkdir($staticDir . '/docs', 0755, true);

    // Create demo files
    file_put_contents($staticDir . '/test.json', json_encode([
        'message' => 'This is a static JSON file',
        'served_by' => 'StaticFileManager (core-routing)',
        'framework' => 'PivotPHP v2.0.0'
    ], JSON_PRETTY_PRINT));

    file_put_contents($staticDir . '/css/app.css', "
/* Demo CSS file served by PivotPHP */
body {
    font-family: Arial, sans-serif;
    background-color: #f5f5f5;
    color: #333;
}

.framework-demo {
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
}
");

    file_put_contents($staticDir . '/js/app.js', "
// Demo JavaScript file served by PivotPHP
console.log('PivotPHP v2.0.0 - Modular routing system!');

function showFrameworkInfo() {
    return {
        framework: 'PivotPHP',
        version: '2.0.0',
        feature: 'Modular routing with core-routing package'
    };
}
");

    file_put_contents($staticDir . '/docs/readme.txt', "
PivotPHP v2.0.0 - Modular Routing

This file is served directly by StaticFileManager from core-routing package.

Features:
- Static file serving with proper MIME types
- Directory organization and security
- Path traversal prevention
- Integration with modular routing system

v2.0.0 Breaking Changes:
- Routing system now uses pivotphp/core-routing
- $app->static() method removed (use HTTP caching instead)
- StaticFileManager now from core-routing package
");
}

// 🏠 Home route - Overview
$app->get('/', function($req, $res) {
    return $res->json([
        'title' => 'PivotPHP v2.0.0 - Static Files Demo',
        'version' => '2.0.0',
        'routing' => 'Modular (pivotphp/core-routing)',
        'static_file_serving' => [
            'method' => '$app->staticFiles()',
            'purpose' => 'Serve actual files from filesystem',
            'examples' => [
                'GET /public/test.json' => 'Demo JSON file from disk',
                'GET /assets/app.css' => 'Demo CSS file from disk',
                'GET /assets/app.js' => 'Demo JavaScript file from disk',
                'GET /docs/readme.txt' => 'Demo text file from disk'
            ],
            'features' => [
                'automatic_mime_detection' => true,
                'security_checks' => true,
                'cache_headers' => true,
                'directory_traversal_protection' => true
            ]
        ],
        'breaking_changes' => [
            'removed' => '$app->static() method',
            'alternative' => 'Use regular routes with Cache-Control headers',
            'example' => '$res->header("Cache-Control", "public, max-age=3600")'
        ]
    ]);
});

// 📁 Register static file directories using $app->staticFiles()
// This uses StaticFileManager from core-routing to serve actual files from disk
try {
    // Register /public route to serve files from static-demo directory
    $app->staticFiles('/public', $staticDir, [
        'index' => ['index.html', 'index.htm'],
        'dotfiles' => 'ignore',
        'extensions' => false,
        'fallthrough' => true,
        'redirect' => true
    ]);

    // Register /assets route for CSS/JS files
    $app->staticFiles('/assets', $staticDir, [
        'index' => false,
        'dotfiles' => 'deny'
    ]);

    // Register /docs route for documentation files
    $app->staticFiles('/docs', $staticDir . '/docs');

} catch (Exception $e) {
    // Handle directory registration errors gracefully
    $app->get('/static-error', function($req, $res) use ($e) {
        return $res->status(500)->json([
            'error' => 'Static file setup failed',
            'message' => $e->getMessage(),
            'suggestion' => 'Check directory permissions and paths'
        ]);
    });
}

// 📊 Static files information
$app->get('/static-info', function($req, $res) {
    return $res->json([
        'staticFiles_method' => [
            'purpose' => 'Serve actual files from disk',
            'package' => 'pivotphp/core-routing',
            'class' => 'StaticFileManager',
            'api_call' => '$app->staticFiles(\'/assets\', \'./public/assets\')',
            'features' => [
                'mime_type_detection' => 'Automatic based on file extension',
                'security' => 'Path traversal prevention',
                'cache_headers' => 'ETag and Last-Modified support',
                'index_files' => 'index.html, index.htm support'
            ],
            'use_cases' => [
                'css_js_files' => 'Frontend assets',
                'images' => 'Static images',
                'documents' => 'PDFs, text files',
                'downloads' => 'Static downloads'
            ]
        ],
        'migration_from_v1' => [
            'removed_feature' => '$app->static() method',
            'reason' => 'Simplification and modular architecture',
            'alternative' => 'Use HTTP caching headers with regular routes',
            'example' => [
                'old' => '$app->static(\'/health\', fn() => $res->json([...]))',
                'new' => '$app->get(\'/health\', fn() => $res->header("Cache-Control", "max-age=300")->json([...]))'
            ]
        ]
    ]);
});

// Example: Static-like response using HTTP caching (replacement for $app->static())
$app->get('/api/health', function($req, $res) {
    return $res
        ->header('Cache-Control', 'public, max-age=300') // 5 minutes cache
        ->json([
            'status' => 'healthy',
            'framework' => 'PivotPHP',
            'version' => '2.0.0',
            'routing' => 'modular (core-routing)',
            'note' => 'Uses HTTP caching instead of $app->static()'
        ]);
});

// Example: Version endpoint with caching
$app->get('/api/version', function($req, $res) {
    return $res
        ->header('Cache-Control', 'public, max-age=3600') // 1 hour cache
        ->json([
            'framework' => 'PivotPHP Core',
            'version' => '2.0.0',
            'edition' => 'Modular Routing',
            'php_version' => PHP_VERSION,
            'routing_package' => 'pivotphp/core-routing ^1.0',
            'breaking_changes' => [
                'modular_routing' => true,
                'removed_static_method' => true,
                'removed_classes' => ['StaticRouteManager', 'MockRequest', 'MockResponse']
            ]
        ]);
});

// 🔧 Static files listing endpoint
$app->get('/files/list', function($req, $res) {
    return $res->json([
        'demo_static_paths' => [
            '/public' => 'Static files from demo directory',
            '/assets' => 'CSS and JS files',
            '/docs' => 'Documentation files'
        ],
        'cached_api_endpoints' => [
            '/api/health' => 'Health check with 5min cache',
            '/api/version' => 'Version info with 1hr cache'
        ],
        'note' => 'Static files served by StaticFileManager from core-routing package'
    ]);
});

// Clean up demo files on shutdown (optional)
register_shutdown_function(function() use ($staticDir) {
    if (is_dir($staticDir)) {
        // Recursively remove demo directory
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($staticDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($staticDir);
    }
});

// 🚀 Run the application
$app->run();
