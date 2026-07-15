# PsrLogger: Nome de Log Hardcoded com Referencia ao Nome Antigo do Projeto

## Titulo
`PsrLogger` usa nome de arquivo `express-php.log` hardcoded, referenciando nome depreciado

## Contexto
`src/Logging/PsrLogger.php` e a nova implementacao PSR-3 do framework (v2.0.0+).
No construtor, o path padrao para o log e definido como `sys_get_temp_dir() . '/express-php.log'`.

## Problema Identificado

```php
// src/Logging/PsrLogger.php, linha 26
$this->logPath = $logPath ?: ($_ENV['LOG_PATH'] ?? sys_get_temp_dir() . '/express-php.log');
```

1. **Nome historico errado**: O framework se chama `PivotPHP`, nao `express-php`. O nome
   `express-php` provavelmente e um residuo do nome original do projeto. Isso confunde
   operadores que procuram logs em producao.

2. **Sem separador de diretorio**: `sys_get_temp_dir() . '/express-php.log'` concatena
   manualmente uma `/`. Em Windows, `sys_get_temp_dir()` retorna caminho com `\` e a
   concatenacao com `/` pode gerar path invalido. Usar `DIRECTORY_SEPARATOR` ou
   `rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'pivotphp.log'` seria correto.

3. **Leitura de `$_ENV['LOG_PATH']`**: A variavel de ambiente e lida diretamente de `$_ENV`
   sem passar pelo sistema de configuracao do framework (`Config`). Isso cria dois caminhos
   paralelos de configuracao.

## Impacto Tecnico
- Logs do framework aparecem como `express-php.log` — confuso em producao
- Potencial path invalido em ambientes Windows
- Inconsistencia de configuracao: ignora `Config` e le `$_ENV` diretamente

## Risco
Baixo (funcional, mas confuso)

## Solucao Recomendada

```php
// Antes
$this->logPath = $logPath ?: ($_ENV['LOG_PATH'] ?? sys_get_temp_dir() . '/express-php.log');

// Depois
$defaultLog = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'pivotphp.log';
$this->logPath = $logPath ?: ($_ENV['LOG_PATH'] ?? $defaultLog);
```

## Prioridade
Baixa

## Esforco Estimado
5 minutos

## Arquivos Afetados
- `src/Logging/PsrLogger.php` (linha 26)

## Criterios de Aceite
- [ ] Nome do arquivo de log padrao e `pivotphp.log` (ou configuravel sem valor hardcoded errado)
- [ ] Separador de diretorio usa `DIRECTORY_SEPARATOR`
- [ ] PHPStan Level 9 passa
