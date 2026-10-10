<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Routing;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Response;

/**
 * Teste de regressão para SPEC-076:
 * Duas instâncias de Application no mesmo processo não compartilham rotas,
 * prefixos de grupo nem middlewares.
 */
class ApplicationInstanceStateIsolationTest extends TestCase
{
    public function testTwoApplicationsInSameProcessDoNotLeakRoutes(): void
    {
        $admin = new Application(__DIR__ . '/../../..');
        $public = new Application(__DIR__ . '/../../..');

        $admin->get(
            '/admin/apagar-tudo',
            function ($req, $res) {
                return $res->json(['apagado' => true]);
            }
        );

        $public->get(
            '/',
            function ($req, $res) {
                return $res->json(['home' => true]);
            }
        );

        // 1. $public não deve responder à rota registrada exclusivamente em $admin (deve ser 404)
        $publicAdminReq = new ServerRequest('GET', '/admin/apagar-tudo');
        $publicAdminRes = $public->handle($publicAdminReq);
        $this->assertSame(404, $publicAdminRes->getStatusCode(), 'Aplicação pública não pode expor rotas do admin');

        // 2. $admin deve responder 200 na sua própria rota
        $adminReq = new ServerRequest('GET', '/admin/apagar-tudo');
        $adminRes = $admin->handle($adminReq);
        $this->assertSame(200, $adminRes->getStatusCode());
        $body = json_decode((string) $adminRes->getBody(), true);
        $this->assertTrue($body['apagado']);

        // 3. $public deve responder 200 na home
        $publicHomeReq = new ServerRequest('GET', '/');
        $publicHomeRes = $public->handle($publicHomeReq);
        $this->assertSame(200, $publicHomeRes->getStatusCode());

        // 4. $admin não deve responder à rota home da pública (deve ser 404)
        $adminHomeReq = new ServerRequest('GET', '/');
        $adminHomeRes = $admin->handle($adminHomeReq);
        $this->assertSame(404, $adminHomeRes->getStatusCode(), 'Aplicação admin não pode expor rotas da pública');
    }

    public function testGroupPrefixesAndMiddlewaresDoNotLeakBetweenApplications(): void
    {
        $app1 = new Application(__DIR__ . '/../../..');
        $app2 = new Application(__DIR__ . '/../../..');

        $authBlocker = function ($req, $res, $next) {
            return $res->status(401)->json(['error' => 'blocked_by_app1']);
        };

        $app1->group(
            '/api/v1',
            function () use ($app1) {
                $app1->get(
                    '/secure',
                    function ($req, $res) {
                        return $res->json(['ok' => true]);
                    }
                );
            },
            [$authBlocker]
        );

        // app2 define rota /api/v1/secure sem middleware bloqueador
        $app2->group(
            '/api/v1',
            function () use ($app2) {
                $app2->get(
                    '/secure',
                    function ($req, $res) {
                        return $res->json(['ok' => true, 'app' => 2]);
                    }
                );
            }
        );

        $req = new ServerRequest('GET', '/api/v1/secure');

        // app1 deve ser bloqueada pelo seu middleware
        $res1 = $app1->handle($req);
        $this->assertSame(401, $res1->getStatusCode());

        // app2 não deve herdar o middleware de grupo da app1
        $res2 = $app2->handle($req);
        $this->assertSame(200, $res2->getStatusCode());
        $body2 = json_decode((string) $res2->getBody(), true);
        $this->assertSame(2, $body2['app']);
    }

    public function testSameStaticDirectoryIsServedByEachApplication(): void
    {
        $dir = sys_get_temp_dir() . '/pivot-core-spec076-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/app.txt', 'x');

        try {
            $first = new Application(__DIR__ . '/../../..');
            $second = new Application(__DIR__ . '/../../..');

            $first->staticFiles('/assets', $dir);
            $second->staticFiles('/assets', $dir);

            $this->assertSame(200, $first->handle(new ServerRequest('GET', '/assets/app.txt'))->getStatusCode());
            $this->assertSame(200, $second->handle(new ServerRequest('GET', '/assets/app.txt'))->getStatusCode());
        } finally {
            unlink($dir . '/app.txt');
            rmdir($dir);
        }
    }
}
