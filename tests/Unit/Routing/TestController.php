<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Unit\Routing;

use PivotPHP\Http\ExpressRequest;
use PivotPHP\Http\ExpressResponse;

/**
 * Test class for array callable functionality
 */
class TestController
{
    public function index(ExpressRequest $req, ExpressResponse $res): string
    {
        return 'controller index';
    }

    public function show(ExpressRequest $req, ExpressResponse $res): string
    {
        $id = $req->param('id');
        return "controller show: {$id}";
    }

    public static function staticMethod(ExpressRequest $req, ExpressResponse $res): string
    {
        return 'static method';
    }

    public function healthCheck(ExpressRequest $req, ExpressResponse $res): array
    {
        return [
            'status' => 'ok',
            'timestamp' => time(),
            'method' => 'healthCheck'
        ];
    }

    public function withParameters(ExpressRequest $req, ExpressResponse $res): array
    {
        return [
            'user_id' => $req->param('userId'),
            'post_id' => $req->param('postId')
        ];
    }
}
