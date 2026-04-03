<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Transport;

use JsonException;
use Maguto\Dilovod\Config;
use Maguto\Dilovod\Exception\ApiException;
use Maguto\Dilovod\Exception\TransportException;
use Maguto\Dilovod\Request\Packet;
use Maguto\Dilovod\Response\ResponseData;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class PsrTransport implements TransportInterface
{
    /** @var Config */
    private $config;

    /** @var ClientInterface */
    private $httpClient;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    /** @var LoggerInterface|null */
    private $logger;

    public function __construct(
        Config $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->logger = $logger;
    }

    public function send(Packet $packet): ResponseData
    {
        $json = json_encode($packet, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $this->log(LogLevel::DEBUG, 'Dilovod API request', [
            'action' => $packet->action->value,
            'packet' => $packet->withMaskedKey()->jsonSerialize(),
        ]);

        $httpRequest = $this->requestFactory
            ->createRequest('POST', $this->config->apiUrl)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($json));

        try {
            $httpResponse = $this->httpClient->sendRequest($httpRequest);
        } catch (ClientExceptionInterface $e) {
            $this->log(LogLevel::ERROR, 'Dilovod API transport error', [
                'error' => $e->getMessage(),
            ]);

            throw new TransportException(
                'HTTP request failed: ' . $e->getMessage(),
                null,
                $e
            );
        }

        $statusCode = $httpResponse->getStatusCode();
        $body = (string) $httpResponse->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            $this->log(LogLevel::ERROR, 'Dilovod API HTTP error', [
                'statusCode' => $statusCode,
                'body' => $body,
            ]);

            throw new TransportException(
                sprintf('HTTP %d: %s', $statusCode, $httpResponse->getReasonPhrase()),
                $statusCode
            );
        }

        $responseData = $this->parseResponseBody($body);

        $this->log(LogLevel::DEBUG, 'Dilovod API response', [
            'action' => $packet->action->value,
            'isError' => $responseData->isError(),
        ]);

        if ($responseData->isError()) {
            throw new ApiException(
                $responseData->getError() ?? 'Unknown API error',
                $responseData->toArray()
            );
        }

        return $responseData;
    }

    private function parseResponseBody(string $body): ResponseData
    {
        if ($body === '') {
            throw new TransportException('Empty response body');
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new TransportException(
                'Invalid JSON in response: ' . $e->getMessage(),
                null,
                $e
            );
        }

        // API може повернути скалярне значення: "ok", число (ID) тощо.
        if (!is_array($decoded)) {
            return new ResponseData(['_result' => $decoded]);
        }

        return new ResponseData($decoded);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            $this->logger->log($level, $message, $context);
        }
    }
}
