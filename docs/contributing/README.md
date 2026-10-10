# Como Contribuir com o PivotPHP

Obrigado pelo interesse em contribuir com o PivotPHP! Este guia contém todas as informações necessárias para participar do desenvolvimento do framework.

## 🚀 Formas de Contribuir

### 1. Reportar Bugs e Issues
- Relatar problemas encontrados
- Sugerir melhorias e novas funcionalidades
- Contribuir com documentação
- Compartilhar casos de uso

### 2. Desenvolvimento
- Corrigir bugs existentes
- Implementar novas funcionalidades
- Melhorar performance
- Escrever testes

### 3. Documentação
- Melhorar documentação existente
- Criar novos guias e tutoriais
- Traduzir documentação
- Criar exemplos práticos

### 4. Comunidade
- Ajudar outros desenvolvedores
- Compartilhar extensões
- Criar conteúdo educativo
- Evangelizar o framework

## 📋 Antes de Começar

### Pré-requisitos

- PHP 8.1 ou superior
- Composer 2.0+
- Git
- Conhecimento básico de PSR (PHP Standards Recommendations)

### Configuração do Ambiente

```bash
# 1. Fork do repositório no GitHub
# 2. Clone seu fork
git clone https://github.com/seu-usuario/pivotphp-core.git
cd pivotphp-core

# 3. Instalar dependências
composer install

# 4. Configurar remote upstream
git remote add upstream https://github.com/pivotphp-core/framework.git

# 5. Criar branch para desenvolvimento
git checkout -b feature/minha-funcionalidade
```

### Estrutura do Projeto

```
pivotphp-core/
├── src/                    # Código fonte do framework
│   ├── Core/              # Classes principais
│   ├── Http/              # HTTP components
│   ├── Routing/           # Sistema de roteamento
│   ├── Middleware/        # Middlewares padrão
│   ├── Providers/         # Service providers
│   └── ...
├── tests/                 # Suite de testes
├── docs/                  # Documentação
├── examples/              # Exemplos de uso
├── benchmarks/           # Benchmarks de performance
├── config/               # Configurações
└── scripts/              # Scripts utilitários
```

## 🐛 Reportando Bugs

### Template de Issue para Bugs

```markdown
## Descrição do Bug
[Descrição clara e concisa do problema]

## Passos para Reproduzir
1. Instalar PivotPHP v1.0.0
2. Criar rota com '...'
3. Executar '...'
4. Ver erro

## Comportamento Esperado
[O que deveria acontecer]

## Comportamento Atual
[O que realmente acontece]

## Ambiente
- PHP Version: 8.1.x
- PivotPHP Version: 1.0.0
- OS: Ubuntu 22.04
- Servidor: Apache/Nginx/Built-in

## Código de Exemplo
```php
// Código mínimo que reproduz o problema
$app = new Application();
// ...
```

## Logs de Erro
```
[Logs relevantes ou stack trace]
```

## Informações Adicionais
[Qualquer contexto adicional]
```

### Verificação Antes de Reportar

1. **Busque issues existentes** para evitar duplicatas
2. **Teste na versão mais recente** do framework
3. **Use o template** fornecido para issues
4. **Inclua código mínimo** que reproduza o problema
5. **Forneça informações completas** do ambiente

## 💡 Sugerindo Funcionalidades

### Template de Feature Request

```markdown
## Resumo da Funcionalidade
[Descrição concisa da funcionalidade solicitada]

## Motivação e Caso de Uso
[Por que esta funcionalidade seria útil?]

## Descrição Detalhada
[Descrição detalhada da funcionalidade]

## Possível Implementação
[Se você tem ideias sobre como implementar]

## Alternativas Consideradas
[Outras soluções que você considerou]

## Impacto
- [ ] Breaking change
- [ ] Nova funcionalidade
- [ ] Melhoria de performance
- [ ] Melhoria de documentação
```

## 🔧 Contribuindo com Código

### Fluxo de Desenvolvimento

1. **Fork e Clone** o repositório
2. **Criar branch** específica para sua funcionalidade
3. **Implementar** a funcionalidade
4. **Escrever testes** para sua implementação
5. **Executar suite de testes** completa
6. **Documentar** mudanças
7. **Fazer commit** seguindo convenções
8. **Abrir Pull Request**

### Convenções de Código

#### PSR Standards

O PivotPHP segue as PSRs:
- **PSR-1**: Basic Coding Standard
- **PSR-2**: Coding Style Guide (deprecated, use PSR-12)
- **PSR-4**: Autoloading Standard
- **PSR-12**: Extended Coding Style Guide

#### Exemplo de Código

```php
<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware de exemplo seguindo as convenções (PSR-15).
 */
final class ExampleMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = hrtime(true);

        $response = $handler->handle($request);

        return $response->withHeader('X-Processing-Time', sprintf('%.2fms', (hrtime(true) - $start) / 1e6));
    }
}
```

#### Convenções Específicas

```php
// ✅ Bom
class UserController
{
    public function getUsers(Request $req, Response $res): Response
    {
        $users = $this->userService->getAllUsers();
        return $res->json($users);
    }
}

// ❌ Evitar
class usercontroller
{
    public function getusers($req, $res)
    {
        $users = getUsersFromDatabase();
        echo json_encode($users);
    }
}
```

### Testes

#### Executando Testes

```bash
# Todos os testes
composer test

# Testes específicos
vendor/bin/phpunit tests/Http/RequestTest.php

# Testes com coverage
composer test:coverage

# Análise estática
composer phpstan
```

#### Escrevendo Testes

```php
<?php

namespace PivotPHP\Core\Tests\Integration;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;

class UserRoutesTest extends TestCase
{
    public function testRouteParameterIsExtracted(): void
    {
        $app = new Application(__DIR__ . '/../..');
        $app->get('/users/:id<\d+>', fn ($req, $res) => $res->json(['id' => $req->param('id')]));

        $response = $app->handle(new ServerRequest('GET', '/users/123'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"id":"123"}', (string) $response->getBody());
    }

    public function testNonNumericIdIsNotFound(): void
    {
        $app = new Application(__DIR__ . '/../..');
        $app->get('/users/:id<\d+>', fn ($req, $res) => $res->json([]));

        $this->assertSame(404, $app->handle(new ServerRequest('GET', '/users/abc'))->getStatusCode());
    }
}
```

### Convenções de Commit

#### Formato de Commit

```
<tipo>(<escopo>): <descrição>

<corpo opcional>

<rodapé opcional>
```

#### Tipos de Commit

- **feat**: Nova funcionalidade
- **fix**: Correção de bug
- **docs**: Mudanças na documentação
- **style**: Mudanças de formatação
- **refactor**: Refatoração de código
- **test**: Adição ou correção de testes
- **chore**: Tarefas de manutenção

#### Exemplos

```bash
# Funcionalidade
feat(routing): add support for route groups with middleware

# Correção
fix(http): handle empty request body correctly

# Documentação
docs(middleware): add examples for custom middleware

# Refatoração
refactor(core): improve application initialization performance

# Testes
test(http): add comprehensive request validation tests
```

### Pull Request

#### Template de Pull Request

```markdown
## Descrição
[Breve descrição das mudanças]

## Tipo de Mudança
- [ ] Bug fix (mudança que corrige um problema)
- [ ] New feature (mudança que adiciona funcionalidade)
- [ ] Breaking change (mudança que quebra compatibilidade)
- [ ] Documentação
- [ ] Refatoração

## Como Foi Testado
[Descreva os testes realizados]

## Checklist
- [ ] Meu código segue as convenções do projeto
- [ ] Realizei self-review do código
- [ ] Comentei partes complexas do código
- [ ] Atualizei a documentação
- [ ] Adicionei testes que provam que a correção/funcionalidade funciona
- [ ] Testes novos e existentes passam
- [ ] Mudanças foram testadas em PHP 8.1+

## Issues Relacionadas
Fixes #123
Related to #456
```

#### Revisão de Código

Critérios para aprovação:
- **Funcionalidade**: Funciona conforme esperado
- **Código**: Segue convenções e boas práticas
- **Testes**: Cobertura adequada de testes
- **Documentação**: Documentação atualizada
- **Performance**: Não degrada performance
- **Compatibilidade**: Mantém compatibilidade

## 📚 Contribuindo com Documentação

### Estrutura da Documentação

```
docs/
├── index.md                  # Índice principal
├── implementations/           # Guias práticos
├── technical/             # Documentação técnica
│   ├── application.md
│   ├── http/
│   ├── routing/
│   ├── middleware/
│   └── ...
├── performance/            # Performance e otimização
├── testing/               # Guias de teste
└── contributing/          # Contribuição
```

### Escrevendo Documentação

#### Boas Práticas

1. **Clareza**: Use linguagem simples e direta
2. **Exemplos**: Inclua exemplos práticos e funcionais
3. **Estrutura**: Organize o conteúdo logicamente
4. **Consistência**: Mantenha formato consistente
5. **Atualização**: Mantenha sincronizado com o código

#### Template de Documentação

```markdown
# Título Principal

Breve descrição do que será abordado.

## Conceitos Fundamentais

### Subtítulo

Explicação conceitual...

## Exemplos Práticos

### Exemplo Básico

```php
// Código de exemplo comentado
$app = new Application();

$app->get('/', function($req, $res) {
    return $res->json(['message' => 'Hello World']);
});
```

### Exemplo Avançado

```php
// Exemplo mais complexo
// ...
```

## API Reference

### Método `exemplo()`

```php
public function exemplo(string $param): ReturnType
```

**Parâmetros:**
- `$param` (string) - Descrição do parâmetro

**Retorno:**
- `ReturnType` - Descrição do retorno

**Exemplo:**
```php
$result = $obj->exemplo('valor');
```

## Veja Também

- [Link para documentação relacionada]
- [Outro link relevante]
```

## 🔌 Criando Extensões

### Estrutura de Extensão

```php
// composer.json
{
    "name": "vendor/express-extension",
    "type": "library",
    "require": {
        "pivotphp-core/framework": "^2.1"
    },
    "extra": {
        "pivotphp-core": {
            "providers": [
                "Vendor\\Extension\\ServiceProvider"
            ]
        }
    }
}
```

### Service Provider

```php
<?php

namespace Vendor\Extension;

use PivotPHP\Core\Providers\ServiceProvider;

class ExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('extension', ExtensionService::class);
    }

    public function boot(): void
    {
        // Bootstrap da extensão
    }
}
```

## 🎯 Diretrizes Específicas

### Performance

- **Benchmarks**: Execute benchmarks antes e depois
- **Memory**: Monitore uso de memória
- **Profiling**: Use ferramentas de profiling quando necessário

### Segurança

- **Validação**: Sempre valide entrada de usuários
- **Sanitização**: Sanitize saídas
- **Best Practices**: Siga práticas de segurança do PHP

### Compatibilidade

- **PHP Version**: Mínimo PHP 8.1
- **Dependencies**: Minimize dependências externas
- **Breaking Changes**: Evite quando possível

## 🏆 Reconhecimento

### Contribuidores

Todos os contribuidores são reconhecidos:
- Listados no `CONTRIBUTORS.md`
- Mencionados nos release notes
- Reconhecimento na documentação

### Tipos de Contribuição

- 🐛 **Bug Fixes**
- ✨ **New Features**
- 📝 **Documentation**
- 🚀 **Performance**
- 🛡️ **Security**
- 🧪 **Testing**

## ❓ Precisa de Ajuda?

### Canais de Comunicação

- **GitHub Issues**: Para bugs e feature requests
- **GitHub Discussions**: Para discussões gerais

### Documentação Útil

- [Exemplos](../reference/examples.md)
- [Documentação da API](../technical/application.md)
- [Guias de Teste](../testing/api_testing.md)

## 📜 Código de Conduta

### Nossos Valores

- **Respeito**: Trate todos com respeito
- **Inclusão**: Seja inclusivo e acolhedor
- **Colaboração**: Trabalhe junto para o bem comum
- **Qualidade**: Busque sempre a excelência

### Comportamentos Esperados

- Use linguagem acolhedora e inclusiva
- Respeite diferentes pontos de vista
- Aceite críticas construtivas graciosamente
- Foque no que é melhor para a comunidade

### Comportamentos Inaceitáveis

- Linguagem ou imagens sexualizadas
- Trolling, comentários insultuosos
- Assédio público ou privado
- Publicar informações privadas sem permissão

Contribuir com o PivotPHP é uma excelente maneira de aprender, ensinar e construir algo incrível junto com a comunidade. Agradecemos sua participação! 🚀
