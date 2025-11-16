# Guia de Versionamento Semântico - PivotPHP Core

## Visão Geral

O PivotPHP Core segue rigorosamente o **Versionamento Semântico (SemVer)** no formato `X.Y.Z`:

```
X.Y.Z
│ │ └─── PATCH: Correções de bugs (compatível com versões anteriores)
│ └───── MINOR: Novas funcionalidades (compatível com versões anteriores)
└─────── MAJOR: Mudanças incompatíveis (quebra compatibilidade)
```

## 🔢 Quando Incrementar Cada Número

### 🚨 MAJOR (X) - Mudanças Incompatíveis

Incremente o número MAJOR quando fizer mudanças **incompatíveis** com versões anteriores:

#### ❌ Breaking Changes que Exigem MAJOR:
- **Remoção de classes públicas**: `Router`, `Application`, `Request`, `Response`
- **Remoção de métodos públicos**: `$app->get()`, `$req->param()`, `$res->json()`
- **Mudança de assinatura de métodos**: Alterar parâmetros obrigatórios
- **Mudança de comportamento esperado**: Alterar valores de retorno padrão
- **Remoção de middleware**: `AuthMiddleware`, `CsrfMiddleware`
- **Mudança de namespace**: `PivotPHP\Core\*` para outro namespace
- **Alteração de estrutura de dados**: Formato de resposta JSON, estrutura de configuração
- **Remoção de suporte PHP**: Parar de suportar PHP 8.1
- **Mudança de dependências principais**: Trocar PSR-7 por outra especificação

#### 📝 Exemplos de MAJOR:
```
1.1.4 → 2.0.0  # Remoção do método deprecated $req->getBody()
2.0.0 → 3.0.0  # Mudança na interface do Container DI
3.0.0 → 4.0.0  # Reescrita completa do sistema de roteamento
```

#### ⚠️ Procedimento para MAJOR:
1. **Documentar breaking changes** detalhadamente
2. **Criar guia de migração** (`MIGRATION_v2.0.0.md`)
3. **Deprecar funcionalidades** por pelo menos 1 versão MINOR antes
4. **Avisar a comunidade** com antecedência (GitHub)
5. **Testar intensivamente** todas as mudanças

---

### ✨ MINOR (Y) - Novas Funcionalidades

Incremente o número MINOR quando **adicionar** funcionalidades mantendo compatibilidade:

#### ✅ Adições que Justificam MINOR:
- **Novas classes públicas**: `JsonResponseMiddleware`, `LoggerService`
- **Novos métodos públicos**: `$app->patch()`, `$req->cookies()`, `$res->redirect()`
- **Novos middleware**: `RateLimitMiddleware`, `CompressionMiddleware`
- **Novos utilitários**: `OpenApiExporter`, `PerformanceMonitor`
- **Parâmetros opcionais**: Adicionar parâmetro opcional a método existente
- **Novas funcionalidades opt-in**: Features que não afetam comportamento padrão
- **Melhorias de performance**: Que não alteram comportamento público
- **Suporte a novas versões PHP**: Adicionar suporte ao PHP 8.4
- **Novas integrações**: Suporte a novos PSRs, bibliotecas opcionais

#### 📝 Exemplos de MINOR:
```
1.1.4 → 1.2.0  # Adição do OpenApiExporter
1.2.0 → 1.3.0  # Novo sistema de eventos
1.3.0 → 1.4.0  # Middleware de cache automático
```

#### ⚠️ Procedimento para MINOR:
1. **Manter 100% compatibilidade** com versões anteriores
2. **Adicionar testes** para todas as novas funcionalidades
3. **Documentar** todas as novas features
4. **Atualizar** examples/ e docs/
5. **Verificar** que código existente continua funcionando

---

### 🔧 PATCH (Z) - Correções de Bugs

Incremente o número PATCH quando **corrigir bugs** mantendo compatibilidade:

#### 🐛 Correções que Justificam PATCH:
- **Correção de bugs**: Comportamento incorreto sem alterar API
- **Melhorias de segurança**: Patches de vulnerabilidades
- **Correções de performance**: Otimizações que não alteram comportamento
- **Correções de documentação**: Typos, exemplos incorretos
- **Correções de testes**: Testes falso-positivos ou instáveis
- **Correções de dependências**: Updates de segurança em deps
- **Correções de compatibilidade**: Suporte melhor a versões existentes do PHP
- **Refatoração interna**: Melhorias de código sem alterar API pública

#### 📝 Exemplos de PATCH:
```
1.1.4 → 1.1.5  # Correção de memory leak no pool de objetos
1.1.5 → 1.1.6  # Fix de XSS no middleware de segurança
1.1.6 → 1.1.7  # Otimização de performance no router
```

#### ⚠️ Procedimento para PATCH:
1. **Identificar** e **isolar** o bug
2. **Criar testes** que reproduzem o problema
3. **Implementar** a correção mínima necessária
4. **Verificar** que não quebra nada existente
5. **Deploy rápido** (patches devem ser releases rápidos)

---

## 🛠️ Como Usar o Script de Versionamento

O PivotPHP Core inclui um script automatizado para gerenciar versões:

### Comandos Disponíveis:

```bash
# Incrementar PATCH (1.1.4 → 1.1.5)
scripts/release/version-bump.sh patch

# Incrementar MINOR (1.1.4 → 1.2.0)
scripts/release/version-bump.sh minor

# Incrementar MAJOR (1.1.4 → 2.0.0)
scripts/release/version-bump.sh major

# Visualizar próxima versão sem aplicar
scripts/release/version-bump.sh minor --dry-run

# Fazer bump sem criar commit/tag
scripts/release/version-bump.sh patch --no-commit

# Fazer bump sem criar tag (mas com commit)
scripts/release/version-bump.sh minor --no-tag
```

### O que o Script Faz Automaticamente:

1. **Lê** a versão atual do arquivo `VERSION`
2. **Calcula** a nova versão baseada no tipo de bump
3. **Atualiza** o arquivo `VERSION`
4. **Atualiza** `composer.json` (se tiver campo version)
5. **Cria commit** automático com mensagem padronizada
6. **Cria tag Git** com a nova versão
7. **Valida** formato semântico (X.Y.Z)

### Exemplo de Uso Completo:

```bash
# Cenário: Correção de bug de segurança
$ scripts/release/version-bump.sh patch

ℹ️  Versão atual: 1.1.4
ℹ️  Nova versão: 1.1.5
ℹ️  Tipo de bump: patch

Confirma o bump de 1.1.4 para 1.1.5? (y/N): y

✅ VERSION file atualizado para 1.1.5
✅ composer.json atualizado para 1.1.5
✅ Commit criado
✅ Tag v1.1.5 criada

🎉 Versão bumped com sucesso!
  • 1.1.4 → 1.1.5
  • Tipo: patch
  • Commit criado: ✅
  • Tag criada: ✅

ℹ️  Para publicar: git push origin --tags
```

---

## 📋 Checklist de Versionamento

### Antes de Qualquer Release:

#### ✅ Validações Obrigatórias:
- [ ] Todos os testes passando (`composer test`)
- [ ] PHPStan Level 9 sem erros (`composer phpstan`)
- [ ] PSR-12 compliance (`composer cs:check`)
- [ ] Cobertura de testes ≥30% (`composer test:coverage`)
- [ ] Testes de segurança passando (`composer test:security`)
- [ ] Performance ≥30K ops/sec (`composer benchmark`)
- [ ] Validação completa (`scripts/quality/quality-check.sh`)

#### ✅ Documentação:
- [ ] CHANGELOG.md atualizado
- [ ] Documentação técnica atualizada
- [ ] Exemplos funcionando
- [ ] README atualizado (se necessário)

#### ✅ Git:
- [ ] Todas as mudanças commitadas
- [ ] Branch limpo (`git status`)
- [ ] Merge com main (se trabalhando em feature branch)

### Para MINOR e MAJOR:

#### ✅ Comunicação:
- [ ] Anunciar no GitHub da comunidade
- [ ] Criar release notes detalhadas
- [ ] Atualizar roadmap (se aplicável)

#### ✅ Para MAJOR apenas:
- [ ] Guia de migração criado
- [ ] Breaking changes documentados
- [ ] Período de feedback da comunidade
- [ ] Testes de compatibilidade extensivos

---

## 🎯 Diretrizes Específicas do PivotPHP

### Performance Benchmarks:
- **PATCH**: Melhorias de performance são PATCH se não alteram API
- **MINOR**: Novas otimizações que adicionam funcionalidade (ex: novo modo high-performance)
- **MAJOR**: Mudanças que quebram garantias de performance existentes

### PSR Compliance:
- **PATCH**: Correções para melhor aderência a PSR existente
- **MINOR**: Suporte a nova PSR (ex: PSR-18)
- **MAJOR**: Mudança de PSR fundamental (ex: trocar PSR-7 por PSR-17)

### Middleware:
- **PATCH**: Correções em middleware existente
- **MINOR**: Novo middleware disponível
- **MAJOR**: Remoção ou mudança radical de middleware core

### APIs Internas vs Públicas:
- **APIs Públicas**: Qualquer classe/método documentado em docs/
- **APIs Internas**: Classes em namespace `*\Internal\*`
- **Mudanças internas**: Geralmente PATCH, a menos que afetem performance

---

## 🚀 Workflow de Release

### 1. Desenvolvimento
```bash
# Trabalhe em feature branch
git checkout -b feature/new-middleware
# ... desenvolva ...
git commit -m "feat: add rate limiting middleware"
```

### 2. Preparação
```bash
# Volte para main
git checkout main
git merge feature/new-middleware

# Execute validações
scripts/quality/quality-check.sh
```

### 3. Versionamento
```bash
# Para nova funcionalidade (MINOR)
scripts/release/version-bump.sh minor

# Resultado: 1.1.4 → 1.2.0
```

### 4. Publicação
```bash
# Push com tags
git push origin main --tags

# Publique no Packagist (automático via webhook)
# Anuncie na comunidade
```

---

## 📚 Recursos Adicionais

### Documentação:
- [Semantic Versioning Official](https://semver.org/)
- [PivotPHP Changelog](../CHANGELOG.md)
- [Contributing Guidelines](../CONTRIBUTING.md)

### Scripts Relacionados:
- `scripts/release/version-bump.sh` - Gerenciamento de versões
- `scripts/release/prepare_release.sh` - Preparação para release
- `scripts/quality/quality-check.sh` - Validação de qualidade

### Comunidade:
- [GitHub Issues](https://github.com/PivotPHP/pivotphp-core/issues)
- [GitHub Discussions](https://github.com/PivotPHP/pivotphp-core/discussions)

---

## ❓ Dúvidas Frequentes

### **Q: Adicionar um parâmetro opcional a um método é MINOR ou PATCH?**
**A:** MINOR - adicionar funcionalidade, mesmo que opcional, é considerado nova feature.

### **Q: Corrigir um bug que muda ligeiramente o comportamento é PATCH ou MINOR?**
**A:** PATCH - se o comportamento anterior era objetivamente um bug, a correção é PATCH.

### **Q: Melhorar performance 50% sem mudar API é MINOR ou PATCH?**
**A:** PATCH - melhorias de performance que não adicionam funcionalidade são PATCH.

### **Q: Deprecar uma função é MINOR ou MAJOR?**
**A:** MINOR - deprecation é MINOR, remoção é MAJOR.

### **Q: Atualizar dependência que pode quebrar compatibilidade é MAJOR?**
**A:** Depende - se a API pública do PivotPHP não muda, pode ser MINOR ou PATCH.

---

**📝 Nota**: Este guia deve ser seguido rigorosamente para garantir previsibilidade e confiança da comunidade PivotPHP Core.

---

*Última atualização: v1.1.4 - Documentação criada junto com consolidação de scripts*
