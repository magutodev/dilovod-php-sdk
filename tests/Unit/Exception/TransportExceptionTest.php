<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit\Exception;

use Maguto\Dilovod\Exception\TransportException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TransportExceptionTest extends TestCase
{
    public function testFullConstruction(): void
    {
        $previous = new RuntimeException('curl failed');

        $exception = new TransportException('Server error', 502, $previous);

        $this->assertSame('Server error', $exception->getMessage());
        $this->assertSame(502, $exception->getHttpStatusCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultsAreNull(): void
    {
        $exception = new TransportException('Network error');

        $this->assertNull($exception->getHttpStatusCode());
        $this->assertNull($exception->getPrevious());
    }
}
