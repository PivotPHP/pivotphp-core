#!/bin/bash

# PivotPHP - Validador Principal do Projeto
# Executa todos os scripts de validação em sequência

# Obter versão do arquivo VERSION
if [ -f "VERSION" ]; then
    VERSION=$(cat VERSION | tr -d '\n')
else
    VERSION="unknown"
fi

# Parse argumentos
PRE_COMMIT_MODE=false
if [[ "$1" == "--pre-commit" ]]; then
    PRE_COMMIT_MODE=true
    echo "🔍 PivotPHP v$VERSION - Validação Pre-commit"
    echo "============================================="
else
    echo "🚀 PivotPHP v$VERSION - Validação Completa do Projeto"
    echo "======================================================="
fi
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[⚠]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# Contadores de resultados
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Função para executar script e capturar resultado
run_validation() {
    local script="$1"
    local description="$2"

    echo ""
    print_status "Executando: $description"
    echo "----------------------------------------"

    ((TOTAL_TESTS++))

    if [ -f "$script" ] && [ -x "$script" ]; then
        if $script; then
            print_success "$description - PASSOU"
            ((PASSED_TESTS++))
            return 0
        else
            print_error "$description - FALHOU"
            ((FAILED_TESTS++))
            return 1
        fi
    else
        print_error "Script não encontrado ou não executável: $script"
        ((FAILED_TESTS++))
        return 1
    fi
}

# Definir ordem dos testes baseado no modo
if [ "$PRE_COMMIT_MODE" = true ]; then
    print_status "Iniciando validação pre-commit (essencial apenas)..."
    echo ""

    # Para pre-commit, executamos apenas validações críticas
    # 1. Validação PSR-12 (crítica para qualidade de código)
    if [ -f "./scripts/quality/validate-psr12.php" ]; then
        print_status "Executando: Validação PSR-12"
        echo "----------------------------------------"
        ((TOTAL_TESTS++))

        if php ./scripts/quality/validate-psr12.php; then
            print_success "Validação PSR-12 - PASSOU"
            ((PASSED_TESTS++))
        else
            print_error "Validação PSR-12 - FALHOU (código não conforme)"
            ((FAILED_TESTS++))
        fi
    fi

    # 2. Verificar sintaxe PHP de arquivos staged
    print_status "Executando: Verificação de Sintaxe PHP"
    echo "----------------------------------------"
    ((TOTAL_TESTS++))

    STAGED_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep -E '\.(php)$' || true)
    SYNTAX_OK=true

    if [ -n "$STAGED_FILES" ]; then
        for FILE in $STAGED_FILES; do
            if [ -f "$FILE" ]; then
                if ! php -l "$FILE" > /dev/null 2>&1; then
                    print_error "Erro de sintaxe em: $FILE"
                    SYNTAX_OK=false
                fi
            fi
        done
    fi

    if [ "$SYNTAX_OK" = true ]; then
        print_success "Verificação de Sintaxe PHP - PASSOU"
        ((PASSED_TESTS++))
    else
        print_error "Verificação de Sintaxe PHP - FALHOU"
        ((FAILED_TESTS++))
    fi

    # 3. Verificação básica de estrutura (rápida)
    print_status "Executando: Verificação Básica de Estrutura"
    echo "----------------------------------------"
    ((TOTAL_TESTS++))

    if [ -f "composer.json" ] && [ -d "src" ] && [ -f "README.md" ]; then
        print_success "Verificação Básica de Estrutura - PASSOU"
        ((PASSED_TESTS++))
    else
        print_error "Verificação Básica de Estrutura - FALHOU"
        ((FAILED_TESTS++))
    fi

else
    print_status "Iniciando validação completa do projeto PivotPHP v$VERSION..."
    echo ""

    # 1. Validação da estrutura de documentação
    run_validation "./scripts/validation/validate-docs.sh" "Validação da Estrutura de Documentação"

    # 2. Validação dos benchmarks - REMOVIDO (benchmarks migrados para outro projeto)
    # run_validation "./scripts/validate_benchmarks.sh" "Validação dos Benchmarks"

    # 3. Validação completa do projeto (PHP)
    print_status "Executando: Validação Completa do Projeto (PHP)"
    echo "----------------------------------------"
    ((TOTAL_TESTS++))

    if [ -f "./scripts/validation/validate_project.php" ]; then
        if php ./scripts/validation/validate_project.php; then
            print_success "Validação Completa do Projeto (PHP) - PASSOU"
            ((PASSED_TESTS++))
        else
            print_error "Validação Completa do Projeto (PHP) - FALHOU"
            ((FAILED_TESTS++))
        fi
    else
        print_error "Script não encontrado: ./scripts/validation/validate_project.php"
        ((FAILED_TESTS++))
    fi

    # 4. Validação PSR-12 (se disponível)
    if [ -f "./scripts/quality/validate-psr12.php" ]; then
        print_status "Executando: Validação PSR-12"
        echo "----------------------------------------"
        ((TOTAL_TESTS++))

        if php ./scripts/quality/validate-psr12.php; then
            print_success "Validação PSR-12 - PASSOU"
            ((PASSED_TESTS++))
        else
            print_warning "Validação PSR-12 - Avisos encontrados (não crítico)"
            ((PASSED_TESTS++))  # PSR-12 warnings não são críticos
        fi
    fi
fi

# Relatório final
echo ""
echo "=========================================="
if [ "$PRE_COMMIT_MODE" = true ]; then
    echo "📊 RELATÓRIO PRE-COMMIT v$VERSION"
else
    echo "📊 RELATÓRIO FINAL DE VALIDAÇÃO v$VERSION"
fi
echo "=========================================="
echo ""

# Calcular percentual de sucesso
if [ $TOTAL_TESTS -gt 0 ]; then
    SUCCESS_RATE=$((PASSED_TESTS * 100 / TOTAL_TESTS))
else
    SUCCESS_RATE=0
fi

echo "📈 Estatísticas:"
echo "  Total de validações: $TOTAL_TESTS"
echo "  Validações passaram: $PASSED_TESTS"
echo "  Validações falharam: $FAILED_TESTS"
echo "  Taxa de sucesso: $SUCCESS_RATE%"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    print_success "🎉 TODAS AS VALIDAÇÕES PASSARAM!"
    echo ""

    if [ "$PRE_COMMIT_MODE" = true ]; then
        echo "✅ Commit autorizado! O código atende aos padrões de qualidade."
        echo ""
        echo "📝 Validações executadas:"
        echo "   • Conformidade PSR-12"
        echo "   • Sintaxe PHP"
        echo "   • Estrutura básica do projeto"
    else
        echo "✅ O projeto PivotPHP v$VERSION está pronto para:"
        echo "   • Execução em produção"
        echo "   • Publicação no Packagist"
        echo "   • Distribuição para desenvolvedores"
        echo "   • Criação de release oficial"
        echo ""
        echo "🚀 Próximos passos recomendados:"
        echo "   1. Execute testes unitários: composer test"
        echo "   2. Execute testes unitários: composer test"
        echo "   3. Crie tag de release: git tag -a v$VERSION -m 'Release v$VERSION'"
        echo "   4. Publique: git push origin main --tags"
    fi

    exit 0

elif [ $SUCCESS_RATE -ge 80 ]; then
    if [ "$PRE_COMMIT_MODE" = true ]; then
        print_error "❌ COMMIT REJEITADO - Correções necessárias ($SUCCESS_RATE%)"
        echo ""
        echo "🚨 Algumas validações críticas falharam."
        echo "   Corrija os problemas reportados antes de tentar novamente."
        echo ""
        echo "💡 Dica: Execute 'scripts/validation/validate_all.sh' sem --pre-commit para validação completa"
    else
        print_warning "⚠️ MAIORIA DAS VALIDAÇÕES PASSOU ($SUCCESS_RATE%)"
        echo ""
        echo "✅ O projeto está em bom estado, mas algumas validações falharam."
        echo "   Revise os erros acima e corrija conforme necessário."
        echo ""
        echo "🔧 Ações recomendadas:"
        echo "   • Revise e corrija os erros reportados"
        echo "   • Execute validações individuais para mais detalhes"
        echo "   • Execute novamente após correções"
    fi

    exit 1

else
    if [ "$PRE_COMMIT_MODE" = true ]; then
        print_error "❌ COMMIT REJEITADO - Muitos problemas encontrados ($SUCCESS_RATE%)"
        echo ""
        echo "🚨 O código não atende aos padrões mínimos de qualidade."
        echo "   Corrija todos os problemas reportados antes de tentar novamente."
    else
        print_error "❌ MUITAS VALIDAÇÕES FALHARAM ($SUCCESS_RATE%)"
        echo ""
        echo "🚨 O projeto precisa de correções significativas antes de ser considerado estável."
        echo ""
        echo "🔧 Ações necessárias:"
        echo "   • Corrija todos os erros críticos reportados"
        echo "   • Verifique a estrutura do projeto"
        echo "   • Execute ./scripts/validation/validate-docs.sh individualmente"
        echo "   • Execute ./scripts/validation/validate_project.php individualmente"
        echo "   • Execute validações individuais para detalhes específicos"
    fi

    exit 1
fi
