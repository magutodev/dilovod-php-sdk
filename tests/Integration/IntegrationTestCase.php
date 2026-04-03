<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Integration;

use GuzzleHttp\Client as GuzzleClient;
use Maguto\Dilovod\Config;
use Maguto\Dilovod\DilovodClient;
use Maguto\Dilovod\Transport\PsrTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Базовий TestCase для інтеграційних тестів.
 * Завантажує .env, створює реальний DilovodClient.
 */
abstract class IntegrationTestCase extends TestCase
{
    /** @var DilovodClient */
    protected $client;

    /** @var Config */
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadEnv();

        $apiKey = $this->env('DILOVOD_API_KEY');
        $apiUrl = $this->env('DILOVOD_API_URL', 'https://api.dilovod.ua');

        if ($apiKey === '') {
            $this->markTestSkipped(
                'DILOVOD_API_KEY is not set. Copy .env.example to .env and fill in your credentials.'
            );
        }

        $this->config = new Config($apiKey, $apiUrl);

        $psr17 = new Psr17Factory();
        $httpClient = new GuzzleClient(['timeout' => 30]);
        $transport = new PsrTransport($this->config, $httpClient, $psr17, $psr17);

        $this->client = new DilovodClient($this->config, $transport);
    }

    /**
     * @return void
     */
    private function loadEnv()
    {
        $envFile = \dirname(__DIR__, 2) . '/.env';

        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }

    /**
     * @return string
     */
    private function env(string $key, string $default = '')
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
