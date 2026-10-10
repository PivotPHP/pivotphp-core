# 🛡️ Testando Middlewares

Guia prático para testar middlewares no PivotPHP.

## 🧪 Estrutura de Teste de Middleware

### Teste Unitário de Middleware
```php
<?php
// tests/Unit/MiddlewareTest.php
use PivotPHP\Security\Headers\SecurityHeadersMiddleware; // pivotphp/security
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

        // X-XSS-Protection (obsoleto) não é enviado
        $this->assertFalse($response->hasHeader('X-XSS-Protection'));
    }
}
```

## 🔐 Testando autenticação e CORS

`AuthMiddleware` e `CorsMiddleware` do core foram removidos na v4.0.0. Os middlewares equivalentes
(`JwtAuthMiddleware`, `CorsMiddleware`, `CsrfMiddleware`...) vivem no pacote `pivotphp/security`, que
mantém os próprios testes unitários. Na aplicação, teste a **integração** — a pipeline com as suas
rotas:

```php
use Nyholm\Psr7\ServerRequest;
use PivotPHP\Http\Factory\Psr17Factory;
use PivotPHP\Security\Jwt\{JwtAuthMiddleware, JwtConfig, JwtIssuer};

$config = new JwtConfig($_ENV['JWT_SECRET'], publicPaths: ['/health']);
$app->use(new JwtAuthMiddleware(new Psr17Factory(), $config));

$token = (new JwtIssuer($config))->issue(['sub' => 'u1'], 60);

$ok = $app->handle(new ServerRequest('GET', '/me', ['Authorization' => "Bearer {$token}"]));
$this->assertSame(200, $ok->getStatusCode());

$denied = $app->handle(new ServerRequest('GET', '/me'));
$this->assertSame(401, $denied->getStatusCode());
```

## 🚦 Rate limiting

O core não inclui mais middleware de rate limiting (removido na v4.0.0 por falha de segurança em
PHP-FPM — veja [RateLimitMiddleware.md](../technical/middleware/RateLimitMiddleware.md)). Os testes do
`RateLimitMiddleware` ficam no pacote `pivotphp/security`.

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
