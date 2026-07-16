# 🔐 Autenticação Nativa do PivotPHP

Guia completo dos métodos de autenticação nativos do PivotPHP, incluindo JWT, Basic Auth, Bearer Token, API Key e configurações avançadas.

> ⚠️ **Nota de revisão:** os namespaces de import foram corrigidos (`AuthMiddleware` é
> `PivotPHP\Core\Middleware\Security\AuthMiddleware`, não `Http\Psr15\Middleware\...`). O
> **conteúdo das chaves de configuração abaixo não foi totalmente reauditado** — a
> implementação real de `AuthMiddleware::__construct(array $config = [], array $publicPaths = [])`
> (`src/Middleware/Security/AuthMiddleware.php`) só lê as chaves `authMethods`, `jwtSecret`,
> `basicAuthCallback`, `bearerAuthCallback`, `customAuthCallback` (mais `header`/`prefix`/`secret`
> usados internamente). Chaves como `jwtAlgorithm`, `excludePaths` (dentro de `$config` — o
> real é o **2º argumento posicional** do construtor), `jwtOptions`, `tokenLocation`,
> `headerName`, `headerPrefix`, `errorMessages`, `basicAuthRealm` usadas nos exemplos abaixo
> **não são lidas pela classe real** e são silenciosamente ignoradas. Confirme cada opção em
> `src/Middleware/Security/AuthMiddleware.php` antes de depender dela em produção.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [JWT Authentication](#jwt-authentication)
- [Basic Authentication](#basic-authentication)
- [Bearer Token](#bearer-token)
- [API Key Authentication](#api-key-authentication)
- [Autenticação Multi-método](#autenticação-multi-método)
- [Configurações Avançadas](#configurações-avançadas)
- [Segurança e Boas Práticas](#segurança-e-boas-práticas)
- [Testing](#testing)
- [Exemplos Práticos](#exemplos-práticos)

## 🔍 Visão Geral

O PivotPHP oferece um sistema de autenticação robusto e flexível que suporta múltiplos métodos de autenticação com detecção automática e configuração granular.

### Métodos Suportados

1. **JWT (JSON Web Token)** - Para aplicações modernas e SPAs
2. **Basic Authentication** - Para APIs simples e legacy
3. **Bearer Token** - Para tokens de acesso personalizados
4. **API Key** - Para integrações de sistemas

### Características Principais

- **Detecção Automática** - Identifica automaticamente o método baseado nos headers
- **Multi-método** - Suporte a múltiplos métodos simultâneos
- **Configuração Flexível** - Altamente configurável por método
- **Middleware Integrado** - Funciona perfeitamente com outros middlewares
- **PSR-15 Compliant** - Totalmente compatível com PSR-15

## 🎫 JWT Authentication

### Configuração Básica

```php
<?php

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Middleware\Security\AuthMiddleware;

$app = new Application();

// Configuração JWT básica
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => 'sua_chave_secreta_muito_segura_aqui',
    'jwtAlgorithm' => 'HS256',  // Padrão
    'excludePaths' => ['/login', '/register', '/public']
]));
```

### Configuração Avançada JWT

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => $_ENV['JWT_SECRET'],
    'jwtAlgorithm' => 'HS256',
    'jwtOptions' => [
        'issuer' => 'minha-aplicacao.com',
        'audience' => 'api.minha-aplicacao.com',
        'expiration' => 3600,  // 1 hora
        'not_before' => 0,     // Válido imediatamente
        'leeway' => 60         // 60 segundos de tolerância para clock skew
    ],
    'tokenLocation' => 'header',  // 'header', 'query', 'cookie'
    'headerName' => 'Authorization',
    'headerPrefix' => 'Bearer ',
    'errorMessages' => [
        'missing_token' => 'Token de acesso requerido',
        'invalid_token' => 'Token inválido ou expirado',
        'expired_token' => 'Token expirado'
    ]
]));
```

### Gerando Tokens JWT

```php
<?php

use PivotPHP\Core\Authentication\JWTHelper;

// Login endpoint que gera JWT
$app->post('/login', function($req, $res) {
    $credentials = $req->json();

    // Validar credenciais (implementar sua lógica)
    $user = validateUserCredentials($credentials['email'], $credentials['password']);

    if (!$user) {
        return $res->status(401)->json(['error' => 'Credenciais inválidas']);
    }

    // Payload do token
    $payload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'permissions' => $user['permissions'] ?? [],
        'iss' => 'minha-aplicacao.com',         // Issuer
        'aud' => 'api.minha-aplicacao.com',     // Audience
        'iat' => time(),                        // Issued At
        'exp' => time() + 3600,                 // Expiration (1 hora)
        'nbf' => time(),                        // Not Before
        'jti' => uniqid('jwt_', true)           // JWT ID
    ];

    $token = JWTHelper::encode($payload, $_ENV['JWT_SECRET']);

    return $res->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => 3600,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
});
```

### Usando Token JWT

```javascript
// Frontend JavaScript
const token = localStorage.getItem('access_token');

fetch('/api/protected', {
    method: 'GET',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

```php
// Rota protegida
$app->get('/api/protected', function($req, $res) {
    // Usuário já autenticado pelo middleware
    $user = $req->user;

    return $res->json([
        'message' => 'Acesso autorizado',
        'user_id' => $user['user_id'],
        'role' => $user['role']
    ]);
});
```

## 🔑 Basic Authentication

### Configuração Basic Auth

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['basic'],
    'basicAuthCallback' => function($username, $password) {
        // Implementar validação de usuário
        return validateUser($username, $password);
    },
    'basicAuthRealm' => 'API Restrita'
]));
```

### Callback de Validação

```php
// Validação com banco de dados
$app->use(new AuthMiddleware([
    'authMethods' => ['basic'],
    'basicAuthCallback' => function($username, $password) use ($db) {
        $user = $db->query(
            'SELECT * FROM users WHERE email = ? AND active = 1',
            [$username]
        )->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            return [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'permissions' => json_decode($user['permissions'] ?? '[]', true)
            ];
        }

        return false;
    }
]));
```

### Usando Basic Auth

```bash
# cURL com Basic Auth
curl -u "usuario@email.com:senha123" https://api.exemplo.com/protected

# Com header Authorization
curl -H "Authorization: Basic dXN1YXJpb0BlbWFpbC5jb206c2VuaGExMjM=" \
     https://api.exemplo.com/protected
```

```php
// Cliente PHP
$credentials = base64_encode("usuario@email.com:senha123");

$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic $credentials"
    ]
]);

$response = file_get_contents('https://api.exemplo.com/protected', false, $context);
```

## 🎟️ Bearer Token

### Configuração Bearer Token

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['bearer'],
    'bearerAuthCallback' => function($token) {
        // Validar token customizado
        return validateBearerToken($token);
    }
]));
```

### Callback de Validação Bearer

```php
function validateBearerToken($token) {
    // Exemplo com Redis para tokens de sessão
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);

    $sessionData = $redis->get("session:$token");

    if ($sessionData) {
        $session = json_decode($sessionData, true);

        // Verificar se não expirou
        if ($session['expires'] > time()) {
            return [
                'user_id' => $session['user_id'],
                'email' => $session['email'],
                'role' => $session['role'],
                'session_id' => $token
            ];
        }
    }

    return false;
}

$app->use(new AuthMiddleware([
    'authMethods' => ['bearer'],
    'bearerAuthCallback' => 'validateBearerToken'
]));
```

### Gerando Bearer Tokens

```php
$app->post('/session', function($req, $res) {
    $credentials = $req->json();
    $user = validateUserCredentials($credentials['email'], $credentials['password']);

    if (!$user) {
        return $res->status(401)->json(['error' => 'Credenciais inválidas']);
    }

    // Gerar token de sessão
    $sessionToken = bin2hex(random_bytes(32));

    // Armazenar no Redis
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);

    $sessionData = json_encode([
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'created' => time(),
        'expires' => time() + 7200  // 2 horas
    ]);

    $redis->setex("session:$sessionToken", 7200, $sessionData);

    return $res->json([
        'access_token' => $sessionToken,
        'token_type' => 'Bearer',
        'expires_in' => 7200
    ]);
});
```

## 🔐 API Key Authentication

### Configuração API Key

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['api_key'],
    'apiKeyCallback' => function($apiKey) {
        return validateApiKey($apiKey);
    },
    'apiKeyLocation' => 'header',  // 'header', 'query'
    'apiKeyHeaderName' => 'X-API-Key',
    'apiKeyQueryParam' => 'api_key'
]));
```

### Validação de API Key

```php
function validateApiKey($apiKey) {
    // Validação com banco de dados
    $db = new PDO('mysql:host=localhost;dbname=app', $user, $pass);

    $stmt = $db->prepare('
        SELECT ak.*, u.email, u.role
        FROM api_keys ak
        JOIN users u ON ak.user_id = u.id
        WHERE ak.key_hash = ? AND ak.active = 1 AND ak.expires_at > NOW()
    ');

    $stmt->execute([hash('sha256', $apiKey)]);
    $result = $stmt->fetch();

    if ($result) {
        // Atualizar último uso
        $updateStmt = $db->prepare('UPDATE api_keys SET last_used = NOW() WHERE id = ?');
        $updateStmt->execute([$result['id']]);

        return [
            'user_id' => $result['user_id'],
            'email' => $result['email'],
            'role' => $result['role'],
            'api_key_id' => $result['id'],
            'permissions' => json_decode($result['permissions'] ?? '[]', true)
        ];
    }

    return false;
}
```

### Usando API Key

```bash
# Header
curl -H "X-API-Key: sua_api_key_aqui" https://api.exemplo.com/data

# Query parameter
curl https://api.exemplo.com/data?api_key=sua_api_key_aqui
```

```php
// Cliente PHP
$apiKey = 'sua_api_key_aqui';

$context = stream_context_create([
    'http' => [
        'header' => "X-API-Key: $apiKey"
    ]
]);

$response = file_get_contents('https://api.exemplo.com/data', false, $context);
```

## 🔀 Autenticação Multi-método

### Configuração Multi-método

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'bearer', 'api_key'],
    'allowMultiple' => true,  // Permite múltiplos métodos
    'priority' => ['jwt', 'bearer', 'api_key', 'basic'],  // Ordem de prioridade

    // Configurações JWT
    'jwtSecret' => $_ENV['JWT_SECRET'],

    // Configurações Basic Auth
    'basicAuthCallback' => 'validateUser',

    // Configurações Bearer Token
    'bearerAuthCallback' => 'validateBearerToken',

    // Configurações API Key
    'apiKeyCallback' => 'validateApiKey',
    'apiKeyHeaderName' => 'X-API-Key'
]));
```

### Detecção Automática

O middleware detecta automaticamente o método baseado nos headers presentes:

```php
// JWT: Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
// Basic: Authorization: Basic dXNlcjpwYXNz
// Bearer: Authorization: Bearer custom_session_token
// API Key: X-API-Key: api_key_value
```

### Configuração por Rota

```php
// JWT apenas para rotas administrativas
$app->group('/admin', function() use ($app) {
    $app->use(new AuthMiddleware([
        'authMethods' => ['jwt'],
        'jwtSecret' => $_ENV['JWT_SECRET'],
        'requiredRole' => 'admin'
    ]));

    $app->get('/users', [AdminController::class, 'getUsers']);
});

// API Key para integrações externas
$app->group('/api/v1', function() use ($app) {
    $app->use(new AuthMiddleware([
        'authMethods' => ['api_key'],
        'apiKeyCallback' => 'validateApiKey'
    ]));

    $app->get('/data', [ApiController::class, 'getData']);
});

// Multi-método para rotas gerais
$app->group('/api', function() use ($app) {
    $app->use(new AuthMiddleware([
        'authMethods' => ['jwt', 'bearer'],
        'jwtSecret' => $_ENV['JWT_SECRET'],
        'bearerAuthCallback' => 'validateBearerToken'
    ]));

    $app->get('/profile', [UserController::class, 'getProfile']);
});
```

## ⚙️ Configurações Avançadas

### Exclusão de Rotas

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => $_ENV['JWT_SECRET'],
    'excludePaths' => [
        '/login',
        '/register',
        '/forgot-password',
        '/public/*',        // Wildcard
        '/api/health',
        '/api/status'
    ],
    'excludePatterns' => [
        '/^\/public\/.*/',  // Regex patterns
        '/^\/assets\/.*/',
        '/^\/docs\/.*/'
    ]
]));
```

### Controle de Roles e Permissões

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => $_ENV['JWT_SECRET'],
    'requiredRole' => 'user',  // Role mínima necessária
    'requiredPermissions' => ['read_data', 'write_data'],
    'roleHierarchy' => [
        'super_admin' => ['admin', 'moderator', 'user'],
        'admin' => ['moderator', 'user'],
        'moderator' => ['user'],
        'user' => []
    ]
]));
```

### Configuração de Erro Customizada

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => $_ENV['JWT_SECRET'],
    'onAuthFailure' => function($req, $res, $error) {
        // Log do erro de autenticação
        error_log("Auth failure: {$error['message']} for IP: {$req->ip()}");

        // Resposta customizada
        return $res->status(401)->json([
            'error' => 'Acesso negado',
            'message' => 'Token de autenticação inválido ou expirado',
            'code' => $error['code'],
            'timestamp' => date('c')
        ]);
    },
    'onAuthSuccess' => function($req, $user) {
        // Log de sucesso opcional
        error_log("Successful auth for user: {$user['email']}");

        // Adicionar dados extras ao usuário
        $user['last_login'] = date('c');
        return $user;
    }
]));
```

### Rate Limiting por Usuário

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => $_ENV['JWT_SECRET'],
    'rateLimiting' => [
        'enabled' => true,
        'maxRequests' => 1000,  // Por usuário
        'timeWindow' => 3600,   // 1 hora
        'keyGenerator' => function($user) {
            return "rate_limit:user:{$user['user_id']}";
        }
    ]
]));
```

## 🔒 Segurança e Boas Práticas

### 1. Segurança de Tokens JWT

```php
// ✅ Boas práticas para JWT
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => $_ENV['JWT_SECRET'],  // Use variáveis de ambiente
    'jwtAlgorithm' => 'HS256',          // Algoritmo seguro
    'jwtOptions' => [
        'expiration' => 900,             // 15 minutos (token curto)
        'refresh_threshold' => 300,      // Refresh quando restam 5 min
        'max_age' => 86400,             // Máximo 24 horas
        'issuer' => 'minha-app.com',
        'audience' => 'api.minha-app.com'
    ]
]));

// Implementar refresh token
$app->post('/refresh', function($req, $res) {
    $refreshToken = $req->json()['refresh_token'] ?? null;

    if (!$refreshToken || !validateRefreshToken($refreshToken)) {
        return $res->status(401)->json(['error' => 'Refresh token inválido']);
    }

    $user = getUserFromRefreshToken($refreshToken);
    $newAccessToken = generateAccessToken($user);

    return $res->json([
        'access_token' => $newAccessToken,
        'expires_in' => 900
    ]);
});
```

### 2. Rotação de API Keys

```php
// Sistema de rotação de API keys
class ApiKeyManager {
    public static function rotateKey($userId) {
        $db = new PDO(/* conexão */);

        // Desativar chave atual
        $db->prepare('UPDATE api_keys SET active = 0 WHERE user_id = ? AND active = 1')
           ->execute([$userId]);

        // Gerar nova chave
        $newKey = bin2hex(random_bytes(32));
        $keyHash = hash('sha256', $newKey);

        $db->prepare('
            INSERT INTO api_keys (user_id, key_hash, created_at, expires_at, active)
            VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), 1)
        ')->execute([$userId, $keyHash]);

        return $newKey;
    }
}
```

### 3. Auditoria de Autenticação

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => $_ENV['JWT_SECRET'],
    'auditCallback' => function($event, $data) {
        $auditLogger = new AuditLogger();

        $auditLogger->log([
            'event' => $event,
            'user_id' => $data['user']['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('c'),
            'additional_data' => $data
        ]);
    }
]));
```

## 🧪 Testing

### Teste de JWT

```php
<?php

namespace Tests\Auth;

use Tests\TestCase;
use PivotPHP\Core\Authentication\JWTHelper;

class JwtAuthTest extends TestCase
{
    private string $jwtSecret = 'test_secret_key';

    public function testValidJwtAuthentication(): void
    {
        // Configurar middleware
        $this->app->use(new AuthMiddleware([
            'authMethods' => ['jwt'],
            'jwtSecret' => $this->jwtSecret
        ]));

        $this->app->get('/protected', function($req, $res) {
            return $res->json(['user' => $req->user]);
        });

        // Gerar token válido
        $payload = ['user_id' => 1, 'email' => 'test@example.com'];
        $token = JWTHelper::encode($payload, $this->jwtSecret);

        // Fazer requisição com token
        $response = $this->get('/protected', [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.user_id', 1);
        $response->assertJsonPath('user.email', 'test@example.com');
    }

    public function testInvalidJwtReturns401(): void
    {
        $this->app->use(new AuthMiddleware([
            'authMethods' => ['jwt'],
            'jwtSecret' => $this->jwtSecret
        ]));

        $this->app->get('/protected', function($req, $res) {
            return $res->json(['protected' => true]);
        });

        // Token inválido
        $response = $this->get('/protected', [
            'Authorization' => 'Bearer invalid_token'
        ]);

        $response->assertStatus(401);
    }

    public function testExpiredJwtReturns401(): void
    {
        // Token expirado
        $payload = [
            'user_id' => 1,
            'exp' => time() - 3600  // Expirado há 1 hora
        ];
        $expiredToken = JWTHelper::encode($payload, $this->jwtSecret);

        $response = $this->get('/protected', [
            'Authorization' => "Bearer $expiredToken"
        ]);

        $response->assertStatus(401);
    }
}
```

### Teste de Multi-método

```php
class MultiMethodAuthTest extends TestCase
{
    public function testJwtAuthenticationWorks(): void
    {
        $this->setupMultiMethodAuth();

        $jwtToken = JWTHelper::encode(['user_id' => 1], 'secret');

        $response = $this->get('/api/data', [
            'Authorization' => "Bearer $jwtToken"
        ]);

        $response->assertStatus(200);
    }

    public function testApiKeyAuthenticationWorks(): void
    {
        $this->setupMultiMethodAuth();

        $response = $this->get('/api/data', [
            'X-API-Key' => 'valid_api_key'
        ]);

        $response->assertStatus(200);
    }

    private function setupMultiMethodAuth(): void
    {
        $this->app->use(new AuthMiddleware([
            'authMethods' => ['jwt', 'api_key'],
            'jwtSecret' => 'secret',
            'apiKeyCallback' => function($key) {
                return $key === 'valid_api_key' ? ['user_id' => 2] : false;
            }
        ]));

        $this->app->get('/api/data', function($req, $res) {
            return $res->json(['data' => 'protected']);
        });
    }
}
```

## 💡 Exemplos Práticos

### API SaaS Completa

```php
<?php

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Middleware\Security\AuthMiddleware;

$app = new Application();

// Middleware de autenticação para toda a API
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'api_key'],
    'excludePaths' => ['/auth/*', '/health', '/docs'],

    // JWT para usuários da aplicação
    'jwtSecret' => $_ENV['JWT_SECRET'],
    'jwtOptions' => [
        'issuer' => 'myapp.com',
        'audience' => 'api.myapp.com',
        'expiration' => 3600
    ],

    // API Key para integrações
    'apiKeyCallback' => function($apiKey) {
        return ApiKeyService::validate($apiKey);
    },
    'apiKeyHeaderName' => 'X-API-Key',

    // Auditoria
    'auditCallback' => function($event, $data) {
        AuditService::log($event, $data);
    }
]));

// Endpoints de autenticação (excluídos do middleware)
$app->post('/auth/login', function($req, $res) {
    $credentials = $req->json();

    $user = UserService::authenticate($credentials['email'], $credentials['password']);

    if (!$user) {
        return $res->status(401)->json(['error' => 'Credenciais inválidas']);
    }

    $token = JWTHelper::encode([
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'iss' => 'myapp.com',
        'aud' => 'api.myapp.com',
        'iat' => time(),
        'exp' => time() + 3600
    ], $_ENV['JWT_SECRET']);

    return $res->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => 3600,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
});

// Endpoints protegidos
$app->get('/api/profile', function($req, $res) {
    return $res->json(['user' => $req->user]);
});

$app->get('/api/data', function($req, $res) {
    $data = DataService::getUserData($req->user['user_id']);
    return $res->json(['data' => $data]);
});
```

### Sistema de Permissões

```php
// Middleware customizado para verificar permissões
class PermissionMiddleware extends BaseMiddleware
{
    private array $requiredPermissions;

    public function __construct(array $permissions)
    {
        $this->requiredPermissions = $permissions;
    }

    public function handle($request, $response, callable $next)
    {
        $user = $request->user ?? null;

        if (!$user) {
            return $response->status(401)->json(['error' => 'Não autenticado']);
        }

        $userPermissions = $user['permissions'] ?? [];

        foreach ($this->requiredPermissions as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return $response->status(403)->json([
                    'error' => 'Permissão insuficiente',
                    'required_permission' => $permission
                ]);
            }
        }

        return $next($request, $response);
    }
}

// Uso
$app->get('/admin/users',
    new PermissionMiddleware(['admin', 'user_management']),
    function($req, $res) {
        $users = UserService::getAllUsers();
        return $res->json(['users' => $users]);
    }
);
```

---

## 🔗 Links Relacionados

- [Autenticação Customizada](usage_custom.md) - Criando métodos de autenticação personalizados
- [AuthMiddleware](../middleware/AuthMiddleware.md) - Documentação completa do middleware
- [JWT Helper](../helpers/JWTHelper.md) - Utilitários para JWT
- [Security Best Practices](../../guides/security.md) - Práticas de segurança

## 📚 Recursos Adicionais

- **JWT Debugger**: https://jwt.io para debug de tokens
- **Security Headers**: Combinação com SecurityHeadersMiddleware
- **Rate Limiting**: Integração com RateLimitMiddleware
- **CORS**: Configuração com CorsMiddleware

Para dúvidas ou contribuições, consulte o [guia de contribuição](../../contributing/README.md).
