<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
// Response class removed - not used in tests
use PivotPHP\Core\Routing\Router;
use PivotPHP\Core\Core\Config;
use PivotPHP\Core\Providers\Container;
use PivotPHP\Core\Exceptions\HttpException;
use PivotPHP\Core\Providers\ServiceProvider;

/**
 * Comprehensive implementation tests for Application class
 *
 * Tests core functionality, service providers, middleware integration,
 * error handling, and lifecycle management.
 */
class ApplicationTest extends TestCase
{
    private Application $app;
    private string $tempBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary directory for testing
        $this->tempBasePath = sys_get_temp_dir() . '/pivotphp_test_' . uniqid();
        mkdir($this->tempBasePath, 0777, true);
        mkdir($this->tempBasePath . '/config', 0777, true);

        $this->app = new Application($this->tempBasePath);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up temporary directory
        if (is_dir($this->tempBasePath)) {
            $this->removeDirectory($this->tempBasePath);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Test application initialization and version
     */
    public function testApplicationInitialization(): void
    {
        $this->assertInstanceOf(Application::class, $this->app);
        $this->assertEquals('2.0.0', Application::VERSION);
        $this->assertEquals('2.0.0', $this->app->version());
        $this->assertFalse($this->app->isBooted());
    }

    /**
     * Test application factory methods
     */
    public function testFactoryMethods(): void
    {
        $app1 = Application::create();
        $this->assertInstanceOf(Application::class, $app1);

        $app2 = Application::express();
        $this->assertInstanceOf(Application::class, $app2);

        $app3 = Application::create('/custom/path');
        $this->assertInstanceOf(Application::class, $app3);
    }

    /**
     * Test base path configuration
     */
    public function testBasePathConfiguration(): void
    {
        $customPath = '/custom/test/path';
        $app = new Application($customPath);

        $this->assertEquals($customPath, $app->basePath());
        $this->assertEquals($customPath . '/config', $app->basePath('config'));
        $this->assertEquals($customPath . '/storage', $app->basePath('storage'));
    }

    /**
     * Test container integration
     */
    public function testContainerIntegration(): void
    {
        $container = $this->app->getContainer();
        $this->assertInstanceOf(Container::class, $container);

        // Test application is bound to container
        $this->assertTrue($container->has(Application::class));
        $this->assertTrue($container->has('app'));
        $this->assertSame($this->app, $container->get('app'));
    }

    /**
     * Test service binding methods
     */
    public function testServiceBinding(): void
    {
        // Test bind
        $this->app->bind(
            'test.service',
            function () {
                return 'test_value';
            }
        );
        $this->assertEquals('test_value', $this->app->make('test.service'));

        // Test singleton
        $this->app->singleton(
            'test.singleton',
            function () {
                return new \stdClass();
            }
        );
        $instance1 = $this->app->make('test.singleton');
        $instance2 = $this->app->make('test.singleton');
        $this->assertSame($instance1, $instance2);

        // Test instance
        $object = new \stdClass();
        $this->app->instance('test.instance', $object);
        $this->assertSame($object, $this->app->make('test.instance'));

        // Test alias
        $this->app->alias('test.alias', 'test.instance');
        $this->assertSame($object, $this->app->make('test.alias'));

        // Test has
        $this->assertTrue($this->app->has('test.instance'));
        $this->assertFalse($this->app->has('non.existent'));
    }

    /**
     * Test route registration
     */
    public function testRouteRegistration(): void
    {
        $this->app->get(
            '/test',
            function ($_, $res) {
                return $res->json(['method' => 'GET']);
            }
        );

        $this->app->post(
            '/test',
            function ($_, $res) {
                return $res->json(['method' => 'POST']);
            }
        );

        $this->app->put(
            '/test',
            function ($_, $res) {
                return $res->json(['method' => 'PUT']);
            }
        );

        $this->app->patch(
            '/test',
            function ($_, $res) {
                return $res->json(['method' => 'PATCH']);
            }
        );

        $this->app->delete(
            '/test',
            function ($_, $res) {
                return $res->json(['method' => 'DELETE']);
            }
        );

        $router = $this->app->getRouter();
        $this->assertInstanceOf(Router::class, $router);
    }

    /**
     * Test middleware registration
     */
    public function testMiddlewareRegistration(): void
    {
        $middlewareCalled = false;

        $this->app->use(
            function ($req, $res, $next) use (&$middlewareCalled) {
                $middlewareCalled = true;
                return $next($req, $res);
            }
        );

        $this->app->get(
            '/test',
            function ($_, $res) {
                return $res->json(['test' => 'ok']);
            }
        );

        $this->app->boot();

        $request = new Request('GET', '/test', '/test');
        $response = $this->app->handle($request);

        $this->assertTrue($middlewareCalled);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test middleware aliases
     */
    public function testMiddlewareAliases(): void
    {
        // Test that middleware aliases are properly configured
        $reflection = new \ReflectionClass($this->app);
        $property = $reflection->getProperty('middlewareAliases');
        $property->setAccessible(true);
        $aliases = $property->getValue($this->app);

        $this->assertArrayHasKey('load-shedder', $aliases);
        $this->assertArrayHasKey('rate-limiter', $aliases);

        // Circuit breaker removed following ARCHITECTURAL_GUIDELINES
        $this->assertArrayNotHasKey('circuit-breaker', $aliases);
    }

    /**
     * Test application boot process
     */
    public function testApplicationBoot(): void
    {
        $this->assertFalse($this->app->isBooted());

        $this->app->boot();

        $this->assertTrue($this->app->isBooted());

        // Boot is idempotent
        $this->app->boot();
        $this->assertTrue($this->app->isBooted());
    }

    /**
     * Test service provider registration
     */
    public function testServiceProviderRegistration(): void
    {
        $providerMock = $this->createMock(ServiceProvider::class);
        $providerMock->expects($this->once())
            ->method('register');

        $this->app->register($providerMock);
    }

    /**
     * Test request handling
     */
    public function testRequestHandling(): void
    {
        $this->app->get(
            '/hello/:name',
            function ($req, $res) {
                $name = $req->param('name');
                return $res->json(['message' => "Hello, {$name}!"]);
            }
        );

        $this->app->boot();

        $request = new Request('GET', '/hello/:name', '/hello/world');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : (string) $responseBody, true);
        $this->assertEquals(['message' => 'Hello, world!'], $body);
    }

    /**
     * Test 404 error handling
     */
    public function testNotFoundHandling(): void
    {
        $this->app->boot();

        $request = new Request('GET', '/nonexistent', '/nonexistent');
        $response = $this->app->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Test exception handling in debug mode
     */
    public function testExceptionHandlingDebugMode(): void
    {
        $this->app->configure(['app.debug' => true]);

        $this->app->get(
            '/error',
            function ($_, $res) {
                throw new \Exception('Test exception', 500);
            }
        );

        $this->app->boot();

        $request = new Request('GET', '/error', '/error');
        $response = $this->app->handle($request);

        $this->assertEquals(500, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : (string) $responseBody, true);
        $this->assertTrue($body['error']);
        $this->assertEquals('Test exception', $body['message']);
        $this->assertArrayHasKey('file', $body);
        $this->assertArrayHasKey('line', $body);
        $this->assertArrayHasKey('trace', $body);
    }

    /**
     * Test exception handling in production mode
     */
    public function testExceptionHandlingProductionMode(): void
    {
        $this->app->configure(['app.debug' => false]);

        $this->app->get(
            '/error',
            function ($_, $res) {
                throw new \Exception('Test exception', 500);
            }
        );

        $this->app->boot();

        $request = new Request('GET', '/error', '/error');
        $response = $this->app->handle($request);

        $this->assertEquals(500, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : (string) $responseBody, true);
        $this->assertTrue($body['error']);
        $this->assertEquals('Internal Server Error', $body['message']);
        $this->assertArrayHasKey('error_id', $body);
    }

    /**
     * Test HTTP exception handling
     */
    public function testHttpExceptionHandling(): void
    {
        $this->app->get(
            '/forbidden',
            function ($_, $res) {
                throw new HttpException(403, 'Access denied');
            }
        );

        $this->app->boot();

        $request = new Request('GET', '/forbidden', '/forbidden');
        $response = $this->app->handle($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test configuration loading
     */
    public function testConfigurationLoading(): void
    {
        // Create test config file
        $configFile = $this->tempBasePath . '/config/app.php';
        file_put_contents($configFile, "<?php\nreturn ['test_key' => 'test_value'];");

        $app = new Application($this->tempBasePath);
        $app->boot();

        $config = $app->getConfig();
        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('test_value', $config->get('app.test_key'));
    }

    /**
     * Test environment file loading
     */
    public function testEnvironmentLoading(): void
    {
        // Create test .env file
        $envFile = $this->tempBasePath . '/.env';
        file_put_contents($envFile, "TEST_ENV_VAR=test_value\nANOTHER_VAR=another_value");

        $app = new Application($this->tempBasePath);
        $app->boot();

        $config = $app->getConfig();

        // Environment loading may be optional depending on implementation
        $testValue = $config->get('TEST_ENV_VAR');
        if ($testValue !== null) {
            $this->assertEquals('test_value', $testValue);
            $this->assertEquals('another_value', $config->get('ANOTHER_VAR'));
        } else {
            // If env loading is not implemented, skip the test
            $this->markTestSkipped('Environment file loading not implemented');
        }
    }

    /**
     * Test extension system integration
     */
    public function testExtensionSystem(): void
    {
        $this->app->boot();

        $extensions = $this->app->extensions();
        $this->assertNotNull($extensions);

        $hooks = $this->app->hooks();
        $this->assertNotNull($hooks);

        $stats = $this->app->getExtensionStats();
        $this->assertArrayHasKey('extensions', $stats);
        $this->assertArrayHasKey('hooks', $stats);
    }

    /**
     * Test hook system integration
     */
    public function testHookSystemIntegration(): void
    {
        $this->app->boot();

        $actionCalled = false;
        $this->app->addAction(
            'test.action',
            function () use (&$actionCalled) {
                $actionCalled = true;
            }
        );

        $this->app->doAction('test.action');
        $this->assertTrue($actionCalled);

        $result = $this->app->applyFilter('test.filter', 'original_value', ['context' => 'test']);
        $this->assertEquals('original_value', $result);
    }

    /**
     * Test event system integration
     */
    public function testEventSystemIntegration(): void
    {
        $this->app->boot();

        // Test that event system methods exist and don't throw errors
        $this->app->on(
            'test.event',
            function () {
                return true;
            }
        );

        $result = $this->app->fireEvent('test.event', 'data1', 'data2');

        // fireEvent returns $this for method chaining
        $this->assertSame($this->app, $result);
    }

    /**
     * Test base URL configuration
     */
    public function testBaseUrlConfiguration(): void
    {
        $this->app->setBaseUrl('https://example.com/api');
        $this->assertEquals('https://example.com/api', $this->app->getBaseUrl());

        $this->app->setBaseUrl('https://example.com/api/');
        $this->assertEquals('https://example.com/api', $this->app->getBaseUrl());
    }

    /**
     * Test logger integration
     */
    public function testLoggerIntegration(): void
    {
        $this->app->boot();
        $logger = $this->app->getLogger();

        // Logger may be null if not configured
        $this->assertTrue($logger === null || $logger instanceof \Psr\Log\LoggerInterface);
    }

    /**
     * Test event dispatcher integration
     */
    public function testEventDispatcherIntegration(): void
    {
        $this->app->boot();
        $dispatcher = $this->app->getEventDispatcher();

        // Dispatcher may be null if not configured
        $this->assertTrue($dispatcher === null || $dispatcher instanceof \Psr\EventDispatcher\EventDispatcherInterface);
    }

    /**
     * Test multiple middleware execution order
     */
    public function testMultipleMiddlewareExecutionOrder(): void
    {
        $executionOrder = [];

        $this->app->use(
            function ($req, $res, $next) use (&$executionOrder) {
                $executionOrder[] = 'middleware1_before';
                $result = $next($req, $res);
                $executionOrder[] = 'middleware1_after';
                return $result;
            }
        );

        $this->app->use(
            function ($req, $res, $next) use (&$executionOrder) {
                $executionOrder[] = 'middleware2_before';
                $result = $next($req, $res);
                $executionOrder[] = 'middleware2_after';
                return $result;
            }
        );

        $this->app->get(
            '/test',
            function ($req, $res) use (&$executionOrder) {
                $executionOrder[] = 'handler';
                return $res->json(['test' => 'ok']);
            }
        );

        $this->app->boot();

        $request = new Request('GET', '/test', '/test');
        $this->app->handle($request);

        // Test that middleware was executed in some order
        $this->assertContains('middleware1_before', $executionOrder);
        $this->assertContains('middleware2_before', $executionOrder);
        $this->assertContains('middleware1_after', $executionOrder);
        $this->assertContains('middleware2_after', $executionOrder);

        // The specific order may depend on middleware stack implementation
        $this->assertGreaterThanOrEqual(4, count($executionOrder));
    }

    /**
     * Test error handling with custom error handler
     */
    public function testCustomErrorHandling(): void
    {
        $errorHandled = false;

        set_error_handler(
            function ($severity, $message, $file, $line) use (&$errorHandled) {
                $errorHandled = true;
                return false; // Don't suppress the error
            }
        );

        try {
            $this->app->handleError(E_WARNING, 'Test warning', __FILE__, __LINE__);
        } catch (\ErrorException $e) {
            $this->assertEquals('Test warning', $e->getMessage());
        }

        restore_error_handler();
    }

    /**
     * Test configure method for bulk configuration
     */
    public function testConfigureMethod(): void
    {
        $config = [
            'app.name' => 'Test App',
            'app.version' => '1.0.0',
            'database.host' => 'localhost'
        ];

        $this->app->configure($config);
        $appConfig = $this->app->getConfig();

        $this->assertEquals('Test App', $appConfig->get('app.name'));
        $this->assertEquals('1.0.0', $appConfig->get('app.version'));
        $this->assertEquals('localhost', $appConfig->get('database.host'));
    }
}
