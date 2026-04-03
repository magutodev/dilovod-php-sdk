<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit;

use DateTimeImmutable;
use Maguto\Dilovod\Config;
use Maguto\Dilovod\DilovodClient;
use Maguto\Dilovod\Enum\Action;
use Maguto\Dilovod\Enum\Operator;
use Maguto\Dilovod\Request\Packet;
use Maguto\Dilovod\Request\QueryBuilder;
use Maguto\Dilovod\Response\ResponseData;
use Maguto\Dilovod\Response\ResultSet;
use Maguto\Dilovod\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;

final class QueryBuilderTest extends TestCase
{
    // ── toParams: прямий запит ───────────────────────────

    public function testDirectQueryParams(): void
    {
        $builder = $this->builder('catalogs.goods')
            ->fields(['id' => 'good', 'code' => 'code', 'parent.code' => 'parentCode'])
            ->where('parentCode', Operator::equal(), 101010)
            ->limit(50);

        $this->assertSame([
            'from' => 'catalogs.goods',
            'fields' => ['id' => 'good', 'code' => 'code', 'parent.code' => 'parentCode'],
            'filters' => [['alias' => 'parentCode', 'operator' => '=', 'value' => 101010]],
            'limit' => 50,
        ], $builder->toParams());
    }

    public function testTablePartQueryParams(): void
    {
        $params = $this->builder('documents.saleOrder.tpGoods')
            ->fields(['owner.date' => 'order_date', 'good' => 'good', 'qty' => 'qty'])
            ->where('order_date', Operator::greaterOrEqual(), '2025-06-01 00:00:00')
            ->toParams();

        $this->assertSame('documents.saleOrder.tpGoods', $params['from']);
        $this->assertSame('>=', $params['filters'][0]['operator']);
    }

    public function testFromMethodOverridesConstructor(): void
    {
        $params = $this->builder('catalogs.goods')
            ->from('catalogs.persons')
            ->toParams();

        $this->assertSame('catalogs.persons', $params['from']);
    }

    // ── toParams: balance ────────────────────────────────

    public function testBalanceParams(): void
    {
        $params = $this->builder()
            ->balance('goods', '2025-01-01 00:00:00', ['good', 'storage'])
            ->fields(['good' => 'good', 'qty' => 'qty'])
            ->toParams();

        $this->assertSame([
            'type' => 'balance',
            'register' => 'goods',
            'date' => '2025-01-01 00:00:00',
            'dimensions' => ['good', 'storage'],
        ], $params['from']);
    }

    public function testBalanceWithDateTimeInterface(): void
    {
        $params = $this->builder()
            ->balance('goods', new DateTimeImmutable('2025-03-15 10:00:00'))
            ->toParams();

        $this->assertSame('2025-03-15 10:00:00', $params['from']['date']);
    }

    public function testBalanceWithoutDimensions(): void
    {
        $params = $this->builder()
            ->balance('goods', '2025-01-01 00:00:00')
            ->toParams();

        $this->assertArrayNotHasKey('dimensions', $params['from']);
    }

    // ── toParams: turnover ───────────────────────────────

    public function testTurnoverParams(): void
    {
        $params = $this->builder()
            ->turnover('saleIncomes', '2025-01-01', '2025-06-30', ['good', 'firm'])
            ->fields(['good' => 'good', 'amountReciept' => 'income'])
            ->toParams();

        $this->assertSame('turnover', $params['from']['type']);
        $this->assertSame('saleIncomes', $params['from']['register']);
        $this->assertSame('2025-01-01', $params['from']['startDate']);
        $this->assertSame('2025-06-30', $params['from']['endDate']);
        $this->assertSame(['good', 'firm'], $params['from']['dimensions']);
    }

    // ── toParams: balanceAndTurnover ─────────────────────

    public function testBalanceAndTurnoverParams(): void
    {
        $params = $this->builder()
            ->balanceAndTurnover('goods', '2025-01-01', '2025-06-30')
            ->fields(['good' => 'good', 'qtyStart' => 'start', 'qtyFinal' => 'final'])
            ->toParams();

        $this->assertSame('balanceAndTurnover', $params['from']['type']);
        $this->assertSame('goods', $params['from']['register']);
    }

    // ── toParams: sliceLast ──────────────────────────────

    public function testSliceLastParams(): void
    {
        $params = $this->builder()
            ->sliceLast('goodsPrices', '2025-07-28 11:38:33')
            ->fields(['good' => 'good', 'price' => 'price', 'priceType' => 'priceType'])
            ->where('priceType', Operator::equal(), '1101300000001002')
            ->toParams();

        $this->assertSame([
            'type' => 'sliceLast',
            'register' => 'goodsPrices',
            'date' => '2025-07-28 11:38:33',
        ], $params['from']);
    }

    // ── toParams: field() по одному ──────────────────────

    public function testFieldAddsIncrementally(): void
    {
        $params = $this->builder('catalogs.goods')
            ->field('id', 'good')
            ->field('code', 'code')
            ->toParams();

        $this->assertSame(['id' => 'good', 'code' => 'code'], $params['fields']);
    }

    // ── toParams: multiple filters ───────────────────────

    public function testMultipleFilters(): void
    {
        $params = $this->builder('catalogs.goods')
            ->where('code', Operator::contains(), 'test')
            ->where('parent', Operator::inHierarchy(), '1100300000001000')
            ->toParams();

        $this->assertCount(2, $params['filters']);
        $this->assertSame('%', $params['filters'][0]['operator']);
        $this->assertSame('IH', $params['filters'][1]['operator']);
    }

    public function testInListFilter(): void
    {
        $params = $this->builder('catalogs.goods')
            ->where('storage', Operator::inList(), ['1100700000001', '1100700000002'])
            ->toParams();

        $this->assertSame('IL', $params['filters'][0]['operator']);
        $this->assertSame(['1100700000001', '1100700000002'], $params['filters'][0]['value']);
    }

    // ── toParams: limit ──────────────────────────────────

    public function testLimitSimple(): void
    {
        $params = $this->builder('catalogs.goods')->limit(100)->toParams();

        $this->assertSame(100, $params['limit']);
    }

    public function testLimitWithOffset(): void
    {
        $params = $this->builder('catalogs.goods')->limit(50, 100)->toParams();

        $this->assertSame(['offset' => 100, 'count' => 50], $params['limit']);
    }

    // ── toParams: flags ──────────────────────────────────

    public function testWithoutLinks(): void
    {
        $params = $this->builder('catalogs.goods')->withoutLinks()->toParams();

        $this->assertFalse($params['assembleLinks']);
    }

    public function testAssembleLinksNotIncludedByDefault(): void
    {
        $params = $this->builder('catalogs.goods')->toParams();

        $this->assertArrayNotHasKey('assembleLinks', $params);
    }

    public function testMultilang(): void
    {
        $params = $this->builder('catalogs.goods')->multilang()->toParams();

        $this->assertTrue($params['multilang']);
    }

    public function testMultilangNotIncludedByDefault(): void
    {
        $params = $this->builder('catalogs.goods')->toParams();

        $this->assertArrayNotHasKey('multilang', $params);
    }

    // ── toParams: dimensions ─────────────────────────────

    public function testDimensions(): void
    {
        $params = $this->builder()
            ->balance('goods', '2025-01-01 00:00:00')
            ->dimensions(['good', 'storage'])
            ->toParams();

        $this->assertSame(['good', 'storage'], $params['dimensions']);
    }

    // ── toParams: empty builder ──────────────────────────

    public function testEmptyParamsWhenNothingSet(): void
    {
        $this->assertSame([], $this->builder()->toParams());
    }

    // ── get(): assembleLinks=true ────────────────────────

    public function testGetReturnsResultSetFromArrayResponse(): void
    {
        $response = new ResponseData([
            ['id' => '1100300000001001', 'code' => 'A001', 'name' => 'Товар 1'],
            ['id' => '1100300000001002', 'code' => 'A002', 'name' => 'Товар 2'],
        ]);

        $builder = $this->builderWithResponse($response, 'catalogs.goods');
        $result = $builder->fields(['id' => 'id', 'code' => 'code', 'name' => 'name'])->get();

        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertCount(2, $result);
        $this->assertSame('A001', $result->first()['code']);
        $this->assertNull($result->getColumns());
    }

    public function testGetReturnsEmptyResultSet(): void
    {
        $response = new ResponseData([]);
        $builder = $this->builderWithResponse($response, 'catalogs.goods');

        $result = $builder->get();

        $this->assertTrue($result->isEmpty());
        $this->assertSame(0, $result->count());
    }

    // ── get(): assembleLinks=false ───────────────────────

    public function testGetWithoutLinksConvertsColumnarFormat(): void
    {
        $response = new ResponseData([
            'columns' => ['firm', 'good', 'qty', 'amount'],
            'data' => [
                ['1100400000001001', '1100300000001001', '9968.000', '199360.00'],
                ['1100400000001001', '1100300000001002', '963.000', '28890.00'],
            ],
        ]);

        $builder = $this->builderWithResponse($response)
            ->balance('goods', '2025-01-01 00:00:00')
            ->fields(['firm' => 'firm', 'good' => 'good', 'qty' => 'qty', 'amount' => 'amount'])
            ->withoutLinks();

        $result = $builder->get();

        $this->assertCount(2, $result);
        $this->assertSame(['firm', 'good', 'qty', 'amount'], $result->getColumns());
        $this->assertSame('9968.000', $result->first()['qty']);
        $this->assertSame('1100300000001002', $result->toArray()[1]['good']);
    }

    // ── first() ──────────────────────────────────────────

    public function testFirstReturnsFirstRow(): void
    {
        $response = new ResponseData([
            ['id' => '1100300000001001', 'name' => 'First'],
        ]);

        $result = $this->builderWithResponse($response, 'catalogs.goods')
            ->fields(['id' => 'id', 'name' => 'name'])
            ->first();

        $this->assertSame('First', $result['name']);
    }

    public function testFirstReturnsNullOnEmpty(): void
    {
        $response = new ResponseData([]);

        $result = $this->builderWithResponse($response, 'catalogs.goods')->first();

        $this->assertNull($result);
    }

    // ── get() sends correct action ───────────────────────

    public function testGetSendsRequestAction(): void
    {
        $captured = null;
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')
            ->willReturnCallback(function (Packet $packet) use (&$captured) {
                $captured = $packet;

                return new ResponseData([]);
            });

        $config = new Config('test-key');
        $client = new DilovodClient($config, $transport);

        $client->query('catalogs.goods')
            ->fields(['id' => 'id'])
            ->where('code', Operator::equal(), 'TEST')
            ->get();

        $this->assertSame(Action::REQUEST, $captured->action->value);
        $this->assertSame('catalogs.goods', $captured->params['from']);
        $this->assertSame(['id' => 'id'], $captured->params['fields']);
    }

    // ── Helpers ──────────────────────────────────────────

    /**
     * @param string|null $from
     * @return QueryBuilder
     */
    private function builder($from = null)
    {
        $transport = $this->createMock(TransportInterface::class);
        $config = new Config('test-key');
        $client = new DilovodClient($config, $transport);

        return new QueryBuilder($client, $from);
    }

    /**
     * @param string|null $from
     * @return QueryBuilder
     */
    private function builderWithResponse(ResponseData $response, $from = null)
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturn($response);
        $config = new Config('test-key');
        $client = new DilovodClient($config, $transport);

        return new QueryBuilder($client, $from);
    }
}
