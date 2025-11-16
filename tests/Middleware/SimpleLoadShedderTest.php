<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\LoadShedder;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Http\Psr7\Uri;

class SimpleLoadShedderTest extends TestCase
{
    private LoadShedder $loadShedder;

    protected function setUp(): void
    {
        $this->loadShedder = new LoadShedder(5, 60); // 5 requests per 60 seconds
    }

    public function testAllowsRequestsUnderLimit(): void
    {
        $request = new Request('GET', '/test', '/test');
        $response = new Response(200);

        $called = false;
        $next = function ($req, $res) use (&$called) {
            $called = true;
            return $res;
        };

        $result = ($this->loadShedder)($request, $response, $next);

        $this->assertTrue($called);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testCanBeDisabled(): void
    {
        $this->loadShedder->disable();

        $request = new Request('GET', '/test', '/test');
        $response = new Response(200);

        $called = false;
        $next = function ($req, $res) use (&$called) {
            $called = true;
            return $res;
        };

        // Should allow request even if we simulate multiple calls
        for ($i = 0; $i < 10; $i++) {
            $result = ($this->loadShedder)($request, $response, $next);
            $this->assertEquals(200, $result->getStatusCode());
        }

        $this->assertTrue($called);
    }

    public function testCanBeReEnabled(): void
    {
        $this->loadShedder->disable();
        $this->loadShedder->enable();

        $request = new Request('GET', '/test', '/test');
        $response = new Response(200);

        $called = false;
        $next = function ($req, $res) use (&$called) {
            $called = true;
            return $res;
        };

        $result = ($this->loadShedder)($request, $response, $next);

        $this->assertTrue($called);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetStats(): void
    {
        $stats = $this->loadShedder->getStats();

        $this->assertIsArray($stats);
        $this->assertTrue($stats['enabled']);
        $this->assertEquals(5, $stats['max_requests']);
        $this->assertEquals(60, $stats['window_seconds']);
        $this->assertIsInt($stats['active_clients']);
        $this->assertIsInt($stats['total_requests_tracked']);
    }

    public function testStatsAfterDisabling(): void
    {
        $this->loadShedder->disable();
        $stats = $this->loadShedder->getStats();

        $this->assertFalse($stats['enabled']);
    }
}
