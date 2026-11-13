<?php

declare(strict_types=1);

namespace PivotPHP\Core\Providers;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use PivotPHP\Core\Exceptions\Container\ServiceNotFoundException;
use PivotPHP\Core\Exceptions\Container\ContainerException;

/**
 * Simple PSR-11 compliant container implementation
 *
 * Nota arquitetural: esta classe é a implementação canônica ativa do
 * container (Application usa exclusivamente esta, não Core\Container —
 * ver docs/technical/DEPRECATION_AND_REMOVAL_PLAN.md ITEM-001). O
 * namespace Providers/ historicamente deveria conter apenas providers que
 * *registram* serviços, não os serviços em si (ver
 * docs/technical/INCONSISTENCIES_REPORT.md M-06) — Logger e
 * EventDispatcher já foram movidos para namespaces mais corretos
 * (Logging\PsrLogger, Events\EventDispatcher). Container permanece aqui
 * deliberadamente: movê-lo agora seria uma terceira mudança de identidade
 * em pouco tempo (Core\Container → Providers\Container → outro
 * namespace), sem benefício real para quem consome a classe.
 */
class Container implements ContainerInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $services = [];

    /**
     * @var array<string, callable>
     */
    private array $factories = [];

    /**
     * @var array<string, bool>
     */
    private array $shared = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * Carregadores adiados: id do serviço → callable que registra quem o fornece.
     *
     * @var array<string, callable>
     */
    private array $deferred = [];

    /**
     * {@inheritdoc}
     */
    public function get(string $id): mixed
    {
        if ($this->deferred !== []) {
            $this->loadDeferred($id);
        }

        // Resolve alias
        $id = $this->aliases[$id] ?? $id;

        // Check if service is bound
        if (!$this->has($id)) {
            throw new ServiceNotFoundException("Service '{$id}' not found in container");
        }

        // Return singleton instance if already created
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        try {
            // Get service instance
            if (isset($this->factories[$id])) {
                $service = $this->factories[$id]($this);
            } else {
                $service = $this->services[$id];
            }

            // Store as singleton if marked as shared
            if ($this->shared[$id] ?? true) {
                $this->instances[$id] = $service;
            }

            return $service;
        } catch (\Throwable $e) {
            throw new ContainerException(
                "Error resolving service '{$id}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Indica se o serviço está registrado com exatamente esta fábrica e ainda não foi resolvido.
     *
     * Permite a um provider saber se o serviço que ele registrou continua intocado — nem
     * instanciado, nem substituído por outro binding — e assim pular trabalho que só teria efeito
     * se o serviço estivesse em uso.
     */
    public function isPendingFactory(string $id, callable $factory): bool
    {
        $id = $this->aliases[$id] ?? $id;

        return !isset($this->instances[$id]) && ($this->factories[$id] ?? null) === $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        if ($this->deferred !== []) {
            $this->loadDeferred($id);
        }

        // Resolve alias
        $id = $this->aliases[$id] ?? $id;

        return isset($this->services[$id]) || isset($this->factories[$id]);
    }

    /**
     * Adia o registro dos serviços `$ids`: `$loader` roda uma única vez, na primeira vez em que
     * um deles for consultado, resolvido ou redefinido, e deve registrá-los no contêiner.
     *
     * @param array<int, string> $ids
     */
    public function defer(array $ids, callable $loader): void
    {
        foreach ($ids as $id) {
            $this->deferred[$id] = $loader;
        }
    }

    /**
     * Executa o carregador adiado de `$id` (ou do serviço para o qual ele é alias), se houver.
     */
    private function loadDeferred(string $id): void
    {
        $loader = $this->deferred[$id] ?? $this->deferred[$this->aliases[$id] ?? $id] ?? null;
        if ($loader === null) {
            return;
        }

        // Remove todas as entradas do carregador antes de executá-lo: ele registra esses mesmos ids.
        foreach ($this->deferred as $key => $candidate) {
            if ($candidate === $loader) {
                unset($this->deferred[$key]);
            }
        }

        $loader();
    }

    /**
     * Bind a concrete implementation to the container
     */
    public function bind(
        string $abstract,
        mixed $concrete = null,
        bool $shared = true
    ): void {
        // Quem redefine um serviço adiado espera sobrepor o registro original, não ser sobreposto.
        if ($this->deferred !== []) {
            $this->loadDeferred($abstract);
        }

        $concrete = $concrete ?? $abstract;

        if (is_callable($concrete)) {
            $this->factories[$abstract] = $concrete;
        } else {
            $this->services[$abstract] = $concrete;
        }

        $this->shared[$abstract] = $shared;

        // Remove cached instance if it exists
        unset($this->instances[$abstract]);
    }

    /**
     * Bind a singleton to the container
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared in the container
     */
    public function instance(string $abstract, mixed $instance): void
    {
        // Quem redefine um serviço adiado espera sobrepor o registro original, não ser sobreposto.
        if ($this->deferred !== []) {
            $this->loadDeferred($abstract);
        }

        $this->instances[$abstract] = $instance;
        $this->services[$abstract] = $instance;
        $this->shared[$abstract] = true;
    }

    /**
     * Create an alias for a service
     */
    public function alias(string $alias, string $abstract): void
    {
        // Quem redefine um serviço adiado espera sobrepor o registro original, não ser sobreposto.
        if ($this->deferred !== []) {
            $this->loadDeferred($alias);
        }

        $this->aliases[$alias] = $abstract;
    }

    /**
     * Remove a service from the container
     */
    public function forget(string $abstract): void
    {
        // Quem redefine um serviço adiado espera sobrepor o registro original, não ser sobreposto.
        if ($this->deferred !== []) {
            $this->loadDeferred($abstract);
        }

        unset(
            $this->services[$abstract],
            $this->factories[$abstract],
            $this->instances[$abstract],
            $this->shared[$abstract]
        );
    }

    /**
     * Flush all services from the container
     */
    public function flush(): void
    {
        $this->services = [];
        $this->factories = [];
        $this->instances = [];
        $this->shared = [];
        $this->aliases = [];
        $this->deferred = [];
    }
}
