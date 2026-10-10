<?php

namespace PivotPHP\Core\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Utils\Utils;

class UtilsTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset session state
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    public function testSanitizeString(): void
    {
        // Test normal string
        $this->assertEquals('Hello World', Utils::sanitizeString('Hello World'));

        // Test string with HTML tags
        $this->assertEquals('Hello World', Utils::sanitizeString('<script>Hello World</script>'));

        // Test string with whitespace
        $this->assertEquals('Hello World', Utils::sanitizeString('  Hello World  '));

        // Test non-string input
        $this->assertEquals(123, Utils::sanitizeString(123));
        $this->assertEquals(null, Utils::sanitizeString(null));
        $this->assertEquals(true, Utils::sanitizeString(true));
    }

    public function testSanitizeEmail(): void
    {
        // Test normal email
        $this->assertEquals('user@example.com', Utils::sanitizeEmail('user@example.com'));

        // Test email with whitespace
        $this->assertEquals('user@example.com', Utils::sanitizeEmail('  user@example.com  '));

        // Test non-string input
        $this->assertEquals(123, Utils::sanitizeEmail(123));
        $this->assertEquals(null, Utils::sanitizeEmail(null));
    }

    public function testSanitizeArray(): void
    {
        // Test normal array
        $input = ['<script>hello</script>', '  world  ', 'test'];
        $expected = ['hello', 'world', 'test'];
        $this->assertEquals($expected, Utils::sanitizeArray($input));

        // Test non-array input
        $this->assertEquals('string', Utils::sanitizeArray('string'));
        $this->assertEquals(123, Utils::sanitizeArray(123));
    }

    public function testIsEmail(): void
    {
        // Valid emails
        $this->assertTrue(Utils::isEmail('user@example.com'));
        $this->assertTrue(Utils::isEmail('test.email+tag@domain.co.uk'));
        $this->assertTrue(Utils::isEmail('user123@subdomain.example.org'));

        // Invalid emails
        $this->assertFalse(Utils::isEmail('invalid-email'));
        $this->assertFalse(Utils::isEmail('user@'));
        $this->assertFalse(Utils::isEmail('@domain.com'));
        $this->assertFalse(Utils::isEmail('user space@domain.com'));

        // Non-string input
        $this->assertFalse(Utils::isEmail(123));
        $this->assertFalse(Utils::isEmail(null));
        $this->assertFalse(Utils::isEmail([]));
    }

    public function testIsBool(): void
    {
        // Boolean values
        $this->assertTrue(Utils::isBool(true));
        $this->assertTrue(Utils::isBool(false));

        // String representations
        $this->assertTrue(Utils::isBool('0'));
        $this->assertTrue(Utils::isBool('1'));

        // Numeric representations
        $this->assertTrue(Utils::isBool(0));
        $this->assertTrue(Utils::isBool(1));

        // Invalid values
        $this->assertFalse(Utils::isBool('true'));
        $this->assertFalse(Utils::isBool('false'));
        $this->assertFalse(Utils::isBool(2));
        $this->assertFalse(Utils::isBool('hello'));
    }

    public function testIsInt(): void
    {
        // Valid integers
        $this->assertTrue(Utils::isInt(123));
        $this->assertTrue(Utils::isInt('456'));
        $this->assertTrue(Utils::isInt('0'));
        $this->assertTrue(Utils::isInt(-789));

        // Invalid integers
        $this->assertFalse(Utils::isInt('12.34'));
        $this->assertFalse(Utils::isInt('hello'));
        $this->assertFalse(Utils::isInt([]));
        $this->assertFalse(Utils::isInt(null));
    }

    public function testIsString(): void
    {
        // Valid strings
        $this->assertTrue(Utils::isString('hello'));
        $this->assertTrue(Utils::isString(''));
        $this->assertTrue(Utils::isString('123'));

        // Invalid strings (numeric values should not be considered strings)
        $this->assertFalse(Utils::isString(123));
        $this->assertFalse(Utils::isString(12.34));
        $this->assertFalse(Utils::isString([]));
        $this->assertFalse(Utils::isString(null));
        $this->assertFalse(Utils::isString(true));
    }

    public function testIsStringOrNumeric(): void
    {
        // Valid string or numeric values
        $this->assertTrue(Utils::isStringOrNumeric('hello'));
        $this->assertTrue(Utils::isStringOrNumeric(''));
        $this->assertTrue(Utils::isStringOrNumeric('123'));
        $this->assertTrue(Utils::isStringOrNumeric(123));
        $this->assertTrue(Utils::isStringOrNumeric(12.34));

        // Invalid values
        $this->assertFalse(Utils::isStringOrNumeric([]));
        $this->assertFalse(Utils::isStringOrNumeric(null));
        $this->assertFalse(Utils::isStringOrNumeric(true));
    }

    public function testIsArray(): void
    {
        // Valid arrays
        $this->assertTrue(Utils::isArray([]));
        $this->assertTrue(Utils::isArray([1, 2, 3]));
        $this->assertTrue(Utils::isArray(['key' => 'value']));

        // Invalid arrays
        $this->assertFalse(Utils::isArray('string'));
        $this->assertFalse(Utils::isArray(123));
        $this->assertFalse(Utils::isArray(null));
    }

    public function testCorsHeaders(): void
    {
        // Test default CORS headers
        $headers = Utils::corsHeaders();
        $expected = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type,Authorization',
            'Access-Control-Allow-Credentials' => 'true'
        ];
        $this->assertEquals($expected, $headers);

        // Test custom CORS headers
        $headers = Utils::corsHeaders(
            ['https://example.com', 'https://api.example.com'],
            ['GET', 'POST'],
            ['Content-Type', 'X-API-Key']
        );
        $expected = [
            'Access-Control-Allow-Origin' => 'https://example.com,https://api.example.com',
            'Access-Control-Allow-Methods' => 'GET,POST',
            'Access-Control-Allow-Headers' => 'Content-Type,X-API-Key',
            'Access-Control-Allow-Credentials' => 'true'
        ];
        $this->assertEquals($expected, $headers);
    }

    public function testLog(): void
    {
        // Não deve vazar saída ao usar destination Utils::DEST_SUPPRESS
        Utils::log('Test message', 'info', Utils::DEST_SUPPRESS);
        Utils::log('Error message', 'error', Utils::DEST_SUPPRESS);
        $this->assertTrue(true);
    }

    public function testRandomToken(): void
    {
        // Test default length (32)
        $token1 = Utils::randomToken();
        $this->assertEquals(32, strlen($token1));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token1);

        // Test custom length
        $token2 = Utils::randomToken(16);
        $this->assertEquals(16, strlen($token2));

        $token3 = Utils::randomToken(64);
        $this->assertEquals(64, strlen($token3));

        // Test uniqueness
        $this->assertNotEquals($token1, Utils::randomToken());
    }

    public function testAdvancedSanitization(): void
    {
        // Test complex HTML sanitization - strip_tags removes all HTML tags
        $input = '<div onclick="alert(\'xss\')">Hello <script>alert("xss")</script> World</div>';
        $result = Utils::sanitizeString($input);
        $this->assertEquals('Hello alert("xss") World', $result);

        // Test nested array sanitization
        $input = [
            'user' => '<script>alert("xss")</script>John',
            'data' => [
                'comment' => '  <b>Bold text</b>  ',
                'tags' => ['<em>tag1</em>', '  tag2  ']
            ]
        ];

        // Since sanitizeArray only handles flat arrays, we test accordingly
        $flatArray = ['<script>test</script>', '  hello  '];
        $result = Utils::sanitizeArray($flatArray);
        $this->assertEquals(['test', 'hello'], $result);
    }

    public function testEmailValidationEdgeCases(): void
    {
        // Test various edge cases for email validation
        $validEmails = [
            'simple@domain.com',
            'user.name@domain.com',
            'user+tag@domain.com',
            'user123@sub.domain.co.uk'
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue(Utils::isEmail($email), "Failed for email: $email");
        }

        $invalidEmails = [
            'plainaddress',
            '@missinglocalpart.com',
            'missing-domain@.com',
            'spaces in@domain.com',
            'user@',
            '@domain.com'
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(Utils::isEmail($email), "Should fail for email: $email");
        }
    }

    public function testFormatBytes(): void
    {
        // Test bytes
        $this->assertEquals('512 B', Utils::formatBytes(512));
        $this->assertEquals('0 B', Utils::formatBytes(0));

        // Test KB - Note: the function only formats when bytes > 1024, so 1024 exactly stays as bytes
        $this->assertEquals('1024 B', Utils::formatBytes(1024));
        $this->assertEquals('2.5 KB', Utils::formatBytes(2560));

        // Test MB
        $this->assertEquals('1024 KB', Utils::formatBytes(1024 * 1024));
        $this->assertEquals('1.5 MB', Utils::formatBytes(1024 * 1024 * 1.5));

        // Test GB
        $this->assertEquals('1024 MB', Utils::formatBytes(1024 * 1024 * 1024));

        // Test precision
        $this->assertEquals('1.123 KB', Utils::formatBytes(1150, 3));
        $this->assertEquals('1 KB', Utils::formatBytes(1150, 0));

        // Test very large numbers
        $this->assertEquals('1024 GB', Utils::formatBytes(1024 * 1024 * 1024 * 1024));
        $this->assertEquals('1024 TB', Utils::formatBytes(1024 * 1024 * 1024 * 1024 * 1024));

        // Test edge cases
        $this->assertEquals('1023 B', Utils::formatBytes(1023));
        $this->assertEquals('1000 PB', Utils::formatBytes(1024 * 1024 * 1024 * 1024 * 1024 * 1000));
    }

    public function testCamelCase(): void
    {
        $this->assertEquals('helloWorld', Utils::camelCase('hello_world'));
        $this->assertEquals('helloWorld', Utils::camelCase('hello-world'));
        $this->assertEquals('helloWorld', Utils::camelCase('hello world'));
        $this->assertEquals('helloWorldTest', Utils::camelCase('hello_world_test'));
        $this->assertEquals('helloWorldTest', Utils::camelCase('hello-world-test'));
        $this->assertEquals('hello', Utils::camelCase('hello'));
        $this->assertEquals('h', Utils::camelCase('h'));
        $this->assertEquals('', Utils::camelCase(''));
        $this->assertEquals('helloWorldFromSnake', Utils::camelCase('hello_world_from_snake'));
    }

    public function testSnakeCase(): void
    {
        $this->assertEquals('hello_world', Utils::snakeCase('HelloWorld'));
        $this->assertEquals('hello_world', Utils::snakeCase('helloWorld'));
        $this->assertEquals('hello_world_test', Utils::snakeCase('HelloWorldTest'));
        $this->assertEquals('hello', Utils::snakeCase('hello'));
        $this->assertEquals('h', Utils::snakeCase('H'));
        $this->assertEquals('', Utils::snakeCase(''));
        $this->assertEquals('a_b_c_d', Utils::snakeCase('ABCD'));
        $this->assertEquals('hello_world_from_camel', Utils::snakeCase('HelloWorldFromCamel'));
    }

    public function testKebabCase(): void
    {
        $this->assertEquals('hello-world', Utils::kebabCase('HelloWorld'));
        $this->assertEquals('hello-world', Utils::kebabCase('helloWorld'));
        $this->assertEquals('hello-world-test', Utils::kebabCase('HelloWorldTest'));
        $this->assertEquals('hello', Utils::kebabCase('hello'));
        $this->assertEquals('h', Utils::kebabCase('H'));
        $this->assertEquals('', Utils::kebabCase(''));
        $this->assertEquals('a-b-c-d', Utils::kebabCase('ABCD'));
        $this->assertEquals('hello-world-from-camel', Utils::kebabCase('HelloWorldFromCamel'));
    }

    public function testUuid4(): void
    {
        $uuid1 = Utils::uuid4();
        $uuid2 = Utils::uuid4();

        // Test format (8-4-4-4-12)
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $uuid1
        );
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $uuid2
        );

        // Test version 4 (should have '4' in the version position)
        $this->assertEquals('4', $uuid1[14]);
        $this->assertEquals('4', $uuid2[14]);

        // Test variant bits (should be 8, 9, a, or b)
        $this->assertContains($uuid1[19], ['8', '9', 'a', 'b']);
        $this->assertContains($uuid2[19], ['8', '9', 'a', 'b']);

        // Test uniqueness
        $this->assertNotEquals($uuid1, $uuid2);

        // Test multiple generations are unique
        $uuids = [];
        for ($i = 0; $i < 10; $i++) {
            $uuids[] = Utils::uuid4();
        }
        $this->assertEquals(10, count(array_unique($uuids)));
    }

    public function testIsJson(): void
    {
        // Valid JSON
        $this->assertTrue(Utils::isJson('{}'));
        $this->assertTrue(Utils::isJson('[]'));
        $this->assertTrue(Utils::isJson('{"key": "value"}'));
        $this->assertTrue(Utils::isJson('[1, 2, 3]'));
        $this->assertTrue(Utils::isJson('"string"'));
        $this->assertTrue(Utils::isJson('123'));
        $this->assertTrue(Utils::isJson('true'));
        $this->assertTrue(Utils::isJson('null'));

        // Invalid JSON
        $this->assertFalse(Utils::isJson('{invalid}'));
        $this->assertFalse(Utils::isJson('{"key": value}'));
        $this->assertFalse(Utils::isJson('[1, 2, 3,]'));
        $this->assertFalse(Utils::isJson('undefined'));
        $this->assertFalse(Utils::isJson(''));
        $this->assertFalse(Utils::isJson('hello world'));

        // Complex valid JSON
        $complexJson = json_encode(
            [
                'users' => [
                    ['name' => 'John', 'age' => 30],
                    ['name' => 'Jane', 'age' => 25]
                ],
                'meta' => [
                    'total' => 2,
                    'active' => true
                ]
            ]
        );
        $this->assertTrue(Utils::isJson($complexJson));
    }

    public function testTruncate(): void
    {
        // Test normal truncation
        $this->assertEquals('Hello...', Utils::truncate('Hello World', 8));
        $this->assertEquals('Hello W...', Utils::truncate('Hello World', 10));

        // Test string shorter than limit
        $this->assertEquals('Short', Utils::truncate('Short', 10));
        $this->assertEquals('Exact Len', Utils::truncate('Exact Len', 9));

        // Test custom suffix
        $this->assertEquals('Hello[...]', Utils::truncate('Hello World', 10, '[...]'));
        $this->assertEquals('Hello~~', Utils::truncate('Hello World', 7, '~~'));

        // Test edge cases
        $this->assertEquals('...', Utils::truncate('Hello', 3));
        $this->assertEquals('H...', Utils::truncate('Hello', 4));
        $this->assertEquals('', Utils::truncate('Hello', 0, ''));

        // Test empty string
        $this->assertEquals('', Utils::truncate('', 10));

        // Test exact length with suffix
        $this->assertEquals('Hello', Utils::truncate('Hello', 5));
        $this->assertEquals('Hello', Utils::truncate('Hello', 6));
    }

    public function testSlug(): void
    {
        // Basic slug generation
        $this->assertEquals('hello-world', Utils::slug('Hello World'));
        $this->assertEquals('hello-world', Utils::slug('  Hello   World  '));
        $this->assertEquals('hello-world', Utils::slug('Hello-World'));

        // Test special characters removal
        $this->assertEquals('helloworld', Utils::slug('Hello!@#$%^&*()World'));
        $this->assertEquals('hello-world-123', Utils::slug('Hello World 123'));

        // Test unicode characters (function preserves them, doesn't convert)
        $this->assertEquals('café-crème', Utils::slug('Café Crème'));
        $this->assertEquals('niño-muñoz', Utils::slug('Niño Muñoz'));

        // Test multiple spaces/hyphens
        $this->assertEquals('hello-world', Utils::slug('Hello---World'));
        $this->assertEquals('hello-world', Utils::slug('Hello    World'));
        $this->assertEquals('hello-world', Utils::slug('Hello - - - World'));

        // Test edge cases
        $this->assertEquals('', Utils::slug(''));
        $this->assertEquals('', Utils::slug('!@#$%^&*()'));
        $this->assertEquals('hello', Utils::slug('Hello'));
        $this->assertEquals('hello-world-from-long-text', Utils::slug('Hello World From Long Text'));

        // Test numbers and letters preservation
        $this->assertEquals('product-123-abc', Utils::slug('Product 123 ABC'));
        $this->assertEquals('test-v20', Utils::slug('Test v2.0')); // Note: dots are removed
    }

    public function testLogDestinations(): void
    {
        // Test suppressed logging - should not produce any output
        Utils::log('Test message', 'info', Utils::DEST_SUPPRESS);
        $this->assertTrue(true); // If we get here, suppression worked

        // Test logging to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'utils_test_log');
        Utils::log('Test log message', 'debug', $tempFile);

        $logContent = file_get_contents($tempFile);
        $this->assertStringContainsString('[debug] Test log message', $logContent);
        $this->assertStringContainsString(date('Y-m-d'), $logContent);

        unlink($tempFile);
    }
}
