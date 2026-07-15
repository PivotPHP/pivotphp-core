# HeaderRequest.php: Ausencia de declare(strict_types=1)

## Titulo
`HeaderRequest` nao possui `declare(strict_types=1)` enquanto `CustomHeaderCollection` possui

## Contexto
`src/Http/CustomHeaderCollection.php` (novo arquivo) possui `declare(strict_types=1)` na
primeira linha. A classe pai `src/Http/HeaderRequest.php` nao possui essa declaracao.
O projeto exige PHPStan Level 9 e PSR-12, e o padrao do projeto e usar strict_types.

## Problema Identificado

```php
// src/Http/HeaderRequest.php - linha 1
<?php
// Sem declare(strict_types=1)
namespace PivotPHP\Core\Http;
```

Ausencia de `strict_types=1` em `HeaderRequest.php` significa:

1. Coercao implicita de tipos em chamadas de metodo: `getHeader(123)` seria coagido
   para `"123"` silenciosamente, ao inves de gerar `TypeError`
2. Inconsistencia com a subclasse `CustomHeaderCollection` que tem strict_types
3. Os metodos `hasHeader($name)`, `getHeader($name)`, `__get($name)` nao tem type hints
   nos parametros — com strict_types e type hints, isso geraria erros em tempo de analise

Adicionalmente, os metodos `getHeader`, `hasHeader` e `__get` usam `$name` sem
declaracao de tipo no parametro, o que viola o padrao do restante do projeto.

## Impacto Tecnico
- Coercao silenciosa de tipos em metodos de HeaderRequest
- Inconsistencia de comportamento entre instancia de `HeaderRequest` e `CustomHeaderCollection`
- PHPStan pode emitir avisos sobre falta de type hints

## Risco
Baixo em runtime, mas viola padrao de qualidade do projeto

## Solucao Recomendada

```php
// Adicionar ao topo
declare(strict_types=1);

// Adicionar type hints nos metodos publicos
public function __get(string $name): mixed { ... }
public function getHeader(string $name): ?string { ... }
public function hasHeader(string $name): bool { ... }
public function getAllHeaders(): array { ... }
```

## Prioridade
Baixa

## Esforco Estimado
15 minutos

## Arquivos Afetados
- `src/Http/HeaderRequest.php`

## Criterios de Aceite
- [ ] `declare(strict_types=1)` presente
- [ ] Todos os metodos publicos tem type hints completos
- [ ] PHPStan Level 9 passa sem erros
- [ ] PSR-12 check passa
