<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit;

use DateTimeImmutable;
use Maguto\Dilovod\Config;
use Maguto\Dilovod\DilovodClient;
use Maguto\Dilovod\Enum\Action;
use Maguto\Dilovod\Request\OrderBuilder;
use Maguto\Dilovod\Request\Packet;
use Maguto\Dilovod\Response\ResponseData;
use Maguto\Dilovod\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;

final class OrderBuilderTest extends TestCase
{
    // ── toParams ─────────────────────────────────────────

    public function testMethodIsSaleOrderCreate(): void
    {
        $params = $this->builder()->toParams();

        $this->assertSame('saleOrderCreate', $params['method']);
        $this->assertArrayHasKey('arguments', $params);
    }

    public function testHeaderFields(): void
    {
        $params = $this->builder()
            ->firm('1100400000001001')
            ->person('1100100000001001')
            ->remarkFromPerson('Зателефонуйте')
            ->toParams();

        $header = $params['arguments']['header'];
        $this->assertSame('1100400000001001', $header['firm']);
        $this->assertSame('1100100000001001', $header['person']);
        $this->assertSame('Зателефонуйте', $header['remarkFromPerson']);
    }

    public function testAllHeaderMethods(): void
    {
        $params = $this->builder()
            ->firm('f')
            ->person('p')
            ->storage('s')
            ->currency('c')
            ->state('st')
            ->manager('m')
            ->priceType('pt')
            ->paymentForm('pf')
            ->deliveryMethod('dm')
            ->remark('rem')
            ->remarkFromPerson('rfp')
            ->remarkForPerson('rfpr')
            ->number('NN001')
            ->contract('con')
            ->contact('cnt')
            ->discountPercent(5.5)
            ->date('2025-03-15 10:00:00')
            ->supplyDate(new DateTimeImmutable('2025-03-20 00:00:00'))
            ->toParams();

        $h = $params['arguments']['header'];
        $this->assertSame('f', $h['firm']);
        $this->assertSame('st', $h['state']);
        $this->assertSame('NN001', $h['number']);
        $this->assertSame(5.5, $h['discountPercent']);
        $this->assertSame('2025-03-15 10:00:00', $h['date']);
        $this->assertSame('2025-03-20 00:00:00', $h['supplyDate']);
    }

    public function testHeaderFieldDirectAccess(): void
    {
        $params = $this->builder()
            ->headerField('customField', 'customValue')
            ->toParams();

        $this->assertSame('customValue', $params['arguments']['header']['customField']);
    }

    public function testAddProductById(): void
    {
        $params = $this->builder()
            ->addProduct('1100300000022632', qty: 1)
            ->addProduct('1100300000022876', qty: 2, price: 150.00)
            ->toParams();

        $goods = $params['arguments']['goods'];
        $this->assertCount(2, $goods);
        $this->assertSame('1100300000022632', $goods[0]['good']);
        $this->assertSame(1.0, $goods[0]['qty']);
        $this->assertArrayNotHasKey('price', $goods[0]);
        $this->assertSame(150.00, $goods[1]['price']);
    }

    public function testAddProductOptionalFields(): void
    {
        $params = $this->builder()
            ->addProduct(
                '1100300000022632',
                qty: 3,
                price: 100.0,
                unit: '1103600000001025',
                discount: 10.0,
                remark: 'Подарунок',
            )
            ->toParams();

        $item = $params['arguments']['goods'][0];
        $this->assertSame('1103600000001025', $item['unit']);
        $this->assertSame(10.0, $item['discount']);
        $this->assertSame('Подарунок', $item['remark']);
    }

    public function testAddProductByArticle(): void
    {
        $params = $this->builder()
            ->addProductByArticle('ART-001', qty: 5, price: 200.0)
            ->toParams();

        $item = $params['arguments']['goods'][0];
        $this->assertSame('ART-001', $item['productNum']);
        $this->assertSame(5.0, $item['qty']);
        $this->assertSame(200.0, $item['price']);
        $this->assertArrayNotHasKey('good', $item);
    }

    public function testAutoPlacement(): void
    {
        $params = $this->builder()
            ->withAutoPlacement()
            ->toParams();

        $this->assertSame(['autoPlacement' => true], $params['arguments']['placement']);
    }

    public function testNoPlacementByDefault(): void
    {
        $params = $this->builder()->toParams();

        $this->assertArrayNotHasKey('placement', $params['arguments']);
    }

    public function testNovaPoshtaTtn(): void
    {
        $delivery = ['Ref' => 'uuid-123', 'IntDocNumber' => '20450000001234'];

        $params = $this->builder()
            ->withNovaPoshtaTtn($delivery)
            ->toParams();

        $this->assertSame($delivery, $params['arguments']['deliveryData']);
    }

    public function testEmptyBuilderHasEmptyArguments(): void
    {
        $params = $this->builder()->toParams();

        $this->assertSame([], $params['arguments']);
    }

    // ── Full example matching API docs ───────────────────

    public function testFullOrderMatchesApiFormat(): void
    {
        $params = $this->builder()
            ->firm('1100400000001001')
            ->person('1100100000001001')
            ->remarkFromPerson('call me please')
            ->addProduct('1100300000022632', qty: 1)
            ->addProduct('1100300000022876', qty: 2)
            ->withAutoPlacement()
            ->toParams();

        $this->assertSame('saleOrderCreate', $params['method']);
        $this->assertSame('1100400000001001', $params['arguments']['header']['firm']);
        $this->assertCount(2, $params['arguments']['goods']);
        $this->assertTrue($params['arguments']['placement']['autoPlacement']);
    }

    // ── send() ───────────────────────────────────────────

    public function testSendUsesCallActionAndExtractsId(): void
    {
        $captured = null;
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')
            ->willReturnCallback(function (Packet $packet) use (&$captured): ResponseData {
                $captured = $packet;

                // Реальний формат відповіді saleOrderCreate
                return new ResponseData([
                    'status' => 'success',
                    'data' => [
                        'header' => [
                            'id' => ['id' => '1109100000001001', 'pr' => '03.04.2026 Замовлення 0000000001'],
                            'date' => '2026-04-03 12:52:02',
                            'number' => '0000000001',
                        ],
                        'tableParts' => ['tpGoods' => []],
                        'misc' => null,
                    ],
                ]);
            });

        $config = new Config(apiKey: 'test-key');
        $client = new DilovodClient($config, $transport);

        $id = $client->createOrder()
            ->firm('1100400000001001')
            ->person('1100100000001001')
            ->addProduct('1100300000022632', qty: 1)
            ->send();

        $this->assertSame(Action::Call, $captured->action);
        $this->assertSame('saleOrderCreate', $captured->params['method']);
        $this->assertSame('1109100000001001', $id);
    }

    // ── Helpers ──────────────────────────────────────────

    private function builder(): OrderBuilder
    {
        $transport = $this->createMock(TransportInterface::class);
        $config = new Config(apiKey: 'test-key');
        $client = new DilovodClient($config, $transport);

        return new OrderBuilder($client);
    }
}
