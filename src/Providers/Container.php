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
     * {@inheritdoc}
     */
    public function get(string $id): mixed
    {
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
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        // Resolve alias
        $id = $this->aliases[$id] ?? $id;

        return isset($this->services[$id]) || isset($this->factories[$id]);
    }

    /**
     * Bind a concrete implementation to the container
     */
    public function bind(
        string $abstract,
        mixed $concrete = null,
        bool $shared = true
    ): void {
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
        $this->instances[$abstract] = $instance;
        $this->services[$abstract] = $instance;
        $this->shared[$abstract] = true;
    }

    /**
     * Create an alias for a service
     */
    public function alias(string $alias, string $abstract): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Remove a service from the container
     */
    public function forget(string $abstract): void
    {
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
    }
}
