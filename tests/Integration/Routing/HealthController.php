<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Routing;

use PivotPHP\Core\Core\Application;
use PivotPHP\Http\ExpressRequest;
use PivotPHP\Http\ExpressResponse;

/**
 * Test controller for integration tests
 */
class HealthController
{
    public function healthCheck(ExpressRequest $req, ExpressResponse $res)
    {
        return $res->json(
            [
                'status' => 'ok',
                'timestamp' => time(),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'version' => Application::VERSION
            ]
        );
    }

    public function getUserHealth(ExpressRequest $req, ExpressResponse $res)
    {
        $userId = $req->param('userId');
        return $res->json(
            [
                'user_id' => $userId,
                'status' => 'healthy',
                'checked_at' => date('Y-m-d H:i:s')
            ]
        );
    }

    public static function staticHealthCheck(ExpressRequest $req, ExpressResponse $res)
    {
        return $res->json(
            [
                'status' => 'static_ok',
                'method' => 'static'
            ]
        );
    }
}
