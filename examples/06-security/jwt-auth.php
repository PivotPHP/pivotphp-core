<?php

/**
 * PivotPHP — JWT authentication with pivotphp/security
 *
 * Requires firebase/php-jwt (composer require firebase/php-jwt).
 *
 * Run:   JWT_SECRET=$(php -r 'echo bin2hex(random_bytes(32));') \
 *        php -S localhost:8000 examples/06-security/jwt-auth.php
 * Try:   TOKEN=$(curl -s -X POST http://localhost:8000/login -H 'Content-Type: application/json' \
 *             -d '{"username":"alice","password":"secret"}' | php -r 'echo json_decode(stream_get_contents(STDIN))->token;')
 *        curl http://localhost:8000/api/profile -H "Authorization: Bearer $TOKEN"
 *        curl -i http://localhost:8000/api/profile
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Http\Factory\Psr17Factory;
use PivotPHP\Security\Headers\SecurityHeadersMiddleware;
use PivotPHP\Security\Jwt\JwtAuthMiddleware;
use PivotPHP\Security\Jwt\JwtConfig;
use PivotPHP\Security\Jwt\JwtIssuer;

// HS256 needs a secret of at least 32 bytes; never hard-code it in production.
$secret = getenv('JWT_SECRET') ?: 'example-only-secret-with-32-bytes!';

$jwt = new JwtConfig($secret, issuer: 'pivotphp-example', publicPaths: ['/login']);
$issuer = new JwtIssuer($jwt);

// Demo user store: passwords are hashed with password_hash()
$users = ['alice' => ['id' => 1, 'password' => password_hash('secret', PASSWORD_DEFAULT), 'role' => 'admin']];

$app = new Application();
$app->use(new SecurityHeadersMiddleware());
$app->use(new JwtAuthMiddleware(new Psr17Factory(), $jwt));

$app->post('/login', function ($req, $res) use ($users, $issuer) {
    $username = $req->input('username');
    $password = $req->input('password');
    $user = is_string($username) ? ($users[$username] ?? null) : null;

    if ($user === null || !is_string($password) || !password_verify($password, $user['password'])) {
        return $res->error(401, 'Invalid credentials');
    }

    return $res->json([
        'token' => $issuer->issue(['sub' => (string) $user['id'], 'role' => $user['role']], ttl: 3600),
        'expires_in' => 3600,
    ]);
});

// The middleware stores the verified claims in the "user" attribute
$app->get('/api/profile', fn ($req, $res) => $res->json(['claims' => $req->psr7()->getAttribute('user')]));

$app->run();
