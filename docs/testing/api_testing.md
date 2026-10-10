# 🧪 Testando sua API

Guia prático para testar endpoints de API criados com PivotPHP.

## 🚀 Configuração Inicial

### Instalação do PHPUnit
```bash
composer require --dev phpunit/phpunit
```

### Estrutura de Testes
```
tests/
├── Feature/           # Testes de funcionalidades completas
│   ├── AuthTest.php
│   └── UsersApiTest.php
├── Unit/              # Testes unitários
│   ├── MiddlewareTest.php
│   └── RouterTest.php
└── TestCase.php       # Classe base para testes
```

## 📝 Exemplo Prático: Testando uma API de Usuários

### 1. Criando a Base de Teste
```php
<?php
// tests/TestCase.php
use PivotPHP\Core\Core\Application;
use PivotPHP\Http\Factory\Psr17Factory;
use PivotPHP\Security\Cors\{CorsConfig, CorsMiddleware};
use PivotPHP\Security\Headers\SecurityHeadersMiddleware;
use PivotPHP\Security\Jwt\{JwtAuthMiddleware, JwtConfig, JwtIssuer};
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application();
        $this->configureApp();
    }

    protected function configureApp(): void
    {
        // Configuração padrão para testes
        $this->app->use(new SecurityHeadersMiddleware());
        $this->app->use(new CorsMiddleware(new Psr17Factory(), new CorsConfig(['https://app.test'])));
    }

    protected function makeRequest(string $method, string $uri, array $data = []): array
    {
        // Helper para fazer requisições de teste
        $request = $this->createMockRequest($method, $uri, $data);
        $response = $this->app->handle($request);

        return [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody()->getContents(), true),
            'headers' => $response->getHeaders()
        ];
    }
}
```

### 2. Testando Endpoints
```php
<?php
// tests/Feature/UsersApiTest.php
class UsersApiTest extends TestCase
{
    public function test_pode_listar_usuarios(): void
    {
        // Configurar rota
        $this->app->get('/api/users', function($req, $res) {
            $res->json([
                'users' => [
                    ['id' => 1, 'name' => 'João'],
                    ['id' => 2, 'name' => 'Maria']
                ]
            ]);
        });

        // Fazer requisição
        $response = $this->makeRequest('GET', '/api/users');

        // Verificar resultado
        $this->assertEquals(200, $response['status']);
        $this->assertCount(2, $response['body']['users']);
        $this->assertEquals('João', $response['body']['users'][0]['name']);
    }

    public function test_pode_criar_usuario(): void
    {
        $this->app->post('/api/users', function($req, $res) {
            $userData = $req->psr7()->getParsedBody();

            // Simular criação no banco
            $user = [
                'id' => 3,
                'name' => $userData['name'],
                'email' => $userData['email']
            ];

            $res->status(201)->json(['user' => $user]);
        });

        $userData = [
            'name' => 'Pedro',
            'email' => 'pedro@email.com'
        ];

        $response = $this->makeRequest('POST', '/api/users', $userData);

        $this->assertEquals(201, $response['status']);
        $this->assertEquals('Pedro', $response['body']['user']['name']);
        $this->assertEquals('pedro@email.com', $response['body']['user']['email']);
    }

    public function test_valida_dados_obrigatorios(): void
    {
        $this->app->post('/api/users', function($req, $res) {
            if (empty($req->input('name'))) {
                $res->status(400)->json(['error' => 'Nome é obrigatório']);
                return;
            }

            $res->status(201)->json(['success' => true]);
        });

        $response = $this->makeRequest('POST', '/api/users', []);

        $this->assertEquals(400, $response['status']);
        $this->assertEquals('Nome é obrigatório', $response['body']['error']);
    }
}
```

## 🔐 Testando Autenticação

### Teste de JWT
```php
public function test_rota_protegida_requer_autenticacao(): void
{
    // pivotphp/security: segredo HS256 com ≥ 32 bytes
    $config = new JwtConfig('a-32-bytes-long-secret-for-tests');
    $this->app->use(new JwtAuthMiddleware(new Psr17Factory(), $config));

    $this->app->get('/api/protected', function($req, $res) {
        $res->json(['message' => 'Rota protegida', 'user' => $req->psr7()->getAttribute('user')]);
    });

    // Sem token
    $response = $this->makeRequest('GET', '/api/protected');
    $this->assertEquals(401, $response['status']);

    // Com token válido
    $token = (new JwtIssuer($config))->issue(['user_id' => 1], 60);
    $response = $this->makeRequestWithAuth('GET', '/api/protected', [], $token);
    $this->assertEquals(200, $response['status']);
}
```

## 🧪 Comandos Úteis

### Executar Todos os Testes
```bash
composer test
# ou
./vendor/bin/phpunit
```

### Executar Testes Específicos
```bash
# Testar apenas uma classe
./vendor/bin/phpunit tests/Feature/UsersApiTest.php

# Testar apenas um método
./vendor/bin/phpunit --filter test_pode_criar_usuario
```

### Cobertura de Código
```bash
./vendor/bin/phpunit --coverage-html coverage/
```

## 💡 Dicas de Boas Práticas

### ✅ O que Fazer
- **Teste comportamentos, não implementação**
- **Use nomes descritivos** para os métodos de teste
- **Organize testes por funcionalidade**
- **Mock dependências externas** (banco, APIs)
- **Teste casos de erro** além dos casos de sucesso

### ❌ O que Evitar
- **Não teste frameworks externos** (PHPUnit, PivotPHP internals)
- **Não faça testes dependentes** uns dos outros
- **Não ignore casos extremos** (dados vazios, nulos, etc.)

## 🔄 Exemplo Completo: API CRUD

```php
class CrudApiTest extends TestCase
{
    private array $mockUsers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupRoutes();
        $this->mockUsers = [
            1 => ['id' => 1, 'name' => 'João', 'email' => 'joao@email.com'],
            2 => ['id' => 2, 'name' => 'Maria', 'email' => 'maria@email.com']
        ];
    }

    private function setupRoutes(): void
    {
        // GET /users - Listar
        $this->app->get('/users', function($req, $res) {
            $res->json(['users' => array_values($this->mockUsers)]);
        });

        // GET /users/:id - Buscar
        $this->app->get('/users/:id', function($req, $res) {
            $id = (int) $req->params['id'];
            if (!isset($this->mockUsers[$id])) {
                $res->status(404)->json(['error' => 'Usuário não encontrado']);
                return;
            }
            $res->json(['user' => $this->mockUsers[$id]]);
        });

        // POST /users - Criar
        $this->app->post('/users', function($req, $res) {
            $data = $req->psr7()->getParsedBody();
            $id = count($this->mockUsers) + 1;
            $user = array_merge(['id' => $id], $data);
            $this->mockUsers[$id] = $user;
            $res->status(201)->json(['user' => $user]);
        });

        // PUT /users/:id - Atualizar
        $this->app->put('/users/:id', function($req, $res) {
            $id = (int) $req->params['id'];
            if (!isset($this->mockUsers[$id])) {
                $res->status(404)->json(['error' => 'Usuário não encontrado']);
                return;
            }
            $this->mockUsers[$id] = array_merge($this->mockUsers[$id], $req->psr7()->getParsedBody());
            $res->json(['user' => $this->mockUsers[$id]]);
        });

        // DELETE /users/:id - Deletar
        $this->app->delete('/users/:id', function($req, $res) {
            $id = (int) $req->params['id'];
            if (!isset($this->mockUsers[$id])) {
                $res->status(404)->json(['error' => 'Usuário não encontrado']);
                return;
            }
            unset($this->mockUsers[$id]);
            $res->status(204)->send();
        });
    }

    public function test_crud_completo(): void
    {
        // CREATE
        $newUser = ['name' => 'Pedro', 'email' => 'pedro@email.com'];
        $response = $this->makeRequest('POST', '/users', $newUser);
        $this->assertEquals(201, $response['status']);
        $userId = $response['body']['user']['id'];

        // READ
        $response = $this->makeRequest('GET', "/users/{$userId}");
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Pedro', $response['body']['user']['name']);

        // UPDATE
        $updateData = ['name' => 'Pedro Silva'];
        $response = $this->makeRequest('PUT', "/users/{$userId}", $updateData);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Pedro Silva', $response['body']['user']['name']);

        // DELETE
        $response = $this->makeRequest('DELETE', "/users/{$userId}");
        $this->assertEquals(204, $response['status']);

        // Verificar se foi deletado
        $response = $this->makeRequest('GET', "/users/{$userId}");
        $this->assertEquals(404, $response['status']);
    }
}
```

---

*📝 Esta documentação mostra como testar APIs de forma prática e eficiente com PivotPHP!*
