<?php

declare(strict_types=1);

namespace PivotPHP\Core\Providers;

use PivotPHP\Core\Core\Application;

/**
 * Extension Manager
 *
 * Simple and effective extension management for the microframework.
 * Provides basic extension functionality without unnecessary complexity.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 */
class ExtensionManager
{
    /**
     * Registered extensions
     */
    private array $extensions = [];

    /**
     * Extension statistics
     */
    private array $stats = [
        'registered' => 0,
        'enabled' => 0,
        'disabled' => 0,
    ];

    /**
     * Extension states
     */
    private array $extensionStates = [];

    /**
     * Cache for class_exists() checks to avoid repeated autoloading
     */
    private array $classExistsCache = [];

    /**
     * Application instance
     */
    private Application $app;

    /**
     * Constructor
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register an extension
     */
    public function register(string $name, callable $extension): void
    {
        $this->extensions[$name] = $extension;
        $this->extensionStates[$name] = true; // enabled by default

        // Execute the extension to register services
        try {
            $extension($this->app);
        } catch (\Exception $e) {
            // Log the exception details for debugging
            error_log("Extension '{$name}' failed to execute: " . $e->getMessage());
            error_log($e->getTraceAsString());
            // If extension fails, still register it but mark as disabled
            $this->extensionStates[$name] = false;
        }

        $this->updateStats();
    }

    /**
     * Register extension (alias for register)
     */
    public function registerExtension(string $name, mixed $extension): void
    {
        // Convert string to callable if needed
        if (is_string($extension)) {
            // If it's a class name, instantiate and register it
            if (!isset($this->classExistsCache[$extension])) {
                $this->classExistsCache[$extension] = class_exists($extension);
            }
            if ($this->classExistsCache[$extension]) {
                $extension = function ($app) use ($extension) {
                    $instance = new $extension($app);
                    if (method_exists($instance, 'register')) {
                        $instance->register();
                    }
                    return $instance;
                };
            } else {
                $extension = function () use ($extension) {
                    return $extension;
                };
            }
        }

        if (is_callable($extension)) {
            $this->register($name, $extension);
        }
    }

    /**
     * Get registered extension
     */
    public function get(string $name): ?callable
    {
        return $this->extensions[$name] ?? null;
    }

    /**
     * Check if extension exists
     */
    public function has(string $name): bool
    {
        return isset($this->extensions[$name]);
    }

    /**
     * Get all registered extensions
     */
    public function all(): array
    {
        return $this->extensions;
    }

    /**
     * Remove an extension
     */
    public function remove(string $name): void
    {
        unset($this->extensions[$name]);
        unset($this->extensionStates[$name]);
        $this->updateStats();
    }

    /**
     * Clear all extensions
     */
    public function clear(): void
    {
        $this->extensions = [];
        $this->extensionStates = [];
        $this->updateStats();
    }

    /**
     * Get extension count
     */
    public function count(): int
    {
        return count($this->extensions);
    }

    /**
     * Execute an extension if it exists
     */
    public function execute(string $name, mixed ...$args): mixed
    {
        if (!$this->has($name)) {
            return null;
        }

        return ($this->extensions[$name])(...$args);
    }

    /**
     * Load extensions from configuration
     */
    public function loadFromConfig(array $config): void
    {
        foreach ($config as $name => $extensionConfig) {
            if (is_callable($extensionConfig)) {
                $this->register($name, $extensionConfig);
            }
        }
    }

    /**
     * Enable an extension
     */
    public function enable(string $name): bool
    {
        if (!$this->has($name)) {
            return false;
        }

        $this->extensionStates[$name] = true;
        $this->updateStats();
        return true;
    }

    /**
     * Disable an extension
     */
    public function disable(string $name): bool
    {
        if (!$this->has($name)) {
            return false;
        }

        $this->extensionStates[$name] = false;
        $this->updateStats();
        return true;
    }

    /**
     * Check if extension is enabled
     */
    public function isEnabled(string $name): bool
    {
        return $this->extensionStates[$name] ?? false;
    }

    /**
     * Get extension statistics
     */
    public function getStats(): array
    {
        return array_merge(
            $this->stats,
            [
                'total' => $this->stats['registered']
            ]
        );
    }

    /**
     * Check if extension exists (alias for has)
     */
    public function hasExtension(string $name): bool
    {
        return $this->has($name);
    }

    /**
     * Check if extension is enabled (alias for isEnabled)
     */
    public function isExtensionEnabled(string $name): bool
    {
        return $this->isEnabled($name);
    }

    /**
     * Disable extension (alias for disable)
     */
    public function disableExtension(string $name): bool
    {
        return $this->disable($name);
    }

    /**
     * Enable extension (alias for enable)
     */
    public function enableExtension(string $name): bool
    {
        return $this->enable($name);
    }

    /**
     * Update internal statistics
     */
    private function updateStats(): void
    {
        $this->stats['registered'] = count($this->extensions);
        $this->stats['enabled'] = count(array_filter($this->extensionStates));
        $this->stats['disabled'] = $this->stats['registered'] - $this->stats['enabled'];
    }

    /**
     * Get application instance
     */
    public function getApp(): Application
    {
        return $this->app;
    }
}
