# 🔄 Testes de Integração

Guia prático para testes de integração no PivotPHP - testando o fluxo completo da aplicação.

## 🎯 O que são Testes de Integração?

Testes de integração verificam se diferentes componentes da aplicação funcionam corretamente quando integrados:

- **API + Banco de Dados**
- **Middleware + Autenticação**
- **Controllers + Services**
- **Aplicação + Serviços externos**

## 🚀 Configuração para Testes de Integração

### 1. Banco de Dados de Teste

```php
<?php
// tests/IntegrationTestCase.php
abstract class IntegrationTestCase extends TestCase
{
    protected PDO $testDb;
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();
        $this->setupApplication();
        $this->seedDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    private function setupTestDatabase(): void
    {
        // Usar SQLite em memória para testes rápidos
        $this->testDb = new PDO('sqlite::memory:');
        $this->testDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Criar schema
        $this->createTables();
    }

    private function createTables(): void
    {
        $sql = "
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(255) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                published BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );

            CREATE TABLE access_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token VARCHAR(255) UNIQUE NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
        ";

        $this->testDb->exec($sql);
    }

    private function seedDatabase(): void
    {
        // Usuários de teste
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@test.com',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin'
            ],
            [
                'username' => 'user1',
                'email' => 'user1@test.com',
                'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'user'
            ]
        ];

        $stmt = $this->testDb->prepare(
            'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );

        foreach ($users as $user) {
            $stmt->execute(array_values($user));
        }

        // Posts de teste
        $posts = [
            [1, 'Primeiro Post', 'Conteúdo do primeiro post', 1],
            [1, 'Segundo Post', 'Conteúdo do segundo post', 0],
            [2, 'Post do Usuário', 'Conteúdo do usuário comum', 1]
        ];

        $stmt = $this->testDb->prepare(
            'INSERT INTO posts (user_id, title, content, published) VALUES (?, ?, ?, ?)'
        );

        foreach ($posts as $post) {
            $stmt->execute($post);
        }
    }

    private function cleanDatabase(): void
    {
        $tables = ['access_tokens', 'posts', 'users'];
        foreach ($tables as $table) {
            $this->testDb->exec("DELETE FROM {$table}");
        }
    }

    private function setupApplication(): void
    {
        $this->app = new Application();

        // Configurar dependências com banco de teste
        $this->app->singleton('db', function() {
            return $this->testDb;
        });

        // Configurar middlewares
        // (PivotPHP\Core\Middleware\Security\SecurityHeadersMiddleware,
        //  PivotPHP\Core\Middleware\Http\CorsMiddleware)
        $this->app->use(new SecurityHeadersMiddleware());
        $this->app->use(new CorsMiddleware());

        // Configurar rotas
        $this->setupRoutes();
    }

    private function setupRoutes(): void
    {
        // Rotas de autenticação
        $this->app->post('/auth/login', [AuthController::class, 'login']);
        $this->app->post('/auth/logout', [AuthController::class, 'logout']);

        // Rotas de usuários
        $this->app->group('/api/users', function($group) {
            $group->get('/', [UserController::class, 'index']);
            $group->get('/:id', [UserController::class, 'show']);
            $group->post('/', [UserController::class, 'create']);
            $group->put('/:id', [UserController::class, 'update']);
            $group->delete('/:id', [UserController::class, 'delete']);
        }, [new AuthMiddleware(['authMethods' => ['bearer']])]);

        // Rotas de posts
        $this->app->group('/api/posts', function($group) {
            $group->get('/', [PostController::class, 'index']);
            $group->get('/:id', [PostController::class, 'show']);
            $group->post('/', [PostController::class, 'create']);
            $group->put('/:id', [PostController::class, 'update']);
            $group->delete('/:id', [PostController::class, 'delete']);
        }, [new AuthMiddleware(['authMethods' => ['bearer']])]);
    }

    protected function authenticateUser(string $username): string
    {
        // Buscar usuário
        $stmt = $this->testDb->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new \Exception("User {$username} not found");
        }

        // Gerar token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        // Salvar token
        $stmt = $this->testDb->prepare(
            'INSERT INTO access_tokens (user_id, token, expires_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$user['id'], $token, $expiresAt]);

        return $token;
    }

    protected function makeAuthenticatedRequest(
        string $method,
        string $uri,
        array $data = [],
        string $username = 'user1'
    ): array {
        $token = $this->authenticateUser($username);

        $request = $this->createRequest($method, $uri, $data)
                        ->withHeader('Authorization', "Bearer {$token}");

        $response = $this->app->handle($request);

        return [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody()->getContents(), true),
            'headers' => $response->getHeaders()
        ];
    }
}
```

## 🧪 Exemplo: Teste Completo de CRUD

### Teste de API de Posts

```php
<?php
// tests/Integration/PostsApiTest.php
class PostsApiTest extends IntegrationTestCase
{
    public function test_fluxo_completo_crud_posts(): void
    {
        // 1. Listar posts (deve mostrar apenas publicados)
        $response = $this->makeAuthenticatedRequest('GET', '/api/posts');

        $this->assertEquals(200, $response['status']);
        $this->assertCount(2, $response['body']['posts']); // Apenas publicados

        // 2. Criar novo post
        $newPost = [
            'title' => 'Post de Integração',
            'content' => 'Conteúdo do teste de integração',
            'published' => true
        ];

        $response = $this->makeAuthenticatedRequest('POST', '/api/posts', $newPost);

        $this->assertEquals(201, $response['status']);
        $this->assertEquals('Post de Integração', $response['body']['post']['title']);
        $postId = $response['body']['post']['id'];

        // 3. Buscar post específico
        $response = $this->makeAuthenticatedRequest('GET', "/api/posts/{$postId}");

        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Post de Integração', $response['body']['post']['title']);

        // 4. Atualizar post
        $updateData = [
            'title' => 'Post Atualizado',
            'content' => 'Conteúdo atualizado'
        ];

        $response = $this->makeAuthenticatedRequest('PUT', "/api/posts/{$postId}", $updateData);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Post Atualizado', $response['body']['post']['title']);

        // 5. Verificar se foi atualizado no banco
        $stmt = $this->testDb->prepare('SELECT title, content FROM posts WHERE id = ?');
        $stmt->execute([$postId]);
        $dbPost = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('Post Atualizado', $dbPost['title']);
        $this->assertEquals('Conteúdo atualizado', $dbPost['content']);

        // 6. Deletar post
        $response = $this->makeAuthenticatedRequest('DELETE', "/api/posts/{$postId}");

        $this->assertEquals(204, $response['status']);

        // 7. Verificar se foi deletado
        $stmt = $this->testDb->prepare('SELECT COUNT(*) as count FROM posts WHERE id = ?');
        $stmt->execute([$postId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(0, $count['count']);
    }

    public function test_permissoes_de_acesso(): void
    {
        // Usuario comum não pode acessar posts de outros usuários
        $response = $this->makeAuthenticatedRequest('PUT', '/api/posts/1', [
            'title' => 'Tentativa de hack'
        ], 'user1');

        $this->assertEquals(403, $response['status']);
        $this->assertStringContains('permission', strtolower($response['body']['error']));

        // Admin pode acessar qualquer post
        $response = $this->makeAuthenticatedRequest('PUT', '/api/posts/3', [
            'title' => 'Admin pode editar'
        ], 'admin');

        $this->assertEquals(200, $response['status']);
    }

    public function test_validacao_de_dados(): void
    {
        // Tentar criar post sem título
        $invalidPost = [
            'content' => 'Conteúdo sem título'
        ];

        $response = $this->makeAuthenticatedRequest('POST', '/api/posts', $invalidPost);

        $this->assertEquals(400, $response['status']);
        $this->assertArrayHasKey('errors', $response['body']);
        $this->assertArrayHasKey('title', $response['body']['errors']);
    }
}
```

## 🔐 Teste de Autenticação Completa

### Sistema de Login/Logout

```php
<?php
// tests/Integration/AuthenticationTest.php
class AuthenticationTest extends IntegrationTestCase
{
    public function test_fluxo_completo_autenticacao(): void
    {
        // 1. Login com credenciais válidas
        $loginData = [
            'username' => 'user1',
            'password' => 'user123'
        ];

        $response = $this->makeRequest('POST', '/auth/login', $loginData);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('token', $response['body']);
        $this->assertArrayHasKey('user', $response['body']);

        $token = $response['body']['token'];

        // 2. Usar token para acessar rota protegida
        $response = $this->makeRequestWithAuth('GET', '/api/users', [], $token);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('users', $response['body']);

        // 3. Logout (invalidar token)
        $response = $this->makeRequestWithAuth('POST', '/auth/logout', [], $token);

        $this->assertEquals(200, $response['status']);

        // 4. Tentar usar token invalidado
        $response = $this->makeRequestWithAuth('GET', '/api/users', [], $token);

        $this->assertEquals(401, $response['status']);
    }

    public function test_tentativa_login_credenciais_invalidas(): void
    {
        $loginData = [
            'username' => 'user1',
            'password' => 'senha_errada'
        ];

        $response = $this->makeRequest('POST', '/auth/login', $loginData);

        $this->assertEquals(401, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('Invalid credentials', $response['body']['error']);
    }

    public function test_acesso_sem_autenticacao(): void
    {
        $response = $this->makeRequest('GET', '/api/users');

        $this->assertEquals(401, $response['status']);
        $this->assertStringContains('Authentication required', $response['body']['error']);
    }

    public function test_token_expirado(): void
    {
        // Criar token expirado diretamente no banco
        $expiredToken = bin2hex(random_bytes(32));
        $expiredDate = date('Y-m-d H:i:s', time() - 3600); // 1 hora atrás

        $stmt = $this->testDb->prepare(
            'INSERT INTO access_tokens (user_id, token, expires_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([1, $expiredToken, $expiredDate]);

        $response = $this->makeRequestWithAuth('GET', '/api/users', [], $expiredToken);

        $this->assertEquals(401, $response['status']);
    }
}
```

## 🌐 Teste de Middleware Stack

### Teste de Sequência de Middlewares

```php
<?php
// tests/Integration/MiddlewareStackTest.php
class MiddlewareStackTest extends IntegrationTestCase
{
    public function test_ordem_execucao_middlewares(): void
    {
        $executionOrder = [];

        // Middleware que registra ordem de execução
        $trackingMiddleware1 = new class($executionOrder) implements MiddlewareInterface {
            private array $order;

            public function __construct(array &$order) {
                $this->order = &$order;
            }

            public function process($request, $handler) {
                $this->order[] = 'middleware1_before';
                $response = $handler->handle($request);
                $this->order[] = 'middleware1_after';
                return $response;
            }
        };

        $trackingMiddleware2 = new class($executionOrder) implements MiddlewareInterface {
            private array $order;

            public function __construct(array &$order) {
                $this->order = &$order;
            }

            public function process($request, $handler) {
                $this->order[] = 'middleware2_before';
                $response = $handler->handle($request);
                $this->order[] = 'middleware2_after';
                return $response;
            }
        };

        $this->app->use($trackingMiddleware1);
        $this->app->use($trackingMiddleware2);

        $this->app->get('/test-order', function($req, $res) use (&$executionOrder) {
            $executionOrder[] = 'handler';
            $res->json(['message' => 'OK']);
        });

        $response = $this->makeRequest('GET', '/test-order');

        $this->assertEquals(200, $response['status']);

        // Verificar ordem correta: MW1 before -> MW2 before -> Handler -> MW2 after -> MW1 after
        $expectedOrder = [
            'middleware1_before',
            'middleware2_before',
            'handler',
            'middleware2_after',
            'middleware1_after'
        ];

        $this->assertEquals($expectedOrder, $executionOrder);
    }

    public function test_middleware_interrompe_cadeia(): void
    {
        $executed = [];

        // Middleware que bloqueia
        $blockingMiddleware = new class($executed) implements MiddlewareInterface {
            private array $executed;

            public function __construct(array &$executed) {
                $this->executed = &$executed;
            }

            public function process($request, $handler) {
                $this->executed[] = 'blocking_middleware';

                // Retornar resposta sem chamar próximo middleware
                return new Response(403, [], Stream::createFromString('{"error": "Blocked"}'));
            }
        };

        $this->app->use($blockingMiddleware);

        $this->app->get('/test-block', function($req, $res) use (&$executed) {
            $executed[] = 'handler'; // Não deve ser executado
            $res->json(['message' => 'OK']);
        });

        $response = $this->makeRequest('GET', '/test-block');

        $this->assertEquals(403, $response['status']);
        $this->assertEquals('Blocked', $response['body']['error']);

        // Handler não deve ter sido executado
        $this->assertEquals(['blocking_middleware'], $executed);
    }
}
```

## 📊 Teste de Performance

### Teste de Carga Básico

```php
<?php
// tests/Integration/PerformanceTest.php
class PerformanceTest extends IntegrationTestCase
{
    public function test_performance_multiplas_requisicoes(): void
    {
        $startTime = microtime(true);
        $requestCount = 100;

        for ($i = 0; $i < $requestCount; $i++) {
            $response = $this->makeAuthenticatedRequest('GET', '/api/posts');
            $this->assertEquals(200, $response['status']);
        }

        $totalTime = microtime(true) - $startTime;
        $averageTime = $totalTime / $requestCount;

        // Cada requisição deve levar menos que 50ms
        $this->assertLessThan(0.05, $averageTime,
            "Average request time too high: {$averageTime}s");
    }

    public function test_memoria_nao_vaza(): void
    {
        $initialMemory = memory_get_usage();

        for ($i = 0; $i < 50; $i++) {
            $this->makeAuthenticatedRequest('POST', '/api/posts', [
                'title' => "Post {$i}",
                'content' => "Content {$i}",
                'published' => true
            ]);
        }

        // Forçar garbage collection
        gc_collect_cycles();

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // Não deve consumir mais que 5MB
        $this->assertLessThan(5 * 1024 * 1024, $memoryIncrease,
            "Memory usage increased too much: " . ($memoryIncrease / 1024 / 1024) . "MB");
    }
}
```

## 🛠️ Helpers para Testes de Integração

### Utilitários Comuns

```php
<?php
// tests/Support/IntegrationHelpers.php
trait IntegrationHelpers
{
    protected function assertDatabaseHas(string $table, array $data): void
    {
        $conditions = [];
        $values = [];

        foreach ($data as $column => $value) {
            $conditions[] = "{$column} = ?";
            $values[] = $value;
        }

        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE " . implode(' AND ', $conditions);
        $stmt = $this->testDb->prepare($sql);
        $stmt->execute($values);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertGreaterThan(0, $result['count'],
            "Failed asserting that table {$table} contains matching record");
    }

    protected function assertDatabaseMissing(string $table, array $data): void
    {
        $conditions = [];
        $values = [];

        foreach ($data as $column => $value) {
            $conditions[] = "{$column} = ?";
            $values[] = $value;
        }

        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE " . implode(' AND ', $conditions);
        $stmt = $this->testDb->prepare($sql);
        $stmt->execute($values);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(0, $result['count'],
            "Failed asserting that table {$table} does not contain matching record");
    }

    protected function createUser(array $data = []): array
    {
        $userData = array_merge([
            'username' => 'testuser_' . uniqid(),
            'email' => 'test_' . uniqid() . '@email.com',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user'
        ], $data);

        $stmt = $this->testDb->prepare(
            'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute(array_values($userData));

        $userData['id'] = $this->testDb->lastInsertId();
        unset($userData['password_hash']);

        return $userData;
    }

    protected function refreshDatabase(): void
    {
        $this->cleanDatabase();
        $this->seedDatabase();
    }
}
```

## 💡 Dicas de Boas Práticas

### ✅ O que Fazer
- **Use banco em memória** (SQLite) para testes rápidos
- **Isole testes** - cada teste deve ser independente
- **Teste cenários reais** de uso da aplicação
- **Verifique estado do banco** após operações
- **Teste fluxos de erro** e casos extremos
- **Use transações** para rollback automático

### ❌ O que Evitar
- **Não use banco de produção** para testes
- **Não faça testes dependentes** da ordem de execução
- **Não ignore cleanup** entre testes
- **Não teste apenas casos de sucesso**

---

*🔄 Testes de integração garantem que sua aplicação funciona como um todo!*
