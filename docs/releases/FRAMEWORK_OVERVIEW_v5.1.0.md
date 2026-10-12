# PivotPHP v5.1.0 - Framework Overview

**Version:** 5.1.0 (Minor)
**Release Date:** 2026-10-12
**PHP Requirements:** 8.1+

## Summary

Release minor focada em arquitetura de resolução de rotas, pipeline pré-compilado de middlewares e ganho de performance sem quebra de compatibilidade (SPEC-114).

### Principais Mudanças

1. **Arquitetura de Resolução de Rotas Tipada:**
   - A `Application` agora consome a nova camada de resolução `RouteResolution` do `pivotphp/core-routing: ^3.1.0`.
   - O despacho de rotas é feito de forma tipada através do resultado de correspondência (`RouteMatched`), extraindo diretamente parâmetros e cadeia de middlewares sem inspeção dinâmica repetitiva.
   - Tratamento nativo e imediato de métodos não permitidos (`MethodNotAllowed`), gerando cabeçalho HTTP `Allow`, status 405 Method Not Allowed e resposta 204 No Content imediata para preflight OPTIONS implícito.

2. **Middleware Adapter Pattern (`ExpressMiddlewareAdapter`):**
   - Substitui o encapsulamento de middlewares Express (`fn($req, $res, $next)`) que antes instanciava classes anônimas dinamicamente a cada execução.
   - Reduz consideravelmente o overhead de alocação de objetos e uso de memória no runtime por requisição.

3. **Pipeline Pré-Compilado (`PrecompiledPipeline` & `MiddlewareHandler`):**
   - A cadeia de execução de middlewares (globais, de grupo e de rota) é pré-compilada em estruturas imutáveis encadeadas via Chain of Responsibility.
   - Elimina duplicação de iterações e chamadas recursivas dinâmicas durante o ciclo de vida HTTP.

4. **Compatibilidade e Performance:**
   - 100% retrocompatível com a sintaxe Express.js existente.
   - Resolução e despacho até 88.5% mais rápidos em rotas estáticas e 98.3% mais rápidos em OPTIONS.
   - Ganho comprovado de +4.2% em throughput sob testes de carga k6.

Consulte o [CHANGELOG](../../CHANGELOG.md) para detalhes completos de cada commit.
