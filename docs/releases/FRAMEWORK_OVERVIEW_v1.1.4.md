# PivotPHP Core v1.1.4 - Framework Overview

**Versão:** 1.1.4 (Script Consolidation & Infrastructure Optimization Edition)
**Data de Release:** Janeiro 2025
**Status:** Production Release

## 📋 Visão Geral

PivotPHP Core v1.1.4 representa um marco na **maturidade da infraestrutura** do framework. Esta versão foca na **consolidação de scripts**, **automação de versioning** e **otimização da experiência de desenvolvimento**. É uma evolução fundamental que elimina complexidade desnecessária enquanto mantém toda a performance e funcionalidade das versões anteriores.

## 🎯 Objetivos da Versão

- **Consolidação de Scripts:** Redução de 40% no número de scripts (25 → 15)
- **Automação de Versioning:** Detecção automática via arquivo VERSION obrigatório
- **Infraestrutura Limpa:** Eliminação de hardcoding e duplicações
- **GitHub Actions Otimizado:** Workflows consolidados e corrigidos
- **Experiência do Desenvolvedor:** Ferramentas simplificadas e confiáveis
- **Documentação Completa:** Guias de versionamento e infraestrutura

## 📊 Métricas da Versão

### Consolidação de Infraestrutura
- **Scripts Removidos:** 10 scripts duplicados/obsoletos
- **Scripts Ativos:** 15 scripts consolidados e otimizados
- **Redução de Complexidade:** 40% menos arquivos para manter
- **Hardcoding Eliminado:** 100% dos scripts agora usam detecção automática
- **GitHub Actions:** 4 → 3 workflows (25% redução)

### Performance (Mantida de v1.1.3)
- **JSON Pooling:** 161K ops/sec (small), 17K ops/sec (medium), 1.7K ops/sec (large)
- **Request Creation:** 28,693 ops/sec
- **Response Creation:** 131,351 ops/sec
- **Object Pooling:** 24,161 ops/sec
- **Route Processing:** 31,699 ops/sec
- **Performance Média:** 40,476 ops/sec

### Qualidade de Código
- **PHPStan:** Level 9, 0 erros
- **PSR-12:** 100% compliance
- **Testes:** 684 CI tests + 131 integration tests
- **Cobertura:** ≥30% (automated validation)
- **Scripts Validation:** 100% success rate com validação rigorosa

## 🆕 Principais Inovações v1.1.4

### 🔧 Sistema de Scripts Consolidado

**Biblioteca Compartilhada:**
```bash
# Nova biblioteca de utilitários
scripts/utils/version-utils.sh

# Funções disponíveis:
- get_version()              # Detecção automática de versão
- get_project_root()         # Detecção do diretório raiz
- validate_project_context() # Validação do contexto PivotPHP
- print_version_banner()     # Banner consistente
```

**Scripts Principais Consolidados:**
- `scripts/quality/quality-check.sh` - ⭐ **Principal**: Validação completa consolidada
- `scripts/release/version-bump.sh` - ⭐ **Versioning**: Gerenciamento semântico automático
- `scripts/release/prepare_release.sh` - ⭐ **Release**: Preparação automatizada

### 📦 Sistema de Versionamento Automático

**Arquivo VERSION Obrigatório:**
```bash
# Arquivo VERSION na raiz do projeto
echo "1.1.4" > VERSION

# Validação rigorosa:
- Formato X.Y.Z obrigatório
- Scripts falham se arquivo ausente
- Detecção automática em todos os scripts
```

**Comandos de Versionamento:**
```bash
# Increment patch (1.1.4 → 1.1.5)
scripts/release/version-bump.sh patch

# Increment minor (1.1.4 → 1.2.0)
scripts/release/version-bump.sh minor

# Increment major (1.1.4 → 2.0.0)
scripts/release/version-bump.sh major

# Preview next version
scripts/release/version-bump.sh minor --dry-run
```

### 🚀 GitHub Actions Otimizado

**Workflows Consolidados:**
- `ci.yml` - CI/CD principal com scripts consolidados
- `pre-release.yml` - Validação pré-release com detecção automática
- `release.yml` - Release final com validação de consistência

**Melhorias Implementadas:**
- Usa `scripts/quality/quality-check.sh` consolidado
- Detecção automática da versão do arquivo VERSION
- URLs corrigidas para repositório PivotPHP Core
- Validação de consistência entre Git tags e VERSION file

## 🔄 Scripts Removidos (Duplicados/Obsoletos)

### ❌ Scripts Eliminados:
1. `quality-check-v114.sh` → Hardcoded version
2. `validate_all_v114.sh` → Hardcoded version
3. `quick-quality-check.sh` → Duplicação
4. `simple_pre_release.sh` → Substituído
5. `quality-gate.sh` → Funcionalidade incorporada
6. `quality-metrics.sh` → Funcionalidade incorporada
7. `test-php-versions-quick.sh` → Duplicação
8. `ci-validation.sh` → Funcionalidade incorporada
9. `setup-precommit.sh` → Script único de configuração
10. `adapt-psr7-v1.php` → Script específico não essencial

### ✅ Scripts Consolidados Mantidos:
- **Qualidade (5):** quality/quality-check.sh, validation/validate_all.sh, validation/validate_project.php, validation/validate-documentation.php, validation/validate-psr12.php
- **Release (3):** release/version-bump.sh, release/prepare_release.sh, release/release.sh
- **Documentação (2):** validation/validate-docs.sh, validation/validate_openapi.sh
- **Testes (2):** testing/run_stress_tests.sh, testing/test-all-php-versions.sh
- **Utilitários (3):** validation/validate_benchmarks.sh, utils/switch-psr7-version.php, utils/version-utils.sh

## 📚 Nova Documentação

### 📖 Guia de Versionamento Semântico
**Arquivo:** `docs/VERSIONING_GUIDE.md` (315 linhas)

**Conteúdo Abrangente:**
- **Quando incrementar MAJOR, MINOR, PATCH**
- **Exemplos específicos do PivotPHP Core**
- **Workflow completo de development → release**
- **Como usar `scripts/release/version-bump.sh`**
- **Checklist de validação pré-release**
- **FAQ com dúvidas comuns**

### 🔧 Documentação de Scripts
**Arquivo:** `scripts/README.md` (atualizado)

**Organização por Categoria:**
- Scripts principais para uso diário
- Scripts de validação específica
- Utilitários e configuração
- Workflow recomendado
- Resolução de problemas

## 🛡️ Validação Rigorosa

### ❌ Condições de Erro Crítico:
```bash
# Arquivo VERSION não encontrado
❌ ERRO CRÍTICO: Arquivo VERSION não encontrado
❌ PivotPHP Core requer um arquivo VERSION na raiz do projeto

# Arquivo VERSION vazio
❌ ERRO CRÍTICO: Arquivo VERSION está vazio ou inválido
❌ Arquivo VERSION deve conter uma versão semântica válida (X.Y.Z)

# Formato inválido
❌ ERRO CRÍTICO: Formato de versão inválido: invalid.format
❌ Formato esperado: X.Y.Z (versionamento semântico)
```

### ✅ Validações Implementadas:
- **Formato semântico obrigatório:** X.Y.Z
- **Detecção de contexto:** Verifica se está no projeto PivotPHP Core
- **Mensagens claras:** Erros críticos em português
- **Falha rápida:** Scripts param imediatamente ao detectar problemas

## 🔄 Workflow de Desenvolvimento Atualizado

### 🚀 Desenvolvimento Diário:
```bash
# Validação antes de commit
scripts/quality/quality-check.sh

# Validação completa (opcional)
scripts/validation/validate_all.sh
```

### 📦 Preparação de Release:
```bash
# 1. Bump da versão
scripts/release/version-bump.sh [patch|minor|major]

# 2. Preparação final
scripts/release/prepare_release.sh

# 3. Release (se validação passou)
scripts/release/release.sh
```

### 🧪 Validação Estendida:
```bash
# Testes cross-version PHP
scripts/testing/test-all-php-versions.sh

# Testes de stress
scripts/testing/run_stress_tests.sh

# Validação de documentação
scripts/validate-documentation.php
```

## 🏗️ Arquitetura Consolidada

### 📁 Estrutura de Scripts Otimizada:
```
scripts/
├── lib/
│   └── version-utils.sh      # 🆕 Biblioteca compartilhada
├── quality-check.sh          # ⭐ Script principal consolidado
├── version-bump.sh           # ⭐ Gerenciamento de versões
├── prepare_release.sh        # ⭐ Preparação de release
├── validate_all.sh           # Orchestrador principal
├── validate_project.php      # Validação de projeto
├── validate-documentation.php # Validação de documentação
├── test-all-php-versions.sh  # Testes multi-versão
├── run_stress_tests.sh       # Testes de stress
└── [8 outros scripts especializados]
```

### 🔗 Integração Perfeita:
- **VERSION file** como única fonte de verdade
- **Scripts consolidados** eliminam duplicação
- **GitHub Actions** alinhados com infraestrutura
- **Documentação** completa e atualizada

## 📈 Comparação de Versões

| Aspecto | v1.1.3 | v1.1.4 | Melhoria |
|---------|--------|--------|----------|
| **Scripts ativos** | 25 | 15 | 40% redução |
| **Scripts duplicados** | 10 | 0 | 100% eliminação |
| **Hardcoding** | Presente | Ausente | 100% eliminação |
| **GitHub Actions** | 4 workflows | 3 workflows | 25% redução |
| **Detecção de versão** | Manual | Automática | 100% automação |
| **Documentação de infraestrutura** | Limitada | Completa | 315 linhas adicionais |

## 🎯 Benefícios para Desenvolvedores

### ✅ Simplificação:
- **Menos arquivos** para entender e manter
- **Comando único** para validação completa
- **Versioning automático** sem intervenção manual
- **Mensagens claras** em caso de erro

### ✅ Confiabilidade:
- **Validação rigorosa** impede erros comuns
- **Scripts testados** com detecção de contexto
- **Workflows funcionais** sem referências quebradas
- **Documentação atualizada** e sincronizada

### ✅ Produtividade:
- **Setup mais rápido** com menos configuração
- **Comandos intuitivos** seguindo convenções
- **Workflow padronizado** para toda a equipe
- **Troubleshooting fácil** com guias detalhados

## 🚀 Roadmap Futuro

### v1.1.5 (Próxima PATCH):
- Pequenas correções baseadas em feedback
- Otimizações de performance pontuais
- Melhorias na documentação

### v1.2.0 (Próxima MINOR):
- Novas funcionalidades mantendo compatibilidade
- Middleware adicional
- Integrações com novas PSRs

### v2.0.0 (Próxima MAJOR):
- Mudanças arquiteturais se necessário
- Breaking changes planejados
- Evolução baseada em feedback da comunidade

## 📋 Checklist de Migração para v1.1.4

### ✅ Para Desenvolvedores:
- [ ] Verificar arquivo `VERSION` existe na raiz do projeto
- [ ] Atualizar comandos para usar scripts consolidados
- [ ] Revisar workflow local com novos scripts
- [ ] Ler `docs/VERSIONING_GUIDE.md` para versionamento

### ✅ Para Projetos:
- [ ] Remover referências a scripts removidos
- [ ] Atualizar CI/CD para usar workflows atualizados
- [ ] Verificar que VERSION file está no formato X.Y.Z
- [ ] Testar scripts consolidados no ambiente local

## 🔗 Recursos e Links

### 📚 Documentação:
- **Guia de Versionamento:** `docs/VERSIONING_GUIDE.md`
- **Scripts README:** `scripts/README.md`
- **Consolidação Summary:** `CONSOLIDATION_SUMMARY.md`

### 🛠️ Scripts Principais:
- **Validação Principal:** `scripts/quality/quality-check.sh`
- **Gerenciamento de Versão:** `scripts/release/version-bump.sh`
- **Preparação Release:** `scripts/release/prepare_release.sh`

### 🌐 Comunidade:
- **GitHub:** https://github.com/PivotPHP/pivotphp-core
- **Packagist:** https://packagist.org/packages/pivotphp/core

---

## 📝 Conclusão

PivotPHP Core v1.1.4 estabelece uma **base sólida e limpa** para o desenvolvimento futuro. A consolidação de scripts e automação de versioning reduz significativamente a complexidade operacional enquanto mantém todas as capacidades técnicas do framework.

Esta versão representa um **investimento na experiência do desenvolvedor** e na **sustentabilidade do projeto** a longo prazo. Com scripts mais limpos, workflows otimizados e documentação completa, v1.1.4 prepara o PivotPHP Core para crescer de forma sustentável e confiável.

**🚀 PivotPHP Core v1.1.4 - Infrastructure Excellence Edition**
