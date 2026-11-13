# PivotPHP v5.3.0 - Framework Overview

**Version:** 5.3.0 (Minor)
**Release Date:** 2026-11-13
**PHP Requirements:** 8.1+

## Summary

Release minor focada em reduzir o trabalho que o core faz em toda requisição no PHP-FPM (SPEC-116): o que
uma requisição não usa deixa de ser carregado e montado. Não há mudança de API incompatível; a única opção
que altera comportamento, `lazyHandlers()`, é desligada por padrão.

### Principais Mudanças

1. **Providers do core sob demanda:**
   - Os seis providers do core (`Container`, `Event`, `Logging`, `Hook`, `Extension`, `Routing`) não são mais
     instanciados no construtor de `Application`. Cada um é carregado quando um dos serviços que fornece é
     consultado, resolvido ou redefinido pela primeira vez (`Container::defer()`).
   - Serviços, aliases e ordem de boot continuam os mesmos. Um binding feito pela aplicação antes do primeiro
     uso prevalece sobre o do provider.

2. **Eventos, hooks e extensões só quando usados:**
   - `ApplicationStarted`, `RequestReceived` e `ResponseSent` só são criados e despachados quando pode existir
     um listener: um listener registrado, o dispatcher ou o listener provider já resolvidos, ou um dispatcher
     próprio registrado no contêiner.
   - Os hooks `app.registered` e `app.booting` só são disparados se o `HookManager` estiver em uso ou houver
     listener PSR-14. O `ExtensionManager` só é instanciado quando pedido.

3. **Handlers preguiçosos (opcional):**
   - `$app->lazyHandlers()` adia o carregamento e a validação dos handlers `[Classe::class, 'método']` para o
     despacho da rota. Só o controller da rota que casou é carregado.
   - Com a opção ligada, um handler inválido responde `500` quando a rota é chamada, em vez de lançar
     `InvalidArgumentException` no registro. Por isso ela é desligada por padrão.

4. **Caminho da requisição mais curto:**
   - O `BodyParser` não é instanciado para requisições sem `Content-Type`.
   - Handlers `Closure` são executados sem passar pelo `CallableResolver`.

### Desempenho medido

Cenário `GET /api/bare` da suíte `pivotphp-benchmarks` (Nginx + PHP-FPM 8.3, OPcache, 1 vCPU), CPU do container
por requisição, 5 rodadas pareadas:

| Variante | µs por requisição | vs. 5.2.0 | Arquivos carregados |
|---|---:|---:|---:|
| core 5.2.0 | 563,1 ± 19,5 | — | 71 |
| core 5.3.0 | 527,4 ± 9,8 | −6,3% ± 1,5 | 51 |
| core 5.3.0 + `lazyHandlers()` | 516,6 ± 12,0 | −8,2% ± 1,4 | 48 |

O piso da medição (PHP puro no mesmo Nginx + PHP-FPM) é de 259 µs: o custo do framework acima dele cai de
~304 µs para ~268 µs e ~257 µs.

### Qualidade e Conformidade

- 423 testes aprovados (PHPUnit), incluindo 20 novos para o registro adiado, os eventos condicionais e os
  handlers preguiçosos.
- PHPStan nível 9 com 0 erros; PSR-12 verificado com PHP_CodeSniffer.

Consulte o [CHANGELOG](../../CHANGELOG.md) para os detalhes.
