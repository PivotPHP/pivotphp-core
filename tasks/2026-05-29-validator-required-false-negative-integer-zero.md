# Validator: Regra required Gera Falso Negativo para Integer Zero

## Titulo
`Validator::validateRule()` com regra `required` trata `0` (inteiro) como valor ausente

## Contexto
`src/Validation/Validator.php` foi atualizado com `declare(strict_types=1)` na v2.0.0.
A logica de validacao da regra `required` usa `empty()` com excecao explicitamente para
strings `'0'` e inteiro `0`.

## Problema Identificado

```php
// src/Validation/Validator.php, linhas 105-109
case 'required':
    if (empty($value) && $value !== '0' && $value !== 0) {
        $this->addError($field, 'required');
        return false;
    }
    break;
```

A correcao para `$value !== 0` aborda o caso basico, mas existem outros valores falsy
que representam dados validos em APIs:

1. **`0.0` (float zero)**: `empty(0.0)` retorna `true` e `0.0 !== '0'` e `0.0 !== 0`
   sao ambos `true`. Entao um campo de preco `price: 0.0` seria rejeitado como ausente.

2. **`false` (boolean false)**: `empty(false)` retorna `true`. Um campo boolean `active: false`
   e valido e presente, mas seria rejeitado.

3. **`[]` (array vazio)**: Dependendo do contexto, um array vazio pode ser um valor
   valido intencional (ex: lista de permissoes vazia).

A abordagem com `empty()` e excecoes pontuais e fragil — a cada tipo descoberto, uma
nova excecao e necessaria.

## Impacto Tecnico
- `price: 0.0` em um formulario de produto falha validacao `required` incorretamente
- `active: false` em um payload de usuario falha validacao `required` incorretamente
- Comportamento surpreendente para consumidores da API

## Risco
Medio (depende dos tipos de dados usados pela aplicacao)

## Solucao Recomendada

Substituir a abordagem por verificacao explicita de `null` e string vazia:

```php
// Antes
if (empty($value) && $value !== '0' && $value !== 0) {
    $this->addError($field, 'required');
    return false;
}

// Depois — verifica apenas ausencia real de valor
if ($value === null || $value === '') {
    $this->addError($field, 'required');
    return false;
}
```

Essa abordagem e semanticamente mais precisa: `required` significa "o campo nao pode
ser null ou string vazia", nao "o campo nao pode ser falsy".

## Prioridade
Media

## Esforco Estimado
20 minutos (implementacao + testes para todos os tipos falsy)

## Arquivos Afetados
- `src/Validation/Validator.php` (linhas 105-110, case 'required')
- Adicionar testes em `tests/` para `Validator` cobrindo: `0`, `0.0`, `false`, `[]`, `''`, `null`

## Criterios de Aceite
- [ ] `required` aceita `0` (int), `0.0` (float), `false` (bool) como valores validos
- [ ] `required` rejeita `null` e `''` (string vazia)
- [ ] Testes unitarios para todos os tipos falsy
- [ ] Sem regressao nos testes existentes de validacao
