<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit\Webhook;

use Maguto\Dilovod\Exception\InvalidArgumentException;
use Maguto\Dilovod\Webhook\WebhookEvent;
use Maguto\Dilovod\Webhook\WebhookParser;
use PHPUnit\Framework\TestCase;

final class WebhookParserTest extends TestCase
{
    public function testParseValidWebhook(): void
    {
        $json = json_encode([
            'action' => 'objectChanged',
            'params' => [
                'objectName' => 'documents.saleOrder',
                'id' => '1109100000001038',
            ],
        ], JSON_THROW_ON_ERROR);

        $event = WebhookParser::parse($json);

        $this->assertInstanceOf(WebhookEvent::class, $event);
        $this->assertSame('objectChanged', $event->action);
        $this->assertSame('documents.saleOrder', $event->objectName);
        $this->assertSame('1109100000001038', $event->id);
    }

    public function testParseInvalidJsonThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid webhook JSON');

        WebhookParser::parse('{bad json}');
    }

    public function testParseMissingActionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"action"');

        WebhookParser::parse('{"params":{"objectName":"x","id":"1"}}');
    }

    public function testParseMissingParamsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WebhookParser::parse('{"action":"objectChanged"}');
    }

    public function testParseMissingObjectNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"objectName"');

        WebhookParser::parse('{"action":"objectChanged","params":{"id":"1"}}');
    }

    public function testParseMissingIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WebhookParser::parse('{"action":"objectChanged","params":{"objectName":"x"}}');
    }

    public function testIsValidSourceWithCorrectIp(): void
    {
        $this->assertTrue(WebhookParser::isValidSource('88.198.50.253'));
    }

    public function testIsValidSourceWithWrongIp(): void
    {
        $this->assertFalse(WebhookParser::isValidSource('1.2.3.4'));
    }
}
