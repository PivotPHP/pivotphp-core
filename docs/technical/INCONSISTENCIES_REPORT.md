# Relatório de Inconsistencias Arquiteturais e de Qualidade — PivotPHP Core v2.0.0

**Data do Relatório:** 2026-05-29
**Versao analisada:** 2.0.0 (Legacy Cleanup Edition)
**Responsavel:** Agentes especializados de analise arquitetural e qualidade de codigo

---

## Sumario Executivo

| Severidade | Quantidade | Impacto Principal                                        |
|------------|:----------:|----------------------------------------------------------|
| Critico    |     3      | Seguranca, corretude funcional, container morto          |
| Alto       |     7      | Bugs fatais em dead code, spoofing de IP, violacao PSR   |
| Medio      |    11      | Duplicacao, acoplamento, crescimento ilimitado de memoria|
| Baixo      |    5       | Legibilidade, duplicacao de funcoes nativas              |
| **Total**  |   **26**   |                                                          |

---

## Indice

- [Secao 1 — Inconsistencias Criticas](#secao-1--inconsistencias-criticas)
- [Secao 2 — Inconsistencias de Impacto Alto](#secao-2--inconsistencias-de-impacto-alto)
- [Secao 3 — Inconsistencias de Impacto Medio](#secao-3--inconsistencias-de-impacto-medio)
- [Secao 4 — Inconsistencias de Impacto Baixo](#secao-4--inconsistencias-de-impacto-baixo)
- [Status de Correcao](#status-de-correcao)
- [Priorizacao por Sprint](#priorizacao-por-sprint)

---

## Secao 1 — Inconsistencias Criticas

### C-01 — Dois Containers IoC incompativeis coexistem no projeto

**Descricao:**
Existem duas classes `Container` com responsabilidades identicas, em namespaces distintos, sem qualquer integracao entre si.

- `src/Core/Container.php` — construtor privado, padrao singleton, resolucao por Reflection. Nunca instanciado pela `Application`.
- `src/Providers/Container.php` — implementa `Psr\Container\ContainerInterface` (PSR-11), instanciado diretamente pela `Application` na linha 138.

Os testes em `tests/Core/ContainerTest.php` cobrem exclusivamente `Core\Container`, ou seja, cobrem codigo morto que nao e executado em producao.

**Arquivos afetados:**

| Arquivo                               | Situacao            |
|---------------------------------------|---------------------|
| `src/Core/Container.php`              | Container morto (478 linhas) |
| `src/Providers/Container.php`         | Container ativo (167 linhas) |
| `src/Core/Application.php` linha 138  | Instancia `Providers\Container` |
| `tests/Core/ContainerTest.php`        | Cobre container morto |

**Impacto tecnico:**
- Cobertura de testes falsa: os testes passam mas nao validam o comportamento real do sistema.
- Risco de manutencao: alteracoes em `Providers\Container` nao sao detectadas pelos testes existentes.
- Violacao do principio de fonte unica de verdade para o container.

**Recomendacao:**
1. Remover `src/Core/Container.php` ou documenta-lo explicitamente como utilitario sem relacao com a `Application`.
2. Mover os testes para cobrir `Providers\Container`.
3. Definir um unico container como padrao em toda a documentacao.

**Status: Parcialmente resolvido (2026-07-15).** `Core\Container` foi marcado `@deprecated v2.1.0`
(ver `docs/technical/DEPRECATION_AND_REMOVAL_PLAN.md` ITEM-001), com remocao planejada para v3.0.0.
`Application` ja usa exclusivamente `Providers\Container`. `tests/Core/ContainerTest.php` continua
cobrindo o container deprecated (intencional durante o ciclo de deprecation) — nao foi movido para
cobrir `Providers\Container` porque ja existe cobertura propria deste ultimo em outros testes.

---

### C-02 — Leitura dupla de `php://input` ignora cache e pode esvaziar o body PSR-7

**Descricao:**
O metodo `getCachedInput()` (linha 97) foi introduzido para evitar multiplas leituras do stream `php://input`, que e destruido apos a primeira leitura em PHP. Porem, o metodo `parseBody()` (linha 988) realiza uma segunda leitura direta com `file_get_contents('php://input')`, ignorando completamente o cache.

```php
// src/Http/Request.php:97 — cache correto
private function getCachedInput(): string
{
    $input = @file_get_contents('php://input'); // primeira leitura
    ...
}

// src/Http/Request.php:988 — leitura direta que ignora o cache
private function parseBody(): void
{
    $input = file_get_contents('php://input'); // stream ja pode estar vazio
    ...
}
```

O body PSR-7 e populado via `getCachedInput()` (linha 181), enquanto o body Express.js e populado por `parseBody()`. Se `getCachedInput()` for chamado primeiro, a segunda leitura em `parseBody()` retornara string vazia.

**Arquivos afetados:**

| Arquivo                        | Linhas        |
|--------------------------------|---------------|
| `src/Http/Request.php`         | 97-109, 988   |

**Impacto tecnico:**
- Body da requisicao pode ficar vazio dependendo da ordem de acesso entre API Express.js e PSR-7.
- Comportamento nao determinista dificulta depuracao.
- Falhas silenciosas em endpoints POST/PUT/PATCH.

**Recomendacao:**
Substituir `file_get_contents('php://input')` na linha 988 por `$this->getCachedInput()`.

**Status: Resolvido.** `parseBody()` ja usa `$this->getCachedInput()` (nao ha mais leitura
direta de `php://input` nesse metodo). Ver tambem `tasks/2026-05-29-parsebody-logic-bug-json-array-fallback.md`,
que documenta uma correcao relacionada (fallback de array/escalar JSON) no mesmo metodo.

---

### C-03 — `Psr7Pool::resetServerRequest()` descarta `$headers` e `$serverParams`

**Descricao:**
O metodo `resetServerRequest()` em `src/Http/Pool/Psr7Pool.php` recebe `$headers` e `$serverParams` como parametros mas nao os aplica ao objeto reutilizado do pool. Apenas `method`, `uri`, `body` e `protocolVersion` sao resetados.

```php
// src/Http/Pool/Psr7Pool.php:236-250
private static function resetServerRequest(
    ServerRequestInterface $request,
    string $method,
    UriInterface $uri,
    StreamInterface $body,
    array $headers,       // recebido mas ignorado
    string $version,
    array $serverParams   // recebido mas ignorado
): ServerRequestInterface {
    return $request
        ->withMethod($method)
        ->withUri($uri)
        ->withBody($body)
        ->withProtocolVersion($version);
    // $headers e $serverParams nunca sao aplicados
}
```

**Arquivos afetados:**

| Arquivo                          | Linhas    |
|----------------------------------|-----------|
| `src/Http/Pool/Psr7Pool.php`     | 236-250   |

**Impacto tecnico (seguranca):**
- Requests reutilizados do pool carregam headers da requisicao anterior (ex.: `Authorization`, `Cookie`, `X-User-Id`).
- `$serverParams` da requisicao anterior pode vazar para a requisicao corrente.
- Vulnerabilidade de vazamento de dados entre requisicoes em ambientes de alta concorrencia (Swoole, ReactPHP, FrankenPHP).

**Recomendacao:**
Aplicar `withoutHeader()` para limpar todos os headers existentes antes de aplicar os novos, e aplicar `$serverParams` via metodo `withServerParams()`.

**Status: Resolvido (2026-07-15).** Headers ja eram limpos via `withoutHeader()` antes desta
correcao. `$serverParams` continuava sendo recebido e completamente ignorado — `ServerRequestInterface`
(PSR-7) nao define um metodo `with*` para isso, entao `ServerRequest::withServerParams()` foi
adicionado (extensao pratica, fora da interface formal, no mesmo padrao de `withCookieParams()`)
e passou a ser usado em `resetServerRequest()`. Coberto por
`Psr7PoolTest::testResetServerRequestDoesNotLeakHeadersOrServerParamsBetweenReuses()`.

---

## Secao 2 — Inconsistencias de Impacto Alto

### A-01 — Tres implementacoes de rate limiting incompativeis

**Descricao:**
O projeto contem tres mecanismos de controle de taxa de requisicoes com designs mutuamente incompativeis:

| Componente                                    | Mecanismo de estado       | Interface       |
|-----------------------------------------------|--------------------------|-----------------|
| `src/Middleware/LoadShedder.php`              | Array em memoria (`$requestCounts`) | Closure/callable |
| `src/Middleware/RateLimiter.php`              | Nao identificado         | Propria          |
| `src/Middleware/Performance/RateLimitMiddleware.php` | `$_SESSION` (PHP)  | PSR-15           |

`RateLimitMiddleware` inicia sessao PHP (`session_start()`) dentro de um middleware PSR-15, violando o principio de statelessness HTTP e tornando o componente incompativel com proxies, load balancers e servidores asincronos.

**Arquivos afetados:**

| Arquivo                                              | Linhas   |
|------------------------------------------------------|----------|
| `src/Middleware/Performance/RateLimitMiddleware.php` | 38-64    |
| `src/Middleware/LoadShedder.php`                     | Geral    |
| `src/Middleware/RateLimiter.php`                     | Geral    |

**Impacto tecnico:**
- `RateLimitMiddleware` falha em ambientes sem sessao PHP (APIs REST puras, Swoole).
- Tres implementacoes sem composicao ou hierarquia criam ambiguidade para desenvolvedores.
- Impossibilidade de usar as tres juntas sem conflito de estado.

**Recomendacao:**
Consolidar em uma unica interface com implementacoes intercambiaveis (storage em memoria, Redis, sessao). Extrair a logica de estado para um `RateLimitStorage` injetavel.

**Status: Resolvido.** `RateLimitMiddleware` e `LoadShedder` marcados `@deprecated v2.1.0`
(`trigger_error(E_USER_DEPRECATED)` em ambos), apontando para `RateLimiter` como implementacao
canonica. Nao ha mais ambiguidade sobre qual usar; as duas deprecated serao removidas em v3.0.0
(ver `DEPRECATION_AND_REMOVAL_PLAN.md` ITEM-003/ITEM-004).

---

### A-02 — `MiddlewareStack::warmupCommonPipelines()` chama metodo inexistente

**Descricao:**
O metodo `warmupCommonPipelines()` em `MiddlewareStack.php` contem closures que chamam `$resp->setHeader()`. Este metodo nao existe na classe `Response`. O metodo correto e `header()`.

```php
// src/Middleware/MiddlewareStack.php:283
function ($req, $resp, $next) {
    $resp->setHeader('Access-Control-Allow-Origin', '*'); // metodo inexistente
    return $next($req, $resp);
}
```

O metodo correto em `src/Http/Response.php` e `header(string $name, string $value): self` (linha 172).

**Arquivos afetados:**

| Arquivo                                | Linhas      |
|----------------------------------------|-------------|
| `src/Middleware/MiddlewareStack.php`   | 283, 289, 295, 296 |
| `src/Http/Response.php`               | 172 (metodo correto) |

**Impacto tecnico:**
- Fatal error (`Call to undefined method`) ao chamar `warmupCommonPipelines()` em producao.
- Dead code com bug fatal que nao e coberto por testes.

**Recomendacao:**
Substituir todas as ocorrencias de `setHeader(` por `header(` no metodo `warmupCommonPipelines()` e adicionar testes cobrindo o warmup.

**Status: Resolvido.** `warmupCommonPipelines()` nao existe mais em `MiddlewareStack.php` (metodo
removido, nao apenas corrigido) — confirmado via busca no projeto inteiro.

---

### A-03 — Dois metodos de obtencao de IP com logicas incompativeis

**Descricao:**
A classe `Request` expoe dois metodos publicos para obtencao do IP do cliente com comportamentos distintos:

```php
// src/Http/Request.php:419 — valida com FILTER_FLAG_NO_PRIV_RANGE
public function ip(): string
{
    // Rejeita IPs privados (192.168.x.x, 10.x.x.x, etc.)
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return $ip;
    }
}

// src/Http/Request.php:1085 — sem validacao alguma
public function getIp(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]); // retorna sem validar
    }
}
```

**Arquivos afetados:**

| Arquivo                  | Linhas       |
|--------------------------|--------------|
| `src/Http/Request.php`   | 419, 1085    |

**Impacto tecnico (seguranca):**
- `getIp()` e vulneravel a IP spoofing via header `X-Forwarded-For` sem qualquer validacao.
- Codigo de autenticacao ou auditoria que usa `getIp()` pode registrar IPs forjados.
- Inconsistencia silenciosa: o desenvolvedor nao tem indicacao de qual metodo e seguro.

**Recomendacao:**
Deprecar `getIp()` e unificar no metodo `ip()`. Documentar explicitamente o comportamento de validacao.

**Status: Resolvido.** `getIp()` marcado `@deprecated`, emite `trigger_error(E_USER_DEPRECATED)` e
delega para `ip()` internamente — o problema de seguranca (spoofing via `X-Forwarded-For` sem
validacao) desaparece porque `getIp()` agora executa a mesma validacao de `ip()`
(`DEPRECATION_AND_REMOVAL_PLAN.md` ITEM-002).

---

### A-04 — `ApiDocumentationMiddleware` viola PSR-15 instanciando `Response` Express.js

**Descricao:**
`ApiDocumentationMiddleware` implementa `MiddlewareInterface` (PSR-15) mas instancia diretamente `PivotPHP\Core\Http\Response` (classe Express.js do framework) em vez de usar `ResponseInterface` via factory.

```php
// src/Middleware/Http/ApiDocumentationMiddleware.php:78, 137, 193
$response = new Response(); // Response Express.js, nao PSR-7
```

A assinatura do metodo `process()` retorna `ResponseInterface`, mas o objeto criado e a implementacao Express.js especifica do framework.

**Arquivos afetados:**

| Arquivo                                              | Linhas        |
|------------------------------------------------------|---------------|
| `src/Middleware/Http/ApiDocumentationMiddleware.php` | 8, 78, 137, 193 |

**Impacto tecnico:**
- Acoplamento duro ao `Response` Express.js impede uso do middleware com outros stacks PSR-15.
- Viola o contrato de desacoplamento esperado em componentes PSR.

**Recomendacao:**
Injetar um `ResponseFactoryInterface` via construtor e substituir `new Response()` por `$this->responseFactory->createResponse()`.

**Status: Resolvido.** `ApiDocumentationMiddleware` nao instancia mais `Response` (Express.js) —
usa `Psr7Response` (implementacao PSR-7 propria do framework, em `src/Http/Psr7/`) diretamente
via `withHeader()`/`withBody()` imutaveis. Nao e exatamente a factory injetada sugerida na
recomendacao, mas resolve o problema real: o middleware nao depende mais da classe Express.js
especifica do framework, apenas de tipos PSR-7.

---

### A-05 — Bug no `Validator`: inteiro zero falha validacao incorretamente

**Descricao:**
A validacao de inteiros no `Validator` usa o resultado de `filter_var()` diretamente em contexto booleano. O valor `0` e um inteiro valido mas `filter_var(0, FILTER_VALIDATE_INT)` retorna `0`, que e avaliado como `false` em PHP.

```php
// src/Validation/Validator.php:125
if (!filter_var($value, FILTER_VALIDATE_INT)) {
    // $value = 0 entra aqui incorretamente
    $errors[] = "$field must be an integer";
}
```

**Arquivos afetados:**

| Arquivo                           | Linha |
|-----------------------------------|-------|
| `src/Validation/Validator.php`    | 125   |

**Impacto tecnico:**
- Falso positivo: o valor `0` e rejeitado como "nao e inteiro".
- APIs que aceitam IDs ou quantidades zero retornam erros de validacao incorretos.

**Recomendacao:**
Substituir por comparacao estrita:
```php
if (filter_var($value, FILTER_VALIDATE_INT) === false) {
```

**Status: Resolvido.** `Validator.php` ja usa `filter_var($value, FILTER_VALIDATE_INT) === false`
(comparacao estrita) exatamente como recomendado. Ver tambem a correcao relacionada da regra
`required` (`0`/`'0'`/`0.0`/`false` tratados como valores presentes) em
`tasks/2026-05-29-validator-required-false-negative-integer-zero.md`.

---

### A-06 — Estado estatico em `MiddlewareStack` incompativel com servidores asincronos

**Descricao:**
`MiddlewareStack` possui cinco propriedades estaticas que persistem entre requisicoes no mesmo processo PHP:

```php
// src/Middleware/MiddlewareStack.php:30-53
private static array $compiledPipelines = [];
private static array $stats = [];
private static array $groupMiddlewares = [];
private static ?MiddlewarePipelineCompilerInterface $compiler = null;
private static ?SerializationCacheInterface $serializationCache = null;
```

**Arquivos afetados:**

| Arquivo                                | Linhas  |
|----------------------------------------|---------|
| `src/Middleware/MiddlewareStack.php`   | 30-53   |

**Impacto tecnico:**
- Em Swoole, ReactPHP ou FrankenPHP, o estado de uma requisicao vaza para a proxima dentro do mesmo worker.
- Pipeline compilado para uma rota pode ser incorretamente reutilizado em outra rota.
- Impossibilidade de isolar contextos de requisicao sem reset manual.

**Recomendacao:**
Documentar explicitamente a incompatibilidade com servidores asincronos. Para suporte asincrono, converter propriedades estaticas para instancia ou usar contexto por corrotina.

**Status: Resolvido (2026-07-15).** Documentado explicitamente no docblock da classe
`MiddlewareStack` (a recomendacao pedia documentacao, nao refatoracao — converter as 5
propriedades estaticas para instancia/corrotina e uma mudanca arquitetural maior, fora de
escopo desta rodada). O docblock explica quais propriedades sao afetadas, o impacto pratico
em Swoole/ReactPHP/FrankenPHP, e que `clearCache()` deve ser chamado explicitamente entre
requisicoes nesses ambientes.

---

### A-07 — `Response.json()` com auto-emit oculto exige dois flags para desativar

**Descricao:**
O metodo `json()` em `Response` emite automaticamente a saida HTTP como efeito colateral. Para desativa-lo sao necessarios dois flags independentes: `$testMode` (linha 65) e `$disableAutoEmit` (linha 75). A documentacao nao menciona esse comportamento.

```php
// src/Http/Response.php:290
if (!$this->testMode && !$this->disableAutoEmit) {
    $this->emit(); // efeito colateral oculto
}
```

**Arquivos afetados:**

| Arquivo                  | Linhas         |
|--------------------------|----------------|
| `src/Http/Response.php`  | 65, 75, 290, 315, 340 |

**Impacto tecnico:**
- Comportamento surpresa para desenvolvedores que esperam `json()` apenas serializar dados.
- Dificuldade de teste unitario sem ativar flags especificos.
- Headers podem ser enviados prematuramente em fluxos de middleware complexos.

**Recomendacao:**
Separar `json()` (serializa e configura headers) de `emit()` (envia a resposta). Tornar o auto-emit opt-in, nao opt-out.

---

## Secao 3 — Inconsistencias de Impacto Medio

### M-01 — Dois `PoolManager` com designs opostos

**Descricao:**
Existem dois `PoolManager` com arquiteturas opostas:

| Arquivo                              | Design      | Caracteristica               |
|--------------------------------------|-------------|------------------------------|
| `src/Http/Pool/PoolManager.php`      | Instancia   | Singleton opcional, pools nomeados genericos |
| `src/Http/Psr7/Pool/PoolManager.php` | Estatico    | Todos os metodos estaticos, pools especificos PSR-7 |

**Arquivos afetados:** `src/Http/Pool/PoolManager.php`, `src/Http/Psr7/Pool/PoolManager.php`

**Impacto tecnico:** Ambiguidade na escolha do pool. Impossivel substituir um pelo outro sem alteracoes de chamada.

**Recomendacao:** Unificar em uma unica interface `PoolManagerInterface` com implementacoes distintas para pools genericos e PSR-7.

---

### M-02 — Conversao camelCase duplicada em 6 locais

**Descricao:**
A logica de conversao de string para camelCase e replicada inline em pelo menos 6 pontos do codigo sem uso de funcao utilitaria centralizada.

**Arquivos afetados:**

| Arquivo                          | Descricao                              |
|----------------------------------|----------------------------------------|
| `src/Http/Request.php`           | Linhas 530-533, 547-550, 572-576, 593-597 (dentro da classe anonima) |
| `src/Http/HeaderRequest.php`     | Conversao de headers                   |
| `src/Utils/Utils.php`            | `camelCase()` (linha 252)              |
| `src/Support/Str.php`            | `camelCase()` (linha 13)              |

**Impacto tecnico:** Edge cases resolvidos de forma diferente em cada local. Bug corrigido em um ponto nao e propagado aos demais.

**Recomendacao:** Centralizar toda conversao camelCase em `Str::camelCase()` e substituir as implementacoes inline.

---

### M-03 — `Utils` e `Str` duplicam camel/snake/kebab com resultados distintos em edge cases

**Descricao:**
Ambas as classes oferecem metodos de conversao de case com implementacoes diferentes:

| Metodo          | `src/Utils/Utils.php` | `src/Support/Str.php` |
|-----------------|----------------------|----------------------|
| camelCase       | linha 252            | linha 13             |
| snakeCase       | linha 263            | ausente (tem `snake`) |
| kebabCase       | linha 275            | linha 42 (`kebab`)   |

Implementacoes distintas podem produzir resultados diferentes para strings com numeros, underscores duplos ou caracteres especiais.

**Arquivos afetados:** `src/Utils/Utils.php`, `src/Support/Str.php`

**Recomendacao:** Definir `Str` como fonte unica de verdade. Deprecar metodos duplicados em `Utils` com redirecionamento para `Str`.

---

### M-04 — `Application::use()` sem type hint e com complexidade ciclomatica elevada

**Descricao:**
O metodo `use()` (linha 411) nao possui type hint no parametro `$middleware`, impossibilitando analise estatica e autocomplemento de IDEs.

```php
// src/Core/Application.php:411
public function use($middleware): self // sem type hint
```

O metodo possui complexidade ciclomatica aproximada de 6 (3 blocos `if/elseif/else` aninhados com verificacoes multiplas).

**Arquivos afetados:** `src/Core/Application.php` linha 411

**Recomendacao:** Adicionar union type `string|callable|object` e extrair cada branch em metodo privado.

---

### M-05 — `LoadShedder::$requestCounts` cresce indefinidamente

**Descricao:**
O array `$requestCounts` em `LoadShedder` (linha 34) acumula uma entrada por IP de cliente. A limpeza via `array_filter` remove apenas entradas antigas por janela de tempo, mas nao limita o tamanho total do array. Em cenarios de alto volume com muitos IPs distintos, o array cresce sem limite.

Alem disso, a busca pelo cliente usa `array_keys($this->requestCounts)` (linha 143), que e O(n) linear.

**Arquivos afetados:** `src/Middleware/LoadShedder.php` linhas 34, 76, 84, 143

**Impacto tecnico:** Vazamento de memoria em producao com trafego diversificado.

**Recomendacao:** Adicionar limite maximo de entradas com politica LRU ou usar estrutura de dados com expiracão automatica.

---

### M-06 — Namespace `Providers/` contem implementacoes, nao providers

**Descricao:**
O diretorio `src/Providers/` contem classes que sao implementacoes diretas de servicos:

| Arquivo                          | Tipo real                |
|----------------------------------|--------------------------|
| `src/Providers/Container.php`    | Implementacao PSR-11     |
| `src/Providers/Logger.php`       | Implementacao PSR-3      |
| `src/Providers/EventDispatcher.php` | Implementacao de evento |

Providers de servico tipicamente registram servicos no container — estas classes sao os proprios servicos.

**Recomendacao:** Mover para namespaces semanticamente corretos (`Core\Container`, `Logging\Logger`) ou criar providers separados que registrem estas implementacoes.

---

### M-07 — `ExtensionManager` acoplado diretamente a `Application`

**Descricao:**
`ExtensionManager` recebe `Application` via construtor (linha 51) e armazena referencia direta (linha 46). `Application` provavelmente instancia `ExtensionManager`, criando dependencia ciclica implicita.

```php
// src/Providers/ExtensionManager.php:46, 51
private Application $app;
public function __construct(Application $app)
```

**Arquivos afetados:** `src/Providers/ExtensionManager.php` linhas 46-51

**Recomendacao:** Introduzir interface `ApplicationInterface` e injetar a interface em vez da classe concreta.

---

### M-08 — `GlobalsToServerRequestAdapter::createUploadedFile()` sem verificacao de arquivo

**Descricao:**
O metodo `createUploadedFile()` (linha 154) cria um `Stream` a partir de `$file['tmp_name']` sem verificar se o arquivo temporario existe, se o upload teve erro (`UPLOAD_ERR_OK`), ou se `tmp_name` nao esta vazio.

```php
// src/Http/Adapters/GlobalsToServerRequestAdapter.php:156
$stream = Stream::createFromFile($file['tmp_name']); // sem verificacao de existencia
```

**Arquivos afetados:** `src/Http/Adapters/GlobalsToServerRequestAdapter.php` linhas 154-165

**Impacto tecnico:** Excecao ou comportamento indefinido ao processar uploads com erro (ex.: `UPLOAD_ERR_NO_FILE`).

**Recomendacao:** Verificar `$file['error'] === UPLOAD_ERR_OK` antes de criar o stream e tratar os casos de erro conforme a especificacao PSR-7.

---

### M-09 — Dois `Logger.php` em namespaces distintos

**Descricao:**
Existem duas implementacoes de logger:

| Arquivo                          | Namespace                     | Extends/Implements      |
|----------------------------------|-------------------------------|-------------------------|
| `src/Providers/Logger.php`       | `PivotPHP\Core\Providers`     | `Psr\Log\AbstractLogger` |
| `src/Logging/Logger.php`         | `PivotPHP\Core\Logging`       | Propria                  |

**Recomendacao:** Manter apenas `Logging\Logger` (namespace semanticamente correto) e remover ou deprecar `Providers\Logger`.

---

### M-10 — Classe anonima de 90 linhas em `setHeaders()` com zero testabilidade

**Descricao:**
O metodo `setHeaders()` em `Request` (linha 509) define uma classe anonima de aproximadamente 90 linhas que estende `HeaderRequest`. Esta classe anonima reimplementa a logica de conversao camelCase (duplicacao do M-02) e nao pode ser testada isoladamente.

**Arquivos afetados:** `src/Http/Request.php` linhas 512-601

**Recomendacao:** Extrair para uma classe nomeada interna `CustomHeaderRequest` ou equivalente.

---

### M-11 — `Validator.php` sem `declare(strict_types=1)`

**Descricao:**
`src/Validation/Validator.php` e o unico arquivo do projeto sem `declare(strict_types=1)`. Isso permite coercao implicita de tipos em chamadas de metodo, podendo mascarar erros de tipo.

**Arquivos afetados:** `src/Validation/Validator.php` (linha 1)

**Recomendacao:** Adicionar `declare(strict_types=1)` e verificar se algum teste passa argumentos do tipo errado que seriam rejeitados com strict types.

---

## Secao 4 — Inconsistencias de Impacto Baixo

### B-01 — `Application` com aproximadamente 1229 linhas (God Class)

**Descricao:**
`src/Core/Application.php` acumula responsabilidades de bootstrap, roteamento, middleware, configuracao, tratamento de erros e logging. Com 1229 linhas, viola o principio de responsabilidade unica.

**Arquivos afetados:** `src/Core/Application.php`

**Recomendacao:** Extrair responsabilidades em servicos dedicados: `BootstrapService`, `ErrorHandler`, `MiddlewareRegistry`.

---

### B-02 — `Str::startsWith/endsWith/contains` reimplementam funcoes nativas PHP 8.0+

**Descricao:**
PHP 8.0 introduziu `str_starts_with()`, `str_ends_with()` e `str_contains()`. Os metodos em `Str` reimplementam o mesmo comportamento sem adicionar valor.

```php
// src/Support/Str.php:80, 88, 96
public static function startsWith(string $haystack, string $needle): bool
public static function endsWith(string $haystack, string $needle): bool
public static function contains(string $haystack, string $needle): bool
```

**Arquivos afetados:** `src/Support/Str.php` linhas 80-100

**Recomendacao:** Deprecar os metodos e redirecionar internamente para as funcoes nativas. O framework ja requer PHP 8.1+.

---

### B-03 — `Arr::only()` e `Arr::except()` sem type hint em `$keys`

**Descricao:**
Ambos os metodos usam `$keys` sem type hint, com logica manual de normalizacao via `func_get_args()`:

```php
// src/Utils/Arr.php:162, 188
public static function only(array $array, $keys): array
public static function except(array $array, $keys): array
```

**Arquivos afetados:** `src/Utils/Arr.php` linhas 162, 188

**Recomendacao:** Declarar `array|string $keys` como tipo e simplificar a normalizacao interna.

---

### B-04 — Dois metodos de error handling com logica duplicada em `Application.php`

**Descricao:**
`Application` possui pelo menos dois pontos de tratamento de excecao (linhas 323, 350 e 631) que chamam `handleException()`, mas com contextos e fluxos de retorno distintos. A logica de determinacao de status HTTP (`$e instanceof HttpException`) esta duplicada.

**Arquivos afetados:** `src/Core/Application.php` linhas 323, 350, 631, 772-811

**Recomendacao:** Centralizar toda logica de tratamento em `handleException()` e garantir que todos os pontos de captura usem o mesmo fluxo.

---

### B-05 — Magic strings HTTP hardcoded em `handleException()` ignorando mapa em `Response::error()`

**Descricao:**
`handleException()` hardcoda strings de mensagem HTTP:

```php
// src/Core/Application.php:806
'message' => $statusCode === 404 ? 'Not Found' : 'Internal Server Error',
```

`Response::error()` (linha 405) ja possui logica de mensagens de erro padrao que poderia ser reutilizada.

**Arquivos afetados:** `src/Core/Application.php` linha 806, `src/Http/Response.php` linha 405

**Recomendacao:** Delegar a mensagem de erro ao metodo `Response::error()` ou a um mapa centralizado de status HTTP.

---

## Status de Correcao

Ultima atualizacao: 2026-05-29

| ID   | Descricao Resumida                              | Severidade | Status                                      |
|------|-------------------------------------------------|------------|---------------------------------------------|
| C-01 | Dois Containers IoC incompativeis               | Critico    | Depreciado (`@deprecated` + `trigger_error` em `getInstance()`; remocao v3.0.0) |
| C-02 | Leitura dupla de `php://input`                  | Critico    | Corrigido (`parseBody()` usa `getCachedInput()`) |
| C-03 | `resetServerRequest()` ignora headers/params    | Critico    | Corrigido (headers aplicados e limpos; `serverParams` aplicados) |
| A-01 | Tres implementacoes de rate limiting            | Alto       | Depreciado (`LoadShedder` e `RateLimitMiddleware` com `@deprecated` + `trigger_error`; remocao v3.0.0) |
| A-02 | `warmupCommonPipelines()` chama metodo invalido | Alto       | Corrigido (metodo `warmupCommonPipelines()` removido) |
| A-03 | Dois metodos de IP com logicas distintas        | Alto       | Depreciado (`getIp()` delega para `ip()`; `@deprecated` + `trigger_error`; remocao v3.0.0) |
| A-04 | `ApiDocumentationMiddleware` viola PSR-15       | Alto       | Corrigido (usa `Psr7Response` + `Stream` puros; sem instanciar `Response` Express.js) |
| A-05 | Bug Validator: inteiro zero falha validacao     | Alto       | Corrigido (comparacao estrita `=== false`) |
| A-06 | Estado estatico em `MiddlewareStack`            | Alto       | Corrigido (2026-07-15: incompatibilidade documentada explicitamente no docblock da classe) |
| A-07 | `Response.json()` com auto-emit oculto          | Alto       | Corrigido (2026-07-15: auto-emit removido de `json()`/`text()`/`html()`; `Application::run()` e o unico ponto de emissao, guardado por `isSent()`; `disableAutoEmit()` mantido como no-op para BC) |
| M-01 | Dois `PoolManager` com designs opostos          | Medio      | Parcialmente resolvido (2026-07-15: unificacao completa via `PoolManagerInterface` comum e tecnicamente inviavel sem reescrever um dos dois do zero — `Http\Psr7\Pool\PoolManager` e 100% estatico, e interfaces PHP nao cobrem metodos estaticos; `Http\Pool\PoolManager` e por instancia. Confirmado que nenhuma das duas classes e usada no caminho de producao hoje (pooling real usa `HttpPoolFacade`/`Psr7Pool`). Documentada a distincao explicitamente no docblock de ambas para eliminar a ambiguidade — o impacto tecnico original ("ambiguidade na escolha do pool") fica resolvido; a unificacao arquitetural full fica fora de escopo) |
| M-02 | Conversao camelCase duplicada em 6 locais       | Medio      | Corrigido (`HeaderRequest::headerToCamel()` centralizado; `CustomHeaderCollection` herda) |
| M-03 | `Utils` e `Str` duplicam case conversion        | Medio      | Corrigido (`Utils::camelCase/snake/kebab` delegam para `Str`) |
| M-04 | `Application::use()` sem type hint              | Medio      | Corrigido (decomposto em `resolveClassMiddleware()` e `wrapObjectMiddleware()`) |
| M-05 | `LoadShedder::$requestCounts` cresce sem limite | Medio      | Depreciado (classe depreciada; remocao v3.0.0) |
| M-06 | Namespace `Providers/` contem implementacoes    | Medio      | Parcialmente resolvido, decisao final (2026-07-15): `EventDispatcher`/`ListenerProvider`/`Logger` movidos para `Events/`/`Logging/`, versoes em `Providers/` deprecated. `Container` fica deliberadamente em `Providers/` — e a implementacao canonica ativa (ver C-01); move-la agora seria uma terceira mudanca de identidade (`Core\Container` -> `Providers\Container` -> outro namespace) sem beneficio real. Decisao documentada no docblock da classe. |
| M-07 | `ExtensionManager` acoplado a `Application`     | Medio      | Corrigido (2026-07-15: `Core\ApplicationInterface` criada — marker interface, ja que `ExtensionManager` nunca chama metodos especificos de `Application`, so repassa a referencia adiante; `Application implements ApplicationInterface`; `ExtensionManager` tipado contra a interface) |
| M-08 | `createUploadedFile()` sem verificacao de erro  | Medio      | Corrigido (verificacao de `file_exists` adicionada) |
| M-09 | Dois `Logger.php` em namespaces distintos       | Medio      | Corrigido (`Logging/PsrLogger.php` criado; `Providers/Logger.php` depreciado; `Logging/Logger.php` morto removido) |
| M-10 | Classe anonima de 90 linhas sem testabilidade   | Medio      | Corrigido (extraida para `CustomHeaderCollection` em `src/Http/`) |
| M-11 | `Validator.php` sem `strict_types`              | Medio      | Corrigido (`declare(strict_types=1)` adicionado) |
| B-01 | `Application` God Class (1229 linhas)           | Baixo      | Pendente Sprint 4 |
| B-02 | `Str` reimplementa funcoes nativas PHP 8.0+     | Baixo      | Depreciado (`@deprecated` + `trigger_error` nos 3 metodos; remocao v3.0.0) |
| B-03 | `Arr::only/except` sem type hint em `$keys`     | Baixo      | Corrigido (type hint `array|string` adicionado) |
| B-04 | Error handling duplicado em `Application`       | Baixo      | Corrigido (2026-07-15: a logica de status via `instanceof HttpException` ja era unica, dentro de `handleException()`; a duplicacao real era a closure identica de `set_exception_handler()` em `configureBasicErrorHandling()`/`configureErrorHandling()`, extraida para o metodo publico `handleUncaughtException()`, agora testavel isoladamente) |
| B-05 | Magic strings HTTP em `handleException()`       | Baixo      | Corrigido (2026-07-15: `Response::defaultErrorMessage()` extraido e reutilizado por `error()` e `handleException()`; cobre todos os status do mapa, nao so 404) |

---

## Priorizacao por Sprint

### Sprint 1 — Criticos e Seguranca (semana 1-2)

Foco: corretude funcional e seguranca. Nenhum item de Sprint 1 deve ir para producao sem correcao.

| ID   | Item                                              | Esforco estimado |
|------|---------------------------------------------------|------------------|
| C-03 | Corrigir `resetServerRequest()` — vazamento de headers entre requests | Baixo |
| C-02 | Corrigir leitura dupla de `php://input` em `parseBody()` | Baixo |
| A-05 | Corrigir bug Validator para inteiro zero           | Baixo |
| A-02 | Corrigir `warmupCommonPipelines()` — metodo invalido `setHeader` | Baixo |
| A-03 | Deprecar `getIp()` e documentar vulnerabilidade a spoofing | Medio |
| C-01 | Definir container canonico e corrigir cobertura de testes | Medio |

### Sprint 2 — Qualidade Arquitetural (semana 3-4)

Foco: eliminar duplicacao, consolidar responsabilidades e corrigir violacoes de contrato.

| ID   | Item                                              | Esforco estimado |
|------|---------------------------------------------------|------------------|
| A-01 | Consolidar tres implementacoes de rate limiting   | Alto |
| A-04 | Corrigir `ApiDocumentationMiddleware` — injetar `ResponseFactoryInterface` | Medio |
| A-07 | Separar `json()` de `emit()` em Response          | Medio |
| M-02 | Centralizar conversao camelCase em `Str`          | Medio |
| M-03 | Deprecar duplicatas em `Utils`, unificar em `Str` | Baixo |
| M-08 | Adicionar verificacao de erro de upload em `createUploadedFile()` | Baixo |
| M-11 | Adicionar `strict_types` em `Validator.php`       | Baixo |
| M-09 | Remover `Providers\Logger` duplicado              | Baixo |

### Sprint 3 — Divida Tecnica e Refatoracao (semana 5-6)

Foco: estrutura de longo prazo, testabilidade e compatibilidade com servidores asincronos.

| ID   | Item                                              | Esforco estimado |
|------|---------------------------------------------------|------------------|
| A-06 | Documentar incompatibilidade estatica com Swoole/ReactPHP | Baixo |
| M-05 | Adicionar limite de tamanho em `LoadShedder::$requestCounts` | Medio |
| M-01 | Unificar `PoolManager` com interface comum        | Alto |
| M-10 | Extrair classe anonima de `setHeaders()` para classe nomeada | Medio |
| M-04 | Adicionar type hint e reduzir complexidade em `Application::use()` | Medio |
| M-06 | Reorganizar namespace `Providers/`                | Alto |
| M-07 | Introduzir interface para desacoplar `ExtensionManager` | Medio |
| B-01 | Decompor `Application` — extrair `ErrorHandler`, `MiddlewareRegistry` | Alto |
| B-02 | Deprecar `Str::startsWith/endsWith/contains`      | Baixo |
| B-03 | Adicionar type hints em `Arr::only/except`        | Baixo |
| B-04 | Centralizar error handling em `Application`       | Medio |
| B-05 | Eliminar magic strings HTTP de `handleException()` | Baixo |

---

*Documento gerado em 2026-05-29. Ultima revisao de status: 2026-05-29 (pos-refatoracao Sprints 1-3). Revisao recomendada apos cada release.*
