# PivotPHP v5.2.0 - Framework Overview

**Version:** 5.2.0 (Minor)
**Release Date:** 2026-10-12
**PHP Requirements:** 8.1+

## Summary

Release minor focada no desacoplamento da camada de logging do núcleo do framework (SPEC-112), tornando o PivotPHP Core mais leve, seguro e modular. A responsabilidade de configuração avançada, formatação JSON estruturada e mascaramento de dados sensíveis foi delegada para o pacote complementar dedicado `pivotphp/logger` (SPEC-113), mantendo total interoperabilidade com qualquer implementação PSR-3.

### Principais Mudanças

1. **Desacoplamento do Logging (`SPEC-112`):**
   - O `LoggingServiceProvider` foi refatorado para não criar diretórios em disco (`mkdir`) nem abrir arquivos de log automaticamente no boot do container.
   - Em ambiente padrão sem configuração ou dependências extras, a aplicação registra e utiliza `Psr\Log\NullLogger` com overhead zero de I/O em disco.
   - O `composer.json` sugere `"pivotphp/logger": "^0.1"` e flexibiliza a restrição de `psr/log` para aceitar `^1.1|^2.0|^3.0`.

2. **Integração Automática com `pivotphp/logger` (`SPEC-113`):**
   - Quando o pacote `pivotphp/logger` estiver instalado e a configuração `logging` estiver definida (ou detectada via factory `PivotPHP\Logger\LoggerFactory`), o `LoggingServiceProvider` delega a instanciação para o logger estruturado completo.
   - Recursos avançados como mascaramento de campos confidenciais (`password`, `token`, `secret`, `authorization`, `credit_card`), formatação JSON resiliente a strings binárias e não-UTF-8, e rotação diária de arquivos ficam a cargo do novo pacote.

3. **Retrocompatibilidade e Depreciação Segura:**
   - Para manter compatibilidade retroativa com aplicações legadas que utilizam caminhos manuais de arquivo, caso `$_ENV['LOG_PATH']` ou `logging.path` seja especificado sem o pacote `pivotphp/logger`, o serviço cria o diretório de destino sob demanda e utiliza um handler compatível.
   - A classe interna `PivotPHP\Core\Logging\PsrLogger` foi anotada com `@deprecated since 5.2.0, will be removed in 6.0.0` e será removida apenas na próxima versão major do framework.

4. **Qualidade e Conformidade:**
   - 100% dos testes unitários aprovados (PHPUnit 10.x com 403 testes).
   - PHPStan no nível mais estrito (Level 9) aprovado com 0 erros.
   - Todas as regras de estilo de código (PHP-CS-Fixer) aplicadas e verificadas.

Consulte o [CHANGELOG](../../CHANGELOG.md) para detalhes completos de cada commit.

