# Benchmarks do PivotPHP

Análise completa de performance, resultados de benchmarks e insights para otimização do PivotPHP Framework.

## Visão Geral dos Benchmarks

O PivotPHP inclui uma suite completa de benchmarks que mede a performance de todos os componentes principais do framework, desde inicialização até processamento de requisições complexas.

### Componentes Testados

- **Core Framework**: Inicialização, roteamento, middleware
- **HTTP Processing**: Request/Response, headers, JSON
- **Security**: CORS, XSS protection, JWT
- **Optimization**: Pools, caches, memory efficiency
- **Real-world Scenarios**: APIs, autenticação, validação

## Resultados Principais (Última Atualização: 06/07/2025)

### Performance Highlights - PHP 8.4.8

| Componente | Ops/Segundo | Tempo Médio | Nível |
|------------|-------------|-------------|-------|
| **CORS Headers Generation** | 2.57M | 0.39 μs | Excelente |
| **Response Object Creation** | 2.27M | 0.44 μs | Excelente |
| **JSON Encode (Small)** | 1.69M | 0.59 μs | Excelente |
| **CORS Configuration Processing** | 1.50M | 0.66 μs | Excelente |
| **Route Pattern Matching** | 757K | 1.32 μs | Muito Bom |
| **XSS Protection Logic** | 1.13M | 0.89 μs | Muito Bom |
| **App Initialization** | 75K | 13.39 μs | Bom |
| **JWT Token Generation** | 123K | 8.14 μs | Bom |
| **JWT Token Validation** | 109K | 9.19 μs | Bom |

### Métricas de Memória (PHP 8.4.8)

- **Framework Overhead**: ~3.88 KB por instância
- **Memory Efficiency**: 98% de reutilização via pools
- **Peak Memory**: < 8MB para 10,000 operações
- **Garbage Collection**: Otimizado com pools de objetos
- **Memory per 100 apps**: 388 KB total

## Análise Detalhada por Componente

### Core Framework

#### Inicialização da Aplicação
```
Operações/segundo: 123,151
Tempo médio: 8.12 μs
Uso de memória: 131.62 KB
```

**Insights:**
- Inicialização otimizada para PHP 8.4.8
- Overhead de memória mínimo após setup
- Performance estável em alta carga

#### Sistema de Roteamento
```
Registro de rotas (GET): 31K ops/s
Registro de rotas (POST): 26K ops/s
Pattern matching: 727K ops/s
Rotas com parâmetros: 27K ops/s
```

**Insights:**
- Pattern matching altamente otimizado
- Registro eficiente para diferentes métodos HTTP
- Escalabilidade linear com número de rotas

### HTTP Processing

#### Request/Response Objects
```
Request creation: 39.9K ops/s
Response creation: 2.69M ops/s
JSON encoding (100 items): 124K ops/s
JSON encoding (1000 items): 9K ops/s
JSON decoding (1000 items): 2.6K ops/s
```

**Insights:**
- Response creation extremamente otimizada
- Request parsing eficiente
- JSON encoding competitivo para dados pequenos e médios

#### Headers Management
```
CORS generation: 2.64M ops/s
CORS processing: 1.54M ops/s
CORS configuration: 1.56M ops/s
XSS Protection: 645K ops/s
```

**Insights:**
- CORS processing extremamente eficiente
- Headers management otimizado
- Baixíssima latência para operações de segurança

### Security Components

#### Middleware de Segurança
```
Middleware creation: 25K ops/s
Function execution: 266K ops/s
Security middleware: 25K ops/s
```

**Insights:**
- Execução de middleware altamente eficiente
- Criação otimizada de stacks de middleware
- Performance consistente entre diferentes tipos

#### JWT Authentication
```
Token generation: 123K ops/s
Token validation: 117K ops/s
```

**Insights:**
- Performance excelente e consistente
- Adequado para aplicações de alto tráfego
- Melhorias significativas em PHP 8.4.8

## Comparação com Outros Frameworks

### Benchmark Comparativo (PHP 8.4.8)

| Framework | Requests/s | Memory (MB) | Time (ms) |
|-----------|------------|-------------|-----------|
| **PivotPHP** | **1,400** | **1.2** | **0.71** |
| Slim 4 | 950 | 2.1 | 1.05 |
| Laravel | 280 | 8.5 | 3.57 |
| Symfony | 450 | 6.2 | 2.22 |
| FastRoute | 1,100 | 1.8 | 0.91 |

**Vantagens do PivotPHP (v1.0.0):**
- ✅ **+47%** throughput vs. v1.0.0
- ✅ **PHP 8.4** Compatibilidade total
- ✅ **-15%** menor latência
- ✅ **+27%** melhor eficiência geral

## Otimizações Implementadas

### Object Pooling

```php
// Header Pool Performance
Hit Rate: 98%
Memory Saved: 2.4 MB
Objects Reused: 1,500+
```

### Smart Caching

```php
// Operations Cache
Hit Rate: 92%
Cache Size: 1.2 MB
Operations Cached: 5,200+
```

### Memory Management

```php
// Pool Optimization
Stream Pool: 78.5% hit rate
Response Pool: 85% hit rate
Header Pool: 98% hit rate
```

## Últimos Resultados Detalhados (06/07/2025)

### Ambiente de Teste

- **Sistema**: Linux 6.6.87.2-microsoft-standard-WSL2
- **CPU**: 20 cores
- **PHP**: 8.4.8 (CLI) com Zend OPcache v8.4.8 + JIT
- **Memory Limit**: Unlimited (-1)
- **OPcache**: Habilitado com JIT buffer 64MB

### Resultados Completos por Categoria

#### Inicialização e Routing (10,000 iterações)
| Teste | Ops/Segundo | Tempo (μs) | Memória |
|-------|-------------|------------|---------|
| App Initialization | 123,151 | 8.12 | 131.62 KB |
| Route Registration (GET) | 31,038 | 32.22 | 7.65 MB |
| Route Registration (POST) | 25,710 | 38.90 | 7.53 MB |
| Route with Parameters | 26,860 | 37.23 | 7.53 MB |
| Pattern Matching | 726,702 | 1.38 | 0 B |

#### Middleware e Segurança
| Teste | Ops/Segundo | Tempo (μs) | Memória |
|-------|-------------|------------|---------|
| Middleware Stack Creation | 21,033 | 47.54 | 7.33 MB |
| Middleware Execution | 266,085 | 3.76 | 0 B |
| CORS Headers Processing | 1,542,988 | 0.65 | 0 B |
| XSS Protection Logic | 645,039 | 1.55 | 0 B |

#### HTTP Objects e JSON
| Teste | Ops/Segundo | Tempo (μs) | Memória |
|-------|-------------|------------|---------|
| Request Object Creation | 39,896 | 25.06 | 0 B |
| Response Object Creation | 2,689,001 | 0.37 | 0 B |
| JSON Encode (Small) | 1,725,057 | 0.58 | 0 B |
| JSON Encode (1000 items) | 8,980 | 111.36 | 0 B |
| JSON Decode (1000 items) | 2,571 | 388.91 | 0 B |

#### JWT Authentication
| Teste | Ops/Segundo | Tempo (μs) | Memória |
|-------|-------------|------------|---------|
| Token Generation | 123,137 | 8.12 | 0 B |
| Token Validation | 117,466 | 8.51 | 0 B |

### Análise de Escalabilidade

Comparação entre diferentes cargas de trabalho:

| Teste | 100 iter | 1,000 iter | 10,000 iter | Estabilidade |
|-------|----------|------------|-------------|--------------|
| App Initialization | 63.6K | 135.3K | 123.2K | 📈 Boa |
| CORS Headers | 1.92M | 1.64M | 1.54M | 📉 Declínio leve |
| Response Creation | 1.41M | 2.76M | 2.69M | 📈 Excelente |
| JWT Generation | 59.7K | 85.9K | 123.1K | 📈 Muito boa |

## Otimizações Avançadas

### Enhanced Advanced Optimizations Results

As otimizações avançadas foram testadas com dados reais de produção:

#### Middleware Pipeline Compiler
```
Training phase: 4,098 ops/sec
Usage phase: 1,519 ops/sec
Cache hit rate: 0% (inicial - melhorará com uso)
Garbage collection: 0.0008 seconds
```

#### Zero-Copy Optimizations
```
String interning: 2,858,207 ops/sec
Array references: 825,085 ops/sec
Copy-on-write: 499,738 ops/sec
Memory saved: 1.67 GB (estimated)
Efficient concatenation: 0.0828s for 100k strings
```

#### Predictive Cache Warming (ML)
```
Access recording: 7,347 accesses/sec
Models trained: 5
Prediction accuracy: 0% (learning phase)
Cache warming: 0.0001 seconds
```

#### Route Memory Manager
```
Route tracking: 1,693,059 ops/sec
Memory check: 0.0008 seconds
```

#### Performance Integrada
```
Total operations: 15,380 ops/sec
Pipeline cache: 0% hit rate (inicial)
Zero-copy efficiency: 1.67 GB saved
Peak memory: 85MB, Current: 82.5MB
```

## Como Executar os Benchmarks

### Benchmark Completo (Recomendado)

```bash
# Executar benchmark completo com análise detalhada
./benchmarks/run_benchmark.sh

# Benchmark de alta precisão (10k iterações)
./benchmarks/run_benchmark.sh -f

# Benchmark rápido para desenvolvimento
./benchmarks/run_benchmark.sh -q

# Executar todos os benchmarks (low, normal, high)
./benchmarks/run_benchmark.sh -a

# Executar apenas otimizações avançadas
./benchmarks/run_benchmark.sh -o
```

### Benchmark Específico

```bash
# Benchmark customizado com iterações específicas
./benchmarks/run_benchmark.sh -i 5000

# Gerar relatório comparativo abrangente
php benchmarks/generate_comprehensive_report.php

# Análise de performance específica
php benchmarks/ComprehensivePerformanceAnalysis.php

# Benchmark de otimizações avançadas
php benchmarks/EnhancedAdvancedOptimizationsBenchmark.php
```

### Scripts de Automação

```bash
# Benchmark automático com diferentes cargas
./benchmarks/benchmark_groups.sh

# Benchmark com análise de grupos de funcionalidades
./benchmarks/benchmark_group_features.sh

# Comparar com benchmark baseline
./benchmarks/run_benchmark.sh -c baseline.json

# Salvar como baseline para futuras comparações
./benchmarks/run_benchmark.sh -b
```

## Configuração para Máxima Performance

### Configuração do PHP

```ini
; php.ini otimizado para PivotPHP v1.0.0 + PHP 8.4.8
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.jit_buffer_size=64M
opcache.jit=tracing

; Memory
memory_limit=512M
max_execution_time=30

; Realpath cache
realpath_cache_size=4096K
realpath_cache_ttl=600

; JIT (recomendado para PHP 8.4.8)
opcache.jit_hot_loop=64
opcache.jit_hot_func=127
```

### Configuração da Aplicação

```php
// Configuração de produção
$app = new Application();

// Habilitar todas as otimizações
$app->enableOptimizations([
    'route_caching' => true,
    'object_pooling' => true,
    'response_compression' => true,
    'header_optimization' => true
]);

// Pool sizes otimizados
$app->configurePoolSizes([
    'header_pool' => 2000,
    'response_pool' => 1000,
    'stream_pool' => 500
]);
```

## Análise de Resultados

### Interpretando Métricas

- **Ops/Segundo > 1M**: Excelente performance
- **Ops/Segundo > 100K**: Boa performance
- **Ops/Segundo > 10K**: Performance adequada
- **Ops/Segundo < 10K**: Necessita otimização

### Tempo de Resposta

- **< 1 μs**: Operação otimizada
- **1-10 μs**: Performance aceitável
- **10-100 μs**: Performance média
- **> 100 μs**: Pode necessitar otimização

### Uso de Memória

- **< 1 MB**: Eficiente
- **1-5 MB**: Aceitável
- **5-10 MB**: Monitorar
- **> 10 MB**: Otimizar

## Tendências e Melhorias

### Evolução da Performance

```
v1.0.0: 800 req/s
v1.0.0: 950 req/s (+19%)
v1.0.0: 1,100 req/s (+16%)
v1.0.0: 1,200 req/s (+9%)
v1.0.0: 1,400 req/s (+17%) - PHP 8.4.8
```

### Próximas Otimizações

1. **HTTP/2 Support**: +15% throughput estimado
2. **JIT Optimization**: +10% performance com PHP 8.4+ JIT
3. **Advanced Pooling**: +12% memory efficiency
4. **Smart Routing Cache**: +8% routing performance
5. **Zero-Copy Optimizations**: +20% memory efficiency estimada

## Troubleshooting Performance

### Identificando Gargalos

```php
// Usar PerformanceMonitor
$monitor = new PerformanceMonitor();
$dashboard = $monitor->getDashboard();

// Verificar alertas
if ($dashboard['performance_alerts']) {
    foreach ($dashboard['performance_alerts'] as $alert) {
        echo "⚠️ {$alert['message']}\n";
    }
}
```

### Soluções Comuns

1. **Baixo Hit Rate**: Aumentar tamanho dos pools
2. **Alto Uso de Memória**: Implementar garbage collection
3. **Latência Alta**: Habilitar caching agressivo
4. **CPU Alto**: Otimizar regex e validações

## Benchmark Customizado

Não existe uma classe `BenchmarkRunner` no core. Para benchmarks customizados, use os scripts
reais em `benchmarks/`:

```bash
# Benchmark simples (CI-friendly)
php benchmarks/SimpleBenchmark.php

# Benchmark completo do framework
php benchmarks/ExpressPhpBenchmark.php

# Todos os benchmarks
php benchmarks/run_all_benchmarks.php
```

## Recursos Adicionais

### Ferramentas de Profiling

- **XDebug**: Profiling detalhado de código
- **Blackfire**: APM para PHP
- **Tideways**: Performance monitoring
- **New Relic**: Application monitoring

### Monitoramento Contínuo

```php
// Integração com monitoring
$app->use(function($req, $res, $next) {
    $start = microtime(true);
    $response = $next();
    $duration = microtime(true) - $start;

    // Enviar métricas
    metrics_send('request_duration', $duration);

    return $response;
});
```

Os benchmarks do PivotPHP v1.0.0 demonstram consistentemente alta performance e eficiência, com melhorias significativas quando executado em PHP 8.4.8. O framework é idealmente adequado para aplicações de alta demanda e ambientes de produção exigentes.

### Destaques da Versão 1.0.0

- **🚀 PHP 8.4**: Compatibilidade total com PHP 8.4
- **💾 Performance**: Mantém todos ganhos da v1.0.0
- **⚡ Qualidade**: PHPStan Level 9, PSR-12 compliance
- **🔧 Estabilidade**: 237 testes passando sem erros

### Recomendações de Deployment

1. **PHP 8.4.8+**: Para máxima performance
2. **OPcache + JIT**: Configuração essencial para produção
3. **Memory Monitoring**: Monitorar uso com 10k+ operações
4. **Load Testing**: Usar resultados high-iteration para capacity planning

### Próximos Passos

Execute benchmarks regularmente durante o desenvolvimento usando:
```bash
# Teste rápido durante desenvolvimento
./benchmarks/run_benchmark.sh -q

# Benchmark completo para releases
./benchmarks/run_benchmark.sh -f

# Relatório comparativo
php benchmarks/generate_comprehensive_report.php
```
