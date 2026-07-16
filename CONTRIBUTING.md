# 🤝 Contribuindo para o PivotPHP

Obrigado pelo seu interesse em contribuir para o PivotPHP! Valorizamos contribuições da comunidade.

## 🚀 Como Contribuir

### 1. Configurar Ambiente de Desenvolvimento

```bash
# Fork o projeto no GitHub
git clone https://github.com/seu-usuario/pivotphp-core.git
cd pivotphp-core

# Instalar dependências
composer install

# Executar testes para verificar se tudo está funcionando
composer test
./vendor/bin/phpunit
./vendor/bin/phpstan analyse
```

### 2. Diretrizes de Código

#### 📋 Padrões de Qualidade
- **PHPStan Level 9**: Máxima análise estática
- **PSR-12**: Padrão de code style
- **PHP 8.1+**: Compatibilidade mínima
- **100% Test Coverage**: Todos os recursos devem ter testes

#### 🎯 Estrutura do Código
```
src/
├── Core/                    # Núcleo do framework
│   ├── Application.php      # Classe principal da aplicação
│   ├── Config.php           # Gerenciamento de configuração
│   └── Container.php        # Container de injeção de dependência
├── Http/                    # Componentes HTTP
│   ├── Request.php          # Implementação PSR-7 Request
│   ├── Response.php         # Implementação PSR-7 Response
│   └── Psr15/               # Implementações PSR-15
├── Routing/                 # Sistema de roteamento
│   ├── Router.php           # Roteador principal
│   └── Route.php            # Representação de rotas
├── Middleware/              # Sistema de middlewares
│   ├── Core/                # Middlewares principais
│   └── MiddlewareStack.php  # Gerenciamento de middleware
├── Providers/               # Service Providers
├── Events/                  # Sistema de eventos PSR-14
├── Authentication/          # Autenticação (JWT)
└── Utils/                   # Utilitários diversos
```

### 3. Desenvolvendo Middlewares

#### Template de Middleware
```php
<?php
namespace PivotPHP\Core\Middleware\Core;

class MeuMiddleware
{
    private array $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'enabled' => true,
            'option1' => 'default'
        ], $options);
    }

    public function __invoke($req, $res, $next): void
    {
        if (!$this->options['enabled']) {
            $next();
            return;
        }

        // Lógica do middleware aqui

        $next();
    }
}
```

#### Localização dos Middlewares
- **Core**: `src/Middleware/Core/` - Funcionalidades principais
- **Security**: `src/Middleware/Security/` - Segurança e autenticação

### 4. Testes

#### Executar Todos os Testes
```bash
# Testes unitários
composer test
./vendor/bin/phpunit

# Análise estática
./vendor/bin/phpstan analyse

# Verificação de code style
./vendor/bin/phpcs --standard=PSR12 src/

# Correção automática de style
./vendor/bin/phpcbf --standard=PSR12 src/
```

#### Criar Novos Testes
```php
<?php
namespace PivotPHP\Core\Tests\Middleware\Core;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Core\MeuMiddleware;

class MeuMiddlewareTest extends TestCase
{
    public function testMiddlewareBasicFunctionality(): void
    {
        $middleware = new MeuMiddleware();

        // Implementar testes
        $this->assertInstanceOf(MeuMiddleware::class, $middleware);
    }
}
```

## 📝 Tipos de Contribuição

### 🐛 Reportar Bugs
- Use o template de issue no GitHub
- Inclua exemplos de código para reproduzir
- Especifique versões (PHP, PivotPHP)

### ✨ Propor Novos Recursos
- Abra uma issue para discussão
- Inclua casos de uso
- Considere impacto na performance e compatibilidade

### 📚 Melhorar Documentação
- Atualize README e guias
- Adicione exemplos práticos
- Traduza para outros idiomas

### 🔧 Corrigir Código
- Mantenha compatibilidade com PHP 8.1+
- Siga os padrões de qualidade
- Adicione testes para mudanças

## 🎯 Processo de Review

### Pull Request Checklist
- [ ] Código segue PSR-12
- [ ] PHPStan Level 9 sem erros
- [ ] Todos os testes passam
- [ ] Documentação atualizada
- [ ] Exemplos funcionando
- [ ] Compatibilidade PHP 8.1+

### Hooks de Git
O projeto inclui hooks automáticos que verificam:
- Sintaxe PHP
- PHPStan Level 9
- Testes unitários
- Code style PSR-12
- Validação do composer.json

## 🏆 Reconhecimento

Contribuidores são listados em:
- README.md
- CONTRIBUTORS.md (se criado)
- Releases do GitHub

### Documentation
- Update both English and Portuguese documentation
- Include code examples
- Keep README files updated

## 🐛 Bug Reports

When reporting bugs, please include:
- PHP version
- PivotPHP version
- Steps to reproduce
- Expected vs actual behavior
- Error messages or logs

## 💡 Feature Requests

For new features:
- Describe the use case
- Explain the benefit to users
- Consider backward compatibility
- Provide implementation ideas if possible

## 🔒 Security Issues

For security vulnerabilities:
- **DO NOT** open a public issue
- Create a private security advisory on GitHub: https://github.com/PivotPHP/pivotphp-core/security/advisories/new

## 📚 Types of Contributions

We welcome:
- Bug fixes
- New middleware development
- Performance improvements
- Documentation improvements
- Example applications
- Test coverage improvements
- Translations

## 🌍 Internationalization

Help us support more languages:
- Translate documentation
- Add language-specific examples
- Localize error messages

## 📋 Pull Request Checklist

Before submitting:
- [ ] Code follows style guidelines
- [ ] Tests pass
- [ ] Documentation updated
- [ ] Backward compatibility maintained
- [ ] Examples work correctly
- [ ] Security implications considered

## 🏷️ Commit Messages

Use clear commit messages:
```
feat: add XSS protection middleware
fix: resolve CSRF token validation issue
docs: update security documentation
test: add middleware integration tests
```

## 📖 Development Resources

- [PivotPHP Documentation](docs/index.md)
- [Middleware Technical Guide](docs/technical/middleware/README.md)
- [Migration Guide](docs/MIGRATION_GUIDE.md)

## 🎯 Contribution Areas

High priority areas:
- Performance optimizations
- Additional security features
- More comprehensive tests
- Better error handling
- Enhanced documentation

## 📞 Getting Help

- Check existing issues and discussions
- Read the documentation thoroughly
- Look at example implementations
- Ask questions in issues (tag with "question")

## 📄 License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for helping make PivotPHP better! 🚀
