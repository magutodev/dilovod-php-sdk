<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit;

use Maguto\Dilovod\Config;
use Maguto\Dilovod\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $config = new Config('test-key-12345678');

        $this->assertSame('test-key-12345678', $config->apiKey);
        $this->assertSame('https://api.dilovod.ua', $config->apiUrl);
        $this->assertSame('0.25', $config->version);
        $this->assertNull($config->clientId);
    }

    public function testCustomValues(): void
    {
        $config = new Config(
            'my-secret-key',
            'https://vps.example.com',
            '0.30',
            'partner-123'
        );

        $this->assertSame('my-secret-key', $config->apiKey);
        $this->assertSame('https://vps.example.com', $config->apiUrl);
        $this->assertSame('0.30', $config->version);
        $this->assertSame('partner-123', $config->clientId);
    }

    public function testApiUrlTrailingSlashIsTrimmed(): void
    {
        $config = new Config(
            'test-key',
            'https://api.dilovod.ua/'
        );

        $this->assertSame('https://api.dilovod.ua', $config->apiUrl);
    }

    public function testApiUrlMultipleTrailingSlashesAreTrimmed(): void
    {
        $config = new Config(
            'test-key',
            'https://api.dilovod.ua///'
        );

        $this->assertSame('https://api.dilovod.ua', $config->apiUrl);
    }

    public function testEmptyApiKeyThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API key must not be empty.');

        new Config('');
    }
}
