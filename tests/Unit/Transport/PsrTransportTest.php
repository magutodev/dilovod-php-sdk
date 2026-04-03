<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit\Transport;

use Maguto\Dilovod\Config;
use Maguto\Dilovod\Enum\Action;
use Maguto\Dilovod\Exception\ApiException;
use Maguto\Dilovod\Exception\TransportException;
use Maguto\Dilovod\Request\Packet;
use Maguto\Dilovod\Response\ResponseData;
use Maguto\Dilovod\Transport\PsrTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

final class PsrTransportTest extends TestCase
{
    private Psr17Factory $psr17;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
    }

    public function testSuccessfulRequest(): void
    {
        $responseBody = json_encode(['id' => '1110800000001029', 'name' => 'Test'], JSON_THROW_ON_ERROR);
        $transport = $this->createTransport($this->mockHttpClient(200, $responseBody));

        $result = $transport->send($this->createPacket());

        $this->assertInstanceOf(ResponseData::class, $result);
        $this->assertSame('1110800000001029', $result->get('id'));
        $this->assertSame('Test', $result->get('name'));
    }

    public function testRequestIsSentAsPostWithJsonContentType(): void
    {
        $capturedRequest = null;
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use (&$capturedRequest): ResponseInterface {
                $capturedRequest = $request;

                return $this->psr17->createResponse(200)
                    ->withBody($this->psr17->createStream('{"ok":true}'));
            });

        $transport = $this->createTransport($httpClient);
        $transport->send($this->createPacket());

        $this->assertNotNull($capturedRequest);
        $this->assertSame('POST', $capturedRequest->getMethod());
        $this->assertSame('application/json', $capturedRequest->getHeaderLine('Content-Type'));
        $this->assertSame('https://api.dilovod.ua', (string) $capturedRequest->getUri());
    }

    public function testRequestBodyContainsSerializedPacket(): void
    {
        $capturedRequest = null;
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use (&$capturedRequest): ResponseInterface {
                $capturedRequest = $request;

                return $this->psr17->createResponse(200)
                    ->withBody($this->psr17->createStream('{"ok":true}'));
            });

        $transport = $this->createTransport($httpClient);
        $packet = $this->createPacket(Action::GetObject, ['id' => '1110800000001029']);
        $transport->send($packet);

        $body = json_decode((string) $capturedRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('getObject', $body['action']);
        $this->assertSame('1110800000001029', $body['params']['id']);
        $this->assertSame('0.25', $body['version']);
    }

    public function testRequestBodyIncludesClientIdWhenSet(): void
    {
        $capturedRequest = null;
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use (&$capturedRequest): ResponseInterface {
                $capturedRequest = $request;

                return $this->psr17->createResponse(200)
                    ->withBody($this->psr17->createStream('{"ok":true}'));
            });

        $transport = $this->createTransport($httpClient);
        $packet = new Packet(
            version: '0.25',
            key: 'secret-api-key-1234',
            action: Action::Request,
            params: ['from' => 'catalogs.goods'],
            clientId: 'partner-app',
        );
        $transport->send($packet);

        $body = json_decode((string) $capturedRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('partner-app', $body['clientID']);
    }

    public function testRequestBodyOmitsClientIdWhenNull(): void
    {
        $capturedRequest = null;
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use (&$capturedRequest): ResponseInterface {
                $capturedRequest = $request;

                return $this->psr17->createResponse(200)
                    ->withBody($this->psr17->createStream('{"ok":true}'));
            });

        $transport = $this->createTransport($httpClient);
        $transport->send($this->createPacket());

        $body = json_decode((string) $capturedRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('clientID', $body);
    }

    public function testApiErrorWithClientMessagesThrowsApiException(): void
    {
        $responseBody = json_encode([
            'error' => 'Access to catalogs.goods denied, check role settings',
            'clientMessages' => [],
        ], JSON_THROW_ON_ERROR);

        $transport = $this->createTransport($this->mockHttpClient(200, $responseBody));

        try {
            $transport->send($this->createPacket());
            $this->fail('Expected ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertSame('Access to catalogs.goods denied, check role settings', $e->getMessage());
            $this->assertIsArray($e->getRawResponse());
            $this->assertSame([], $e->getRawResponse()['clientMessages']);
        }
    }

    public function testApiErrorBadKeyThrowsApiException(): void
    {
        $responseBody = json_encode(['error' => 'bad key'], JSON_THROW_ON_ERROR);
        $transport = $this->createTransport($this->mockHttpClient(200, $responseBody));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('bad key');

        $transport->send($this->createPacket());
    }

    public function testCallMethodErrorThrowsApiException(): void
    {
        $responseBody = json_encode([
            'status' => 'error',
            'data' => [],
            'errorMessage' => 'Bad value type for column person',
        ], JSON_THROW_ON_ERROR);

        $transport = $this->createTransport($this->mockHttpClient(200, $responseBody));

        try {
            $transport->send($this->createPacket());
            $this->fail('Expected ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertSame('Bad value type for column person', $e->getMessage());
        }
    }

    public function testHttpErrorThrowsTransportException(): void
    {
        $transport = $this->createTransport($this->mockHttpClient(502, 'Bad Gateway'));

        try {
            $transport->send($this->createPacket());
            $this->fail('Expected TransportException was not thrown');
        } catch (TransportException $e) {
            $this->assertStringContainsString('502', $e->getMessage());
            $this->assertSame(502, $e->getHttpStatusCode());
        }
    }

    public function testHttp3xxThrowsTransportException(): void
    {
        $transport = $this->createTransport($this->mockHttpClient(301, ''));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessageMatches('/301/');

        $transport->send($this->createPacket());
    }

    public function testNetworkErrorThrowsTransportException(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willThrowException(new class ('Connection refused') extends RuntimeException implements ClientExceptionInterface {});

        $transport = $this->createTransport($httpClient);

        try {
            $transport->send($this->createPacket());
            $this->fail('Expected TransportException was not thrown');
        } catch (TransportException $e) {
            $this->assertStringContainsString('Connection refused', $e->getMessage());
            $this->assertNull($e->getHttpStatusCode());
            $this->assertInstanceOf(ClientExceptionInterface::class, $e->getPrevious());
        }
    }

    public function testEmptyResponseBodyThrowsTransportException(): void
    {
        $transport = $this->createTransport($this->mockHttpClient(200, ''));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Empty response body');

        $transport->send($this->createPacket());
    }

    public function testScalarStringResponseWrappedInResult(): void
    {
        $transport = $this->createTransport($this->mockHttpClient(200, '"ok"'));

        $result = $transport->send($this->createPacket());

        $this->assertSame('ok', $result->getScalarResult());
        $this->assertFalse($result->isError());
    }

    public function testScalarIntResponseWrappedInResult(): void
    {
        $transport = $this->createTransport($this->mockHttpClient(200, '1100300000022632'));

        $result = $transport->send($this->createPacket());

        $this->assertSame(1100300000022632, $result->getScalarResult());
    }

    public function testInvalidJsonResponseThrowsTransportException(): void
    {
        $transport = $this->createTransport($this->mockHttpClient(200, '{invalid json}'));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Invalid JSON in response');

        $transport->send($this->createPacket());
    }

    public function testLoggerReceivesDebugMessages(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var array<int, array{level: string, message: string, context: array<string, mixed>}> */
            public array $logs = [];

            public function log($level, Stringable|string $message, array $context = []): void
            {
                $this->logs[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
            }
        };

        $responseBody = json_encode(['ok' => true], JSON_THROW_ON_ERROR);
        $transport = $this->createTransport($this->mockHttpClient(200, $responseBody), $logger);
        $transport->send($this->createPacket());

        $this->assertCount(2, $logger->logs);
        $this->assertSame('debug', $logger->logs[0]['level']);
        $this->assertSame('Dilovod API request', $logger->logs[0]['message']);
        $this->assertSame('debug', $logger->logs[1]['level']);
        $this->assertSame('Dilovod API response', $logger->logs[1]['message']);
    }

    public function testLoggerDoesNotLeakApiKey(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var array<int, array{level: string, message: string, context: array<string, mixed>}> */
            public array $logs = [];

            public function log($level, Stringable|string $message, array $context = []): void
            {
                $this->logs[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
            }
        };

        $responseBody = json_encode(['ok' => true], JSON_THROW_ON_ERROR);
        $transport = $this->createTransport($this->mockHttpClient(200, $responseBody), $logger);
        $transport->send($this->createPacket());

        $logJson = json_encode($logger->logs, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('secret-api-key-1234', $logJson);
    }

    public function testLoggerReceivesErrorOnTransportFailure(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var array<int, array{level: string, message: string}> */
            public array $logs = [];

            public function log($level, Stringable|string $message, array $context = []): void
            {
                $this->logs[] = ['level' => (string) $level, 'message' => (string) $message];
            }
        };

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willThrowException(new class ('timeout') extends RuntimeException implements ClientExceptionInterface {});

        $transport = $this->createTransport($httpClient, $logger);

        try {
            $transport->send($this->createPacket());
        } catch (TransportException) {
            // expected
        }

        $errorLogs = array_filter($logger->logs, static fn(array $log): bool => $log['level'] === 'error');
        $this->assertNotEmpty($errorLogs);
    }

    // ── Helpers ──────────────────────────────────────────

    private function createTransport(
        ClientInterface $httpClient,
        ?AbstractLogger $logger = null,
    ): PsrTransport {
        $config = new Config(apiKey: 'secret-api-key-1234');

        return new PsrTransport(
            $config,
            $httpClient,
            $this->psr17,
            $this->psr17,
            $logger,
        );
    }

    private function createPacket(
        Action $action = Action::GetObject,
        array $params = ['id' => '1110800000001029'],
    ): Packet {
        return new Packet(
            version: '0.25',
            key: 'secret-api-key-1234',
            action: $action,
            params: $params,
        );
    }

    private function mockHttpClient(int $statusCode, string $body): ClientInterface
    {
        $response = $this->psr17->createResponse($statusCode)
            ->withBody($this->psr17->createStream($body));

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn($response);

        return $httpClient;
    }
}
