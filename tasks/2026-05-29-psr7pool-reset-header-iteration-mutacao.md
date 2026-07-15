# Psr7Pool::resetServerRequest Itera e Muta Colecao Simultaneamente

## Titulo
`resetServerRequest()` itera sobre `getHeaders()` enquanto remove headers via `withoutHeader()`

## Contexto
`src/Http/Pool/Psr7Pool.php` implementa reuso de objetos PSR-7 via pooling. O metodo
`resetServerRequest()` foi modificado para limpar os headers existentes antes de aplicar
os novos, evitando contaminacao entre requests.

## Problema Identificado

```php
// src/Http/Pool/Psr7Pool.php, linhas 252-259
foreach ($request->getHeaders() as $name => $values) {
    $request = $request->withoutHeader($name);
}
```

Embora PSR-7 seja imutavel (cada `withoutHeader()` retorna nova instancia), o `foreach`
itera sobre os headers da instancia **original** (`$request` antes da primeira substituicao).
Porem `$request` e reatribuido dentro do loop — entao na segunda iteracao, `$request` aponta
para a nova instancia (sem o primeiro header), mas o `foreach` ainda percorre os headers
da copia original.

Isso e tecnicamente seguro porque PHP captura o array de `getHeaders()` na primeira chamada
do `foreach`, mas cria uma **armadilha cognitiva grave**:

1. Parece que `$request->getHeaders()` seria recalculado a cada iteracao (nao e)
2. A variavel `$name` do `foreach` e `$values` podem ser confundidas como atualizadas
3. Se a implementacao de `ServerRequestInterface` for trocada por uma que retorne
   um `Generator` ou `Iterator` lazy, o comportamento mudaria

Alem disso, o mesmo padrao ocorre em `resetResponse()` (linhas 284-288).

## Impacto Tecnico
- Codigo correto atualmente, mas fragil e enganoso
- Pode quebrar silenciosamente com implementacoes alternativas de PSR-7
- Dificulta code review (revisor precisa raciocinar sobre avaliacao antecipada do foreach)

## Risco
Medio (correto hoje, arriscado com mudancas futuras)

## Solucao Recomendada

Capturar a lista de headers antes do loop e iterar sobre ela:

```php
// Antes
foreach ($request->getHeaders() as $name => $values) {
    $request = $request->withoutHeader($name);
}

// Depois
$existingHeaders = array_keys($request->getHeaders());
foreach ($existingHeaders as $name) {
    $request = $request->withoutHeader($name);
}
```

O mesmo padrao deve ser aplicado em `resetResponse()`.

## Prioridade
Media

## Esforco Estimado
10 minutos

## Arquivos Afetados
- `src/Http/Pool/Psr7Pool.php` (metodos `resetServerRequest` linhas 252-259 e `resetResponse` linhas 284-288)

## Criterios de Aceite
- [ ] `resetServerRequest()` captura keys antes do foreach
- [ ] `resetResponse()` captura keys antes do foreach
- [ ] Testes de pool passam sem regressao
- [ ] PHPStan Level 9 sem erros
