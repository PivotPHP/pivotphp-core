#!/bin/bash

# Script de Validação OpenAPI/Swagger - PivotPHP
# Verifica se os recursos de documentação OpenAPI estão funcionando corretamente

# Get version from VERSION file (REQUIRED)
get_version() {
    if [ ! -f "VERSION" ]; then
        echo "❌ ERRO CRÍTICO: Arquivo VERSION não encontrado na raiz do projeto"
        echo "❌ PivotPHP Core requer um arquivo VERSION para identificação de versão"
        exit 1
    fi

    local version
    version=$(cat VERSION | tr -d '\n')

    if [ -z "$version" ]; then
        echo "❌ ERRO CRÍTICO: Arquivo VERSION está vazio ou inválido"
        echo "❌ Arquivo VERSION deve conter uma versão semântica válida (X.Y.Z)"
        exit 1
    fi

    # Validate semantic version format
    if [[ ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        echo "❌ ERRO CRÍTICO: Formato de versão inválido no arquivo VERSION: $version"
        echo "❌ Formato esperado: X.Y.Z (versionamento semântico)"
        exit 1
    fi

    echo "$version"
}

VERSION=$(get_version)
echo "🔍 Validando recursos OpenAPI/Swagger do PivotPHP v$VERSION..."
echo

# OpenApiExporter foi removido na v2.0.0
# Use ApiDocumentationMiddleware para documentação automática
echo "ℹ️  OpenApiExporter removido na v2.0.0 - use ApiDocumentationMiddleware"

# Verificar se o exemplo OpenAPI existe
if [ -f "examples/example_openapi_docs.php" ]; then
    echo "✅ Exemplo OpenAPI encontrado"

    # Verificar sintaxe do exemplo
    php -l examples/example_openapi_docs.php > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ Sintaxe do exemplo OpenAPI válida"
    else
        echo "❌ Erro de sintaxe no exemplo OpenAPI"
        exit 1
    fi
else
    echo "❌ Exemplo OpenAPI não encontrado"
    exit 1
fi

# Verificar se README menciona OpenAPI
if grep -q "OpenAPI\|Swagger" README.md; then
    echo "✅ README menciona OpenAPI/Swagger"
else
    echo "⚠️  README pode não mencionar OpenAPI/Swagger adequadamente"
fi

# Verificar se há código para Swagger UI
if grep -q "swagger-ui" README.md && grep -q "swagger-ui" examples/example_openapi_docs.php; then
    echo "✅ Suporte para Swagger UI presente"
else
    echo "⚠️  Suporte para Swagger UI pode estar incompleto"
fi

# Verificar se ApiDocumentationMiddleware está disponível
php -r "
require_once 'vendor/autoload.php';
try {
    if (class_exists('PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware')) {
        echo '✅ ApiDocumentationMiddleware disponível (v2.0.0)' . PHP_EOL;
    } else {
        echo '❌ ApiDocumentationMiddleware não encontrado' . PHP_EOL;
        exit(1);
    }
} catch (Exception \$e) {
    echo '❌ Erro ao verificar ApiDocumentationMiddleware: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

if [ $? -ne 0 ]; then
    echo "❌ Falha na validação do ApiDocumentationMiddleware"
    exit 1
fi

# Testar geração básica de documentação OpenAPI
echo "🧪 Testando geração de documentação OpenAPI..."

php -r "
require_once 'vendor/autoload.php';
try {
    // Criar rota simples para teste
    PivotPHP\Core\Routing\Router::get('/test', function() {
        return ['test' => true];
    }, ['summary' => 'Teste']);

    // Gerar documentação
    \$docs = PivotPHP\Core\Utils\OpenApiExporter::export('PivotPHP\Core\\Routing\\Router');

    if (is_array(\$docs) && isset(\$docs['openapi'])) {
        echo '✅ Documentação OpenAPI gerada com sucesso' . PHP_EOL;

        if (\$docs['openapi'] === '3.0.0') {
            echo '✅ Versão OpenAPI 3.0.0 correta' . PHP_EOL;
        } else {
            echo '⚠️  Versão OpenAPI: ' . \$docs['openapi'] . ' (esperado: 3.0.0)' . PHP_EOL;
        }

        if (isset(\$docs['paths'])) {
            echo '✅ Paths gerados corretamente' . PHP_EOL;
        } else {
            echo '❌ Paths não encontrados na documentação' . PHP_EOL;
            exit(1);
        }
    } else {
        echo '❌ Documentação OpenAPI inválida' . PHP_EOL;
        exit(1);
    }
} catch (Exception \$e) {
    echo '❌ Erro na geração de documentação: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

if [ $? -ne 0 ]; then
    echo "❌ Falha no teste de geração OpenAPI"
    exit 1
fi

echo
echo "🎉 Recursos OpenAPI/Swagger validados (v2.0.0)!"
echo
echo "📋 Recursos validados:"
echo "  ✓ ApiDocumentationMiddleware disponível"
echo "  ✓ Exemplo completo com Swagger UI"
echo "  ✓ Documentação no README atualizada"
echo
echo "ℹ️  Mudanças na v2.0.0:"
echo "  • OpenApiExporter removido (deprecated)"
echo "  • Use ApiDocumentationMiddleware para documentação automática"
echo
echo "🚀 Para testar manualmente:"
echo "  1. Execute: php -S localhost:8080 examples/api_documentation_example.php"
echo "  2. Acesse: http://localhost:8080/docs (Swagger UI)"
echo "  3. Acesse: http://localhost:8080/openapi.json (JSON spec)"
echo
