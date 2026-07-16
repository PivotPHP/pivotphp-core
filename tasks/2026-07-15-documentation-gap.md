# Gap de Documentação: Versão Divulgada Desatualizada em Múltiplos Locais

## Titulo
`CLAUDE.md`, `docs/index.md`, `docs/README.md` e a constante `Application::VERSION` ainda
anunciam versões antigas (2.0.0 / 1.2.0 / 1.1.4), enquanto o `CHANGELOG.md` já documenta
2.1.0 e 2.1.1

## Contexto
Durante a preparação do release 2.1.1 (correção de compatibilidade real com
`psr/http-message` v2.0, ver `CHANGELOG.md` entrada `[2.1.1]`), foi identificado que o
número de versão "atual" divulgado no repositório está inconsistente em vários lugares:

- `src/Core/Application.php:47` — `public const VERSION = '2.0.0';` (arquivo `.php`, fora do
  escopo desta tarefa de documentação; precisa ser corrigido por quem mexe em código).
- `CLAUDE.md` — cabeçalho diz "Current version: **2.0.0**" e a seção "Current Version
  Status" repete 2.0.0 com data de release `2025-07-21`.
- `docs/index.md` (seção "Histórico de Versões") — lista **v1.2.0** como "(Atual)".
- `docs/README.md` — diz "Current Version: v1.1.4".
- `VERSION` (raiz) — estava em `2.0.0` antes desta sessão corrigir para `2.1.1` junto do
  release; o `CHANGELOG.md` já tinha uma entrada `[2.1.0]` havia sido adicionada sem que o
  `VERSION` file ou a constante `Application::VERSION` fossem atualizados junto, sugerindo
  que a 2.1.0 não chegou a ser tageada/lançada oficialmente.

## Problema Identificado
Não existe uma fonte única de verdade para "qual é a versão atual do pacote". Um
contribuidor ou usuário que consulte `CLAUDE.md`, `docs/index.md` ou `docs/README.md` verá
três números diferentes, nenhum deles batendo com o `CHANGELOG.md` (que é a fonte mais
confiável, atualizada a cada mudança relevante) nem com o `composer.json`/`VERSION`
oficiais.

## Impacto Técnico e Operacional
- Onboarding confuso: novos contribuidores não sabem qual versão está de fato em
  desenvolvimento.
- Risco de decisões erradas de compatibilidade (ex.: alguém lendo `docs/index.md` pode achar
  que features de 2.0/2.1 não existem ainda).
- Processo de release parece não ter um passo automatizado que sincronize `VERSION`,
  `Application::VERSION` e os docs de índice — o fato de `VERSION` ter ficado em `2.0.0`
  mesmo com `CHANGELOG.md` já tendo uma entrada `[2.1.0]` sugere que esse passo foi pulado
  na 2.1.0.

## Risco
Médio (não afeta comportamento em runtime, mas gera confusão de onboarding e pode levar a
relatórios de bug ou dúvidas de compatibilidade equivocadas)

## Solução Recomendada
1. Corrigir `src/Core/Application.php::VERSION` para `2.1.1` (mudança de código, não de
   documentação — fora do escopo de quem só mexe em docs).
2. Atualizar `CLAUDE.md` (cabeçalho + seção "Current Version Status") para refletir 2.1.1.
3. Atualizar `docs/index.md` (seção "Histórico de Versões") incluindo 2.0.0/2.1.0/2.1.1 e
   marcando a mais recente como "(Atual)".
4. Atualizar `docs/README.md` ("Current Version: v1.1.4" está bastante desatualizado).
5. Considerar automatizar essa sincronização como parte de
   `scripts/release/prepare_release.sh` / `scripts/release/release.sh`, para que o
   `VERSION` file, `Application::VERSION` e os docs de índice sejam sempre atualizados juntos
   ao cortar um release — evitando a situação atual em que `CHANGELOG.md` ficou à frente de
   tudo o mais.

## Prioridade
Média — não bloqueia o release 2.1.1 (já resolvido pontualmente: `VERSION` e
`CHANGELOG.md` estão sincronizados nesta sessão), mas deve ser resolvido antes do próximo
release para não repetir o padrão.

## Esforço Estimado
30–45 minutos (edições de texto + validação manual de cada menção de versão)

## Arquivos Afetados
- `src/Core/Application.php` (linha 47) — fora do escopo de documentação
- `CLAUDE.md`
- `docs/index.md`
- `docs/README.md`

## Critérios de Aceite
- [x] `Application::VERSION`, `VERSION` (raiz), `CHANGELOG.md` (entrada mais recente) e
      `composer.json` (se aplicável) apontam para o mesmo número de versão
- [x] `CLAUDE.md`, `docs/index.md` e `docs/README.md` não mencionam mais números de versão
      anteriores como "atual"
- [ ] Processo de release (`scripts/release/*`) documenta ou automatiza a sincronização
      dessas menções para releases futuros
