<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Providers;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Providers\Container;

/**
 * Registro adiado de serviços no contêiner (SPEC-116).
 */
class ContainerDeferredTest extends TestCase
{
    public function testLoaderRunsOnceOnFirstLookupOfAnyOfItsIds(): void
    {
        $container = new Container();
        $runs = 0;

        $container->defer(
            ['service', 'service.alias'],
            function () use ($container, &$runs): void {
                $runs++;
                $container->singleton('service', fn (): \stdClass => new \stdClass());
                $container->alias('service.alias', 'service');
            }
        );

        $this->assertSame(0, $runs);
        $this->assertTrue($container->has('service.alias'));
        $this->assertSame($container->get('service'), $container->get('service.alias'));
        $this->assertSame(1, $runs);
    }

    public function testLoaderRunsWhenAnExistingAliasPointsToADeferredId(): void
    {
        $container = new Container();
        $container->alias('short', 'service');
        $container->defer(
            ['service'],
            function () use ($container): void {
                $container->instance('service', 'loaded');
            }
        );

        $this->assertSame('loaded', $container->get('short'));
    }

    public function testRebindingADeferredIdKeepsTheNewBinding(): void
    {
        $container = new Container();
        $container->defer(
            ['service'],
            function () use ($container): void {
                $container->instance('service', 'default');
            }
        );

        $container->instance('service', 'custom');

        $this->assertSame('custom', $container->get('service'));
    }

    public function testUnrelatedLookupsDoNotRunTheLoader(): void
    {
        $container = new Container();
        $runs = 0;
        $container->defer(
            ['service'],
            function () use (&$runs): void {
                $runs++;
            }
        );
        $container->instance('other', 1);

        $this->assertTrue($container->has('other'));
        $this->assertFalse($container->has('missing'));
        $this->assertSame(0, $runs);
    }

    public function testFlushDiscardsDeferredLoaders(): void
    {
        $container = new Container();
        $container->defer(
            ['service'],
            function () use ($container): void {
                $container->instance('service', 'loaded');
            }
        );

        $container->flush();

        $this->assertFalse($container->has('service'));
    }

    public function testIsPendingFactoryTracksResolutionAndRebinding(): void
    {
        $container = new Container();
        $factory = fn (): \stdClass => new \stdClass();
        $container->singleton('service', $factory);
        $container->alias('short', 'service');

        $this->assertTrue($container->isPendingFactory('service', $factory));
        $this->assertTrue($container->isPendingFactory('short', $factory));
        $this->assertFalse($container->isPendingFactory('service', fn (): \stdClass => new \stdClass()));
        $this->assertFalse($container->isPendingFactory('missing', $factory));

        $container->get('service');
        $this->assertFalse($container->isPendingFactory('service', $factory));

        $other = new Container();
        $other->singleton('service', $factory);
        $other->singleton('service', fn (): \stdClass => new \stdClass());
        $this->assertFalse($other->isPendingFactory('service', $factory));
    }
}
