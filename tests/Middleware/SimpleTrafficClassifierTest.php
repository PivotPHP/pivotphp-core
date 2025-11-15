<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\SimpleTrafficClassifier;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Http\Psr7\Uri;

class SimpleTrafficClassifierTest extends TestCase
{
    private SimpleTrafficClassifier $classifier;

    protected function setUp(): void
    {
        $this->classifier = new SimpleTrafficClassifier();
    }

    public function testDefaultPriorityIsNormal(): void
    {
        $request = new Request('GET', '/test', '/test');
        $priority = $this->classifier->classify($request);

        $this->assertEquals(SimpleTrafficClassifier::PRIORITY_NORMAL, $priority);
    }

    public function testCustomDefaultPriority(): void
    {
        $classifier = new SimpleTrafficClassifier(
            [
                'default_priority' => SimpleTrafficClassifier::PRIORITY_HIGH
            ]
        );

        $request = new Request('GET', '/test', '/test');
        $priority = $classifier->classify($request);

        $this->assertEquals(SimpleTrafficClassifier::PRIORITY_HIGH, $priority);
    }

    public function testRuleBasedClassification(): void
    {
        $this->classifier->addRule('/api/', SimpleTrafficClassifier::PRIORITY_HIGH);

        $request = new Request('GET', '/api/users', '/api/users');
        $priority = $this->classifier->classify($request);

        $this->assertEquals(SimpleTrafficClassifier::PRIORITY_HIGH, $priority);
    }

    public function testMethodBasedClassification(): void
    {
        $getRequest = new Request('GET', '/test', '/test');
        $postRequest = new Request('POST', '/test', '/test');

        $this->assertEquals(SimpleTrafficClassifier::PRIORITY_NORMAL, $this->classifier->classify($getRequest));
        $this->assertEquals(SimpleTrafficClassifier::PRIORITY_HIGH, $this->classifier->classify($postRequest));
    }

    public function testMiddlewareInvocation(): void
    {
        $request = new Request('GET', '/test', '/test');
        $response = new Response(200);

        $called = false;
        $next = function ($req, $res) use (&$called) {
            $called = true;
            $this->assertInstanceOf(Request::class, $req);
            $this->assertTrue($req->hasAttribute('priority'));
            $this->assertEquals(SimpleTrafficClassifier::PRIORITY_NORMAL, $req->getAttribute('priority'));
            return $res;
        };

        $result = ($this->classifier)($request, $response, $next);

        $this->assertTrue($called);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testGetStats(): void
    {
        $this->classifier->addRule('/api/', SimpleTrafficClassifier::PRIORITY_HIGH);
        $this->classifier->addRule('/admin/', SimpleTrafficClassifier::PRIORITY_HIGH);

        $stats = $this->classifier->getStats();

        $this->assertIsArray($stats);
        $this->assertEquals(2, $stats['rules_count']);
        $this->assertEquals(SimpleTrafficClassifier::PRIORITY_NORMAL, $stats['default_priority']);
        $this->assertContains(SimpleTrafficClassifier::PRIORITY_HIGH, $stats['available_priorities']);
        $this->assertContains(SimpleTrafficClassifier::PRIORITY_NORMAL, $stats['available_priorities']);
        $this->assertContains(SimpleTrafficClassifier::PRIORITY_LOW, $stats['available_priorities']);
    }
}
