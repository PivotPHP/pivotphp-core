<?php

declare(strict_types=1);

namespace PivotPHP\Core\Authentication;

use Exception;
use InvalidArgumentException;

/**
 * Helper para trabalhar com JWT (JSON Web Tokens) de forma simples
 */
class JWTHelper
{
    /**
     * Gera um token JWT
     *
     * @param  array<string, mixed> $payload Dados do payload
     * @param  string               $secret  Chave secreta
     * @param  array<string, mixed> $options Opções:
     *                                       - algorithm: string (algoritmo, default: 'HS256')
     *                                       - expiresIn: int (segundos para expiração, default: 3600)
     *                                       - issuer: string (emissor do token)
     *                                       - audience: string (audiência do token)
     * @return string Token JWT
     */
    public static function encode(
        array $payload,
        string $secret,
        array $options = []
    ): string {
        $options = array_merge(
            [
                'algorithm' => 'HS256',
                'expiresIn' => 3600, // 1 hora
                'issuer' => null,
                'audience' => null
            ],
            $options
        );

        // Adiciona claims padrão
        $now = time();
        $payload['iat'] = $now; // issued at
        $payload['exp'] = $now + $options['expiresIn']; // expiration time

        if ($options['issuer']) {
            $payload['iss'] = $options['issuer'];
        }

        if ($options['audience']) {
            $payload['aud'] = $options['audience'];
        }

        // Verifica se a biblioteca JWT está disponível
        if (class_exists('Firebase\JWT\JWT')) {
            return \Firebase\JWT\JWT::encode($payload, $secret, $options['algorithm']);
        }

        // Implementação simples para HS256 se a biblioteca não estiver disponível
        if ($options['algorithm'] === 'HS256') {
            return self::encodeHS256($payload, $secret);
        }

        throw new Exception('JWT library not found and algorithm not supported. Install firebase/php-jwt');
    }

    /**
     * Decodifica um token JWT
     *
     * @param  string               $token   Token JWT
     * @param  string               $secret  Chave secreta
     * @param  array<string, mixed> $options Opções:
     *                                       - algorithm: string (algoritmo esperado, default: 'HS256')
     *                                       - leeway: int (margem de tempo em segundos, default: 0)
     * @return array<string, mixed> Payload decodificado
     * @throws Exception Se o token for inválido
     */
    public static function decode(
        string $token,
        string $secret,
        array $options = []
    ): array {
        $options = array_merge(
            [
                'algorithm' => 'HS256',
                'leeway' => 0
            ],
            $options
        );

        // Verifica se a biblioteca JWT está disponível
        if (class_exists('Firebase\JWT\JWT') && class_exists('Firebase\JWT\Key')) {
            \Firebase\JWT\JWT::$leeway = $options['leeway'];
            $decoded = \Firebase\JWT\JWT::decode(
                $token,
                new \Firebase\JWT\Key($secret, $options['algorithm'])
            );
            return (array) $decoded;
        }

        // Implementação simples para HS256 se a biblioteca não estiver disponível
        if ($options['algorithm'] === 'HS256') {
            $leeway = isset($options['leeway']) && is_int($options['leeway']) ? $options['leeway'] : 0;
            return self::decodeHS256($token, $secret, $leeway);
        }

        throw new Exception('JWT library not found and algorithm not supported. Install firebase/php-jwt');
    }

    /**
     * Verifica se um token está válido (não expirado)
     *
     * @param  string               $token   Token JWT
     * @param  string               $secret  Chave secreta
     * @param  array<string, mixed> $options Opções de decodificação
     * @return bool True se válido
     */
    public static function isValid(
        string $token,
        string $secret,
        array $options = []
    ): bool {
        try {
            self::decode($token, $secret, $options);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtém o payload sem validar a assinatura (útil para debug)
     *
     * @param  string $token Token JWT
     * @return array<string, mixed> Payload
     */
    public static function getPayload(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format');
        }

        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
        $decoded = json_decode($payload, true);

        if (!is_array($decoded)) {
            throw new Exception('Invalid JWT payload');
        }

        return $decoded;
    }

    /**
     * Verifica se um token está expirado
     *
     * @param  string $token  Token JWT
     * @param  int    $leeway Margem de tempo em segundos
     * @return bool True se expirado
     */
    public static function isExpired(string $token, int $leeway = 0): bool
    {
        try {
            $payload = self::getPayload($token);
            if (!isset($payload['exp'])) {
                return false; // Token sem expiração
            }

            return time() > ($payload['exp'] + $leeway);
        } catch (Exception $e) {
            return true; // Se não conseguiu decodificar, considera expirado
        }
    }

    /**
     * Implementação simples do HS256 para casos sem biblioteca
     *
     * @param array<string, mixed> $payload
     */
    private static function encodeHS256(array $payload, string $secret): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        $headerJson = json_encode($header);
        $payloadJson = json_encode($payload);

        if ($headerJson === false || $payloadJson === false) {
            throw new InvalidArgumentException('Failed to encode JWT header or payload');
        }

        $headerEncoded = self::base64UrlEncode($headerJson);
        $payloadEncoded = self::base64UrlEncode($payloadJson);

        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Implementação simples de decodificação HS256
     *
     * @return array<string, mixed>
     */
    private static function decodeHS256(
        string $token,
        string $secret,
        int $leeway = 0
    ): array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format');
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        // Verifica a assinatura
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);

        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Invalid JWT signature');
        }

        // Decodifica o payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!is_array($payload)) {
            throw new Exception('Invalid JWT payload');
        }

        // Verifica expiração
        if (isset($payload['exp']) && is_int($payload['exp']) && time() > ($payload['exp'] + $leeway)) {
            throw new Exception('JWT token expired');
        }

        // Verifica se já é válido (nbf - not before)
        if (isset($payload['nbf']) && is_int($payload['nbf']) && time() < ($payload['nbf'] - $leeway)) {
            throw new Exception('JWT token not yet valid');
        }

        return $payload;
    }

    /**
     * Codifica em Base64 URL-safe
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodifica de Base64 URL-safe
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * Gera uma chave secreta aleatória para JWT
     *
     * @param  int $length Tamanho da chave em bytes (default: 32)
     * @return string Chave secreta
     */
    public static function generateSecret(int $length = 32): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Length must be at least 1');
        }
        return bin2hex(random_bytes($length));
    }

    /**
     * Cria um token de refresh
     *
     * @param  mixed  $userId    ID do
     *                           usuário
     * @param  string $secret    Chave secreta
     * @param  int    $expiresIn Tempo de expiração em segundos (default: 30
     *                           dias)
     * @return string Token de refresh
     */
    public static function createRefreshToken(
        $userId,
        string $secret,
        int $expiresIn = 2592000
    ): string {
        return self::encode(
            [
                'user_id' => $userId,
                'type' => 'refresh'
            ],
            $secret,
            [
                'expiresIn' => $expiresIn
            ]
        );
    }

    /**
     * Valida um token de refresh
     *
     * @param  string $token  Token de refresh
     * @param  string $secret Chave secreta
     * @return array<string, mixed>|false Dados do usuário ou false se inválido
     */
    public static function validateRefreshToken(string $token, string $secret)
    {
        try {
            $payload = self::decode($token, $secret);

            if (isset($payload['type']) && $payload['type'] === 'refresh') {
                return $payload;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Cria um token de acesso padrão
     *
     * @param  mixed                $userId    ID do
     *                                         usuário
     * @param  array<string, mixed> $claims    Claims adicionais
     * @param  string               $secret    Chave secreta
     * @param  int                  $expiresIn Tempo de expiração em segundos (default: 1
     *                                         hora)
     * @return string Token de acesso
     */
    public static function createAccessToken(
        $userId,
        array $claims = [],
        string $secret = '',
        int $expiresIn = 3600
    ): string {
        $payload = array_merge(
            $claims,
            [
                'user_id' => $userId,
                'type' => 'access'
            ]
        );

        return self::encode(
            $payload,
            $secret,
            [
                'expiresIn' => $expiresIn
            ]
        );
    }

    /**
     * Valida um token de acesso
     *
     * @param  string $token  Token de acesso
     * @param  string $secret Chave secreta
     * @return array<string, mixed>|false Dados do usuário ou false se inválido
     */
    public static function validateAccessToken(string $token, string $secret)
    {
        try {
            $payload = self::decode($token, $secret);

            if (isset($payload['type']) && $payload['type'] === 'access') {
                return $payload;
            }

            return $payload; // Para compatibilidade com tokens sem type
        } catch (Exception $e) {
            return false;
        }
    }
}
