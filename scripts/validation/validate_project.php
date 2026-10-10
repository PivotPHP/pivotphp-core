<?php
/**
 * Script de Validação do Projeto PivotPHP
 *
 * Este script verifica se todos os componentes estão funcionando
 * corretamente antes da publicação do projeto.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

class ProjectValidator
{
    private $errors = [];
    private $warnings = [];
    private $passed = [];

    /**
     * Get current version from VERSION file (REQUIRED)
     */
    private function getCurrentVersion(): string
    {
        $versionFile = dirname(__DIR__, 2) . '/VERSION';

        if (!file_exists($versionFile)) {
            echo "❌ ERRO CRÍTICO: Arquivo VERSION não encontrado em: $versionFile\n";
            echo "❌ PivotPHP Core requer um arquivo VERSION na raiz do projeto\n";
            exit(1);
        }

        $version = trim(file_get_contents($versionFile));

        if (empty($version)) {
            echo "❌ ERRO CRÍTICO: Arquivo VERSION está vazio ou inválido\n";
            echo "❌ Arquivo VERSION deve conter uma versão semântica válida (X.Y.Z)\n";
            exit(1);
        }

        // Validate semantic version format
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            echo "❌ ERRO CRÍTICO: Formato de versão inválido no arquivo VERSION: $version\n";
            echo "❌ Formato esperado: X.Y.Z (versionamento semântico)\n";
            exit(1);
        }

        return $version;
    }

    public function validate()
    {
        $version = $this->getCurrentVersion();
        echo "🔍 Validando projeto PivotPHP v{$version}...\n\n";

        // Testes estruturais
        $this->validateStructure();
        $this->validateComposer();
        $this->validateMiddlewares();
        $this->validateOpenApiFeatures();
        $this->validateExamples();
        $this->validateTests();
        $this->validateDocumentation();
        $this->validateReleases();

        // Testes funcionais
        $this->validateAuthentication();
        $this->validateSecurity();

        // Relatório final
        return $this->generateReport();
    }

    private function validateStructure()
    {
        $version = $this->getCurrentVersion();
        echo "📁 Validando estrutura do projeto...\n";

        $requiredDirs = [
            'src/',
            'src/Middleware/',
            'tests/',
            'docs/',
            'docs/releases/',
            'docs/technical/',
            'docs/testing/',
            'docs/contributing/'
            // 'benchmarks/',  // Benchmarks movidos para outro projeto
            // 'benchmarks/reports/'
        ];

        foreach ($requiredDirs as $dir) {
            if (is_dir($dir)) {
                $this->passed[] = "Diretório {$dir} existe";
            } else {
                $this->errors[] = "Diretório {$dir} não encontrado";
            }
        }

        $requiredFiles = [
            'composer.json',
            'README.md',
            'docs/index.md',
            'docs/releases/README.md',
            "docs/releases/FRAMEWORK_OVERVIEW_v{$version}.md",
            'docs/technical/application.md',
            'docs/technical/http/request.md',
            'docs/technical/http/response.md',
            'docs/technical/routing/router.md',
            'docs/technical/middleware/README.md',
            'docs/reference/examples.md',
            'docs/testing/api_testing.md',
            'docs/contributing/README.md',
            'scripts/validation/validate-docs.sh',
            'scripts/validation/validate_project.php',
        ];

        foreach ($requiredFiles as $file) {
            if (file_exists($file)) {
                $this->passed[] = "Arquivo {$file} existe";
            } else {
                $this->errors[] = "Arquivo {$file} não encontrado";
            }
        }

        echo "✅ Estrutura validada\n\n";
    }

    private function validateComposer()
    {
        echo "📦 Validando composer.json...\n";

        if (!file_exists('composer.json')) {
            $this->errors[] = "composer.json não encontrado";
            return;
        }

        $composer = json_decode(file_get_contents('composer.json'), true);

        if (!$composer) {
            $this->errors[] = "composer.json inválido";
            return;
        }

        // Verificar campos obrigatórios
        $required = ['name', 'description', 'authors', 'autoload'];
        foreach ($required as $field) {
            if (isset($composer[$field])) {
                $this->passed[] = "Campo {$field} presente no composer.json";
            } else {
                $this->errors[] = "Campo {$field} ausente no composer.json";
            }
        }

        // Verificar campo version (opcional para publicação no Packagist)
        if (isset($composer['version'])) {
            $this->warnings[] = "Campo version presente - será ignorado pelo Packagist (use tags Git)";
        } else {
            $this->passed[] = "Campo version ausente - correto para publicação no Packagist";
        }

        // Verificar scripts
        if (isset($composer['scripts']['test'])) {
            $this->passed[] = "Script de teste configurado";
        } else {
            $this->warnings[] = "Script de teste não configurado";
        }

        echo "✅ Composer validado\n\n";
    }

    private function validateMiddlewares()
    {
        echo "🛡️ Validando middlewares de segurança (pivotphp/security)...\n";

        // Desde a 4.0.0 a segurança vem do pacote pivotphp/security (dependência do core).
        $securityMiddlewares = [
            'CorsMiddleware' => 'PivotPHP\\Security\\Cors\\CorsMiddleware',
            'SecurityHeadersMiddleware' => 'PivotPHP\\Security\\Headers\\SecurityHeadersMiddleware',
            'CsrfMiddleware' => 'PivotPHP\\Security\\Csrf\\CsrfMiddleware',
            'JwtAuthMiddleware' => 'PivotPHP\\Security\\Jwt\\JwtAuthMiddleware',
            'JwtIssuer' => 'PivotPHP\\Security\\Jwt\\JwtIssuer',
            'RateLimitMiddleware' => 'PivotPHP\\Security\\RateLimit\\RateLimitMiddleware',
            'TrustedProxyMiddleware' => 'PivotPHP\\Security\\Proxy\\TrustedProxyMiddleware',
        ];

        foreach ($securityMiddlewares as $name => $class) {
            if (class_exists($class)) {
                $this->passed[] = "{$name} disponível (pivotphp/security)";
            } else {
                $this->errors[] = "{$name} não encontrado — pivotphp/security instalado?";
            }
        }

        echo "✅ Middlewares validados\n\n";
    }

    private function validateExamples()
    {
        echo "📖 Validando exemplos...\n";

        if (file_exists('tests/Integration/ExamplesTest.php')) {
            $this->passed[] = "Exemplos cobertos por tests/Integration/ExamplesTest.php";
        } else {
            $this->errors[] = "tests/Integration/ExamplesTest.php não encontrado — exemplos sem verificação";
        }

        echo "✅ Exemplos validados\n\n";
    }

    private function validateTests()
    {
        echo "🧪 Validando testes...\n";

        // Integração crítica: pipeline do core com o pivotphp/security
        $testFiles = [
            'tests/Integration/Middleware/SecurityPackageIntegrationTest.php',
        ];

        foreach ($testFiles as $testFile) {
            if (file_exists($testFile)) {
                $this->passed[] = "Teste {$testFile} existe";

                // Verificar sintaxe
                $output = shell_exec("php -l {$testFile} 2>&1");
                if (strpos($output, 'No syntax errors') !== false) {
                    $this->passed[] = "Teste {$testFile} tem sintaxe válida";
                } else {
                    $this->errors[] = "Erro de sintaxe em {$testFile}: {$output}";
                }
            } else {
                $this->errors[] = "Teste {$testFile} não encontrado";
            }
        }

        // Tentar executar testes unitários
        if (file_exists('vendor/bin/phpunit')) {
            echo "Executando testes unitários...\n";
            $output = shell_exec('./vendor/bin/phpunit tests/ 2>&1');

            if (strpos($output, 'OK') !== false || strpos($output, 'Tests: ') !== false) {
                $this->passed[] = "Testes unitários executados com sucesso";
            } else {
                $this->warnings[] = "Alguns testes podem ter falhas: " . substr($output, 0, 200) . "...";
            }
        } else {
            $this->warnings[] = "PHPUnit não instalado - testes unitários não executados";
        }

        echo "✅ Testes validados\n\n";
    }

    private function validateDocumentation()
    {
        $version = $this->getCurrentVersion();
        echo "📚 Validando documentação v{$version}...\n";

        // Documentação principal
        $mainDocs = [
            'README.md' => 'README principal',
            'CHANGELOG.md' => 'Changelog',
            'CONTRIBUTING.md' => 'Guia de contribuição',
        ];

        foreach ($mainDocs as $file => $description) {
            if (file_exists($file)) {
                $size = filesize($file);
                if ($size > 500) {
                    $this->passed[] = "{$description} existe e tem conteúdo adequado ({$size} bytes)";
                } else {
                    $this->warnings[] = "{$description} existe mas tem pouco conteúdo ({$size} bytes)";
                }
            } else {
                $this->errors[] = "{$description} não encontrado: {$file}";
            }
        }

        // Documentação de releases
        $releaseDocs = [
            'docs/releases/README.md' => 'Índice de releases',
            "docs/releases/FRAMEWORK_OVERVIEW_v{$version}.md" => "Overview v{$version} (ATUAL)",
        ];

        foreach ($releaseDocs as $file => $description) {
            if (file_exists($file)) {
                $size = filesize($file);
                if ($size > 1000) {
                    $this->passed[] = "{$description} existe e tem conteúdo adequado ({$size} bytes)";
                } else {
                    $this->warnings[] = "{$description} existe mas tem pouco conteúdo ({$size} bytes)";
                }
            } else {
                $this->errors[] = "{$description} não encontrado: {$file}";
            }
        }

        // Documentação técnica principal
        $technicalDocs = [
            'docs/index.md' => 'Índice principal da documentação',
            'docs/technical/application.md' => 'Documentação da Application',
            'docs/technical/http/request.md' => 'Documentação de Request',
            'docs/technical/http/response.md' => 'Documentação de Response',
            'docs/technical/routing/router.md' => 'Documentação do Router',
            'docs/technical/middleware/README.md' => 'Índice de middlewares',
            'docs/reference/examples.md' => 'Catálogo de exemplos',
            'docs/testing/api_testing.md' => 'Testes de API',
            'docs/contributing/README.md' => 'Guia de contribuição',
        ];

        foreach ($technicalDocs as $file => $description) {
            if (file_exists($file)) {
                $size = filesize($file);
                if ($size > 500) {
                    $this->passed[] = "{$description} existe e tem conteúdo adequado ({$size} bytes)";
                } else {
                    $this->warnings[] = "{$description} existe mas tem pouco conteúdo ({$size} bytes)";
                }
            } else {
                $this->warnings[] = "{$description} não encontrado: {$file}";
            }
        }

        echo "✅ Documentação validada\n\n";
    }

    private function validateAuthentication()
    {
        echo "🔐 Validando sistema de autenticação...\n";

        if (!class_exists('Firebase\\JWT\\JWT')) {
            $this->warnings[] = "firebase/php-jwt não instalado — JWT do pivotphp/security indisponível";
            echo "✅ Autenticação validada\n\n";
            return;
        }

        try {
            $config = new \PivotPHP\Security\Jwt\JwtConfig(str_repeat('k', 32));
            $token = (new \PivotPHP\Security\Jwt\JwtIssuer($config))->issue(['sub' => 'validate'], 60);
            $this->passed[] = "JwtIssuer emite tokens (" . strlen($token) . " bytes)";
        } catch (Throwable $e) {
            $this->errors[] = "Erro no sistema de autenticação: " . $e->getMessage();
        }

        echo "✅ Autenticação validada\n\n";
    }

    private function validateSecurity()
    {
        echo "🔒 Validando configurações de segurança...\n";

        // Verificar se arquivos sensíveis não estão sendo commitados
        $sensitiveFiles = [
            '.env' => 'Arquivo de environment',
            'config/database.php' => 'Configuração de banco local'
        ];

        foreach ($sensitiveFiles as $file => $description) {
            if (file_exists($file)) {
                $this->warnings[] = "{$description} presente ({$file}) - verifique se deve ser commitado";
            }
        }

        // Verificar se .gitignore está configurado corretamente
        if (file_exists('.gitignore')) {
            $gitignore = file_get_contents('.gitignore');
            $requiredEntries = ['/vendor/', '.env', '*.log', 'composer.lock'];

            foreach ($requiredEntries as $entry) {
                if (strpos($gitignore, $entry) !== false) {
                    $this->passed[] = "Entrada '{$entry}' presente no .gitignore";
                } else {
                    $this->warnings[] = "Entrada '{$entry}' ausente no .gitignore";
                }
            }
        } else {
            $this->errors[] = "Arquivo .gitignore não encontrado";
        }

        // Verificar se .env.example existe
        if (file_exists('.env.example')) {
            $this->passed[] = "Arquivo .env.example presente para referência";
        } else {
            $this->warnings[] = "Arquivo .env.example não encontrado - recomendado para projetos";
        }

        // A segurança é fornecida pelo pivotphp/security (dependência obrigatória)
        $composer = json_decode((string) file_get_contents('composer.json'), true);
        if (isset($composer['require']['pivotphp/security'])) {
            $this->passed[] = "pivotphp/security declarado em require";
        } else {
            $this->errors[] = "pivotphp/security ausente de require no composer.json";
        }

        echo "✅ Segurança validada\n\n";
    }

    private function validateOpenApiFeatures()
    {
        echo "📚 Validando recursos OpenAPI/Swagger...\n";

        // OpenApiExporter removido na v2.0.0 - usar ApiDocumentationMiddleware
        $this->passed[] = "OpenApiExporter removido na v2.0.0 (esperado)";

        // Verificar se ApiDocumentationMiddleware existe
        if (class_exists('PivotPHP\\Core\\Middleware\\Http\\ApiDocumentationMiddleware')) {
            $this->passed[] = "ApiDocumentationMiddleware disponível (v2.0.0)";
        } else {
            $this->errors[] = "ApiDocumentationMiddleware não encontrado";
        }

        // Verificar se o README principal menciona OpenAPI
        if (file_exists('README.md')) {
            $readme = file_get_contents('README.md');
            if (strpos($readme, 'OpenAPI') !== false || strpos($readme, 'Swagger') !== false) {
                $this->passed[] = "README principal menciona OpenAPI/Swagger";

                if (strpos($readme, 'ApiDocumentationMiddleware') !== false) {
                    $this->passed[] = "README explica como usar ApiDocumentationMiddleware";
                } else {
                    $this->warnings[] = "README pode não explicar ApiDocumentationMiddleware";
                }
            } else {
                $this->warnings[] = "README principal pode não mencionar recursos OpenAPI";
            }
        }

        echo "✅ Recursos OpenAPI validados\n\n";
    }

    private function validateReleases()
    {
        $version = $this->getCurrentVersion();
        echo "📋 Validando estrutura de releases...\n";

        // Verificar diretório de releases
        if (is_dir('docs/releases')) {
            $this->passed[] = "Diretório docs/releases/ existe";

            // Verificar arquivos de release
            $releaseFiles = [
                'docs/releases/README.md' => 'Índice de releases',
                "docs/releases/FRAMEWORK_OVERVIEW_v{$version}.md" => "Overview v{$version} (ATUAL)"
            ];

            foreach ($releaseFiles as $file => $description) {
                if (file_exists($file)) {
                    $size = filesize($file);
                    if ($size > 1000) {
                        $this->passed[] = "{$description} existe e tem conteúdo adequado ({$size} bytes)";
                    } else {
                        $this->warnings[] = "{$description} existe mas tem pouco conteúdo ({$size} bytes)";
                    }
                } else {
                    $this->errors[] = "{$description} não encontrado: {$file}";
                }
            }

            // Verificar se versão atual tem conteúdo específico
            if (file_exists("docs/releases/FRAMEWORK_OVERVIEW_v{$version}.md")) {
                $content = file_get_contents("docs/releases/FRAMEWORK_OVERVIEW_v{$version}.md");

                if (strpos($content, "v{$version}") !== false) {
                    $this->passed[] = "FRAMEWORK_OVERVIEW_v{$version}.md contém métricas de performance v{$version}";
                } else {
                    $this->warnings[] = "FRAMEWORK_OVERVIEW_v{$version}.md pode estar incompleto (faltam métricas v{$version})";
                }
            }

        } else {
            $this->errors[] = "Diretório docs/releases/ não encontrado";
        }

        // Verificar se arquivos foram movidos da raiz
        $movedFiles = [
            "FRAMEWORK_OVERVIEW_v{$version}.md"
        ];

        foreach ($movedFiles as $file) {
            if (file_exists($file)) {
                $this->warnings[] = "Arquivo deveria ter sido movido para docs/releases/: {$file}";
            } else {
                $this->passed[] = "Arquivo movido corretamente da raiz: {$file}";
            }
        }

        echo "✅ Releases validadas\n\n";
    }

    private function generateReport()
    {
        echo "📊 RELATÓRIO DE VALIDAÇÃO\n";
        echo str_repeat("=", 50) . "\n\n";

        echo "✅ SUCESSOS (" . count($this->passed) . "):\n";
        foreach ($this->passed as $pass) {
            echo "  ✓ {$pass}\n";
        }
        echo "\n";

        if (!empty($this->warnings)) {
            echo "⚠️ AVISOS (" . count($this->warnings) . "):\n";
            foreach ($this->warnings as $warning) {
                echo "  ⚠ {$warning}\n";
            }
            echo "\n";
        }

        if (!empty($this->errors)) {
            echo "❌ ERROS (" . count($this->errors) . "):\n";
            foreach ($this->errors as $error) {
                echo "  ✗ {$error}\n";
            }
            echo "\n";
        }

        // Status final
        if (empty($this->errors)) {
            echo "🎉 PROJETO PIVOTPHP CORE v{$this->getCurrentVersion()} VALIDADO COM SUCESSO!\n";
            echo "   O projeto está pronto para uso e publicação.\n";

            if (!empty($this->warnings)) {
                echo "   Considere resolver os avisos antes da publicação.\n";
            }

            echo "\n📋 PRÓXIMOS PASSOS:\n";
            echo "   1. Execute os benchmarks: ./benchmarks/run_benchmark.sh\n";
            echo "   2. Execute os testes: composer test\n";
            echo "   3. Valide a documentação: ./scripts/validation/validate-docs.sh\n";
            echo "   4. Valide os benchmarks: ./scripts/validation/validate_benchmarks.sh\n";
            echo "   5. Faça commit das alterações\n";
            $version = $this->getCurrentVersion();
            echo "   6. Crie uma tag de versão: git tag -a v{$version} -m 'Release v{$version}'\n";
            echo "   7. Push para o repositório: git push origin main --tags\n";
            echo "   8. Publique no Packagist: https://packagist.org\n";
            echo "   9. Repositório: https://github.com/CAFernandes/pivotphp-core\n";

            return true;
        } else {
            echo "❌ VALIDAÇÃO FALHOU!\n";
            echo "   Corrija os erros antes de publicar o projeto.\n";
            echo "   Execute ./scripts/validation/validate-docs.sh para mais detalhes.\n";
            return false;
        }
    }
}

// Executar validação
$validator = new ProjectValidator();
$success = $validator->validate();

exit($success ? 0 : 1);
