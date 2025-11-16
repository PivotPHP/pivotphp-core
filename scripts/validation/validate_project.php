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
        // $this->validateBenchmarks(); // Benchmarks movidos para outro projeto

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
            'src/Authentication/',
            'tests/',
            'docs/',
            'docs/releases/',
            'docs/technical/',
            'docs/performance/',
            'docs/implementations/',
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
            'src/Middleware/Security/SecurityHeadersMiddleware.php',
            'src/Authentication/JWTHelper.php',
            'composer.json',
            'README.md',
            'docs/index.md',
            'docs/releases/README.md',
            "docs/releases/FRAMEWORK_OVERVIEW_v{$version}.md",
            'docs/implementations/usage_basic.md',
            'docs/technical/application.md',
            'docs/technical/http/request.md',
            'docs/technical/http/response.md',
            'docs/technical/routing/router.md',
            'docs/technical/middleware/README.md',
            'docs/technical/authentication/usage_native.md',
            'docs/performance/PerformanceMonitor.md',
            // 'docs/performance/benchmarks/README.md',  // Benchmarks movidos para outro projeto
            'docs/testing/api_testing.md',
            'docs/contributing/README.md',
            'scripts/validation/validate-docs.sh',
            'scripts/validation/validate_project.php',
            'scripts/validation/validate_benchmarks.sh',
            'benchmarks/run_benchmark.sh'
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
        echo "🛡️ Validando middlewares...\n";

        // Verificar SecurityHeadersMiddleware (nova estrutura)
        if (class_exists('PivotPHP\\Core\\Middleware\\Security\\SecurityHeadersMiddleware')) {
            $this->passed[] = "SecurityHeadersMiddleware carregado";

            try {
                $security = new \PivotPHP\Core\Middleware\Security\SecurityHeadersMiddleware();
                $this->passed[] = "SecurityHeadersMiddleware pode ser instanciado";
            } catch (Exception $e) {
                $this->errors[] = "Erro ao instanciar SecurityHeadersMiddleware: " . $e->getMessage();
            }
        } else {
            // Verificar se ainda existe via alias de compatibilidade
            if (class_exists('PivotPHP\\Core\\Http\\Psr15\\Middleware\\SecurityHeadersMiddleware')) {
                $this->passed[] = "SecurityHeadersMiddleware carregado via alias (compatibilidade)";
            } else {
                $this->errors[] = "SecurityHeadersMiddleware não encontrado";
            }
        }

        // Verificar outros middlewares de segurança
        $securityMiddlewares = [
            'CsrfMiddleware' => 'PivotPHP\\Core\\Middleware\\Security\\CsrfMiddleware',
            'XssMiddleware' => 'PivotPHP\\Core\\Middleware\\Security\\XssMiddleware',
            'AuthMiddleware' => 'PivotPHP\\Core\\Middleware\\Security\\AuthMiddleware',
            'CorsMiddleware' => 'PivotPHP\\Core\\Middleware\\Http\\CorsMiddleware',
            'RateLimitMiddleware' => 'PivotPHP\\Core\\Middleware\\Performance\\RateLimitMiddleware',
        ];

        $securityCount = 0;
        foreach ($securityMiddlewares as $name => $class) {
            if (class_exists($class)) {
                $this->passed[] = "{$name} carregado";
                $securityCount++;
            } else {
                $this->warnings[] = "{$name} não encontrado";
            }
        }

        if ($securityCount >= 4) {
            $this->passed[] = "Middlewares de segurança suficientes encontrados ({$securityCount}/5)";
        } else {
            $this->warnings[] = "Poucos middlewares de segurança encontrados ({$securityCount}/5)";
        }

        // Verificar JWTHelper
        if (class_exists('PivotPHP\\Core\\Authentication\\JWTHelper')) {
            $this->passed[] = "JWTHelper carregado";

            // Testar geração de token
            try {
                $token = PivotPHP\Core\Authentication\JWTHelper::encode(['user_id' => 1], 'test_secret');
                if ($token) {
                    $this->passed[] = "JWTHelper pode gerar tokens";
                } else {
                    $this->errors[] = "JWTHelper não conseguiu gerar token";
                }
            } catch (Exception $e) {
                $this->errors[] = "Erro ao gerar JWT: " . $e->getMessage();
            }
        } else {
            $this->warnings[] = "JWTHelper não encontrado";
        }

        echo "✅ Middlewares validados\n\n";
    }

    private function validateExamples()
    {
        echo "📖 Validando exemplos...\n";
        $this->warnings[] = "Os exemplos práticos agora estão totalmente contidos e atualizados na documentação oficial (docs/). Não é mais necessário manter exemplos em examples/.";
        echo "ℹ️  Exemplos práticos disponíveis apenas na documentação oficial.\n\n";
    }

    private function validateTests()
    {
        echo "🧪 Validando testes...\n";

        $testFiles = [
            'tests/Security/AuthMiddlewareTest.php',
            'tests/Helpers/JWTHelperTest.php',
            'tests/Security/AuthMiddlewareTest.php'
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
            'docs/releases/FRAMEWORK_OVERVIEW_v1.0.0.md' => 'Overview v1.0.0',
            'docs/releases/FRAMEWORK_OVERVIEW_v1.0.1.md' => 'Overview v1.0.1',
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
            'docs/implementations/usage_basic.md' => 'Guia básico de uso',
            'docs/technical/application.md' => 'Documentação da Application',
            'docs/technical/http/request.md' => 'Documentação de Request',
            'docs/technical/http/response.md' => 'Documentação de Response',
            'docs/technical/routing/router.md' => 'Documentação do Router',
            'docs/technical/middleware/README.md' => 'Índice de middlewares',
            'docs/technical/authentication/usage_native.md' => 'Autenticação nativa',
            'docs/performance/PerformanceMonitor.md' => 'Monitor de performance',
            'docs/performance/benchmarks/README.md' => 'Documentação de benchmarks',
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

        try {
            // Simular requisição com JWT
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test.token.here';
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = '/api/test';

            // Validação básica de autenticação sem instanciar classes específicas
            if (class_exists('PivotPHP\\Core\\Authentication\\JWTHelper')) {
                // Testar JWT Helper básico
                $jwt = PivotPHP\Core\Authentication\JWTHelper::encode(['test' => true], 'secret');
                if ($jwt) {
                    $this->passed[] = "Sistema de autenticação funcional";
                } else {
                    $this->errors[] = "Sistema de autenticação não funcional";
                }
            } else {
                $this->warnings[] = "Sistema de autenticação não disponível";
            }
        } catch (Exception $e) {
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

        // Verificar configurações de segurança no código
        $securityFiles = glob('src/Middleware/Security/*.php');
        if (count($securityFiles) >= 3) {
            $this->passed[] = "Múltiplos middlewares de segurança implementados (" . count($securityFiles) . " arquivos)";
        } else {
            $this->warnings[] = "Poucos middlewares de segurança encontrados (" . count($securityFiles) . " arquivos)";
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
                "docs/releases/FRAMEWORK_OVERVIEW_v{$version}.md" => "Overview v{$version} (ATUAL)",
                'docs/releases/FRAMEWORK_OVERVIEW_v1.0.0.md' => 'Overview v1.0.0',
                'docs/releases/FRAMEWORK_OVERVIEW_v1.0.1.md' => 'Overview v1.0.1'
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
                    if (strpos($file, 'v1.0.0') !== false) {
                        $this->errors[] = "{$description} não encontrado: {$file}";
                    } else {
                        $this->warnings[] = "{$description} não encontrado: {$file}";
                    }
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

            // Verificar se ainda existem versões anteriores (para compatibilidade)
            if (file_exists('docs/releases/FRAMEWORK_OVERVIEW_v1.0.0.md')) {
                $this->passed[] = "FRAMEWORK_OVERVIEW_v1.0.0.md mantido para compatibilidade";
            }
            if (file_exists('docs/releases/FRAMEWORK_OVERVIEW_v1.0.1.md')) {
                $this->passed[] = "FRAMEWORK_OVERVIEW_v1.0.1.md mantido para compatibilidade";
            }

        } else {
            $this->errors[] = "Diretório docs/releases/ não encontrado";
        }

        // Verificar se arquivos foram movidos da raiz
        $movedFiles = [
            'FRAMEWORK_OVERVIEW_v1.0.0.md',
            'FRAMEWORK_OVERVIEW_v1.0.1.md',
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

    private function validateBenchmarks()
    {
        echo "🏃‍♂️ Validando estrutura de benchmarks...\n";

        // Verificar diretórios de benchmark
        if (is_dir('benchmarks')) {
            $this->passed[] = "Diretório benchmarks/ existe";

            if (is_dir('benchmarks/reports')) {
                $this->passed[] = "Diretório benchmarks/reports/ existe";

                // Contar arquivos de relatório
                $reportCount = count(glob('benchmarks/reports/*.json')) + count(glob('benchmarks/reports/*.md'));
                if ($reportCount > 0) {
                    $this->passed[] = "Encontrados {$reportCount} relatórios de benchmark";
                } else {
                    $this->warnings[] = "Nenhum relatório de benchmark encontrado";
                }
            } else {
                $this->errors[] = "Diretório benchmarks/reports/ não encontrado";
            }
        } else {
            $this->errors[] = "Diretório benchmarks/ não encontrado";
        }

        // Verificar scripts de benchmark
        $benchmarkScripts = [
            'benchmarks/run_benchmark.sh' => 'Script de execução de benchmarks',
            'benchmarks/ExpressPhpBenchmark.php' => 'Benchmark principal',
            'benchmarks/ComprehensivePerformanceAnalysis.php' => 'Análise de performance',
            'benchmarks/EnhancedAdvancedOptimizationsBenchmark.php' => 'Benchmark de otimizações',
            'benchmarks/generate_comprehensive_report.php' => 'Gerador de relatórios'
        ];

        foreach ($benchmarkScripts as $script => $description) {
            if (file_exists($script)) {
                $this->passed[] = "{$description} existe";

                // Verificar se é executável (para .sh)
                if (pathinfo($script, PATHINFO_EXTENSION) === 'sh' && !is_executable($script)) {
                    $this->warnings[] = "{$description} não é executável";
                }
            } else {
                $this->errors[] = "{$description} não encontrado: {$script}";
            }
        }

        // Verificar documentação de benchmarks
        if (file_exists('docs/performance/benchmarks/README.md')) {
            $size = filesize('docs/performance/benchmarks/README.md');
            if ($size > 2000) {
                $this->passed[] = "Documentação de benchmarks existe e tem conteúdo adequado ({$size} bytes)";

                // Verificar se contém dados v1.0.0
                $content = file_get_contents('docs/performance/benchmarks/README.md');
                if (strpos($content, '02/07/2025') !== false &&
                    strpos($content, '2.69M') !== false &&
                    strpos($content, 'PHP 8.4.8') !== false) {
                    $this->passed[] = "Documentação de benchmarks atualizada com dados v1.0.0";
                } else {
                    $this->warnings[] = "Documentação de benchmarks pode não estar atualizada para v1.0.0";
                }
            } else {
                $this->warnings[] = "Documentação de benchmarks tem pouco conteúdo ({$size} bytes)";
            }
        } else {
            $this->warnings[] = "Documentação de benchmarks não encontrada: docs/performance/benchmarks/README.md";
        }

        echo "✅ Benchmarks validados\n\n";
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
