<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Events\ApplicationStarted;
use PivotPHP\Core\Events\Hook;
use PivotPHP\Core\Events\RequestReceived;
use PivotPHP\Core\Events\ResponseSent;
use PivotPHP\Core\Providers\ServiceProvider;
use PivotPHP\Core\Tests\Core\Fixtures\LazyLoadedController;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * O que não é usado numa requisição não é montado — sem mudar o que é observável (SPEC-116).
 */
class DeferredBootstrapTest extends TestCase
{
    private function app(): Application
    {
        $app = new Application(__DIR__ . '/../..');
        $app->get('/', fn ($req, $res) => $res->json(['ok' => true]));

        return $app;
    }

    public function testEventSystemStaysIdleWhenNothingListens(): void
    {
        $app = $this->app();

        $this->assertTrue($app->isEventSystemIdle());

        $response = $app->handle(new ServerRequest('GET', '/'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($app->isEventSystemIdle());
    }

    public function testLifecycleEventsReachListenersRegisteredBeforeHandle(): void
    {
        $app = $this->app();
        $seen = [];

        foreach ([ApplicationStarted::class, RequestReceived::class, ResponseSent::class] as $event) {
            $app->on(
                $event,
                function (object $e) use (&$seen): void {
                    $seen[] = $e::class;
                }
            );
        }

        $this->assertFalse($app->isEventSystemIdle());

        $app->handle(new ServerRequest('GET', '/'));

        $this->assertSame([ApplicationStarted::class, RequestReceived::class, ResponseSent::class], $seen);
    }

    public function testListenerRegisteredDuringTheRequestReceivesResponseSent(): void
    {
        $app = new Application(__DIR__ . '/../..');
        $seen = [];

        $app->get(
            '/',
            function ($req, $res) use ($app, &$seen) {
                $app->on(
                    ResponseSent::class,
                    function (ResponseSent $e) use (&$seen): void {
                        $seen[] = $e::class;
                    }
                );

                return $res->json([]);
            }
        );

        $app->handle(new ServerRequest('GET', '/'));

        $this->assertSame([ResponseSent::class], $seen);
    }

    public function testCustomDispatcherBoundButNotYetResolvedReceivesLifecycleEvents(): void
    {
        $app = $this->app();
        $dispatched = [];

        $app->singleton(
            EventDispatcherInterface::class,
            function () use (&$dispatched): EventDispatcherInterface {
                return new class ($dispatched) implements EventDispatcherInterface {
                    /** @param array<int, string> $dispatched */
                    public function __construct(private array &$dispatched)
                    {
                    }

                    public function dispatch(object $event): object
                    {
                        $this->dispatched[] = $event::class;

                        return $event;
                    }
                };
            }
        );

        $this->assertFalse($app->isEventSystemIdle());

        $app->handle(new ServerRequest('GET', '/'));

        $this->assertContains(RequestReceived::class, $dispatched);
        $this->assertContains(ResponseSent::class, $dispatched);
    }

    public function testCoreHooksReachPsr14ListenersWithoutTheHookManager(): void
    {
        $app = $this->app();
        $hooks = [];

        $app->on(
            Hook::class,
            function (Hook $hook) use (&$hooks): void {
                $hooks[] = $hook->getName();
            }
        );

        $app->boot();

        $this->assertSame(['app.registered', 'app.booting'], $hooks);
    }

    public function testCoreHooksFireBeforeApplicationProvidersBoot(): void
    {
        $app = $this->app();
        $order = [];

        $provider = new class ($app, $order) extends ServiceProvider {
            /** @param array<int, string> $order */
            public function __construct(Application $app, private array &$order)
            {
                parent::__construct($app);
            }

            public function register(): void
            {
            }

            public function boot(): void
            {
                $this->order[] = 'provider.boot';
            }
        };

        // O provider da aplicação é registrado antes de o provider de hooks ser carregado.
        $app->register($provider);
        $app->addAction(
            'app.booting',
            function () use (&$order): void {
                $order[] = 'app.booting';
            }
        );

        $app->boot();

        $this->assertSame(['app.booting', 'provider.boot'], $order);
    }

    public function testDeferredCoreServicesAreAvailableOnDemand(): void
    {
        $app = $this->app();

        $this->assertTrue($app->has('events'));
        $this->assertTrue($app->has('listeners'));
        $this->assertTrue($app->has('logger'));
        $this->assertTrue($app->has('hooks'));
        $this->assertTrue($app->has('extensions'));
        $this->assertTrue($app->has('router'));
        $this->assertSame($app->getContainer(), $app->make(ContainerInterface::class));
        $this->assertSame($app->getContainer(), $app->make('container'));
        $this->assertInstanceOf(LoggerInterface::class, $app->make(LoggerInterface::class));
    }

    public function testBindingMadeBeforeFirstUseOverridesTheCoreProvider(): void
    {
        $app = $this->app();
        $logger = new NullLogger();

        // Nada resolveu o logger ainda: o provider de logging só carrega aqui, e não pode
        // sobrepor o binding da aplicação quando 'logger' for consultado depois.
        $app->instance(LoggerInterface::class, $logger);

        $this->assertTrue($app->has('logger'));
        $this->assertSame($logger, $app->make('logger'));
        $this->assertSame($logger, $app->getLogger());
    }

    public function testDeferredProviderListMirrorsWhatEachProviderProvides(): void
    {
        $app = $this->app();
        $deferred = (new \ReflectionClassConstant(Application::class, 'DEFERRED_PROVIDERS'))->getValue();

        $this->assertIsArray($deferred);
        $this->assertNotEmpty($deferred);

        foreach ($deferred as $class => $ids) {
            $provider = new $class($app);
            $this->assertInstanceOf(ServiceProvider::class, $provider);
            $this->assertEqualsCanonicalizing($provider->provides(), $ids, $class);
        }
    }

    public function testEagerHandlersRejectAnInvalidClassAtRegistration(): void
    {
        $app = new Application(__DIR__ . '/../..');

        $this->expectException(\InvalidArgumentException::class);

        $app->get('/x', ['PivotPHP\\Core\\Tests\\Missing\\Controller', 'show']);
    }

    public function testLazyHandlersLoadTheControllerOnlyWhenItsRouteIsDispatched(): void
    {
        $app = new Application(__DIR__ . '/../..');
        $app->lazyHandlers();

        $app->get('/lazy', [LazyLoadedController::class, 'show']);
        $app->get('/lazy/static', [LazyLoadedController::class, 'ping']);
        $app->get('/other', fn ($req, $res) => $res->json(['handler' => 'closure']));

        $app->handle(new ServerRequest('GET', '/other'));
        $this->assertFalse(class_exists(LazyLoadedController::class, false));

        $instance = $app->handle(new ServerRequest('GET', '/lazy'));
        $this->assertTrue(class_exists(LazyLoadedController::class, false));
        $this->assertSame('{"handler":"instance"}', (string) $instance->getBody());

        $static = $app->handle(new ServerRequest('GET', '/lazy/static'));
        $this->assertSame('{"handler":"static"}', (string) $static->getBody());
    }

    public function testLazyHandlersResolveTheInstanceFromTheContainer(): void
    {
        $app = new Application(__DIR__ . '/../..');
        $app->lazyHandlers();

        $controller = new LazyLoadedController();
        $resolved = 0;
        $app->singleton(
            LazyLoadedController::class,
            function () use ($controller, &$resolved): LazyLoadedController {
                $resolved++;

                return $controller;
            }
        );
        $app->get('/lazy', [LazyLoadedController::class, 'show']);

        $this->assertSame(0, $resolved);

        $app->handle(new ServerRequest('GET', '/lazy'));

        $this->assertSame(1, $resolved);
    }

    public function testLazyHandlersReportAnInvalidHandlerWhenTheRouteIsDispatched(): void
    {
        $app = new Application(__DIR__ . '/../..');
        $app->lazyHandlers();

        $app->get('/missing-class', ['PivotPHP\\Core\\Tests\\Missing\\Controller', 'show']);
        $app->get('/missing-method', [LazyLoadedController::class, 'nope']);
        $app->get('/ok', fn ($req, $res) => $res->json([]));

        $this->assertSame(200, $app->handle(new ServerRequest('GET', '/ok'))->getStatusCode());
        $this->assertSame(500, $app->handle(new ServerRequest('GET', '/missing-class'))->getStatusCode());
        $this->assertSame(500, $app->handle(new ServerRequest('GET', '/missing-method'))->getStatusCode());
    }

    public function testRequestWithoutContentTypeIsHandledWithoutBodyParsing(): void
    {
        $app = new Application(__DIR__ . '/../..');
        $app->get('/', fn ($req, $res) => $res->json(['body' => $req->json()]));
        $app->post('/', fn ($req, $res) => $res->json(['body' => $req->json()]));

        $get = $app->handle(new ServerRequest('GET', '/'));
        $post = $app->handle(
            new ServerRequest('POST', '/', ['Content-Type' => 'application/json'], '{"a":1}')
        );
        $malformed = $app->handle(
            new ServerRequest('POST', '/', ['Content-Type' => 'application/json'], '{"a":')
        );

        $this->assertSame(200, $get->getStatusCode());
        $this->assertSame('{"body":{"a":1}}', (string) $post->getBody());
        $this->assertSame(400, $malformed->getStatusCode());
    }
}
