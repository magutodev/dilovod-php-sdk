<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit;

use Maguto\Dilovod\Enum\Action;
use Maguto\Dilovod\Request\Packet;
use PHPUnit\Framework\TestCase;

final class PacketTest extends TestCase
{
    public function testJsonSerializeMinimal(): void
    {
        $packet = new Packet(
            version: '0.25',
            key: 'secret-api-key',
            action: Action::GetObject,
            params: ['id' => '1110800000001029'],
        );

        $expected = [
            'version' => '0.25',
            'key' => 'secret-api-key',
            'action' => 'getObject',
            'params' => ['id' => '1110800000001029'],
        ];

        $this->assertSame($expected, $packet->jsonSerialize());
    }

    public function testJsonSerializeWithClientId(): void
    {
        $packet = new Packet(
            version: '0.25',
            key: 'secret-api-key',
            action: Action::Request,
            params: ['from' => 'catalogs.goods'],
            clientId: 'partner-app',
        );

        $json = $packet->jsonSerialize();

        $this->assertArrayHasKey('clientID', $json);
        $this->assertSame('partner-app', $json['clientID']);
    }

    public function testJsonSerializeWithoutClientIdOmitsField(): void
    {
        $packet = new Packet(
            version: '0.25',
            key: 'key',
            action: Action::GetObject,
            params: [],
        );

        $this->assertArrayNotHasKey('clientID', $packet->jsonSerialize());
    }

    public function testJsonEncodeProducesValidJson(): void
    {
        $packet = new Packet(
            version: '0.25',
            key: 'test-key',
            action: Action::SaveObject,
            params: ['header' => ['id' => 'catalogs.goods', 'name' => 'Тестовий товар']],
        );

        $json = json_encode($packet, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $this->assertJson($json);
        $this->assertStringContainsString('"action":"saveObject"', $json);
        $this->assertStringContainsString('Тестовий товар', $json);
    }

    public function testWithMaskedKeyMasksAllButLastFour(): void
    {
        $packet = new Packet(
            version: '0.25',
            key: 'abcdefghijklmnop',
            action: Action::GetObject,
            params: [],
        );

        $masked = $packet->withMaskedKey();

        $this->assertSame('************mnop', $masked->key);
    }

    public function testWithMaskedKeyPreservesOtherFields(): void
    {
        $packet = new Packet(
            version: '0.25',
            key: 'secret-key-12345',
            action: Action::Request,
            params: ['from' => 'catalogs.goods'],
            clientId: 'partner',
        );

        $masked = $packet->withMaskedKey();

        $this->assertSame($packet->version, $masked->version);
        $this->assertSame($packet->action, $masked->action);
        $this->assertSame($packet->params, $masked->params);
        $this->assertSame($packet->clientId, $masked->clientId);
        $this->assertNotSame($packet->key, $masked->key);
    }

    public function testWithMaskedKeyShortKey(): void
    {
        $packet = new Packet(
            version: '0.25',
            key: 'ab',
            action: Action::GetObject,
            params: [],
        );

        $masked = $packet->withMaskedKey();

        $this->assertSame('ab', $masked->key);
    }

    public function testWithMaskedKeyDoesNotMutateOriginal(): void
    {
        $packet = new Packet(
            version: '0.25',
            key: 'original-secret-key',
            action: Action::GetObject,
            params: [],
        );

        $packet->withMaskedKey();

        $this->assertSame('original-secret-key', $packet->key);
    }

    public function testFieldOrder(): void
    {
        $packet = new Packet(
            version: '0.25',
            key: 'key',
            action: Action::Call,
            params: ['method' => 'saleOrderCreate'],
            clientId: 'app',
        );

        $keys = array_keys($packet->jsonSerialize());

        $this->assertSame(['version', 'key', 'action', 'params', 'clientID'], $keys);
    }
}
