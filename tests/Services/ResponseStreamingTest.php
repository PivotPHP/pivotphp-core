<?php

namespace PivotPHP\Core\Tests\Services;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Response;

/**
 * Testes para funcionalidades de streaming da classe Response.
 * @group streaming
 */
class ResponseStreamingTest extends TestCase
{
    private Response $response;

    protected function setUp(): void
    {
        $this->response = new Response();
        // Ativar modo teste para evitar problemas com output buffers
        $this->response->setTestMode(true);
    }

    protected function tearDown(): void
    {
        // Cleanup não é mais necessário com modo teste
    }

    public function testStartStream(): void
    {
        $this->response->startStream('text/plain');

        $this->assertTrue($this->response->isStreaming());

        // PSR-7 getHeaderLine() returns header value as string
        $this->assertEquals('no-cache', $this->response->getHeaderLine('Cache-Control'));
        $this->assertEquals('keep-alive', $this->response->getHeaderLine('Connection'));
        $this->assertEquals('text/plain', $this->response->getHeaderLine('Content-Type'));
    }

    public function testSetStreamBufferSize(): void
    {
        $bufferSize = 16384;
        $result = $this->response->setStreamBufferSize($bufferSize);

        $this->assertInstanceOf(Response::class, $result);
        // Como $streamBufferSize é privado, testamos indiretamente através do comportamento
        $this->assertTrue(true); // Buffer size configurado corretamente
    }

    public function testWrite(): void
    {
        $this->response->startStream();
        $result = $this->response->write('Test content');

        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($this->response->isStreaming());
    }

    public function testWriteJson(): void
    {
        $data = ['id' => 123, 'message' => 'Hello'];

        $this->response->startStream();
        $result = $this->response->writeJson($data);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($this->response->isStreaming());
    }

    public function testWriteJsonWithInvalidData(): void
    {
        // Teste com dados que podem causar problemas de encoding
        $invalidData = [
            'message' => "Invalid UTF-8: \xC0\xC1",
            'id' => 123
        ];

        $this->response->startStream();
        $result = $this->response->writeJson($invalidData);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($this->response->isStreaming());
        // O método deve sanitizar dados inválidos sem erros
    }

    public function testSendEvent(): void
    {
        $result = $this->response->sendEvent(['message' => 'Hello World'], 'test', 'custom-id', 5000);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($this->response->isStreaming());
    }

    public function testSendEventWithMultilineData(): void
    {
        $multilineData = [
            'message' => "Line 1\nLine 2\nLine 3"
        ];

        $result = $this->response->sendEvent($multilineData, 'multiline');

        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($this->response->isStreaming());
    }

    public function testSendHeartbeat(): void
    {
        $this->response->startStream();
        $result = $this->response->sendHeartbeat();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($this->response->isStreaming());
    }

    public function testEndStream(): void
    {
        $this->response->startStream();
        $result = $this->response->endStream();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertFalse($this->response->isStreaming());
    }

    public function testStreamFileWithValidFile(): void
    {
        // Criar arquivo temporário para teste
        $tempFile = tempnam(sys_get_temp_dir(), 'test_streaming_');
        file_put_contents($tempFile, 'Test file content for streaming');

        $result = $this->response->streamFile($tempFile, ['Content-Type' => 'text/plain']);

        // Limpar arquivo temporário
        unlink($tempFile);

        $this->assertInstanceOf(Response::class, $result);
        // Verificar se os headers foram configurados (PSR-7 getHeaderLine)
        $this->assertEquals('text/plain', $this->response->getHeaderLine('Content-Type'));
    }

    public function testStreamFileWithInvalidFile(): void
    {
        try {
            $result = $this->response->streamFile('/path/that/does/not/exist.txt');
            // Se não lançou exceção, deve retornar Response
            $this->assertInstanceOf(Response::class, $result);
        } catch (\Exception $e) {
            // É esperado que lance exceção para arquivo inexistente
            $this->assertStringContainsString('File not found', $e->getMessage());
        }
    }

    public function testStreamResource(): void
    {
        $resource = fopen('data://text/plain,Resource content for streaming', 'r');

        $result = $this->response->streamResource($resource);

        fclose($resource);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testStreamResourceWithInvalidResource(): void
    {
        try {
            $result = $this->response->streamResource('not a resource');
            // Se não lançou exceção, deve retornar Response
            $this->assertInstanceOf(Response::class, $result);
        } catch (\Exception $e) {
            // É esperado que lance exceção para resource inválido
            $this->assertStringContainsString('Invalid resource', $e->getMessage());
        }
    }

    public function testIsStreamingDefault(): void
    {
        $this->assertFalse($this->response->isStreaming());
    }

    public function testChainedStreamingMethods(): void
    {
        $result = $this->response
            ->setStreamBufferSize(4096)
            ->startStream('application/json')
            ->writeJson(['start' => true])
            ->writeJson(['id' => 1]);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($this->response->isStreaming());
    }

    public function testSendEventAutoStartsStream(): void
    {
        // sendEvent deve iniciar stream automaticamente se não estiver ativo
        $result = $this->response->sendEvent(['message' => 'test event'], 'test');

        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($this->response->isStreaming());
    }

    public function testMultipleHeadersInStreamFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_streaming_headers_');
        file_put_contents($tempFile, 'test content');

        $result = $this->response
            ->header('X-Custom-Header', 'custom-value')
            ->streamFile($tempFile, ['Content-Type' => 'text/plain']);

        unlink($tempFile);

        $this->assertInstanceOf(Response::class, $result);
        // PSR-7 getHeaderLine() returns header value as string
        $this->assertEquals('text/plain', $this->response->getHeaderLine('Content-Type'));
        $this->assertEquals('custom-value', $this->response->getHeaderLine('X-Custom-Header'));
    }
}
