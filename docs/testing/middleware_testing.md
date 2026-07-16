# 🛡️ Testando Middlewares

Guia prático para testar middlewares no PivotPHP.

## 🧪 Estrutura de Teste de Middleware

### Teste Unitário de Middleware
```php
<?php
// tests/Unit/MiddlewareTest.php
use PivotPHP\Core\Middleware\Security\SecurityHeadersMiddleware;
use PHPUnit\Framework\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    private SecurityHeadersMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeadersMiddleware();
    }

    public function test_adiciona_headers_de_seguranca(): void
    {
        $request = $this->createMockRequest();
        $handler = $this->createMockHandler();

        $response = $this->middleware->process($request, $handler);

        // Verificar headers de segurança
        $this->assertTrue($response->hasHeader('X-Content-Type-Options'));
        $this->assertEquals('nosniff', $response->getHeaderLine('X-Content-Type-Options'));

        $this->assertTrue($response->hasHeader('X-Frame-Options'));
        $this->assertEquals('DENY', $response->getHeaderLine('X-Frame-Options'));

        $this->assertTrue($response->hasHeader('X-XSS-Protection'));
        $this->assertEquals('1; mode=block', $response->getHeaderLine('X-XSS-Protection'));
    }
}
```

## 🔐 Testando AuthMiddleware

### Teste de JWT
```php
<?php
class AuthMiddlewareTest extends TestCase
{
    private AuthMiddleware $middleware;
    private string $secret = 'test-secret-key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuthMiddleware([
            'authMethods' => ['jwt'],
            'jwtSecret' => $this->secret
        ]);
    }

    public function test_permite_requisicao_com_jwt_valido(): void
    {
        $token = $this->generateValidJWT();
        $request = $this->createMockRequest()
            ->withHeader('Authorization', "Bearer {$token}");

        $handler = $this->createMockHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        // Verificar se o usuário foi anexado ao request
        $this->assertNotNull($request->getAttribute('user'));
    }

    public function test_rejeita_requisicao_sem_token(): void
    {
        $request = $this->createMockRequest(); // Sem Authorization header
        $handler = $this->createMockHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Authentication required', $body['error']);
    }

    public function test_rejeita_token_invalido(): void
    {
        $request = $this->createMockRequest()
            ->withHeader('Authorization', 'Bearer token-invalido');

        $handler = $this->createMockHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_rejeita_token_expirado(): void
    {
        $expiredToken = $this->generateExpiredJWT();
        $request = $this->createMockRequest()
            ->withHeader('Authorization', "Bearer {$expiredToken}");

        $handler = $this->createMockHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
    }

    private function generateValidJWT(): string
    {
        $payload = [
            'user_id' => 123,
            'username' => 'testuser',
            'exp' => time() + 3600 // 1 hora
        ];

        return $this->encodeJWT($payload, $this->secret);
    }

    private function generateExpiredJWT(): string
    {
        $payload = [
            'user_id' => 123,
            'username' => 'testuser',
            'exp' => time() - 3600 // Expirado há 1 hora
        ];

        return $this->encodeJWT($payload, $this->secret);
    }
}
```

## 🚦 Testando RateLimitMiddleware

> ⚠️ `RateLimitMiddleware` está depreciado desde v2.1.0 (usa `$_SESSION`) — prefira testar
> `PivotPHP\Core\Middleware\RateLimiter` em código novo. As chaves de configuração reais de
> `RateLimitMiddleware` são `windowMs` (milissegundos) e `max` — não `limit`/`window` como em
> versões anteriores deste exemplo.

```php
<?php
use PivotPHP\Core\Middleware\Performance\RateLimitMiddleware;

class RateLimitMiddlewareTest extends TestCase
{
    public function test_permite_requisicoes_dentro_do_limite(): void
    {
        $middleware = new RateLimitMiddleware([
            'max' => 5,
            'windowMs' => 60000,
        ]);

        $request = $this->createMockRequest();
        $handler = $this->createMockHandler();

        // Fazer 5 requisições (dentro do limite)
        for ($i = 0; $i < 5; $i++) {
            $response = $middleware->process($request, $handler);
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    public function test_bloqueia_requisicoes_acima_do_limite(): void
    {
        $middleware = new RateLimitMiddleware([
            'max' => 3,
            'windowMs' => 60000,
        ]);

        $request = $this->createMockRequest();
        $handler = $this->createMockHandler();

        // Fazer 3 requisições (limite)
        for ($i = 0; $i < 3; $i++) {
            $response = $middleware->process($request, $handler);
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 4ª requisição deve ser bloqueada
        $response = $middleware->process($request, $handler);
        $this->assertEquals(429, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertStringContainsString('Too many requests', $body['error']);
    }
}
```

> O exemplo de headers `X-RateLimit-*` foi removido — confirme em
> `src/Middleware/Performance/RateLimitMiddleware.php` quais headers a versão instalada
> realmente emite antes de testar contra eles.

## ✅ Testando validação de dados (`Validator`)

> Não existe uma classe `ValidationMiddleware`. O framework fornece
> `PivotPHP\Core\Validation\Validator`, que não é um middleware PSR-15 — teste-a diretamente.

```php
<?php
use PivotPHP\Core\Validation\Validator;

class ValidatorTest extends TestCase
{
    public function test_valida_dados_obrigatorios(): void
    {
        $validator = new Validator([
            'name' => 'required',
            'email' => 'required|email',
        ]);

        $result = $validator->validate([
            'name' => 'João',
            'email' => 'joao@email.com',
        ]);

        $this->assertTrue($result);
        $this->assertEmpty($validator->getErrors());
    }

    public function test_rejeita_dados_invalidos(): void
    {
        $validator = new Validator([
            'name' => 'required',
            'email' => 'required|email',
        ]);

        $result = $validator->validate([
            'email' => 'email-invalido', // name ausente, email inválido
        ]);

        $this->assertFalse($result);
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }
}
```

## 🌐 Testando CorsMiddleware

```php
<?php
class CorsMiddlewareTest extends TestCase
{
    public function test_adiciona_headers_cors(): void
    {
        $middleware = new CorsMiddleware([
            'origins' => ['https://example.com'],
            'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'headers' => ['Content-Type', 'Authorization']
        ]);

        $request = $this->createMockRequest()
            ->withHeader('Origin', 'https://example.com');

        $handler = $this->createMockHandler();

        $response = $middleware->process($request, $handler);

        $this->assertTrue($response->hasHeader('Access-Control-Allow-Origin'));
        $this->assertEquals('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));

        $this->assertTrue($response->hasHeader('Access-Control-Allow-Methods'));
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Headers'));
    }

    public function test_rejeita_origem_nao_permitida(): void
    {
        $middleware = new CorsMiddleware([
            'origins' => ['https://allowed.com']
        ]);

        $request = $this->createMockRequest()
            ->withHeader('Origin', 'https://blocked.com');

        $handler = $this->createMockHandler();

        $response = $middleware->process($request, $handler);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_preflight_request(): void
    {
        $middleware = new CorsMiddleware();

        $request = $this->createMockRequest('OPTIONS', '/test')
            ->withHeader('Origin', 'https://example.com')
            ->withHeader('Access-Control-Request-Method', 'POST')
            ->withHeader('Access-Control-Request-Headers', 'Content-Type');

        $handler = $this->createMockHandler();

        $response = $middleware->process($request, $handler);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Origin'));
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Methods'));
    }
}
```

## 🛠️ Helpers para Testes

### Classe Base para Testes de Middleware
```php
<?php
// tests/MiddlewareTestCase.php
abstract class MiddlewareTestCase extends TestCase
{
    protected function createMockRequest(
        string $method = 'GET',
        string $uri = '/test',
        array $body = []
    ): ServerRequestInterface {
        $request = new ServerRequest($method, $uri);

        if (!empty($body)) {
            $stream = Stream::createFromString(json_encode($body));
            $request = $request->withBody($stream)
                             ->withHeader('Content-Type', 'application/json');
        }

        return $request;
    }

    protected function createMockHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], Stream::createFromString('{"success": true}'));
            }
        };
    }

    protected function encodeJWT(array $payload, string $secret): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
}
```

## 💡 Dicas de Boas Práticas

### ✅ O que Fazer
- **Teste cenários de sucesso e falha**
- **Verifique headers adicionados** pelo middleware
- **Teste configurações diferentes**
- **Mock dependências externas** (cache, banco)
- **Verifique se o request é passado adiante** quando apropriado

### ❌ O que Evitar
- **Não teste bibliotecas externas** (JWT libraries, etc.)
- **Não faça testes dependentes** da ordem de execução
- **Não ignore casos extremos** (headers ausentes, dados malformados)

---

*🛡️ Middlewares bem testados garantem a segurança e confiabilidade da sua API!*
