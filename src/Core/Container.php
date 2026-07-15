<?php

declare(strict_types=1);

namespace PivotPHP\Core\Core;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * Container de Inversão de Controle (IoC) para gerenciamento de dependências.
 *
 * Permite registro, resolução automática e gerenciamento do ciclo de vida
 * de objetos e dependências no PivotPHP.
 *
 * @deprecated v2.1.0 Use \PivotPHP\Core\Providers\Container instead.
 */
class Container
{
    /**
     * Instância singleton do container.
     *
     * @var Container|null
     */
    private static ?Container $instance = null;

    /**
     * Bindings registrados no container.
     *
     * @var array<string, mixed>
     */
    private array $bindings = [];

    /**
     * Instâncias singleton registradas.
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Aliases registrados para classes.
     *
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * Tags para agrupamento de services.
     *
     * @var array<string, array<string>>
     */
    private array $tags = [];

    /**
     * Stack para detectar dependências circulares.
     *
     * @var array<string>
     */
    private array $resolutionStack = [];

    /**
     * Construtor privado para singleton.
     */
    private function __construct()
    {
        // Registrar o próprio container
        $this->instance(Container::class, $this);
        $this->alias(Container::class, 'container');
    }

    /**
     * Obtém a instância singleton do container.
     *
     * @return Container
     */
    public static function getInstance(): Container
    {
        trigger_error('PivotPHP\\Core\\Core\\Container is deprecated. Use PivotPHP\\Core\\Providers\\Container instead.', E_USER_DEPRECATED);
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registra um binding no container.
     *
     * @param  string $abstract  Nome abstrato ou interface
     * @param  mixed  $concrete  Implementação concreta (classe,
     *                           closure, instância)
     * @param  bool   $singleton Se deve ser tratado como singleton
     * @return $this
     */
    public function bind(
        string $abstract,
        $concrete = null,
        bool $singleton = false
    ): self {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton,
            'instance' => null
        ];

        return $this;
    }

    /**
     * Registra um singleton no container.
     *
     * @param  string $abstract Nome abstrato ou interface
     * @param  mixed  $concrete Implementação
     *                          concreta
     * @return $this
     */
    public function singleton(string $abstract, $concrete = null): self
    {
        return $this->bind($abstract, $concrete, true);
    }

    /**
     * Registra uma instância existente como singleton.
     *
     * @param  string $abstract Nome abstrato
     * @param  mixed  $instance Instância do objeto ou valor
     * @return $this
     */
    public function instance(string $abstract, $instance): self
    {
        $this->instances[$abstract] = $instance;
        return $this;
    }

    /**
     * Cria um alias para um binding.
     *
     * @param  string $abstract Nome abstrato original
     * @param  string $alias    Alias a ser criado
     * @return $this
     */
    public function alias(string $abstract, string $alias): self
    {
        $this->aliases[$alias] = $abstract;
        return $this;
    }

    /**
     * Adiciona tags a um service.
     *
     * @param  array<string>|string $tags    Tags a serem adicionadas
     * @param  string               $service Nome do service
     * @return $this
     */
    public function tag($tags, string $service): self
    {
        $tags = is_array($tags) ? $tags : [$tags];

        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }
            $this->tags[$tag][] = $service;
        }

        return $this;
    }

    /**
     * Resolve um service do container.
     *
     * @param  string       $abstract   Nome abstrato a ser resolvido
     * @param  array<mixed> $parameters Parâmetros adicionais
     * @return mixed
     * @throws Exception
     */
    public function make(string $abstract, array $parameters = [])
    {
        // Resolver alias
        if (isset($this->aliases[$abstract])) {
            $abstract = $this->aliases[$abstract];
        }

        // Detectar dependência circular
        if (in_array($abstract, $this->resolutionStack)) {
            throw new Exception("Circular dependency detected for {$abstract}");
        }

        // Verificar se já existe uma instância singleton
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Adicionar ao stack de resolução
        $this->resolutionStack[] = $abstract;

        try {
            // Verificar binding registrado
            if (isset($this->bindings[$abstract])) {
                $binding = $this->bindings[$abstract];

                if (
                    !is_array($binding) ||
                    !array_key_exists('singleton', $binding) ||
                    !array_key_exists('instance', $binding) ||
                    !array_key_exists('concrete', $binding)
                ) {
                    throw new Exception("Invalid binding configuration for {$abstract}");
                }

                if ($binding['singleton'] && $binding['instance'] !== null) {
                    return $binding['instance'];
                }

                $instance = $this->build($binding['concrete'], $parameters);

                if ($binding['singleton']) {
                    $binding['instance'] = $instance;
                    $this->bindings[$abstract] = $binding;
                }

                return $instance;
            }

            // Tentar resolver automaticamente
            return $this->build($abstract, $parameters);
        } finally {
            // Remover do stack de resolução
            array_pop($this->resolutionStack);
        }
    }

    /**
     * Constrói uma instância de uma classe.
     *
     * @param  mixed        $concrete   Classe concreta ou closure
     * @param  array<mixed> $parameters Parâmetros adicionais
     * @return mixed
     * @throws Exception
     */
    private function build($concrete, array $parameters = [])
    {
        // Se for uma closure, execute
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        // Se for uma string, tente resolver como classe
        if (is_string($concrete)) {
            return $this->buildClass($concrete, $parameters);
        }

        // Se já for um objeto, retorne
        if (is_object($concrete)) {
            return $concrete;
        }

        throw new Exception("Cannot build instance for: " . var_export($concrete, true));
    }

    /**
     * Constrói uma instância de uma classe específica.
     *
     * @param  string       $className  Nome da classe
     * @param  array<mixed> $parameters Parâmetros adicionais
     * @return object
     * @throws Exception
     */
    private function buildClass(string $className, array $parameters = []): object
    {
        try {
            /**
 * @phpstan-ignore-next-line
*/
            $reflection = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            throw new Exception("Class {$className} not found: " . $e->getMessage());
        }

        if (!$reflection->isInstantiable()) {
            throw new Exception("Class {$className} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        // Se não há construtor, apenas cria a instância
        if ($constructor === null) {
            return $reflection->newInstance();
        }

        // Resolver dependências do construtor
        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolve as dependências de parâmetros.
     *
     * @param  array<ReflectionParameter> $parameters Parâmetros do construtor
     * @param  array<mixed>               $primitives Valores primitivos fornecidos
     * @return array<mixed>
     * @throws Exception
     */
    private function resolveDependencies(array $parameters, array $primitives = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            // Se foi fornecido um valor primitivo, use-o
            if (array_key_exists($name, $primitives)) {
                $dependencies[] = $primitives[$name];
                continue;
            }

            // Tentar resolver tipo
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $className = $type->getName();
                $dependencies[] = $this->make($className);
                continue;
            }

            // Se tem valor padrão, use-o
            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            // Se é nullable, use null
            if ($parameter->allowsNull()) {
                $dependencies[] = null;
                continue;
            }

            throw new Exception("Cannot resolve parameter {$name} for class");
        }

        return $dependencies;
    }

    /**
     * Verifica se um service está registrado.
     *
     * @param  string $abstract Nome abstrato
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               isset($this->aliases[$abstract]);
    }

    /**
     * Remove um binding do container.
     *
     * @param  string $abstract Nome abstrato
     * @return $this
     */
    public function forget(string $abstract): self
    {
        unset($this->bindings[$abstract], $this->instances[$abstract]);
        return $this;
    }

    /**
     * Obtém todos os services com uma tag específica.
     *
     * @param  string $tag Nome da tag
     * @return array<mixed>
     */
    public function tagged(string $tag): array
    {
        if (!isset($this->tags[$tag])) {
            return [];
        }

        $services = [];
        foreach ($this->tags[$tag] as $service) {
            $services[] = $this->make($service);
        }

        return $services;
    }

    /**
     * Executa um callback com dependências resolvidas.
     *
     * @param  callable|array<mixed>     $callback   Callback a ser executado
     * @param  array<mixed> $parameters Parâmetros adicionais
     * @return mixed
     */
    public function call($callback, array $parameters = [])
    {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        }

        if (is_array($callback)) {
            [$class, $method] = $callback;

            if (is_string($class)) {
                $class = $this->make($class);
            }

            $callback = [$class, $method];

            // Resolve dependencies for method
            try {
                $reflection = new \ReflectionMethod($class, $method);
                $dependencies = $this->resolveDependencies($reflection->getParameters(), $parameters);
                /** @var callable $callback */
                return call_user_func($callback, ...$dependencies);
            } catch (\ReflectionException $e) {
                throw new Exception("Method {$method} not found: " . $e->getMessage());
            }
        }

        // Handle closures and functions
        if ($callback instanceof \Closure || (is_string($callback) && is_callable($callback))) {
            try {
                $reflection = new \ReflectionFunction($callback);
                $dependencies = $this->resolveDependencies($reflection->getParameters(), $parameters);
                return call_user_func($callback, ...$dependencies);
            } catch (\ReflectionException $e) {
                throw new Exception("Function reflection failed: " . $e->getMessage());
            }
        }

        // Fallback for other callable types
        /** @var callable $callback */
        return call_user_func($callback, ...$parameters);
    }

    /**
     * Limpa todas as instâncias e bindings.
     *
     * @return $this
     */
    public function flush(): self
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
        $this->tags = [];
        $this->resolutionStack = [];

        // Re-registrar o container
        $this->instance(Container::class, $this);
        $this->alias(Container::class, 'container');

        return $this;
    }

    /**
     * Obtém informações de debug do container.
     *
     * @return array<string, mixed>
     */
    public function getDebugInfo(): array
    {
        return [
            'bindings' => array_keys($this->bindings),
            'instances' => array_keys($this->instances),
            'aliases' => $this->aliases,
            'tags' => array_keys($this->tags)
        ];
    }
}
