<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\RateLimiter;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Response;

/**
 * Comprehensive tests for RateLimiter middleware
 *
 * Tests all 4 rate limiting strategies, security features, metrics,
 * and edge cases following the "less is more" principle.
 */
class RateLimiterTest extends TestCase
{
    private RateLimiter $rateLimiter;
    private Request $request;
    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');
        $this->response = new Response();
    }

    /**
     * Test default configuration
     */
    public function testDefaultConfiguration(): void
    {
        $rateLimiter = new RateLimiter();

        $this->assertInstanceOf(RateLimiter::class, $rateLimiter);

        // Test with a request that should be allowed
        $response = $rateLimiter->handle(
            $this->request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('X-RateLimit-Limit', $response->getHeaders());
        $this->assertArrayHasKey('X-RateLimit-Remaining', $response->getHeaders());
        $this->assertArrayHasKey('X-RateLimit-Reset', $response->getHeaders());
    }

    /**
     * Test fixed window strategy
     */
    public function testFixedWindowStrategy(): void
    {
        $rateLimiter = new RateLimiter(
            [
                'strategy' => RateLimiter::STRATEGY_FIXED_WINDOW,
                'max_requests' => 5,
                'window_size' => 60,
            ]
        );

        // Should allow first 5 requests
        for ($i = 0; $i < 5; $i++) {
            $response = $rateLimiter->handle(
                $this->request,
                $this->response,
                function ($req, $res) {
                    return $res->json(['status' => 'ok']);
                }
            );
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 6th request should be rejected
        $response = $rateLimiter->handle(
            $this->request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertArrayHasKey('Retry-After', $response->getHeaders());
    }

    /**
     * Test sliding window strategy
     */
    public function testSlidingWindowStrategy(): void
    {
        $rateLimiter = new RateLimiter(
            [
                'strategy' => RateLimiter::STRATEGY_SLIDING_WINDOW,
                'max_requests' => 3,
                'window_size' => 60,
            ]
        );

        // Should allow first 3 requests
        for ($i = 0; $i < 3; $i++) {
            $response = $rateLimiter->handle(
                $this->request,
                $this->response,
                function ($req, $res) {
                    return $res->json(['status' => 'ok']);
                }
            );
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 4th request should be rejected
        $response = $rateLimiter->handle(
            $this->request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );
        $this->assertEquals(429, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertEquals('Too Many Requests', $body['error']);
    }

    /**
     * Test token bucket strategy
     */
    public function testTokenBucketStrategy(): void
    {
        $rateLimiter = new RateLimiter(
            [
                'strategy' => RateLimiter::STRATEGY_TOKEN_BUCKET,
                'max_requests' => 3,
                'burst_size' => 2,
                'window_size' => 60,
            ]
        );

        // Should allow initial regular requests (3 tokens)
        for ($i = 0; $i < 3; $i++) {
            $response = $rateLimiter->handle(
                $this->request,
                new Response(),
                function ($req, $res) {
                    return $res->json(['status' => 'ok']);
                }
            );
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 4th request should be rejected (no more tokens)
        $response = $rateLimiter->handle(
            $this->request,
            new Response(),
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * Test leaky bucket strategy
     */
    public function testLeakyBucketStrategy(): void
    {
        $rateLimiter = new RateLimiter(
            [
                'strategy' => RateLimiter::STRATEGY_LEAKY_BUCKET,
                'max_requests' => 3,
                'window_size' => 60,
            ]
        );

        // The leaky bucket implementation allows requests as long as there's capacity
        // Since it's time-based with very small time differences, it usually allows more
        $allowedCount = 0;
        $rejectedCount = 0;

        // Try 10 requests rapidly
        for ($i = 0; $i < 10; $i++) {
            $response = $rateLimiter->handle(
                $this->request,
                new Response(),
                function ($req, $res) {
                    return $res->json(['status' => 'ok']);
                }
            );
            if ($response->getStatusCode() === 200) {
                $allowedCount++;
            } else {
                $rejectedCount++;
            }
        }

        // Should allow some requests but eventually reject
        $this->assertGreaterThan(0, $allowedCount);
        $this->assertGreaterThan(0, $rejectedCount);
    }

    /**
     * Test whitelist functionality
     */
    public function testWhitelistFunctionality(): void
    {
        // Set up whitelisted IP in $_SERVER
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $rateLimiter = new RateLimiter(
            [
                'max_requests' => 1,
                'window_size' => 60,
                'whitelist' => ['127.0.0.1'],
            ]
        );

        // Create request with whitelisted IP
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        // Should allow unlimited requests from whitelisted IP
        for ($i = 0; $i < 5; $i++) {
            $response = $rateLimiter->handle(
                $request,
                $this->response,
                function ($req, $res) {
                    return $res->json(['status' => 'ok']);
                }
            );
            $this->assertEquals(200, $response->getStatusCode());
        }

        // Whitelisted requests don't get rate limit headers (they bypass completely)
        $this->assertArrayNotHasKey('X-RateLimit-Limit', $response->getHeaders());

        // Cleanup
        unset($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Test blacklist functionality
     */
    public function testBlacklistFunctionality(): void
    {
        // Set up blacklisted IP in $_SERVER
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';

        $rateLimiter = new RateLimiter(
            [
                'max_requests' => 100,
                'window_size' => 60,
                'blacklist' => ['192.168.1.100'],
            ]
        );

        // Create request with blacklisted IP
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        // Should immediately reject blacklisted IP
        $response = $rateLimiter->handle(
            $request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertArrayHasKey('X-RateLimit-Reason', $response->getHeaders());
        $this->assertEquals('blacklisted', $response->getHeaders()['X-RateLimit-Reason']);

        // Cleanup
        unset($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Test custom key generator
     */
    public function testCustomKeyGenerator(): void
    {
        $rateLimiter = new RateLimiter(
            [
                'max_requests' => 2,
                'window_size' => 60,
                'key_generator' => function ($request) {
                    return $request->header('X-API-Key') ?: 'anonymous';
                },
            ]
        );

        // Create request with API key (mock the header by adding to $_SERVER)
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');
        $_SERVER['HTTP_X_API_KEY'] = 'test-key-123';

        // Should allow requests based on API key
        for ($i = 0; $i < 2; $i++) {
            $response = $rateLimiter->handle(
                $request,
                $this->response,
                function ($req, $res) {
                    return $res->json(['status' => 'ok']);
                }
            );
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 3rd request should be rejected
        $response = $rateLimiter->handle(
            $request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );
        $this->assertEquals(429, $response->getStatusCode());

        // Cleanup
        unset($_SERVER['HTTP_X_API_KEY']);
    }

    /**
     * Test custom reject response
     */
    public function testCustomRejectResponse(): void
    {
        $rateLimiter = new RateLimiter(
            [
                'max_requests' => 1,
                'window_size' => 60,
                'reject_response' => [
                    'status' => 503,
                    'body' => ['message' => 'Service Unavailable'],
                    'headers' => ['Retry-After' => '120'],
                ],
            ]
        );

        // First request should be allowed
        $response = $rateLimiter->handle(
            $this->request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );
        $this->assertEquals(200, $response->getStatusCode());

        // Second request should be rejected with custom response
        $response = $rateLimiter->handle(
            $this->request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals('120', $response->getHeaders()['Retry-After']);

        $body = json_decode($response->getBody(), true);
        $this->assertEquals('Service Unavailable', $body['message']);
    }

    /**
     * Test metrics collection
     */
    public function testMetricsCollection(): void
    {
        $rateLimiter = new RateLimiter(
            [
                'max_requests' => 2,
                'window_size' => 60,
            ]
        );

        // Make allowed requests
        for ($i = 0; $i < 2; $i++) {
            $rateLimiter->handle(
                $this->request,
                $this->response,
                function ($req, $res) {
                    return $res->json(['status' => 'ok']);
                }
            );
        }

        // Make rejected request
        $rateLimiter->handle(
            $this->request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );

        $metrics = $rateLimiter->getMetrics();

        $this->assertArrayHasKey('total_requests', $metrics);
        $this->assertArrayHasKey('allowed_requests', $metrics);
        $this->assertArrayHasKey('rejected_requests', $metrics);
        $this->assertArrayHasKey('allow_rate', $metrics);
        $this->assertArrayHasKey('reject_rate', $metrics);

        $this->assertEquals(3, $metrics['total_requests']);
        $this->assertEquals(2, $metrics['allowed_requests']);
        $this->assertEquals(1, $metrics['rejected_requests']);
        $this->assertEquals(66.67, $metrics['allow_rate']);
        $this->assertEquals(33.33, $metrics['reject_rate']);
    }

    /**
     * Test reset functionality
     */
    public function testResetFunctionality(): void
    {
        // Set a known IP for testing
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        $rateLimiter = new RateLimiter(
            [
                'strategy' => RateLimiter::STRATEGY_SLIDING_WINDOW,
                'max_requests' => 1,
                'window_size' => 60,
            ]
        );

        // Make request that should be allowed
        $response = $rateLimiter->handle(
            $this->request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );
        $this->assertEquals(200, $response->getStatusCode());

        // Next request should be rejected
        $response = $rateLimiter->handle(
            $this->request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );
        $this->assertEquals(429, $response->getStatusCode());

        // Reset the rate limiter for the IP key
        $rateLimiter->reset('192.168.1.1');

        // Request should be allowed again after reset
        $response = $rateLimiter->handle(
            $this->request,
            new Response(),
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );
        $this->assertEquals(200, $response->getStatusCode());

        // Cleanup
        unset($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Test invalid strategy handling
     */
    public function testInvalidStrategyHandling(): void
    {
        // Invalid strategy should fallback to default behavior (return true)
        $rateLimiter = new RateLimiter(
            [
                'strategy' => 'invalid_strategy',
            ]
        );

        // Should allow request due to default case in match
        $response = $rateLimiter->handle(
            $this->request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test invalid key generator handling
     */
    public function testInvalidKeyGeneratorHandling(): void
    {
        $rateLimiter = new RateLimiter(
            [
                'key_generator' => 'not_a_callable',
            ]
        );

        // Should throw an error when trying to call invalid generator
        $this->expectException(\Error::class);

        $rateLimiter->handle(
            $this->request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );
    }

    /**
     * Test configuration validation
     */
    public function testConfigurationValidation(): void
    {
        // Test with zero max_requests - should reject immediately
        $rateLimiter = new RateLimiter(
            [
                'max_requests' => 0,
            ]
        );

        $response = $rateLimiter->handle(
            $this->request,
            new Response(),
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );
        $this->assertEquals(429, $response->getStatusCode());

        // Test with negative window_size - should still work
        $rateLimiter = new RateLimiter(
            [
                'window_size' => -1,
            ]
        );

        $response = $rateLimiter->handle(
            $this->request,
            new Response(),
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );
        $this->assertEquals(200, $response->getStatusCode()); // Should still work with fallback
    }

    /**
     * Test HTTP headers accuracy
     */
    public function testHttpHeadersAccuracy(): void
    {
        $rateLimiter = new RateLimiter(
            [
                'max_requests' => 5,
                'window_size' => 60,
            ]
        );

        // First request
        $response = $rateLimiter->handle(
            $this->request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );

        $headers = $response->getHeaders();
        $this->assertEquals('5', $headers['X-RateLimit-Limit']);
        $this->assertEquals('4', $headers['X-RateLimit-Remaining']);
        $this->assertIsNumeric($headers['X-RateLimit-Reset']);

        // Second request
        $response = $rateLimiter->handle(
            $this->request,
            $this->response,
            function ($req, $res) {
                return $res->json(['status' => 'ok']);
            }
        );

        $headers = $response->getHeaders();
        $this->assertEquals('5', $headers['X-RateLimit-Limit']);
        $this->assertEquals('3', $headers['X-RateLimit-Remaining']);
    }

    /**
     * Test performance under load
     */
    public function testPerformanceUnderLoad(): void
    {
        $rateLimiter = new RateLimiter(
            [
                'max_requests' => 50,
                'window_size' => 60,
            ]
        );

        $startTime = microtime(true);

        // Simulate 100 requests quickly
        for ($i = 0; $i < 100; $i++) {
            $rateLimiter->handle(
                $this->request,
                $this->response,
                function ($req, $res) {
                    return $res->json(['status' => 'ok']);
                }
            );
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete within reasonable time (less than 1 second)
        $this->assertLessThan(1.0, $duration);

        $metrics = $rateLimiter->getMetrics();
        $this->assertEquals(100, $metrics['total_requests']);
        $this->assertEquals(50, $metrics['allowed_requests']);
        $this->assertEquals(50, $metrics['rejected_requests']);
    }

    /**
     * Test memory usage efficiency
     */
    public function testMemoryUsageEfficiency(): void
    {
        $rateLimiter = new RateLimiter(
            [
                'strategy' => RateLimiter::STRATEGY_SLIDING_WINDOW,
                'max_requests' => 10,
                'window_size' => 60,
            ]
        );

        $initialMemory = memory_get_usage();

        // Make many requests to fill up sliding window
        for ($i = 0; $i < 50; $i++) {
            $rateLimiter->handle(
                $this->request,
                $this->response,
                function ($req, $res) {
                    return $res->json(['status' => 'ok']);
                }
            );
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // Memory increase should be reasonable (less than 1MB for 50 requests)
        $this->assertLessThan(1024 * 1024, $memoryIncrease);
    }
}
