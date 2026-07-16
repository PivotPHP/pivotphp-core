# Modo Debug - PivotPHP

O PivotPHP oferece um sistema robusto de debugging para facilitar o desenvolvimento e resolução de problemas. Este guia explica como configurar e usar o modo debug efetivamente.

## 📋 Índice

- [Configuração Básica](#configuração-básica)
- [Recursos Disponíveis](#recursos-disponíveis)
- [Exemplos Práticos](#exemplos-práticos)
- [Configurações Avançadas](#configurações-avançadas)
- [Considerações de Segurança](#considerações-de-segurança)
- [Troubleshooting](#troubleshooting)

---

## 🔧 Configuração Básica

### 1. Ativando o Modo Debug

**Método 1: Variável de Ambiente (Recomendado)**
```bash
# .env
APP_DEBUG=true
APP_ENV=development
LOG_LEVEL=debug
```

**Método 2: Configuração Direta**
```php
// config/app.php
'debug' => true,
'env' => 'development',
'log_level' => 'debug'
```

**Método 3: Detecção Automática**
```php
// O framework detecta automaticamente se está em desenvolvimento
// quando APP_ENV=development ou APP_DEBUG=true
```

### 2. Verificando o Status do Debug

```php
use PivotPHP\Core\Core\Application;

$app = new Application();

// Verificar se debug está ativo
if ($app->isDebugMode()) {
    echo "Debug mode ativo";
}

// Obter configuração de debug
$debugEnabled = $app->config()->get('app.debug', false);
```

---

## 🛠️ Recursos Disponíveis

### 1. Tratamento de Erros Detalhado

**Em modo debug, você recebe:**
- Stack trace completo
- Variáveis de contexto
- Informações da requisição
- Detalhes do arquivo e linha do erro

```php
// Exemplo de resposta de erro em debug mode
{
    "error": {
        "message": "Undefined variable: user",
        "type": "ErrorException",
        "file": "/app/src/Controllers/UserController.php",
        "line": 42,
        "trace": [
            {
                "file": "/app/src/Controllers/UserController.php",
                "line": 42,
                "function": "show",
                "class": "UserController"
            }
        ],
        "request": {
            "method": "GET",
            "uri": "/api/users/123",
            "headers": {...},
            "params": {...}
        }
    }
}
```

### 2. Logging Avançado

**Níveis de Log Disponíveis:**
```php
use PivotPHP\Core\Services\Logger;

$logger = new Logger();

// Diferentes níveis de log
$logger->debug('Informação de debug');
$logger->info('Informação geral');
$logger->warning('Aviso importante');
$logger->error('Erro crítico');
$logger->critical('Erro fatal');
```

### 3. Performance Monitoring

**Monitoramento automático em debug:**
```php
// Métricas disponíveis automaticamente
$metrics = $app->getPerformanceMetrics();

echo "Tempo de execução: " . $metrics['execution_time'] . "ms\n";
echo "Uso de memória: " . $metrics['memory_usage'] . "MB\n";
echo "Queries executadas: " . $metrics['database_queries'] . "\n";
```

### 4. Debugging de Rotas

```php
// Listar todas as rotas registradas
$routes = $app->getRouter()->getRoutes();

// Debug de rota específica
$app->get('/debug-route', function($req, $res) {
    $res->json([
        'route' => $req->getRoute(),
        'params' => $req->getParams(),
        'query' => $req->getQueryParams(),
        'headers' => $req->getHeaders()
    ]);
});
```

---

## 💡 Exemplos Práticos

### 1. Configuração Completa de Development

```php
// .env para desenvolvimento
APP_DEBUG=true
APP_ENV=development
LOG_LEVEL=debug

// Para projetos com Cycle ORM
CYCLE_LOG_QUERIES=true
CYCLE_PROFILE_QUERIES=true

// Configuração de banco para debug
DB_HOST=localhost
DB_DATABASE=pivotphp_dev
DB_USERNAME=dev_user
DB_PASSWORD=dev_pass
```

### 2. Middleware de Debug Personalizado

```php
<?php

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DebugMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Processar requisição
        $response = $handler->handle($request);
        
        // Adicionar headers de debug
        if (config('app.debug')) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            $memoryUsage = memory_get_usage() - $startMemory;
            
            $response = $response
                ->withHeader('X-Debug-Time', $executionTime . 'ms')
                ->withHeader('X-Debug-Memory', $memoryUsage . ' bytes')
                ->withHeader('X-Debug-Queries', $this->getQueryCount());
        }
        
        return $response;
    }
    
    private function getQueryCount(): int
    {
        // Implementar contagem de queries
        return 0;
    }
}
```

### 3. Endpoint de Debug Info

```php
// Endpoint para informações de debug
$app->get('/debug/info', function($req, $res) {
    if (!$app->isDebugMode()) {
        return $res->status(404)->json(['error' => 'Not found']);
    }
    
    $res->json([
        'framework' => 'PivotPHP',
        'version' => '1.0.1',
        'php_version' => PHP_VERSION,
        'debug_mode' => true,
        'environment' => config('app.env'),
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
        'loaded_extensions' => get_loaded_extensions(),
        'config' => [
            'debug' => config('app.debug'),
            'log_level' => config('app.log_level'),
            'timezone' => config('app.timezone')
        ]
    ]);
});
```

### 4. Debug de Autenticação

```php
// Middleware de debug para autenticação
$app->use(function($req, $res, $next) {
    if (config('app.debug')) {
        error_log("Auth Debug: " . json_encode([
            'headers' => $req->getHeaders(),
            'method' => $req->getMethod(),
            'uri' => $req->getUri()->getPath(),
            'user_agent' => $req->getHeader('User-Agent')[0] ?? 'Unknown'
        ]));
    }
    
    return $next($req, $res);
});
```

---

## ⚙️ Configurações Avançadas

### 1. Configuração de Logs

```php
// config/logging.php
return [
    'default' => 'file',
    'channels' => [
        'file' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => env('LOG_LEVEL', 'info'),
            'max_files' => 7,
        ],
        'debug' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../logs/debug.log',
            'level' => 'debug',
            'max_files' => 3,
        ],
        'console' => [
            'driver' => 'console',
            'level' => 'debug',
        ]
    ]
];
```

### 2. Debug Condicional

```php
// Função helper para debug
function debug_log($message, $context = []) {
    if (config('app.debug')) {
        error_log(json_encode([
            'message' => $message,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s'),
            'memory' => memory_get_usage(true)
        ]));
    }
}

// Uso
debug_log('User authentication attempt', [
    'user_id' => $userId,
    'ip' => $request->getClientIp(),
    'user_agent' => $request->getHeader('User-Agent')[0] ?? 'Unknown'
]);
```

### 3. Profiling de Performance

```php
// Classe para profiling
class DebugProfiler
{
    private static $markers = [];
    
    public static function start($name) {
        if (config('app.debug')) {
            self::$markers[$name] = [
                'start' => microtime(true),
                'memory_start' => memory_get_usage()
            ];
        }
    }
    
    public static function end($name) {
        if (config('app.debug') && isset(self::$markers[$name])) {
            $marker = self::$markers[$name];
            $marker['end'] = microtime(true);
            $marker['memory_end'] = memory_get_usage();
            $marker['duration'] = ($marker['end'] - $marker['start']) * 1000;
            $marker['memory_used'] = $marker['memory_end'] - $marker['memory_start'];
            
            error_log("Profile [$name]: {$marker['duration']}ms, Memory: {$marker['memory_used']} bytes");
        }
    }
}

// Uso
DebugProfiler::start('database_query');
$users = $userRepository->findAll();
DebugProfiler::end('database_query');
```

---

## 🔒 Considerações de Segurança

### ⚠️ Avisos Importantes

1. **NUNCA deixe debug ativo em produção**
   ```php
   // Verificação de segurança
   if (config('app.env') === 'production' && config('app.debug')) {
       throw new \Exception('Debug mode não deve estar ativo em produção!');
   }
   ```

2. **Informações sensíveis em logs**
   ```php
   // ❌ Errado - pode vazar senhas
   debug_log('User login', ['password' => $password]);
   
   // ✅ Correto - omitir dados sensíveis
   debug_log('User login', ['user_id' => $userId, 'success' => true]);
   ```

3. **Proteção de endpoints de debug**
   ```php
   // Proteger endpoints de debug
   $app->get('/debug/*', function($req, $res, $next) {
       if (!config('app.debug') || config('app.env') === 'production') {
           return $res->status(404)->json(['error' => 'Not found']);
       }
       return $next($req, $res);
   });
   ```

### 🛡️ Boas Práticas

1. **Configuração por ambiente**
   ```bash
   # .env.development
   APP_DEBUG=true
   LOG_LEVEL=debug
   
   # .env.production
   APP_DEBUG=false
   LOG_LEVEL=error
   ```

2. **Sanitização de logs**
   ```php
   function sanitizeForLog($data) {
       $sensitive = ['password', 'token', 'secret', 'key'];
       
       foreach ($sensitive as $field) {
           if (isset($data[$field])) {
               $data[$field] = '[REDACTED]';
           }
       }
       
       return $data;
   }
   ```

3. **Rotação de logs**
   ```php
   // Configurar rotação automática
   'max_files' => 7,  // Manter apenas 7 dias
   'max_size' => '10MB',  // Máximo 10MB por arquivo
   ```

---

## 🔧 Troubleshooting

### Problemas Comuns

1. **Debug não está funcionando**
   ```php
   // Verificar configuração
   var_dump(config('app.debug'));
   var_dump($_ENV['APP_DEBUG']);
   var_dump(getenv('APP_DEBUG'));
   ```

2. **Logs não aparecem**
   ```php
   // Verificar permissões do diretório de logs
   chmod 755 logs/
   chmod 644 logs/app.log
   ```

3. **Performance lenta em debug**
   ```php
   // Reduzir nível de log
   LOG_LEVEL=info  // ao invés de debug
   ```

### Comandos Úteis

```bash
# Verificar logs em tempo real
tail -f logs/app.log

# Verificar configuração
php -r "var_dump(getenv('APP_DEBUG'));"

# Limpar logs
rm logs/*.log

# Verificar uso de memória
php -r "echo 'Memory: ' . memory_get_usage(true) . ' bytes';"
```

---

## 📚 Recursos Relacionados

- [Tratamento de Erros](../exceptions/ErrorHandling.md)
- [Performance Monitoring](../../performance/PerformanceMonitor.md)
- [Configuração da Aplicação](../application.md)
- [Logging Avançado](../logging/advanced-logging.md)

---

## 🎯 Próximos Passos

1. **Configure seu ambiente** com as variáveis de debug
2. **Implemente middleware de debug** personalizado
3. **Configure rotação de logs** adequada
4. **Teste em ambiente de desenvolvimento**
5. **Documente configurações** específicas do seu projeto

---

*Desenvolvido com ❤️ para a comunidade PivotPHP*