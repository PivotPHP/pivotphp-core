<?php

namespace PivotPHP\Core\Core;

use PivotPHP\Core\Utils\Arr;

/**
 * Sistema de configuração centralizado do PivotPHP.
 *
 * Gerencia configurações da aplicação com suporte a:
 * - Carregamento de arquivos de configuração
 * - Variáveis de ambiente
 * - Configurações em tempo de execução
 * - Acesso via dot notation
 */
class Config
{
    /**
     * Todas as configurações carregadas.
     *
     * @var array<string, mixed>
     */
    private array $items = [];

    /**
     * Cache de configurações já resolvidas.
     *
     * @var array<string, mixed>
     */
    private array $cache = [];

    /**
     * Diretório base de configurações.
     *
     * @var string|null
     */
    private ?string $configPath = null;

    /**
     * Construtor da classe Config.
     *
     * @param array<string, mixed> $items Configurações iniciais
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Define o diretório de configurações.
     *
     * @param  string $path Caminho para o diretório
     * @return $this
     */
    public function setConfigPath(string $path): self
    {
        $this->configPath = rtrim($path, '/\\');
        return $this;
    }

    /**
     * Carrega configurações de um arquivo.
     *
     * @param  string $name Nome do arquivo (sem extensão)
     * @return $this
     */
    public function load(string $name): self
    {
        if ($this->configPath === null) {
            return $this;
        }

        $file = $this->configPath . DIRECTORY_SEPARATOR . $name . '.php';

        if (file_exists($file)) {
            $config = include $file;
            if (is_array($config)) {
                $this->items[$name] = $config;
                // Clear all cache entries that start with this namespace
                $this->clearCacheForNamespace($name);
            }
        }

        return $this;
    }

    /**
     * Carrega todas as configurações do diretório.
     *
     * @return $this
     */
    public function loadAll(): self
    {
        if ($this->configPath === null || !is_dir($this->configPath)) {
            return $this;
        }

        $files = glob($this->configPath . '/*.php');

        if ($files) {
            foreach ($files as $file) {
                $name = basename($file, '.php');
                $this->load($name);
            }
        }

        return $this;
    }

    /**
     * Obtém um valor de configuração.
     *
     * @param  string|null $key     Chave da configuração (dot
     *                              notation)
     * @param  mixed       $default Valor padrão se não
     *                              encontrado
     * @return mixed
     */
    public function get(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->items;
        }

        // Verificar cache primeiro
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        // Resolver valor
        $value = Arr::get($this->items, $key, $default);

        // Processar variáveis de ambiente
        if (is_string($value)) {
            $value = $this->resolveEnvironmentVariables($value);
        }

        // Cache do resultado
        $this->cache[$key] = $value;

        return $value;
    }

    /**
     * Define um valor de configuração.
     *
     * @param  string $key   Chave da configuração (dot
     *                       notation)
     * @param  mixed  $value Valor a ser definido
     * @return $this
     */
    public function set(string $key, $value): self
    {
        Arr::set($this->items, $key, $value);

        // Limpar cache relacionado - the key itself and all parent/child keys
        $this->clearCacheForKey($key);

        return $this;
    }

    /**
     * Verifica se uma configuração existe.
     *
     * @param  string $key Chave da configuração
     * @return bool
     */
    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    /**
     * Adiciona valores a uma configuração (merge).
     *
     * @param  string       $key    Chave da
     *                              configuração
     * @param  array<mixed> $values Valores a serem adicionados
     * @return $this
     */
    public function push(string $key, array $values): self
    {
        $current = $this->get($key, []);

        if (is_array($current)) {
            $this->set($key, array_merge($current, $values));
        } else {
            $this->set($key, $values);
        }

        return $this;
    }

    /**
     * Remove uma configuração.
     *
     * @param  string $key Chave da configuração
     * @return $this
     */
    public function forget(string $key): self
    {
        Arr::forget($this->items, $key);
        unset($this->cache[$key]);
        return $this;
    }

    /**
     * Resolve variáveis de ambiente em uma string.
     *
     * @param  string $value String com possíveis variáveis
     * @return string
     */
    private function resolveEnvironmentVariables(string $value): string
    {
        $result = preg_replace_callback(
            '/\$\{([^}]+)\}/',
            static function (array $matches): string {
                $parts = explode(':', $matches[1], 2);
                $varName = $parts[0];
                $defaultValue = $parts[1] ?? '';

                if (array_key_exists($varName, $_ENV)) {
                    return is_scalar($_ENV[$varName]) ? (string) $_ENV[$varName] : $defaultValue;
                }

                $env = getenv($varName);

                return is_string($env) && $env !== '' ? $env : $defaultValue;
            },
            $value
        );

        return $result ?? $value;
    }

    /**
     * Carrega variáveis de ambiente de um arquivo .env.
     *
     * @param  string $envFile Caminho para o arquivo .env
     * @return $this
     */
    public function loadEnvironment(string $envFile): self
    {
        if (!file_exists($envFile)) {
            return $this;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines) {
            foreach ($lines as $line) {
                // Ignorar comentários
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parsear linha
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, '"\'');

                    // Definir variável de ambiente
                    if (!getenv($key)) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Obtém configurações de um namespace específico.
     *
     * @param  string $namespace Namespace da configuração
     * @return array<string, mixed>
     */
    public function getNamespace(string $namespace): array
    {
        $value = $this->get($namespace, []);
        return is_array($value) ? $value : [];
    }

    /**
     * Mescla configurações com as existentes.
     *
     * @param  array<string, mixed> $config Configurações a serem mescladas
     * @return $this
     */
    public function merge(array $config): self
    {
        $this->items = array_merge_recursive($this->items, $config);
        $this->cache = []; // Limpar todo o cache
        return $this;
    }

    /**
     * Exporta todas as configurações.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Limpa o cache de configurações.
     *
     * @return $this
     */
    public function clearCache(): self
    {
        $this->cache = [];
        return $this;
    }

    /**
     * Limpa cache de um namespace específico.
     *
     * @param string $namespace Namespace a ser limpo
     * @return void
     */
    private function clearCacheForNamespace(string $namespace): void
    {
        $prefix = $namespace . '.';
        foreach (array_keys($this->cache) as $key) {
            if ($key === $namespace || strpos($key, $prefix) === 0) {
                unset($this->cache[$key]);
            }
        }
    }

    /**
     * Limpa cache relacionado a uma chave específica.
     *
     * @param string $key Chave a ser limpa
     * @return void
     */
    private function clearCacheForKey(string $key): void
    {
        // Clear the key itself
        unset($this->cache[$key]);

        // Clear all parent keys and their cached children
        $keyParts = explode('.', $key);
        $parentKey = '';
        foreach ($keyParts as $part) {
            $parentKey .= ($parentKey ? '.' : '') . $part;

            // Clear this parent level and all its cached children
            $this->clearCacheForNamespace($parentKey);
        }
    }

    /**
     * Cria uma instância de configuração a partir de um array.
     *
     * @param  array<string, mixed> $config Array de configuração
     * @return static
     */
    public static function fromArray(array $config): self
    {
        /**
 * @phpstan-ignore-next-line
*/
        return new static($config);
    }

    /**
     * Cria uma instância carregando de um diretório.
     *
     * @param  string $configPath Caminho do diretório
     * @return static
     */
    public static function fromDirectory(string $configPath): self
    {
        /**
 * @phpstan-ignore-next-line
*/
        $instance = new static();
        return $instance->setConfigPath($configPath)->loadAll();
    }

    /**
     * Obtém informações de debug.
     *
     * @return array<string, mixed>
     */
    public function getDebugInfo(): array
    {
        return [
            'config_path' => $this->configPath,
            'loaded_configs' => array_keys($this->items),
            'cached_keys' => array_keys($this->cache),
            'total_items' => count($this->items, COUNT_RECURSIVE)
        ];
    }
}
