# Missing use Import: CustomHeaderCollection in Request.php

## Titulo
Falta `use` para `CustomHeaderCollection` em `Request.php`

## Contexto
Em `Request::setHeaders()` (linha 511), a classe `CustomHeaderCollection` é instanciada
diretamente sem o `use` correspondente no bloco de imports do arquivo.

## Problema Identificado

`src/Http/Request.php` utiliza `new CustomHeaderCollection($headers)` na linha 511, mas a
declaração `use PivotPHP\Core\Http\CustomHeaderCollection;` está ausente no topo do arquivo.

Como ambas as classes estão no mesmo namespace `PivotPHP\Core\Http`, o código funciona em
runtime (PHP resolve pelo namespace corrente), mas:

1. PHPStan Level 9 pode não detectar o símbolo como resolvido dependendo da configuração
2. IDEs não conseguem oferecer autocompletar nem análise estática sem o `use`
3. Viola o principio de legibilidade: qualquer leitor assume que a classe vem de fora se o
   namespace for diferente, ou que é implícita se for o mesmo — inconsistente com o padrão
   do projeto onde os demais imports do mesmo namespace são declarados explicitamente
   (ex: `use PivotPHP\Core\Http\HeaderRequest;` já existe)

## Impacto Técnico
- Inconsistência de estilo com o restante do arquivo
- Possível falso-negativo em ferramentas de análise estática
- Dificulta rastreabilidade em refatorações futuras (grep por `use` não encontra o uso)

## Risco
Baixo em runtime, Médio para manutenção

## Solução Recomendada
Adicionar no bloco `use` de `src/Http/Request.php`:

```php
use PivotPHP\Core\Http\CustomHeaderCollection;
```

## Prioridade
Baixa

## Esforço Estimado
5 minutos

## Arquivos Afetados
- `src/Http/Request.php` (linha 511, bloco use)

## Exemplo de Melhoria
```php
// Antes (imports existentes — incompleto)
use PivotPHP\Core\Http\HeaderRequest;
use PivotPHP\Core\Http\Contracts\AttributeInterface;
// ... outros imports

// Depois
use PivotPHP\Core\Http\CustomHeaderCollection;
use PivotPHP\Core\Http\HeaderRequest;
use PivotPHP\Core\Http\Contracts\AttributeInterface;
// ... outros imports
```

## Criterios de Aceite
- [ ] `use PivotPHP\Core\Http\CustomHeaderCollection;` presente no bloco de imports
- [ ] PHPStan Level 9 passa sem erros
- [ ] PSR-12 check passa sem violacoes
