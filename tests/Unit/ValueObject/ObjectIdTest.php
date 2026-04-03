<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit\ValueObject;

use Maguto\Dilovod\Exception\InvalidArgumentException;
use Maguto\Dilovod\ValueObject\ObjectId;
use PHPUnit\Framework\TestCase;

final class ObjectIdTest extends TestCase
{
    public function testPrefixAndNumber(): void
    {
        $id = new ObjectId('1103600000001001');

        $this->assertSame('11036', $id->getPrefix());
        $this->assertSame('00000001001', $id->getNumber());
    }

    public function testToString(): void
    {
        $id = new ObjectId('1103600000001001');

        $this->assertSame('1103600000001001', (string) $id);
    }

    public function testIsSameTypeTrue(): void
    {
        $a = new ObjectId('1100300000022632');
        $b = new ObjectId('1100300000022876');

        $this->assertTrue($a->isSameType($b));
    }

    public function testIsSameTypeFalse(): void
    {
        $goods = new ObjectId('1100300000022632');
        $persons = new ObjectId('1100100000001001');

        $this->assertFalse($goods->isSameType($persons));
    }

    public function testInvalidLengthThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ObjectId('123456');
    }

    public function testNonDigitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ObjectId('110030000002263X');
    }
}
