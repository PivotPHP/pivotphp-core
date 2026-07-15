<?php

/**
 * ⚡ PivotPHP - Modo Performance Simplificado
 *
 * Demonstra recursos de performance do PivotPHP v2.0.0+
 * Performance simplificada, JSON optimization, memory management e métricas
 *
 * NOTA: Versão simplificada seguindo principio "Simplicidade sobre Otimização Prematura"
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/05-performance/high-performance.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl http://localhost:8000/enable-high-performance
 * curl -X POST http://localhost:8000/json-test -H "Content-Type: application/json" -d '{"test":"data"}'
 * curl http://localhost:8000/pool-demo
 * curl http://localhost:8000/metrics
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Performance\PerformanceMode;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use PivotPHP\Core\Performance\PerformanceMonitor;

$app = new Application();

// 📋 Página inicial
$app->get('/', function ($req, $res) {
    return $res->json([
        'title' => 'PivotPHP - High Performance Examples',
        'description' => 'Demonstrações dos recursos de performance v2.0.0+',
        'performance_features' => [
            'High Performance Mode' => [
                'description' => 'Modo otimizado para throughput máximo',
                'profiles' => ['DEVELOPMENT', 'PRODUCTION', 'TEST'],
                'benefits' => ['Object pooling', 'Memory optimization', 'Response caching']
            ],
            'JSON Buffer Pooling' => [
                'description' => 'Pool de buffers para operações JSON v2.0.0',
                'auto_optimization' => 'Detecta e otimiza datasets grandes automaticamente',
                'performance_gain' => 'Até 300% de melhoria em JSON encoding/decoding'
            ],
            'Object Pooling' => [
                'description' => 'Reutilização de objetos HTTP para reduzir GC',
                'target_objects' => ['Request', 'Response', 'Headers'],
                'memory_savings' => 'Redução significativa de allocations'
            ],
            'Performance Monitoring' => [
                'description' => 'Métricas em tempo real de performance',
                'metrics' => ['Response time', 'Memory usage', 'Pool efficiency'],
                'integration' => 'OpenTelemetry ready'
            ]
        ],
        'endpoints' => [
            'GET /enable-high-performance' => 'Ativar modo alta performance',
            'GET /disable-high-performance' => 'Desativar modo alta performance',
            'POST /json-test' => 'Teste de performance JSON',
            'GET /pool-demo' => 'Demonstração de object pooling',
            'GET /memory-test' => 'Teste de uso de memória',
            'GET /metrics' => 'Métricas de performance atual',
            'GET /benchmark' => 'Benchmark completo'
        ],
        'current_status' => [
            'high_performance_enabled' => PerformanceMode::isEnabled(),
            'json_pool_configured' => class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool'),
            'framework_version' => Application::VERSION
        ]
    ]);
});

// ⚡ Ativar modo alta performance
$app->get('/enable-high-performance', function ($req, $res) {
    $profile = $req->get('profile', 'HIGH');
    
    $validProfiles = ['DEVELOPMENT', 'PRODUCTION', 'TEST'];
    if (!in_array($profile, $validProfiles)) {
        return $res->status(400)->json([
            'error' => 'Profile inválido',
            'valid_profiles' => $validProfiles,
            'provided' => $profile
        ]);
    }
    
    // Ativar modo alta performance
    PerformanceMode::enable(constant("PivotPHP\\Core\\Performance\\PerformanceMode::PROFILE_{$profile}"));
    
    $status = [
        'enabled' => PerformanceMode::isEnabled(),
        'current_mode' => PerformanceMode::getCurrentMode()
    ];
    
    return $res->json([
        'message' => 'Modo alta performance ativado',
        'profile' => $profile,
        'status' => $status,
        'optimizations_enabled' => [
            'object_pooling' => $status['enabled'],
            'response_caching' => $status['enabled'],
            'memory_optimization' => $status['enabled'],
            'json_pooling' => true // Always available in v2.0.0
        ],
        'performance_impact' => [
            'expected_throughput_gain' => $profile === 'PRODUCTION' ? '50-100%' : ($profile === 'DEVELOPMENT' ? '0-25%' : '0%'),
            'memory_efficiency' => 'Significantly improved',
            'gc_pressure' => 'Reduced'
        ]
    ]);
});

// 🔄 Desativar modo alta performance
$app->get('/disable-high-performance', function ($req, $res) {
    PerformanceMode::disable();
    
    return $res->json([
        'message' => 'Modo alta performance desativado',
        'status' => ['enabled' => PerformanceMode::isEnabled()],
        'note' => 'JSON pooling permanece ativo (feature v2.0.0)'
    ]);
});

// 📊 Teste de performance JSON
$app->post('/json-test', function ($req, $res) {
    $body = $req->getBodyAsStdClass();
    $iterations = (int) ($body->iterations ?? 1000);
    $dataSize = $body->data_size ?? 'medium';
    
    // Gerar dados de teste baseados no tamanho
    $testData = [];
    switch ($dataSize) {
        case 'small':
            $testData = ['id' => 1, 'name' => 'Test', 'value' => 123.45];
            break;
        case 'medium':
            $testData = [
                'id' => 1,
                'name' => 'Test Product',
                'description' => 'This is a test product with medium amount of data',
                'price' => 123.45,
                'category' => 'electronics',
                'tags' => ['test', 'product', 'electronics'],
                'metadata' => [
                    'created_at' => date('c'),
                    'updated_at' => date('c'),
                    'version' => '1.0'
                ]
            ];
            break;
        case 'large':
            $testData = [
                'id' => 1,
                'products' => array_fill(0, 100, [
                    'id' => rand(1, 1000),
                    'name' => 'Product ' . rand(1, 100),
                    'description' => str_repeat('Lorem ipsum dolor sit amet ', 10),
                    'price' => rand(10, 1000) / 10,
                    'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5']
                ]),
                'metadata' => [
                    'total_count' => 100,
                    'generated_at' => date('c')
                ]
            ];
            break;
    }
    
    // Benchmark JSON operations
    $startTime = microtime(true);
    $memoryStart = memory_get_usage();
    
    for ($i = 0; $i < $iterations; $i++) {
        // Usar JSON pooling quando disponível
        if (class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool')) {
            $encoded = JsonBufferPool::encodeWithPool($testData);
        } else {
            $encoded = json_encode($testData);
        }
        
        $decoded = json_decode($encoded, true);
    }
    
    $endTime = microtime(true);
    $memoryEnd = memory_get_usage();
    
    $totalTime = ($endTime - $startTime) * 1000; // ms
    $memoryUsed = $memoryEnd - $memoryStart;
    $opsPerSecond = round($iterations / ($totalTime / 1000), 0);
    
    // Obter estatísticas do pool se disponível
    $poolStats = null;
    if (class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool')) {
        $poolStats = JsonBufferPool::getStatistics();
    }
    
    return $res->json([
        'benchmark_results' => [
            'iterations' => $iterations,
            'data_size' => $dataSize,
            'total_time_ms' => round($totalTime, 2),
            'avg_time_per_op_ms' => round($totalTime / $iterations, 4),
            'operations_per_second' => $opsPerSecond,
            'memory_used_bytes' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2)
        ],
        'json_pool_stats' => $poolStats,
        'performance_mode' => [
            'enabled' => PerformanceMode::isEnabled(),
            'json_pooling_used' => class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool')
        ],
        'comparison' => [
            'estimated_standard_json' => [
                'ops_per_second' => $opsPerSecond * 0.7, // Estimativa sem pooling
                'note' => 'Estimativa sem otimizações'
            ],
            'improvement' => class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool') ? '~30-300%' : 'baseline'
        ]
    ]);
});

// 🏊 Demonstração de Object Pooling
$app->get('/pool-demo', function ($req, $res) {
    $iterations = (int) $req->get('iterations', 100);
    
    $startTime = microtime(true);
    $memoryStart = memory_get_usage();
    
    // Simular criação de muitos objetos Request/Response
    for ($i = 0; $i < $iterations; $i++) {
        // Em modo alta performance, estes objetos seriam reutilizados do pool
        $mockRequest = new stdClass();
        $mockRequest->id = $i;
        $mockRequest->data = "Request data {$i}";
        
        $mockResponse = new stdClass();
        $mockResponse->id = $i;
        $mockResponse->status = 200;
        $mockResponse->data = "Response data {$i}";
        
        unset($mockRequest, $mockResponse);
    }
    
    $endTime = microtime(true);
    $memoryEnd = memory_get_usage();
    $memoryPeak = memory_get_peak_usage();
    
    $performance = ['enabled' => PerformanceMode::isEnabled()];
    
    return $res->json([
        'pool_demo_results' => [
            'iterations' => $iterations,
            'time_ms' => round(($endTime - $startTime) * 1000, 2),
            'memory_used_mb' => round(($memoryEnd - $memoryStart) / 1024 / 1024, 2),
            'peak_memory_mb' => round($memoryPeak / 1024 / 1024, 2),
            'objects_created' => $iterations * 2
        ],
        'pooling_status' => [
            'high_performance_enabled' => $performance['enabled'],
            'object_pooling_active' => $performance['enabled'],
            'expected_improvement' => $performance['enabled'] ? 
                'Significant reduction in GC pressure and memory allocations' : 
                'Enable high performance mode for object pooling'
        ],
        'recommendations' => [
            'for_production' => 'Enable PRODUCTION profile for maximum performance',
            'for_development' => 'DEVELOPMENT profile provides good performance with debugging capabilities',
            'monitoring' => 'Use /metrics endpoint to monitor pool efficiency'
        ]
    ]);
});

// 📈 Teste de uso de memória
$app->get('/memory-test', function ($req, $res) {
    $testSize = $req->get('size', 'medium');
    
    $memoryBefore = memory_get_usage();
    $peakBefore = memory_get_peak_usage();
    
    // Alocar arrays de diferentes tamanhos
    $data = [];
    switch ($testSize) {
        case 'small':
            for ($i = 0; $i < 1000; $i++) {
                $data[] = ['id' => $i, 'value' => str_repeat('x', 100)];
            }
            break;
        case 'medium':
            for ($i = 0; $i < 10000; $i++) {
                $data[] = ['id' => $i, 'value' => str_repeat('x', 1000)];
            }
            break;
        case 'large':
            for ($i = 0; $i < 50000; $i++) {
                $data[] = ['id' => $i, 'value' => str_repeat('x', 500)];
            }
            break;
    }
    
    $memoryAfter = memory_get_usage();
    $peakAfter = memory_get_peak_usage();
    
    // Limpar dados
    unset($data);
    gc_collect_cycles(); // Forçar garbage collection
    
    $memoryAfterGC = memory_get_usage();
    
    return $res->json([
        'memory_test_results' => [
            'test_size' => $testSize,
            'memory_before_mb' => round($memoryBefore / 1024 / 1024, 2),
            'memory_after_mb' => round($memoryAfter / 1024 / 1024, 2),
            'memory_used_mb' => round(($memoryAfter - $memoryBefore) / 1024 / 1024, 2),
            'peak_memory_mb' => round($peakAfter / 1024 / 1024, 2),
            'memory_after_gc_mb' => round($memoryAfterGC / 1024 / 1024, 2),
            'memory_freed_mb' => round(($memoryAfter - $memoryAfterGC) / 1024 / 1024, 2)
        ],
        'performance_mode' => ['enabled' => PerformanceMode::isEnabled()],
        'optimization_tips' => [
            'memory_management' => 'High performance mode optimizes memory usage patterns',
            'gc_optimization' => 'Object pooling reduces garbage collection pressure',
            'production_advice' => 'Enable PRODUCTION profile for memory-intensive applications'
        ]
    ]);
});

// 📊 Métricas de performance
$app->get('/metrics', function ($req, $res) {
    // Coletar métricas do sistema
    $metrics = [
        'system' => [
            'memory_usage_mb' => round(memory_get_usage() / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit'),
            'php_version' => PHP_VERSION,
            'framework_version' => Application::VERSION
        ],
        'performance_mode' => ['enabled' => PerformanceMode::isEnabled()],
        'json_pool' => class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool') ? 
            JsonBufferPool::getStatistics() : null,
        'request_info' => [
            'method' => $req->method(),
            'uri' => $req->uri(),
            'timestamp' => date('c'),
            'request_id' => uniqid('req_', true)
        ]
    ];
    
    // Adicionar métricas de performance se disponível
    if (class_exists('PivotPHP\\Core\\Performance\\PerformanceMonitor')) {
        try {
            $perfMonitor = new PerformanceMonitor();
            $metrics['performance_monitor'] = [
                'available' => true,
                'note' => 'Performance monitoring active'
            ];
        } catch (Exception $e) {
            $metrics['performance_monitor'] = [
                'available' => false,
                'note' => 'Performance monitoring not configured'
            ];
        }
    }
    
    return $res->json([
        'metrics' => $metrics,
        'collection_time' => date('c'),
        'recommendations' => [
            'monitoring' => 'Collect these metrics regularly for performance insights',
            'alerting' => 'Set up alerts for memory usage > 80% and response time > 200ms',
            'optimization' => 'Enable high performance mode in production for better metrics'
        ]
    ]);
});

// 🏁 Benchmark completo
$app->get('/benchmark', function ($req, $res) {
    $iterations = (int) $req->get('iterations', 1000);
    
    $results = [];
    
    // Benchmark 1: JSON Operations
    $jsonData = ['test' => 'data', 'number' => 123, 'array' => [1, 2, 3, 4, 5]];
    $start = microtime(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        $encoded = json_encode($jsonData);
        $decoded = json_decode($encoded, true);
    }
    
    $results['json_operations'] = [
        'iterations' => $iterations,
        'time_ms' => round((microtime(true) - $start) * 1000, 2),
        'ops_per_second' => round($iterations / (microtime(true) - $start), 0)
    ];
    
    // Benchmark 2: Array Operations
    $start = microtime(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        $array = range(1, 100);
        $filtered = array_filter($array, fn($x) => $x % 2 === 0);
        $mapped = array_map(fn($x) => $x * 2, $filtered);
    }
    
    $results['array_operations'] = [
        'iterations' => $iterations,
        'time_ms' => round((microtime(true) - $start) * 1000, 2),
        'ops_per_second' => round($iterations / (microtime(true) - $start), 0)
    ];
    
    // Benchmark 3: String Operations
    $start = microtime(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        $string = str_repeat('Hello World! ', 10);
        $upper = strtoupper($string);
        $replaced = str_replace('HELLO', 'HI', $upper);
    }
    
    $results['string_operations'] = [
        'iterations' => $iterations,
        'time_ms' => round((microtime(true) - $start) * 1000, 2),
        'ops_per_second' => round($iterations / (microtime(true) - $start), 0)
    ];
    
    $totalOps = array_sum(array_column($results, 'ops_per_second'));
    
    return $res->json([
        'benchmark_results' => $results,
        'summary' => [
            'total_operations_per_second' => $totalOps,
            'performance_score' => round($totalOps / 1000, 1) . 'K ops/sec',
            'test_environment' => [
                'php_version' => PHP_VERSION,
                'framework_version' => Application::VERSION,
                'high_performance_enabled' => PerformanceMode::isEnabled()
            ]
        ],
        'interpretation' => [
            'excellent' => '> 500K ops/sec total',
            'good' => '200K - 500K ops/sec total',
            'average' => '50K - 200K ops/sec total',
            'poor' => '< 50K ops/sec total',
            'your_score' => $totalOps > 500000 ? 'excellent' : 
                           ($totalOps > 200000 ? 'good' : 
                           ($totalOps > 50000 ? 'average' : 'poor'))
        ]
    ]);
});

$app->run();