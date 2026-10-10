<?php

/**
 * 🎯 PivotPHP - Restrições de Rota
 * 
 * Demonstra sistema avançado de restrições e validação de rotas
 * Validação de parâmetros, middleware condicional e filtros
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/02-routing/route-constraints.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl http://localhost:8000/users/123
 * curl http://localhost:8000/users/abc  # Deve dar 404
 * curl http://localhost:8000/posts/hello-world
 * curl http://localhost:8000/api/v2/data  # Versão não suportada
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Routing\Router\Router;

$app = new Application();

// 📋 Página inicial
$app->get('/', function ($req, $res) {
    return $res->json([
        'title' => 'PivotPHP - Route Constraints Examples',
        'description' => 'Demonstrações de restrições e validações de rota',
        'constraint_types' => [
            'Regex Constraints' => [
                'numeric_id' => '/users/:id<\\d+> - Apenas números',
                'alpha_slug' => '/posts/:slug<[a-z-]+> - Apenas letras e hífens',
                'date_format' => '/archive/:date<\\d{4}-\\d{2}-\\d{2}> - Formato YYYY-MM-DD'
            ],
            'Custom Validation' => [
                'range_validation' => 'ID entre 1 e 9999',
                'enum_validation' => 'Valores específicos permitidos',
                'business_rules' => 'Regras de negócio customizadas'
            ],
            'Version Constraints' => [
                'api_versioning' => 'Suporte apenas a versões específicas',
                'feature_flags' => 'Recursos baseados em flags'
            ],
            'Conditional Routes' => [
                'time_based' => 'Rotas ativas apenas em horários específicos',
                'user_based' => 'Rotas baseadas no tipo de usuário',
                'environment' => 'Rotas específicas por ambiente'
            ]
        ]
    ]);
});

// 🔢 Constraint básico - ID numérico com validação de range
$app->get('/users/:id<\\d+>', function ($req, $res) {
    $id = (int) $req->param('id');
    
    // Validação adicional de range
    if ($id < 1 || $id > 9999) {
        return $res->status(400)->json([
            'error' => 'ID deve estar entre 1 e 9999',
            'provided_id' => $id,
            'constraint' => 'range_validation'
        ]);
    }
    
    return $res->json([
        'user' => [
            'id' => $id,
            'name' => "User {$id}",
            'valid' => true
        ],
        'constraint_info' => [
            'regex_pattern' => '\\d+',
            'range_validation' => '1-9999',
            'passed_validation' => true
        ]
    ]);
});

// 📝 Constraint de slug com validação de formato
$app->get('/posts/:slug<[a-z0-9-]+>', function ($req, $res) {
    $slug = $req->param('slug');
    
    // Validações adicionais
    if (strlen($slug) < 3) {
        return $res->status(400)->json([
            'error' => 'Slug deve ter pelo menos 3 caracteres',
            'provided_slug' => $slug
        ]);
    }
    
    if (str_starts_with($slug, '-') || str_ends_with($slug, '-')) {
        return $res->status(400)->json([
            'error' => 'Slug não pode começar ou terminar com hífen',
            'provided_slug' => $slug
        ]);
    }
    
    return $res->json([
        'post' => [
            'slug' => $slug,
            'title' => ucwords(str_replace('-', ' ', $slug)),
            'url' => "/posts/{$slug}"
        ],
        'constraint_info' => [
            'regex_pattern' => '[a-z0-9-]+',
            'min_length' => 3,
            'format_rules' => 'Não pode começar/terminar com hífen'
        ]
    ]);
});

// 📅 Constraint de data com validação de data válida
$app->get('/archive/:date<\\d{4}-\\d{2}-\\d{2}>', function ($req, $res) {
    $dateString = $req->param('date');
    
    // Validar se é uma data real
    $dateParts = explode('-', $dateString);
    $year = (int) $dateParts[0];
    $month = (int) $dateParts[1];
    $day = (int) $dateParts[2];
    
    if (!checkdate($month, $day, $year)) {
        return $res->status(400)->json([
            'error' => 'Data inválida',
            'provided_date' => $dateString,
            'constraint' => 'valid_date_required'
        ]);
    }
    
    // Validar range de anos
    $currentYear = (int) date('Y');
    if ($year < 2020 || $year > $currentYear) {
        return $res->status(400)->json([
            'error' => "Ano deve estar entre 2020 e {$currentYear}",
            'provided_year' => $year
        ]);
    }
    
    $date = new DateTime($dateString);
    
    return $res->json([
        'archive' => [
            'date' => $dateString,
            'formatted' => $date->format('d/m/Y'),
            'day_of_week' => $date->format('l'),
            'posts_count' => rand(5, 50)
        ],
        'constraint_info' => [
            'regex_pattern' => '\\d{4}-\\d{2}-\\d{2}',
            'valid_date' => true,
            'year_range' => "2020-{$currentYear}"
        ]
    ]);
});

// 🏷️ Constraint de categoria com enum validation
$app->get('/categories/:type<(technology|business|lifestyle|health)>', function ($req, $res) {
    $type = $req->param('type');
    
    $categoryInfo = [
        'technology' => [
            'description' => 'Tecnologia e Programação',
            'icon' => '💻',
            'color' => 'blue'
        ],
        'business' => [
            'description' => 'Negócios e Empreendedorismo',
            'icon' => '💼',
            'color' => 'green'
        ],
        'lifestyle' => [
            'description' => 'Estilo de Vida',
            'icon' => '🌟',
            'color' => 'purple'
        ],
        'health' => [
            'description' => 'Saúde e Bem-estar',
            'icon' => '🏥',
            'color' => 'red'
        ]
    ];
    
    return $res->json([
        'category' => array_merge(
            ['type' => $type],
            $categoryInfo[$type]
        ),
        'constraint_info' => [
            'enum_pattern' => '(technology|business|lifestyle|health)',
            'allowed_values' => array_keys($categoryInfo),
            'validation_type' => 'enum'
        ]
    ]);
});

// 🌐 Constraint de versão de API
$app->get('/api/v:version<[1-3]>/:endpoint<(users|posts|comments)>', function ($req, $res) {
    $version = (int) $req->param('version');
    $endpoint = $req->param('endpoint');
    
    // Verificar recursos disponíveis por versão
    $versionFeatures = [
        1 => ['users', 'posts'],
        2 => ['users', 'posts', 'comments'],
        3 => ['users', 'posts', 'comments'] // v3 com recursos extras
    ];
    
    if (!in_array($endpoint, $versionFeatures[$version])) {
        return $res->status(404)->json([
            'error' => "Endpoint '{$endpoint}' não disponível na API v{$version}",
            'available_endpoints' => $versionFeatures[$version],
            'upgrade_to' => 'v2 ou v3 para acessar todos os endpoints'
        ]);
    }
    
    $responseData = [
        'api_version' => $version,
        'endpoint' => $endpoint,
        'data' => "Dados do {$endpoint} na API v{$version}",
        'features' => $versionFeatures[$version]
    ];
    
    // Recursos específicos da v3
    if ($version === 3) {
        $responseData['v3_features'] = [
            'advanced_filtering',
            'real_time_updates',
            'batch_operations'
        ];
    }
    
    return $res->json([
        'response' => $responseData,
        'constraint_info' => [
            'version_pattern' => '[1-3]',
            'endpoint_pattern' => '(users|posts|comments)',
            'version_compatibility' => $versionFeatures
        ]
    ]);
});

// 📱 Constraint de tipo de device
$app->get('/mobile/:platform<(ios|android|web)>/app', function ($req, $res) {
    $platform = $req->param('platform');
    
    $platformInfo = [
        'ios' => [
            'app_store_url' => 'https://apps.apple.com/app/myapp',
            'min_version' => 'iOS 14.0',
            'download_size' => '25.4 MB'
        ],
        'android' => [
            'play_store_url' => 'https://play.google.com/store/apps/details?id=com.myapp',
            'min_version' => 'Android 8.0',
            'download_size' => '23.1 MB'
        ],
        'web' => [
            'web_app_url' => 'https://app.mycompany.com',
            'pwa_support' => true,
            'offline_mode' => true
        ]
    ];
    
    return $res->json([
        'platform' => $platform,
        'app_info' => $platformInfo[$platform],
        'constraint_info' => [
            'platform_pattern' => '(ios|android|web)',
            'supported_platforms' => array_keys($platformInfo)
        ]
    ]);
});

// 🔐 Route middleware (4th argument of Router::get): runs only for this route, after the
// constraint matched, and can read the route parameters.
$securityLevel = function ($req, $res, $next) {
    $params = $req->getAttribute('route_params', []);
    $response = $next();

    return $response->withHeader('X-Security-Level', (string) ($params['level'] ?? 'unknown'));
};

$app->get('/secure/:level<(low|medium|high)>/data', function ($req, $res) {
    $level = $req->param('level');
    
    $securityMeasures = [
        'low' => ['Content Type Protection'],
        'medium' => ['Content Type Protection', 'Frame Protection'],
        'high' => ['Content Type Protection', 'Frame Protection', 'HSTS', 'CSP']
    ];
    
    return $res->json([
        'security_level' => $level,
        'applied_measures' => $securityMeasures[$level],
        'data' => "Dados protegidos com nível {$level}",
        'constraint_info' => [
            'level_pattern' => '(low|medium|high)',
            'security_escalation' => 'Cada nível adiciona mais proteções'
        ]
    ]);
}, [], $securityLevel);

// 🎯 Constraint complexo - Múltiplas validações
$app->get('/products/:category<(electronics|books|clothing)>/:id<\\d+>/:action<(view|edit|delete)>', function ($req, $res) {
    $category = $req->param('category');
    $id = (int) $req->param('id');
    $action = $req->param('action');
    
    // Validação de range específica por categoria
    $categoryLimits = [
        'electronics' => ['min' => 1000, 'max' => 9999],
        'books' => ['min' => 1, 'max' => 999],
        'clothing' => ['min' => 2000, 'max' => 2999]
    ];
    
    $limits = $categoryLimits[$category];
    if ($id < $limits['min'] || $id > $limits['max']) {
        return $res->status(400)->json([
            'error' => "ID para categoria '{$category}' deve estar entre {$limits['min']} e {$limits['max']}",
            'provided_id' => $id,
            'category_limits' => $categoryLimits
        ]);
    }
    
    // Simular produto
    $product = [
        'id' => $id,
        'category' => $category,
        'name' => "Produto {$id}",
        'action_requested' => $action
    ];
    
    return $res->json([
        'product' => $product,
        'constraint_info' => [
            'category_pattern' => '(electronics|books|clothing)',
            'id_pattern' => '\\d+',
            'action_pattern' => '(view|edit|delete)',
            'category_id_ranges' => $categoryLimits,
            'validation_passed' => true
        ]
    ]);
});

// 🚫 Demonstração de constraint que sempre falha
$app->get('/impossible/:never<impossible>', function ($req, $res) {
    // Esta rota nunca será alcançada devido ao constraint
    return $res->json(['message' => 'Nunca chegará aqui']);
});

// 📊 Rota de demonstração geral
$app->get('/demo/constraints', function ($req, $res) {
    return $res->json([
        'message' => 'Demonstração de todos os tipos de constraints',
        'constraint_examples' => [
            'Numeric Range' => '/users/123 (1-9999)',
            'Slug Format' => '/posts/meu-post-legal (a-z0-9-)',
            'Date Validation' => '/archive/2024-12-25 (valid date)',
            'Enum Values' => '/categories/technology (predefined list)',
            'API Versioning' => '/api/v2/users (versions 1-3)',
            'Platform Specific' => '/mobile/ios/app (ios|android|web)',
            'Security Levels' => '/secure/high/data (low|medium|high)',
            'Complex Multi' => '/products/electronics/1234/view'
        ],
        'benefits' => [
            'URL Validation',
            'Early Error Detection',
            'Clean 404 Responses',
            'Type Safety',
            'Business Rule Enforcement'
        ]
    ]);
});

$app->run();