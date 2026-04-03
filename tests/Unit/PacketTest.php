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
            '0.25',
            'secret-api-key',
            Action::getObject(),
            ['id' => '1110800000001029']
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
            '0.25',
            'secret-api-key',
            Action::request(),
            ['from' => 'catalogs.goods'],
            'partner-app'
        );

        $json = $packet->jsonSerialize();

        $this->assertArrayHasKey('clientID', $json);
        $this->assertSame('partner-app', $json['clientID']);
    }

    public function testJsonSerializeWithoutClientIdOmitsField(): void
    {
        $packet = new Packet(
            '0.25',
            'key',
            Action::getObject(),
            []
        );

        $this->assertArrayNotHasKey('clientID', $packet->jsonSerialize());
    }

    public function testJsonEncodeProducesValidJson(): void
    {
        $packet = new Packet(
            '0.25',
            'test-key',
            Action::saveObject(),
            ['header' => ['id' => 'catalogs.goods', 'name' => 'Тестовий товар']]
        );

        $json = json_encode($packet, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $this->assertJson($json);
        $this->assertStringContainsString('"action":"saveObject"', $json);
        $this->assertStringContainsString('Тестовий товар', $json);
    }

    public function testWithMaskedKeyMasksAllButLastFour(): void
    {
        $packet = new Packet(
            '0.25',
            'abcdefghijklmnop',
            Action::getObject(),
            []
        );

        $masked = $packet->withMaskedKey();

        $this->assertSame('************mnop', $masked->key);
    }

    public function testWithMaskedKeyPreservesOtherFields(): void
    {
        $packet = new Packet(
            '0.25',
            'secret-key-12345',
            Action::request(),
            ['from' => 'catalogs.goods'],
            'partner'
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
            '0.25',
            'ab',
            Action::getObject(),
            []
        );

        $masked = $packet->withMaskedKey();

        $this->assertSame('ab', $masked->key);
    }

    public function testWithMaskedKeyDoesNotMutateOriginal(): void
    {
        $packet = new Packet(
            '0.25',
            'original-secret-key',
            Action::getObject(),
            []
        );

        $packet->withMaskedKey();

        $this->assertSame('original-secret-key', $packet->key);
    }

    public function testFieldOrder(): void
    {
        $packet = new Packet(
            '0.25',
            'key',
            Action::call(),
            ['method' => 'saleOrderCreate'],
            'app'
        );

        $keys = array_keys($packet->jsonSerialize());

        $this->assertSame(['version', 'key', 'action', 'params', 'clientID'], $keys);
    }
}
