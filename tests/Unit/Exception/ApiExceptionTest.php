<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit\Exception;

use Maguto\Dilovod\Exception\ApiException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApiExceptionTest extends TestCase
{
    public function testFullConstruction(): void
    {
        $raw = ['error' => 'Access denied', 'clientMessages' => []];
        $previous = new RuntimeException('cause');

        $exception = new ApiException('Access denied', $raw, $previous);

        $this->assertSame('Access denied', $exception->getMessage());
        $this->assertSame($raw, $exception->getRawResponse());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultsAreNull(): void
    {
        $exception = new ApiException('error');

        $this->assertNull($exception->getRawResponse());
        $this->assertNull($exception->getPrevious());
    }
}
