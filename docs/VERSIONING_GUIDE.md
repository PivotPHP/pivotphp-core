# Guia de Versionamento Semântico - PivotPHP Core

## Visão Geral

O PivotPHP Core segue rigorosamente o **Versionamento Semântico (SemVer)** no formato `X.Y.Z`:

```
X.Y.Z
│ │ └─── PATCH: Correções de bugs (compatível com versões anteriores)
│ └───── MINOR: Novas funcionalidades (compatível com versões anteriores)
└─────── MAJOR: Mudanças incompatíveis (quebra compatibilidade)
```

A versão atual é **4.1.0**. O arquivo `VERSION` na raiz é a fonte da verdade e deve coincidir com
`Application::VERSION` (`src/Core/Application.php`) e com a entrada mais recente do
[CHANGELOG](../CHANGELOG.md).

## 🔢 Quando Incrementar Cada Número

### 🚨 MAJOR (X) - Mudanças Incompatíveis

Incremente o número MAJOR quando fizer mudanças **incompatíveis** com versões anteriores:

#### ❌ Breaking Changes que Exigem MAJOR:
- **Remoção de classes públicas**: `Router`, `Application`, `Request`, `Response`
- **Remoção de métodos públicos**: `$app->get()`, `$req->param()`, `$res->json()`
- **Mudança de assinatura de métodos**: Alterar parâmetros obrigatórios
- **Mudança de comportamento esperado**: Alterar valores de retorno padrão
- **Remoção de middleware**: `CorsMiddleware`, `SecurityHeadersMiddleware`, `CsrfMiddleware`, etc.
- **Mudança de namespace**: `PivotPHP\Core\*` para outro namespace
- **Alteração de estrutura de dados**: Formato de resposta JSON, estrutura de configuração
- **Remoção de suporte PHP**: Parar de suportar PHP 8.1
- **Extração para pacotes externos**: mover responsabilidades do core para `pivotphp/http`,
  `pivotphp/core-routing` ou `pivotphp/security`
- **Mudança de dependências principais**: Trocar PSR-7 por outra especificação

#### 📝 Exemplos de MAJOR:
```
1.1.4 → 2.0.0  # Remoção de APIs depreciadas (ex.: $req->getBody())
2.1.0 → 3.0.0  # Remoção das APIs em ciclo de depreciação iniciado na 2.1.0
3.0.0 → 4.0.0  # Camada HTTP, roteamento e segurança extraídos para pacotes próprios
```

#### ⚠️ Procedimento para MAJOR:
1. **Documentar breaking changes** detalhadamente
2. **Criar/atualizar o guia de migração** (`docs/MIGRATION_GUIDE.md`)
3. **Deprecar funcionalidades** por pelo menos 1 versão MINOR antes
4. **Avisar a comunidade** com antecedência (GitHub)
5. **Testar intensivamente** todas as mudanças

---

### ✨ MINOR (Y) - Novas Funcionalidades

Incremente o número MINOR quando **adicionar** funcionalidades mantendo compatibilidade:

#### ✅ Adições que Justificam MINOR:
- **Novas classes públicas**: `ApiDocumentationMiddleware`, `Validator`
- **Novos métodos públicos**: `$app->patch()`, `$req->query()`, `$res->redirect()`
- **Novas regras/opções em componentes existentes** (ex.: regras `nullable`/`sometimes` no `Validator`)
- **Parâmetros opcionais**: Adicionar parâmetro opcional a método existente
- **Novas funcionalidades opt-in**: Features que não afetam comportamento padrão
- **Melhorias de performance**: Que não alteram comportamento público
- **Suporte a novas versões PHP**: Adicionar suporte a uma nova versão do PHP
- **Novas integrações**: Suporte a novos PSRs, bibliotecas opcionais

#### 📝 Exemplos de MINOR:
```
4.0.2 → 4.1.0  # Regras nullable/sometimes no Validator e path templating no OpenAPI
4.1.0 → 4.2.0  # Novo endpoint ou opção pública opcional
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
4.0.0 → 4.0.1  # .env carregado antes dos arquivos de config/ (SPEC-101)
4.0.1 → 4.0.2  # Rollback correto em Database::transaction() e listeners de ciclo de vida
4.1.0 → 4.1.1  # Correção de bug pontual sem alterar API
```

#### ⚠️ Procedimento para PATCH:
1. **Identificar** e **isolar** o bug
2. **Criar testes** que reproduzem o problema
3. **Implementar** a correção mínima necessária
4. **Verificar** que não quebra nada existente
5. **Deploy rápido** (patches devem ser releases rápidos)

---

## 🛠️ Como Usar os Scripts de Release

O PivotPHP Core inclui scripts em `scripts/release/` para gerenciar versões e releases.

### version-bump.sh

Incrementa a versão a partir do arquivo `VERSION`, cria o commit e a tag.

```bash
# Incrementar PATCH (4.1.0 → 4.1.1)
scripts/release/version-bump.sh patch

# Incrementar MINOR (4.1.0 → 4.2.0)
scripts/release/version-bump.sh minor

# Incrementar MAJOR (4.1.0 → 5.0.0)
scripts/release/version-bump.sh major

# Visualizar próxima versão sem aplicar
scripts/release/version-bump.sh minor --dry-run

# Fazer bump sem criar commit nem tag
scripts/release/version-bump.sh patch --no-commit

# Fazer bump com commit, mas sem criar tag
scripts/release/version-bump.sh minor --no-tag
```

O script:
1. **Lê** a versão atual do arquivo `VERSION`
2. **Calcula** a nova versão baseada no tipo de bump
3. **Atualiza** o arquivo `VERSION`
4. **Atualiza** `composer.json` **somente se** houver um campo `version` (o core não o define, por
   ser publicado no Packagist)
5. **Cria commit** com mensagem padronizada (`chore: bump version to X.Y.Z`)
6. **Cria tag Git** `vX.Y.Z`
7. **Valida** o formato semântico (X.Y.Z)

### prepare_release.sh

Valida o projeto na versão atual (lida do arquivo `VERSION`) antes de publicar. **Não** altera a
versão. Executa validações de estrutura, sintaxe PHP, testes, PHPStan, `composer validate` e os
scripts de validação do projeto.

```bash
scripts/release/prepare_release.sh          # modo interativo
scripts/release/prepare_release.sh --ci     # modo CI (sem prompts)
```

### release.sh

Cria a release depois da preparação. Exige branch limpo (sem alterações não commitadas) e recebe a
versão como argumento.

```bash
# Criar a release com o tipo explícito
scripts/release/release.sh 4.1.1 patch
scripts/release/release.sh 4.2.0 minor
scripts/release/release.sh 5.0.0 major
```

### Exemplo de Uso Completo

```bash
# Cenário: correção de bug de segurança
$ scripts/release/version-bump.sh patch

ℹ️  Versão atual: 4.1.0
ℹ️  Nova versão: 4.1.1
ℹ️  Tipo de bump: patch

Confirma o bump de 4.1.0 para 4.1.1? (y/N): y

✅ VERSION file atualizado para 4.1.1
✅ Commit criado
✅ Tag v4.1.1 criada

🎉 Versão bumped com sucesso!
  • 4.1.0 → 4.1.1
  • Tipo: patch
  • Commit criado: ✅
  • Tag criada: ✅

ℹ️  Para publicar: git push origin --tags
```

---

## 📋 Checklist de Versionamento

### Antes de Qualquer Release:

#### ✅ Validações Obrigatórias:
- [ ] `VERSION` atualizado e igual a `Application::VERSION`
- [ ] Todos os testes passando (`composer test`)
- [ ] PHPStan Level 9 sem erros (`composer phpstan`)
- [ ] PSR-12 compliance (`composer cs:check`)
- [ ] Validação de documentação (`composer validate:docs`)
- [ ] Validação do projeto (`composer validate:project`)
- [ ] `composer validate` sem erros
- [ ] Auditoria de dependências (`composer audit`)

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

### Performance:
- **PATCH**: Melhorias de performance são PATCH se não alteram API
- **MINOR**: Novas otimizações que adicionam funcionalidade
- **MAJOR**: Mudanças que quebram garantias de performance existentes

### PSR Compliance:
- **PATCH**: Correções para melhor aderência a PSR existente
- **MINOR**: Suporte a nova PSR
- **MAJOR**: Mudança de PSR fundamental (ex.: trocar PSR-7 por PSR-17)

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
composer quality:check
scripts/release/prepare_release.sh
```

### 3. Versionamento
```bash
# Para nova funcionalidade (MINOR)
scripts/release/version-bump.sh minor

# Resultado: 4.1.0 → 4.2.0
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
- [Guia de Contribuição](contributing/README.md)

### Scripts Relacionados:
- `scripts/release/version-bump.sh` - Gerenciamento de versões
- `scripts/release/prepare_release.sh` - Validação para release
- `scripts/release/release.sh` - Criação da release
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

*Última atualização: v4.1.0 - Processo de release alinhado aos scripts atuais (`scripts/release/`)*
