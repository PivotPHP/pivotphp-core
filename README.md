# PivotPHP Microframework

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Latest Stable Version](https://poser.pugx.org/pivotphp/core/v/stable)](https://packagist.org/packages/pivotphp/core)
[![Total Downloads](https://poser.pugx.org/pivotphp/core/downloads)](https://packagist.org/packages/pivotphp/core)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%209-brightgreen.svg)](https://phpstan.org/)
[![PSR-12](https://img.shields.io/badge/PSR--12%20%2F%20PSR--15-compliant-brightgreen)](https://www.php-fig.org/psr/psr-12/)
[![GitHub Issues](https://img.shields.io/github/issues/PivotPHP/pivotphp-core)](https://github.com/PivotPHP/pivotphp-core/issues)
[![GitHub Stars](https://img.shields.io/github/stars/PivotPHP/pivotphp-core)](https://github.com/PivotPHP/pivotphp-core/stargazers)

---

## 🚀 O que é o PivotPHP?

**PivotPHP** é um microframework moderno, leve e seguro, inspirado no Express.js, **especialmente projetado para provas de conceito, prototipagem rápida e estudos**. Oferece uma API familiar e produtiva para validar ideias rapidamente, com arquitetura desacoplada e extensibilidade real quando necessário.

- **⚡ Setup Instantâneo**: API funcionando em menos de 5 minutos, perfeito para validar ideias rapidamente.
- **🎯 Foco em Produtividade**: Sintaxe familiar (Express.js) que acelera o desenvolvimento de protótipos.
- **📚 Documentação Automática**: Geração automática de OpenAPI/Swagger - essencial para apresentar provas de conceito.
- **🛡️ Segurança Integrada**: Middlewares prontos para CSRF, XSS, JWT - protótipos seguros desde o início.
- **🔧 Extensibilidade Simples**: Sistema de plugins e providers para expandir funcionalidades conforme necessário.
- **📊 Performance Adequada**: Throughput de 44,092 ops/sec, footprint de 1.61MB - suficiente para demonstrações.
- **🎨 v2.0.0**: Legacy Cleanup Edition - 18% code reduction, modern namespaces, routing externalized, zero deprecated code.

---

## ✨ Principais Recursos

- 🏗️ **DI Container & Providers**
- 🎪 **Event System**
- 🧩 **Sistema de Extensões**
- 🔧 **Configuração flexível**
- 🔐 **Autenticação Multi-método**
- 🛡️ **Segurança Avançada**
- 📡 **Streaming & SSE**
- 📚 **OpenAPI/Swagger Automático** (v2.0.0 Middleware)
- 🔄 **PSR-7 Híbrido**
- ♻️ **Object Pooling**
- 🚀 **JSON Optimization** (Intelligent Caching)
- 🎯 **Array Callables** (Native Support)
- 🔍 **Enhanced Error Diagnostics**
- ⚡ **Performance Extrema**
- 🧪 **Qualidade e Testes**
- 🎯 **Simplicidade sobre Otimização**
- 🧹 **v2.0.0 Legacy Cleanup** (18% code reduction)
- 🔌 **Modular Routing** (External package, pluggable in v2.1.0)

---

## 💡 Casos de Uso Ideais

- **🔬 Provas de Conceito**: Validar ideias de API rapidamente com setup mínimo
- **🎯 Prototipagem Rápida**: Demonstrar funcionalidades para stakeholders e clientes
- **📚 Estudos e Aprendizado**: Compreender arquiteturas de microframework e PSR standards
- **🧪 Testes de Integração**: Criar APIs mock para testar integrações frontend/mobile
- **🎨 MVPs Educacionais**: Projetos acadêmicos e de portfólio com qualidade profissional
- **🔗 APIs Bridge**: Conectar sistemas legacy com interfaces modernas

**Ideal para:** Desenvolvedores que precisam validar conceitos rapidamente sem a complexidade de frameworks enterprise.

Veja exemplos práticos em [`examples/`](examples/) e [documentação técnica completa](docs/).

---

## 🚀 Início Rápido

### Instalação

```bash
composer require pivotphp/core
```

### Exemplo Básico

```php
<?php
require_once 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Psr15\Middleware\{SecurityMiddleware, CorsMiddleware, AuthMiddleware};

$app = new Application();

// Middlewares de segurança (PSR-15)
$app->use(new SecurityMiddleware());
$app->use(new CorsMiddleware());
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => 'sua_chave_secreta'
]));

// API RESTful
$app->get('/api/users', function($req, $res) {
    $res->json(['users' => $userService->getAll()]);
});

$app->post('/api/users', function($req, $res) {
    $user = $userService->create($req->body);
    $res->status(201)->json(['user' => $user]);
});

// Rotas com validação regex
$app->get('/api/users/:id<\d+>', function($req, $res) {
    // Aceita apenas IDs numéricos
    $res->json(['user_id' => $req->param('id')]);
});

$app->get('/posts/:year<\d{4}>/:month<\d{2}>/:slug<slug>', function($req, $res) {
    // Validação de data e slug na rota
    $res->json([
        'year' => $req->param('year'),
        'month' => $req->param('month'),
        'slug' => $req->param('slug')
    ]);
});

$app->run();
```

### 🛣️ Sintaxes de Roteamento Suportadas

O PivotPHP oferece suporte robusto para múltiplas sintaxes de roteamento:

#### ✅ Sintaxes Suportadas

```php
// 1. Closure/Função Anônima (Recomendado para APIs simples)
$app->get('/users', function($req, $res) {
    return $res->json(['users' => User::all()]);
});

// 2. Array Callable (Recomendado para Controllers)
$app->get('/users', [UserController::class, 'index']);           // Método estático/instância
$app->post('/users', [$userController, 'store']);                // Instância específica
$app->get('/users/:id<\d+>', [UserController::class, 'show']);   // Com validação regex

// 3. Função nomeada (Para helpers simples)
function getUsersHandler($req, $res) {
    return $res->json(['users' => User::all()]);
}
$app->get('/users', 'getUsersHandler');
```

#### ❌ Sintaxes NÃO Suportadas

```php
// ❌ String Controller@method - NÃO FUNCIONA!
$app->get('/users', 'UserController@index'); // TypeError!

// ❌ Brace syntax - Use colon syntax
$app->get('/users/{id}', [Controller::class, 'show']); // Erro - use :id

// ✅ CORRETO: Use colon syntax
$app->get('/users/:id', [Controller::class, 'show']);
```

#### 🎯 Exemplo Completo com Controller

```php
<?php

namespace App\Controllers;

class UserController
{
    // ✅ Métodos devem ser PÚBLICOS
    public function index($req, $res)
    {
        $users = User::paginate($req->query('limit', 10));
        return $res->json(['users' => $users]);
    }

    public function show($req, $res)
    {
        $id = $req->param('id');
        $user = User::find($id);

        if (!$user) {
            return $res->status(404)->json(['error' => 'User not found']);
        }

        return $res->json(['user' => $user]);
    }

    public function store($req, $res)
    {
        $data = $req->body();
        $user = User::create($data);

        return $res->status(201)->json(['user' => $user]);
    }
}

// ✅ Registrar rotas com array callable
$app->get('/users', [UserController::class, 'index']);
$app->get('/users/:id<\d+>', [UserController::class, 'show']);    // Apenas números
$app->post('/users', [UserController::class, 'store']);

// ✅ Com middleware
$app->put('/users/:id', [UserController::class, 'update'])
    ->middleware($authMiddleware);
```

#### ⚡ Validação Automática

```php
// O PivotPHP valida automaticamente array callables:

// ✅ Método público - ACEITO
class PublicController {
    public function handle($req, $res) { return $res->json(['ok' => true]); }
}

// ❌ Método privado - REJEITADO com erro descritivo
class PrivateController {
    private function handle($req, $res) { return $res->json(['ok' => true]); }
}

$app->get('/public', [PublicController::class, 'handle']);   // ✅ Funciona
$app->get('/private', [PrivateController::class, 'handle']); // ❌ Erro claro

// Erro: "Route handler validation failed: Method 'handle' is not accessible"
```

📖 **Documentação completa:** [Array Callable Guide](docs/technical/routing/ARRAY_CALLABLE_GUIDE.md)

### 🔄 Suporte PSR-7 Híbrido

O PivotPHP oferece **compatibilidade híbrida** com PSR-7, mantendo a facilidade da API Express.js enquanto implementa completamente as interfaces PSR-7:

```php
// API Express.js (familiar e produtiva)
$app->get('/api/users', function($req, $res) {
    $id = $req->param('id');
    $name = $req->input('name');
    return $res->json(['user' => $userService->find($id)]);
});

// PSR-7 nativo (para middleware PSR-15)
$app->use(function(ServerRequestInterface $request, ResponseInterface $response, $next) {
    $method = $request->getMethod();
    $uri = $request->getUri();
    $newRequest = $request->withAttribute('processed', true);
    return $next($newRequest, $response);
});

// Lazy loading e Object Pooling automático
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;

OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'warm_up_pools' => true,
    'max_pool_size' => 100,
]);

// Objetos PSR-7 são reutilizados automaticamente
$request = OptimizedHttpFactory::createRequest('GET', '/api/users', '/api/users');
$response = OptimizedHttpFactory::createResponse();
```

**Benefícios da Implementação Híbrida:**
- ✅ **100% compatível** com middleware PSR-15
- ✅ **Imutabilidade** respeitada nos métodos `with*()`
- ✅ **Lazy loading** - objetos PSR-7 criados apenas quando necessário
- ✅ **Object pooling** - reutilização inteligente para melhor performance
- ✅ **API Express.js** mantida para produtividade
- ✅ **Zero breaking changes** - código existente funciona sem alterações

### 🚀 JSON Optimization (Intelligent System)

O PivotPHP mantém o **threshold inteligente de 256 bytes** no sistema de otimização JSON, eliminando overhead para dados pequenos:

#### ⚡ Sistema Inteligente Automático

```php
// ✅ OTIMIZAÇÃO AUTOMÁTICA - Zero configuração necessária
$app->get('/api/users', function($req, $res) {
    $users = User::all();

    // Sistema decide automaticamente:
    // • Poucos usuários (<256 bytes): json_encode() direto
    // • Muitos usuários (≥256 bytes): pooling automático
    return $res->json($users); // Sempre otimizado!
});
```

#### 🎯 Performance por Tamanho de Dados

```php
// Dados pequenos (<256 bytes) - json_encode() direto
$smallData = ['status' => 'ok', 'count' => 42];
$json = JsonBufferPool::encodeWithPool($smallData);
// Performance: 500K+ ops/sec (sem overhead)

// Dados médios (256 bytes - 10KB) - pooling automático
$mediumData = User::paginate(20);
$json = JsonBufferPool::encodeWithPool($mediumData);
// Performance: 119K+ ops/sec (15-30% ganho)

// Dados grandes (>10KB) - pooling otimizado
$largeData = Report::getAllWithRelations();
$json = JsonBufferPool::encodeWithPool($largeData);
// Performance: 214K+ ops/sec (98%+ ganho)
```

#### 🔧 Configuração Avançada (Opcional)

```php
use PivotPHP\Core\Json\Pool\JsonBufferPool;

// Personalizar threshold (padrão: 256 bytes)
JsonBufferPool::configure([
    'threshold_bytes' => 512,      // Usar pool apenas para dados >512 bytes
    'max_pool_size' => 200,        // Máximo 200 buffers
    'default_capacity' => 8192,    // Buffers de 8KB
]);

// Verificar se threshold será aplicado
if (JsonBufferPool::shouldUsePooling($data)) {
    echo "Pool será usado (dados grandes)\n";
} else {
    echo "json_encode() direto (dados pequenos)\n";
}

// Monitoramento em tempo real
$stats = JsonBufferPool::getStatistics();
echo "Eficiência: {$stats['efficiency']}%\n";
echo "Operações: {$stats['total_operations']}\n";
```

#### ✨ Mantido v2.0.0

- ✅ **Threshold Inteligente** - Elimina overhead para dados <256 bytes
- ✅ **Detecção Automática** - Sistema decide quando usar pooling
- ✅ **Zero Configuração** - Funciona perfeitamente out-of-the-box
- ✅ **Performance Garantida** - Nunca mais lento que json_encode()
- ✅ **Monitoramento Integrado** - Estatísticas em tempo real
- ✅ **Compatibilidade Total** - Drop-in replacement transparente

### 🔍 Enhanced Error Diagnostics

PivotPHP v1.2.0 mantém **ContextualException** para diagnósticos avançados de erros:

#### ⚡ Sistema de Erro Inteligente

```php
use PivotPHP\Core\Exceptions\ContextualException;

// Captura automática de contexto e sugestões
try {
    $app->get('/users/:id', [Controller::class, 'privateMethod']);
} catch (ContextualException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Contexto: " . json_encode($e->getContext()) . "\n";
    echo "Sugestão: " . $e->getSuggestion() . "\n";
    echo "Categoria: " . $e->getCategory() . "\n";
}

// Output example:
// Erro: Route handler validation failed
// Contexto: {"method":"privateMethod","class":"Controller","visibility":"private"}
// Sugestão: Make the method public or use a public method instead
// Categoria: ROUTING
```

#### 🎯 Categorias de Erro Disponíveis

```php
// Automaticamente detectadas pelo sistema
ContextualException::CATEGORY_ROUTING      // Problemas de roteamento
ContextualException::CATEGORY_PARAMETER    // Validação de parâmetros
ContextualException::CATEGORY_VALIDATION   // Validação de dados
ContextualException::CATEGORY_MIDDLEWARE   // Problemas de middleware
ContextualException::CATEGORY_HTTP         // Erros HTTP
ContextualException::CATEGORY_SECURITY     // Questões de segurança
ContextualException::CATEGORY_PERFORMANCE  // Problemas de performance
```

#### 🔧 Configuração de Ambiente

```php
// Desenvolvimento - máximo de informações
ContextualException::setEnvironment('development');

// Produção - informações limitadas por segurança
ContextualException::setEnvironment('production');

// Personalizada
ContextualException::configure([
    'show_suggestions' => true,
    'show_context' => false,
    'log_errors' => true,
    'max_context_size' => 1024
]);
```

#### ✨ Recursos v1.2.0

- ✅ **Erro IDs Únicos** - Rastreamento facilitado para debugging
- ✅ **Sugestões Inteligentes** - Orientações específicas para resolver problemas
- ✅ **Contexto Rico** - Informações detalhadas sobre o estado quando o erro ocorreu
- ✅ **Categorização Automática** - Classificação inteligente do tipo de erro
- ✅ **Segurança por Ambiente** - Detalhes reduzidos em produção
- ✅ **Logging Integrado** - Registro automático para análise posterior

📖 **Documentação completa:**
- [Array Callable Guide](docs/technical/routing/ARRAY_CALLABLE_GUIDE.md)
- [JsonBufferPool Optimization Guide](docs/technical/json/BUFFER_POOL_OPTIMIZATION.md)
- [Enhanced Error Diagnostics](docs/technical/error-handling/CONTEXTUAL_EXCEPTION_GUIDE.md)

### 📖 Documentação OpenAPI/Swagger Automática (v1.2.0+)

O PivotPHP v1.2.0+ inclui **middleware automático** para geração de documentação OpenAPI/Swagger:

```php
use PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware;

// ✅ NOVO v1.2.0+: Documentação automática em 3 linhas!
$app = new Application();

// Adicionar middleware de documentação automática
$app->use(new ApiDocumentationMiddleware([
    'docs_path' => '/docs',        // Endpoint JSON OpenAPI
    'swagger_path' => '/swagger',  // Interface Swagger UI
    'base_url' => 'http://localhost:8080'
]));

// Suas rotas com documentação PHPDoc
$app->get('/users', function($req, $res) {
    /**
     * @summary List all users
     * @description Returns a list of all users in the system
     * @tags Users
     * @response 200 array List of users
     */
    return $res->json(['users' => User::all()]);
});

$app->get('/users/:id', function($req, $res) {
    /**
     * @summary Get user by ID
     * @description Returns a single user by their ID
     * @tags Users
     * @param int id User ID
     * @response 200 object User object
     * @response 404 object User not found
     */
    $userId = $req->param('id');
    return $res->json(['user' => User::find($userId)]);
});

// Pronto! Acesse:
// http://localhost:8080/swagger - Interface Swagger UI completa
// http://localhost:8080/docs    - JSON OpenAPI 3.0.0
```

#### 🎯 Recursos do Middleware de Documentação

- ✅ **Geração automática** de OpenAPI 3.0.0 de todas as rotas
- ✅ **Interface Swagger UI** integrada (zero configuração)
- ✅ **Parsing de PHPDoc** para metadados das rotas
- ✅ **Endpoints automáticos** `/docs` e `/swagger`
- ✅ **Configuração flexível** de paths e URLs
- ✅ **Zero dependências** externas
- ✅ **Compatibilidade total** com todas as rotas

#### 📝 Exemplo Completo

Veja o exemplo funcional em [`examples/api_documentation_example.php`](examples/api_documentation_example.php):

```bash
# Rodar o exemplo
php examples/api_documentation_example.php

# Acessar documentação
open http://localhost:8080/swagger
```

---

## 📚 Documentação Completa

Acesse o [Índice da Documentação](docs/index.md) para navegar por todos os guias técnicos, exemplos, referências de API, middlewares, autenticação, performance e mais.

Principais links:
- [Guia de Implementação Básica](docs/implementations/usage_basic.md)
- [Guia com Middlewares Prontos](docs/implementations/usage_with_middleware.md)
- [Guia de Middleware Customizado](docs/implementations/usage_with_custom_middleware.md)
- [Referência Técnica](docs/technical/application.md)
- [Performance e Benchmarks](docs/performance/benchmarks/)

---

## 🧩 Extensões Oficiais

O PivotPHP possui um ecossistema rico de extensões que adicionam funcionalidades poderosas ao framework:

### 🗄️ Cycle ORM Extension
```bash
composer require pivotphp/cycle-orm
```

Integração completa com Cycle ORM para gerenciamento de banco de dados:
- Migrações automáticas
- Repositórios com query builder
- Relacionamentos (HasOne, HasMany, BelongsTo, ManyToMany)
- Suporte a transações
- Múltiplas conexões de banco

```php
use PivotPHP\CycleORM\CycleServiceProvider;

$app->register(new CycleServiceProvider([
    'dbal' => [
        'databases' => [
            'default' => ['connection' => 'mysql://user:pass@localhost/db']
        ]
    ]
]));

// Usar em rotas
$app->get('/users', function($req, $res) use ($container) {
    $users = $container->get('orm')
        ->getRepository(User::class)
        ->findAll();
    $res->json($users);
});
```

### ⚡ ReactPHP Extension
```bash
composer require pivotphp/reactphp
```

Runtime assíncrono para aplicações de longa duração:
- Servidor HTTP contínuo sem reinicializações
- Suporte a WebSocket (em breve)
- Operações I/O assíncronas
- Arquitetura orientada a eventos
- Timers e tarefas periódicas

```php
use PivotPHP\ReactPHP\ReactServiceProvider;

$app->register(new ReactServiceProvider([
    'server' => [
        'host' => '0.0.0.0',
        'port' => 8080
    ]
]));

// Executar servidor assíncrono
$app->runAsync(); // Em vez de $app->run()
```

### 🌐 Extensões da Comunidade

A comunidade PivotPHP está crescendo! Estamos animados para ver as extensões que serão criadas.

**Extensões Planejadas:**
- Gerador de documentação OpenAPI/Swagger
- Sistema de filas para jobs em background
- Cache avançado com múltiplos drivers
- Abstração para envio de emails
- Servidor WebSocket
- Suporte GraphQL

### 🔧 Criando Sua Própria Extensão

```php
namespace MeuProjeto\Providers;

use PivotPHP\Core\Providers\ServiceProvider;

class MinhaExtensaoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar serviços
        $this->container->singleton('meu.servico', function() {
            return new MeuServico();
        });
    }

    public function boot(): void
    {
        // Lógica de inicialização
        $this->app->get('/minha-rota', function($req, $res) {
            $res->json(['extensao' => 'ativa']);
        });
    }
}
```

**Diretrizes para Extensões:**
1. Seguir convenção de nome: `pivotphp-{nome}`
2. Fornecer ServiceProvider estendendo `ServiceProvider`
3. Incluir testes de integração
4. Documentar no `/docs/extensions/`
5. Publicar no Packagist com tag `pivotphp-extension`

---

## 🔄 Compatibilidade PSR-7

O PivotPHP oferece suporte duplo para PSR-7, permitindo uso com projetos modernos (v2.x) e compatibilidade com ReactPHP (v1.x).

### Verificar versão atual
```bash
php scripts/switch-psr7-version.php --check
```

### Alternar entre versões
```bash
# Mudar para PSR-7 v1.x (compatível com ReactPHP)
php scripts/switch-psr7-version.php 1

# Mudar para PSR-7 v2.x (padrão moderno)
php scripts/switch-psr7-version.php 2
```

### Após alternar versões
```bash
# Atualizar dependências
composer update

# Validar o projeto
./scripts/validation/validate_all.sh
```

Veja a [documentação completa sobre PSR-7](docs/technical/compatibility/psr7-dual-support.md) para mais detalhes.

---

## 🏗️ Arquitetura v1.2.0 (Simplicity Edition)

O PivotPHP v1.2.0 simplifica a arquitetura seguindo o princípio "Simplicidade sobre Otimização Prematura", **priorizando facilidade de uso para provas de conceito**:

### 🎯 Recursos v1.2.0

#### 🚀 Array Callables Nativos
```php
// ✅ MANTIDO v1.2.0: Suporte nativo a array callables
$app->get('/users', [UserController::class, 'index']);
$app->post('/users', [$userController, 'store']);

// ✅ Validação automática de métodos
// Se método for privado/protegido, erro claro com sugestão

// ✅ Integração total com IDE
// Autocomplete, refactoring, jump-to-definition
```

#### 🧠 JsonBufferPool Inteligente
```php
// ✅ Sistema com threshold de 256 bytes
// Dados pequenos: json_encode() direto (performance máxima)
// Dados grandes: pooling automático (otimização máxima)

$response = $res->json($anyData); // Sempre otimizado!
```

#### 🔍 Enhanced Error Diagnostics
```php
// ✅ ContextualException com sugestões inteligentes
// Contexto rico, categorização automática, logging integrado

try {
    $app->get('/route', [Controller::class, 'privateMethod']);
} catch (ContextualException $e) {
    // Erro específico com sugestão clara de como resolver
}
```

## 🏗️ Arquitetura v1.2.0 (Simplified Foundation)

O PivotPHP v1.2.0 simplifica a arquitetura v1.1.x, eliminando complexidade desnecessária:

### 🎯 Estrutura de Middlewares Organizada
```
src/Middleware/
├── Security/              # Middlewares de segurança
│   ├── AuthMiddleware.php
│   ├── CsrfMiddleware.php
│   ├── SecurityHeadersMiddleware.php
│   └── XssMiddleware.php
├── Performance/           # Middlewares de performance
│   ├── CacheMiddleware.php
│   └── RateLimitMiddleware.php
└── Http/                 # Middlewares HTTP
    ├── CorsMiddleware.php
    └── ErrorMiddleware.php
```

### ✅ Melhorias da v1.2.0 (Foco em Simplicidade)
- **🎯 Orientado a Protótipos** - Arquitetura simplificada para desenvolvimento rápido
- **📚 Documentação Didática** - Exemplos práticos e guias de aprendizado
- **🔧 Setup Mínimo** - Configuração zero para começar imediatamente
- **💡 Conceitos Claros** - Estrutura lógica e intuitiva para estudos
- **🛡️ Qualidade Educacional** - PHPStan Level 9, 100% testes passando para aprendizado

### 🔄 Migração para v1.2.0
```php
// Imports antigos (ainda funcionam via aliases)
use PivotPHP\Core\Http\Psr15\Middleware\CorsMiddleware;
use PivotPHP\Core\Support\Arr;

// Imports recomendados (nova estrutura)
use PivotPHP\Core\Middleware\Http\CorsMiddleware;
use PivotPHP\Core\Utils\Arr;
```

Veja o [Overview Estrutural](docs/releases/FRAMEWORK_OVERVIEW_v1.2.0.md) para detalhes completos.

---

## ⚠️ Importante: Manutenção do Projeto

**PivotPHP Core é mantido por apenas uma pessoa** e pode não receber atualizações constantemente. Este projeto é ideal para:

- 🔬 **Provas de conceito** e protótipos
- 📚 **Estudos** e aprendizado de arquitetura
- 🧪 **Testes** e validação de ideias
- 🎓 **Projetos educacionais** e acadêmicos

**Não recomendado para:**
- 🏢 Aplicações enterprise críticas
- 📈 Sistemas de produção que exigem suporte 24/7
- 🔄 Projetos que precisam de atualizações frequentes

Se você precisa de um framework com equipe dedicada e suporte empresarial, considere alternativas como Laravel, Symfony ou Slim 4.

---

## 🤝 Comunidade

Junte-se à nossa comunidade crescente de desenvolvedores:

- **GitHub Discussions**: [Inicie uma discussão](https://github.com/PivotPHP/pivotphp-core/discussions) - Compartilhe feedback e ideias

## 🤝 Como Contribuir

Quer ajudar a evoluir o PivotPHP? Veja o [Guia de Contribuição](CONTRIBUTING.md) ou acesse [`docs/contributing/`](docs/contributing/) para saber como abrir issues, enviar PRs ou criar extensões.

**Contribuições são especialmente bem-vindas!** Por ser mantido por uma pessoa, o projeto se beneficia muito da colaboração da comunidade.

---

## 📄 Licença

Este projeto está licenciado sob a Licença MIT. Veja o arquivo [LICENSE](LICENSE) para detalhes.

---

*Desenvolvido com ❤️ para a comunidade PHP*
