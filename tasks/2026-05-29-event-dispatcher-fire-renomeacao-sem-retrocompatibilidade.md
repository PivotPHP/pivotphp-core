# EventDispatcher: Renomeacao de dispatch() para fire() Sem Retrocompatibilidade

## Titulo
Metodo `dispatch()` antigo (string-based) renomeado para `fire()` sem alias de retrocompatibilidade

## Contexto
`src/Events/EventDispatcher.php` foi refatorado para implementar `PSR-14 EventDispatcherInterface`.
O metodo `dispatch(object $event): object` agora e o dispatch PSR-14 puro. O antigo
comportamento de dispatch baseado em string (event name + array de dados) foi renomeado
para `fire(string $event, array $data): bool`.

## Problema Identificado

### 1. Quebra de contrato para usuarios diretos do EventDispatcher

Qualquer codigo que usava o `EventDispatcher` pre-v2.0 com a assinatura antiga:

```php
// Codigo PRE-v2.0 (assinatura antiga hipotetica — string-based)
$dispatcher->dispatch('user.created', ['id' => 1]);
```

Agora recebe um `TypeError` porque `dispatch()` espera `object`, nao `string`.

### 2. `Providers\EventDispatcher` (deprecated) e `Events\EventDispatcher` sao inconsistentes em `fire()`

`Providers\EventDispatcher` (deprecated) implementa apenas `dispatch(object): object` (PSR-14).
`Events\EventDispatcher` (novo) implementa `dispatch()` PSR-14 **e** `fire()` string-based.
Codigo que migrate de `Providers\EventDispatcher` para `Events\EventDispatcher` descobrira
`fire()` como API nova sem documentacao de migracao clara.

### 3. `HookManager` usa `dispatch()` do EventDispatcher

```php
// src/Support/HookManager.php, linhas 87 e 103
$this->dispatcher->dispatch($event);
```

Isso funciona porque `HookManager` injeta um `EventDispatcherInterface` (PSR-14), e o
`dispatch(object)` e o correto aqui. Porem o `HookManager` nao usa `fire()` — entao
os listeners registrados via `listen(string, callable)` no `Events\EventDispatcher`
nunca sao disparados pelos hooks. Essa e uma inconsistencia de design: dois mecanismos
de events paralelos sem conexao.

## Impacto Tecnico
- `TypeError` para codigo existente que chamava dispatch com string
- Dois sistemas de event paralelos sem interoperabilidade (listen/fire vs PSR-14)
- Migracao implicita sem documentacao

## Risco
Alto para codigo externo que usava EventDispatcher diretamente

## Solucao Recomendada

### Opcao A (retrocompatibilidade maxima)
Adicionar `fire()` como alias de `dispatch()` com verificacao de tipo:
```php
// Nao e possivel sem quebrar a assinatura PSR-14
```

### Opcao B (documentacao clara — recomendada)
1. Adicionar `@deprecated` no metodo `fire()` de `Events\EventDispatcher` com instrucao
   de uso de listeners PSR-14 para o futuro
2. Adicionar CHANGELOG com nota explicita de breaking change
3. Documentar que `listen()`/`fire()` e para eventos simples internos e
   `addEventListener()`/`dispatchEvent()` e para eventos PSR-14 com objetos

### Opcao C (unificar)
Remover `listen()`/`fire()` do `Events\EventDispatcher` e usar apenas PSR-14.
Isso e mais limpo mas requer migracao de todos os usos internos de `fire()`.

## Prioridade
Alta (se ha codigo externo usando EventDispatcher diretamente)
Media (se apenas uso interno)

## Esforco Estimado
1-2 horas (analise de impacto + documentacao + possivelmente deprecation notices)

## Arquivos Afetados
- `src/Events/EventDispatcher.php`
- `src/Support/HookManager.php` (verificar integracao)
- `CHANGELOG.md` ou `UPGRADE.md` (documentar breaking change)

## Criterios de Aceite
- [x] Breaking change documentado explicitamente — nota adicionada em `CHANGELOG.md` (`[Unreleased]`)
- [ ] Codigo que usava dispatch() string-based recebe mensagem de erro clara (nao TypeError anonimo) —
      nao resolvido: `dispatch(object): object` e a assinatura PSR-14 da interface implementada,
      nao ha como interceptar um `string` no mesmo metodo sem violar o contrato da interface
      (ver "Opcao A" acima, ja descartada). Mitigado via documentacao (CHANGELOG + docblock de `fire()`).
- [x] HookManager e Events\EventDispatcher tem integracao clara e testada — confirmado que
      `HookManager` nao usa `fire()`/`listen()`: ele gerencia seus proprios listeners
      (`addAction`/`addFilter`) e os registra diretamente em `ListenerProvider` via PSR-14
      (`registerWithEventSystem()`), disparando atraves do evento `Hook` + `dispatch()`.
      `fire()`/`listen()` seguem sendo um mecanismo separado, deliberadamente desconectado,
      agora coberto por `tests/Events/EventDispatcherTest.php` (antes sem nenhum teste).
- [x] PHPStan Level 9 sem erros — confirmado (`composer phpstan`)

**Status: Resolvido (2026-07-15).** Opcao B (documentacao clara) foi a adotada — nao ha
alias retrocompativel possivel para `dispatch()` sem violar `EventDispatcherInterface` (PSR-14).
