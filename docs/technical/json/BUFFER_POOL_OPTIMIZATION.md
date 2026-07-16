# JsonBufferPool Optimization - Guia Completo

> **Nota de revisão:** a versão anterior deste documento (rotulada "v1.1.4+") descrevia um
> modelo de threshold único de "256 bytes" e uma chave de configuração `threshold_bytes` que
> nunca existiram em `src/Json/Pool/JsonBufferPool.php`. Este documento foi reescrito para
> refletir a implementação real. Para a referência formal das constantes, veja
> [CONSTANTS_REFERENCE.md](CONSTANTS_REFERENCE.md).

## 🎯 Visão Geral

O `JsonBufferPool` (`PivotPHP\Core\Json\Pool\JsonBufferPool`) decide automaticamente entre
`json_encode()` direto e um pool de buffers reutilizáveis, com base no **tipo e tamanho do
dado**, não em um threshold único de bytes.

## 🧠 Sistema de Threshold Inteligente

### Como Funciona

`JsonBufferPool::shouldUsePooling($data)` decide usar pooling quando:

- `$data` é um **array com 10+ elementos** (`POOLING_ARRAY_THRESHOLD = 10`) — ou qualquer
  array que contenha um array/objeto aninhado, independentemente do tamanho;
- `$data` é um **objeto com 5+ propriedades públicas** (`POOLING_OBJECT_THRESHOLD = 5`);
- `$data` é uma **string com 1024+ bytes** (`POOLING_STRING_THRESHOLD = 1024`).

Qualquer outro caso (valores pequenos, escalares, booleanos, null) usa `json_encode()` direto.

```php
// Array pequeno, sem aninhamento — usa json_encode() direto
$smallData = ['id' => 1, 'name' => 'John'];
$json = JsonBufferPool::encodeWithPool($smallData);

// Array com 10+ elementos — usa pooling
$largeData = array_fill(0, 100, ['id' => 1, 'name' => 'User', 'email' => 'user@example.com']);
$json = JsonBufferPool::encodeWithPool($largeData);
```

## 🔧 Configuração e Uso

### Uso Automático (Recomendado)

```php
// Zero configuração - funciona automaticamente
$app->get('/api/users', function($req, $res) {
    $users = User::all();

    // JsonBufferPool decide automaticamente com base no tipo/tamanho dos dados
    return $res->json($users);
});
```

### Configuração Manual

`JsonBufferPool::configure(array $config)` aceita **apenas** estas chaves — qualquer outra
chave lança `\InvalidArgumentException`:

```php
use PivotPHP\Core\Json\Pool\JsonBufferPool;

JsonBufferPool::configure([
    'max_pool_size' => 200,        // Máximo de buffers por pool (limite interno: 1000)
    'default_capacity' => 8192,    // Capacidade padrão dos buffers, em bytes (limite: 1MB)
    'size_categories' => [         // Faixas usadas para escolher a capacidade do buffer
        'small' => 1024,
        'medium' => 4096,
        'large' => 16384,
        'xlarge' => 65536,
    ],
]);

// Restaura a configuração padrão
JsonBufferPool::resetConfiguration();
```

Não existe uma chave `threshold_bytes` (ou equivalente) para customizar os limites de
pooling em si — os thresholds de array/objeto/string acima são constantes de classe fixas.

### Controle Manual

```php
// Forçar uso de pooling
$json = JsonBufferPool::encodeWithPool($data);

// Usar json_encode() tradicional
$json = json_encode($data);

// Verificar se um dado específico usaria pooling
if (JsonBufferPool::shouldUsePooling($data)) {
    echo "Pool seria usado";
}

// Limpar todos os pools (libera memória)
JsonBufferPool::clearPools();
```

## 📊 Monitoramento e Métricas

```php
$stats = JsonBufferPool::getStatistics();

// Chaves retornadas por getStatistics():
// - reuse_rate          (float)  percentual de reuso de buffers
// - total_operations    (int)
// - current_usage       (int)    buffers atualmente em uso
// - peak_usage          (int)
// - total_buffers_pooled (int)
// - active_pool_count   (int)
// - pool_sizes          (array)  buffers disponíveis por capacidade formatada
// - pools_by_capacity   (array)  detalhamento por capacidade
// - detailed_stats      (array)  ['allocations' => int, 'reuses' => int]

echo "Taxa de reuso: {$stats['reuse_rate']}%\n";
echo "Operações totais: {$stats['total_operations']}\n";
```

## 🎯 Quando o Pool é Usado

✅ **Aciona pooling:**
- Arrays com 10+ elementos, ou qualquer array com estrutura aninhada
- Objetos com 5+ propriedades públicas
- Strings com 1024+ bytes

❌ **Usa `json_encode()` direto:**
- Arrays pequenos e "planos" (sem aninhamento) com menos de 10 elementos
- Objetos com menos de 5 propriedades
- Strings curtas, escalares, booleanos, `null`

## 🔗 Integração com o Framework

`Response::json()` delega a serialização ao `JsonBufferPool::encodeWithPool()` internamente,
então o comportamento acima se aplica automaticamente a `$res->json($data)` sem configuração
adicional.

## Ver também

- [CONSTANTS_REFERENCE.md](CONSTANTS_REFERENCE.md) - referência formal de todas as constantes de `JsonBufferPool`
- [README.md](README.md) - visão geral do módulo JSON
- [performance-guide.md](performance-guide.md) - guia de performance relacionado
