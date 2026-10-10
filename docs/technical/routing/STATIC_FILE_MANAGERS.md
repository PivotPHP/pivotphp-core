# Arquivos estáticos

`$app->staticFiles($prefixo, $diretorio)` delega ao `StaticFileManager` do
[`pivotphp/core-routing`](https://github.com/PivotPHP/pivotphp-core-routing), que registra **cada
arquivo** do diretório como uma rota `GET`:

```php
$app->staticFiles('/assets', __DIR__ . '/public');
// GET /assets/css/app.css → public/css/app.css
```

- Só arquivos existentes no momento do registro são servidos (sem listagem de diretório).
- Arquivos ocultos (dotfiles, ex.: `.env`) não são registrados; `../` na URL não sai do diretório.
- ⚠️ **Links simbólicos são seguidos**: um symlink dentro do diretório que aponte para fora dele é
  servido. Não coloque links para arquivos sensíveis na pasta pública.
- `SimpleStaticFileManager` é apenas uma subclasse de compatibilidade (deprecated) do `StaticFileManager`.

Opções e detalhes: README do core-routing. Exemplo executável:
[`examples/02-routing/static-files.php`](../../../examples/02-routing/static-files.php).
