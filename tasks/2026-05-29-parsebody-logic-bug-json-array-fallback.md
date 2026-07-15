# Bug de Logica em parseBody: JSON array nao e mapeado para body

## Titulo
`parseBody()` ignora JSON que nao seja `stdClass` (arrays JSON sao descartados)

## Contexto
`Request::parseBody()` em `src/Http/Request.php` foi refatorado para usar `getCachedInput()`.
A logica tenta fazer `json_decode($input)` e popula `$this->body` apenas quando o resultado
e `instanceof stdClass`.

## Problema Identificado

```php
// src/Http/Request.php, linhas 900-905
$decoded = json_decode($input);
if ($decoded instanceof stdClass) {
    $this->body = $decoded;
} else {
    $this->body = new stdClass(); // BUG: JSON array tambem cai aqui
}

if (json_last_error() == JSON_ERROR_NONE) {
    return; // early return mesmo com body descartado
}
```

Cenario problemático: um cliente envia `Content-Type: application/json` com body `[1,2,3]`
(JSON array valido). `json_decode('[1,2,3]')` retorna um `array`, nao um `stdClass`.
A condicao `instanceof stdClass` e falsa, entao `$this->body` e setado para `new stdClass()`
(vazio). Em seguida, `json_last_error() == JSON_ERROR_NONE` e `true`, dispara o `return`
antecipado — e o `$_POST` nunca e consultado como fallback.

Resultado: body completamente perdido para JSON arrays validos.

O metodo `initializePsr7Request()` (linha 207) trata esse cenario corretamente com
`$decoded ?: $_POST`, criando inconsistencia entre a representacao Express.js (`$this->body`)
e a representacao PSR-7 (`parsedBody`).

## Impacto Tecnico
- `$req->body` ou `$req->input('key')` retornam objeto vazio para payloads JSON array
- Inconsistencia entre `getBodyAsStdClass()` e `getParsedBody()` para o mesmo request
- Handlers de rota que recebem listas como `POST /batch` com `[{...},{...}]` falham silenciosamente

## Risco
Alto — bug de corretude silencioso em producao

## Solucao Recomendada
Tratar o resultado de `json_decode` de forma mais abrangente:

```php
private function parseBody(): void
{
    if ($this->method === 'GET') {
        $this->body = new stdClass();
        return;
    }

    $input = $this->getCachedInput();
    if ($input !== '') {
        $decoded = json_decode($input);

        if (json_last_error() === JSON_ERROR_NONE) {
            if ($decoded instanceof stdClass) {
                $this->body = $decoded;
            } elseif (is_array($decoded)) {
                // JSON array: converter para stdClass preservando indices
                $this->body = (object) $decoded;
            } else {
                $this->body = new stdClass();
            }
            return;
        }
    }

    // Fallback para form POST
    if (!empty($_POST)) {
        $this->body = new stdClass();
        foreach ($_POST as $key => $value) {
            $this->body->{$key} = $value;
        }
        return;
    }

    $this->body = new stdClass();
}
```

## Prioridade
Alta

## Esforco Estimado
30 minutos (implementacao + testes unitarios)

## Arquivos Afetados
- `src/Http/Request.php` (metodo `parseBody`, linhas 891-920)
- `tests/Http/RequestTest.php` (adicionar casos de teste para JSON array)

## Criterios de Aceite
- [ ] `$req->input()` funciona para payload JSON array
- [ ] `$req->body` nao e objeto vazio quando o payload e um JSON array valido
- [ ] Comportamento de fallback para `$_POST` nao e afetado
- [ ] Teste cobrindo `POST /endpoint` com `body = [1,2,3]`
- [ ] Teste cobrindo `POST /endpoint` com `body = {"key":"value"}`
- [ ] Teste cobrindo `POST /endpoint` com form urlencoded
- [ ] Teste cobrindo body vazio
