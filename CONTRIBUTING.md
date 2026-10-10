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
├── Core/          # Application, Config, Environment
├── Database/      # Database (PDO)
├── Events/        # Eventos PSR-14
├── Exceptions/    # HttpException, ContextualException
├── Logging/       # PsrLogger
├── Middleware/    # MiddlewareStack e ApiDocumentationMiddleware
├── Providers/     # Container, service providers, extensões
├── Support/       # HookManager, Str
├── Utils/         # Arr, CallableResolver, Utils
└── Validation/    # Validator
```

HTTP (`pivotphp/http`), roteamento (`pivotphp/core-routing`) e segurança (`pivotphp/security`) são
pacotes separados: contribuições nessas áreas vão para os respectivos repositórios.

### 3. Desenvolvendo Middlewares

Novos middlewares devem ser PSR-15 (`Psr\Http\Server\MiddlewareInterface`). Middlewares de
segurança pertencem ao `pivotphp/security`. Veja
[docs/technical/middleware/CustomMiddleware.md](docs/technical/middleware/CustomMiddleware.md).

### 4. Testes

```bash
composer test                 # PHPUnit (unit + integration, inclusive os exemplos)
composer phpstan              # PHPStan nível 9
composer cs:check             # PHPCS (phpcs.xml)
composer cs:fix               # correção automática
```

Teste comportamento pela aplicação sempre que possível:

```php
$app = new Application(__DIR__ . '/..');
$app->get('/x', fn ($req, $res) => $res->json(['ok' => true]));

$response = $app->handle(new \Nyholm\Psr7\ServerRequest('GET', '/x'));
$this->assertSame(200, $response->getStatusCode());
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
