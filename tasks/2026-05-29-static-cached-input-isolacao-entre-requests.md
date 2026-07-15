# Propriedade Estatica cachedInput Quebra Isolamento Entre Requests

## Titulo
`Request::$cachedInput` estatico impede isolamento em ambientes de multiplas requisicoes

## Contexto
`getCachedInput()` armazena o resultado de `php://input` em `private static ?string $cachedInput`.
Em um processo PHP tradicional (FPM, Apache mod_php), cada request e um processo separado,
entao a propriedade estatica e segura. Porem o projeto se posiciona como microframework de
alta performance e menciona pooling de objetos PSR-7.

## Problema Identificado

```php
// src/Http/Request.php, linhas 92-109
private static ?string $cachedInput = null;

private function getCachedInput(): string
{
    if (self::$cachedInput === null) {
        $input = @file_get_contents('php://input');
        // ...
        self::$cachedInput = $input;
    }
    return self::$cachedInput;
}
```

Problemas identificados:

1. **Ambiente de testes**: Como os testes rodam no mesmo processo PHP, uma vez que
   `$cachedInput` e populado por um teste, ele persiste para todos os testes subsequentes
   que criam instancias de `Request`. Isso causa falsos positivos/negativos em testes que
   tentam simular bodies diferentes (ex: testes de `parseBody`).

2. **Runtime ReactPHP/Swoole**: Se o framework for usado em contexto async (Swoole,
   RoadRunner, ReactPHP), onde multiplas requisicoes compartilham o mesmo processo, o
   cache estatico servira o body da primeira requisicao para todas as subsequentes.

3. **`Psr7Pool::warmUp()`** cria `ServerRequest` com `getStream('')`, que internamente
   chamara `getCachedInput()`, populando o cache com string vazia e potencialmente
   descartando o body real da proxima requisicao real.

## Impacto Tecnico
- Testes que dependem de `parseBody()` podem ser flaky
- Incompatibilidade com servidores async (Swoole, RoadRunner)
- Bug de corretude em qualquer ambiente onde o processo e reutilizado

## Risco
Medio-Alto (critico para ambientes async, medio para testes)

## Solucao Recomendada

Trocar a propriedade estatica por propriedade de instancia:

```php
// Antes
private static ?string $cachedInput = null;

private function getCachedInput(): string
{
    if (self::$cachedInput === null) { ... }
    return self::$cachedInput;
}

// Depois
private ?string $cachedInput = null;

private function getCachedInput(): string
{
    if ($this->cachedInput === null) { ... }
    return $this->cachedInput;
}
```

Adicionar metodo de reset para testes, se necessario:
```php
public static function resetInputCache(): void
{
    // Nao precisa mais — cada instancia tem seu proprio cache
}
```

## Prioridade
Media (Alta se Swoole/RoadRunner for alvo)

## Esforco Estimado
15 minutos (mudanca + verificacao de testes)

## Arquivos Afetados
- `src/Http/Request.php` (linhas 92, 99, 103, 105, 108)
- `tests/Http/RequestTest.php` (verificar se testes passam sem interferencia entre si)

## Criterios de Aceite
- [ ] Propriedade `$cachedInput` e de instancia, nao estatica
- [ ] Cada instancia de `Request` le `php://input` de forma independente
- [ ] Testes de `parseBody` com bodies diferentes nao interferem entre si
- [ ] PHPStan Level 9 continua passando
