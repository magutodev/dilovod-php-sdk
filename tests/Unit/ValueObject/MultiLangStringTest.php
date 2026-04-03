<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit\ValueObject;

use Maguto\Dilovod\ValueObject\MultiLangString;
use PHPUnit\Framework\TestCase;

final class MultiLangStringTest extends TestCase
{
    public function testGetReturnsRequestedLang(): void
    {
        $str = new MultiLangString(uk: 'Товар', ru: 'Товар RU');

        $this->assertSame('Товар', $str->get('uk'));
        $this->assertSame('Товар RU', $str->get('ru'));
    }

    public function testGetFallsBackToOtherLang(): void
    {
        $ukOnly = new MultiLangString(uk: 'Тільки UK');
        $ruOnly = new MultiLangString(ru: 'Только RU');

        $this->assertSame('Тільки UK', $ukOnly->get('ru'));
        $this->assertSame('Только RU', $ruOnly->get('uk'));
    }

    public function testGetReturnsNullWhenBothEmpty(): void
    {
        $this->assertNull((new MultiLangString())->get('uk'));
    }

    public function testToStringPrioritizesUk(): void
    {
        $this->assertSame('UK', (string) new MultiLangString(uk: 'UK', ru: 'RU'));
        $this->assertSame('RU', (string) new MultiLangString(ru: 'RU'));
        $this->assertSame('', (string) new MultiLangString());
    }

    public function testJsonSerialize(): void
    {
        $str = new MultiLangString(uk: 'Товар', ru: 'Товар RU');

        $this->assertSame(['uk' => 'Товар', 'ru' => 'Товар RU'], $str->jsonSerialize());
    }

    public function testFromArray(): void
    {
        $str = MultiLangString::fromArray(['uk' => 'Товар', 'ru' => 'Товар RU']);

        $this->assertSame('Товар', $str->uk);
        $this->assertSame('Товар RU', $str->ru);
    }

    public function testFromArrayPartial(): void
    {
        $str = MultiLangString::fromArray(['uk' => 'Only UK']);

        $this->assertSame('Only UK', $str->uk);
        $this->assertNull($str->ru);
    }

    public function testFromString(): void
    {
        $str = MultiLangString::fromString('Same');

        $this->assertSame('Same', $str->uk);
        $this->assertSame('Same', $str->ru);
    }
}
