<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit;

use Maguto\Dilovod\Response\ResultSet;
use PHPUnit\Framework\TestCase;

final class ResultSetTest extends TestCase
{
    public function testCountReturnsNumberOfRows(): void
    {
        $rs = new ResultSet([
            ['id' => '1', 'name' => 'A'],
            ['id' => '2', 'name' => 'B'],
            ['id' => '3', 'name' => 'C'],
        ]);

        $this->assertCount(3, $rs);
    }

    public function testIsEmptyReturnsTrueForEmptySet(): void
    {
        $this->assertTrue((new ResultSet([]))->isEmpty());
    }

    public function testFirstReturnsFirstRow(): void
    {
        $rs = new ResultSet([
            ['id' => '1', 'name' => 'First'],
            ['id' => '2', 'name' => 'Second'],
        ]);

        $this->assertSame(['id' => '1', 'name' => 'First'], $rs->first());
    }

    public function testFirstReturnsNullForEmptySet(): void
    {
        $this->assertNull((new ResultSet([]))->first());
    }

    public function testToArrayReturnsAllRows(): void
    {
        $rows = [
            ['id' => '1', 'qty' => '10.000'],
            ['id' => '2', 'qty' => '5.000'],
        ];

        $this->assertSame($rows, (new ResultSet($rows))->toArray());
    }

    public function testGetColumnsReturnsColumnsWhenProvided(): void
    {
        $rs = new ResultSet(
            [['firm' => '1001', 'qty' => '100']],
            ['firm', 'qty']
        );

        $this->assertSame(['firm', 'qty'], $rs->getColumns());
    }

    public function testPluckExtractsFieldValues(): void
    {
        $rs = new ResultSet([
            ['id' => '1100300000001001', 'code' => 'A001'],
            ['id' => '1100300000001002', 'code' => 'A002'],
            ['id' => '1100300000001003', 'code' => 'A003'],
        ]);

        $this->assertSame(['A001', 'A002', 'A003'], $rs->pluck('code'));
    }

    public function testPluckSkipsMissingKeys(): void
    {
        $rs = new ResultSet([
            ['id' => '1', 'code' => 'A'],
            ['id' => '2'],
            ['id' => '3', 'code' => 'C'],
        ]);

        $this->assertSame(['A', 'C'], $rs->pluck('code'));
    }

    public function testIteratorYieldsAllRows(): void
    {
        $rows = [
            ['id' => '1'],
            ['id' => '2'],
        ];

        $collected = [];
        foreach (new ResultSet($rows) as $row) {
            $collected[] = $row;
        }

        $this->assertSame($rows, $collected);
    }
}
