<?php

declare(strict_types=1);

namespace PivotPHP\Core\Support;

/**
 * Helper para trabalhar com strings
 */
class Str
{
    /**
     * Converte string para camelCase
     */
    public static function camel(string $value): string
    {
        return lcfirst(static::studly($value));
    }

    /**
     * Converte string para StudlyCase
     */
    public static function studly(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }

    /**
     * Converte string para snake_case
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $value = preg_replace('/\s+/u', '', ucwords($value)) ?? $value;
        $value = preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value) ?? $value;
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Converte string para kebab-case
     */
    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    /**
     * Limita o tamanho de uma string
     */
    public static function limit(
        string $value,
        int $limit = 100,
        string $end = '...'
    ): string {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * Gera uma string aleatória
     */
    public static function random(int $length = 16): string
    {
        $string = '';
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $string;
    }

    /**
     * Verifica se uma string começa com outra
     *
     * @deprecated v2.1.0 Use native str_starts_with() instead.
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        trigger_error('Str::startsWith() is deprecated. Use str_starts_with() instead.', E_USER_DEPRECATED);
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * Verifica se uma string termina com outra
     *
     * @deprecated v2.1.0 Use native str_ends_with() instead.
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        trigger_error('Str::endsWith() is deprecated. Use str_ends_with() instead.', E_USER_DEPRECATED);
        return substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * Verifica se uma string contém outra
     *
     * @deprecated v2.1.0 Use native str_contains() instead.
     */
    public static function contains(string $haystack, string $needle): bool
    {
        trigger_error('Str::contains() is deprecated. Use str_contains() instead.', E_USER_DEPRECATED);
        return strpos($haystack, $needle) !== false;
    }

    /**
     * Converte primeira letra para maiúscula
     */
    public static function ucfirst(string $string): string
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    }

    /**
     * Converte para title case
     */
    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Remove acentos de uma string
     */
    public static function ascii(string $value): string
    {
        $transliterationTable = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ç' => 'C', 'ç' => 'c',
            'Ñ' => 'N', 'ñ' => 'n'
        ];

        return strtr($value, $transliterationTable);
    }
}
