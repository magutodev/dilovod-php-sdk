<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Integration;

use Maguto\Dilovod\Enum\Operator;
use Maguto\Dilovod\Exception\ApiException;
use Maguto\Dilovod\Response\ResponseData;

final class DilovodClientTest extends IntegrationTestCase
{
    // ── Metadata ─────────────────────────────────────────

    public function testListMetadata(): void
    {
        $result = $this->client->listMetadata('uk');

        $this->assertArrayHasKey('catalogs.goods', $result);
        $this->assertArrayHasKey('catalogs.persons', $result);
        $this->assertArrayHasKey('id', $result['catalogs.goods']);
        $this->assertArrayHasKey('idPrefix', $result['catalogs.goods']);
        $this->assertArrayHasKey('presentation', $result['catalogs.goods']);
    }

    public function testGetMetadataByName(): void
    {
        $result = $this->client->getMetadata('catalogs.units');

        $this->assertSame('catalogs.units', $result['name']);
        $this->assertArrayHasKey('reqs', $result);
        $this->assertArrayHasKey('idPrefix', $result);
    }

    public function testGetMetadataById(): void
    {
        $list = $this->client->listMetadata();
        $unitsId = $list['catalogs.units']['id'];

        $result = $this->client->getMetadata(null, $unitsId);

        $this->assertSame('catalogs.units', $result['name']);
    }

    // ── CRUD: catalogs.persons ───────────────────────────

    public function testPersonCrudCycle(): void
    {
        // Create
        $id = $this->client->saveObject(
            [
                'id' => 'catalogs.persons',
                'name' => ['uk' => 'SDK інтеграційний тест', 'ru' => 'SDK интеграционный тест'],
            ]
        );

        $this->assertMatchesRegularExpression('/^\d{16}$/', $id);

        // Read
        $obj = $this->client->getObject($id);
        $this->assertInstanceOf(ResponseData::class, $obj);
        $this->assertSame($id, $obj->get('header')['id']['id']);
        $this->assertSame('SDK інтеграційний тест', $obj->get('header')['name']['uk']);
        $this->assertArrayHasKey('misc', $obj->toArray());
        // Довідники — без tableParts
        $this->assertArrayNotHasKey('tableParts', $obj->toArray());

        // Update
        $updatedId = $this->client->saveObject(
            [
                'id' => $id,
                'name' => ['uk' => 'SDK тест ОНОВЛЕНО', 'ru' => 'SDK тест ОБНОВЛЕНО'],
            ]
        );
        $this->assertSame($id, $updatedId);

        $updated = $this->client->getObject($id);
        $this->assertSame('SDK тест ОНОВЛЕНО', $updated->get('header')['name']['uk']);

        // Delete
        $this->client->setDelMark($id);

        $deleted = $this->client->getObject($id);
        $this->assertSame('1', $deleted->get('header')['delMark']);
    }

    // ── CRUD: catalogs.goods (group + item) ──────────────

    public function testGoodsCrudCycle(): void
    {
        // Create group
        $groupId = $this->client->saveObject(
            [
                'id' => 'catalogs.goods',
                'isGroup' => 1,
                'name' => ['uk' => 'SDK Тест Група'],
            ]
        );

        $this->assertMatchesRegularExpression('/^\d{16}$/', $groupId);

        // Create item in group
        $goodId = $this->client->saveObject(
            [
                'id' => 'catalogs.goods',
                'name' => ['uk' => 'SDK Тест Товар'],
                'parent' => $groupId,
            ]
        );

        // Read — товари мають tableParts
        $good = $this->client->getObject($goodId);
        $this->assertArrayHasKey('tableParts', $good->toArray());
        $this->assertSame($groupId, $good->get('header')['parent']['id']);

        // Cleanup
        $this->client->setDelMark($goodId);
        $this->client->setDelMark($groupId);
    }

    // ── QueryBuilder: request ────────────────────────────

    public function testQueryWithAssembleLinks(): void
    {
        $result = $this->client->query('catalogs.firms')
            ->fields(['id' => 'id', 'name' => 'name', 'code' => 'code'])
            ->limit(5)
            ->get();

        $this->assertGreaterThan(0, $result->count());

        $first = $result->first();
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        // assembleLinks=true додає __pr суфікс
        $this->assertArrayHasKey('id__pr', $first);
    }

    public function testQueryWithoutLinks(): void
    {
        $result = $this->client->query('catalogs.firms')
            ->fields(['id' => 'id', 'name' => 'name'])
            ->withoutLinks()
            ->get();

        $this->assertGreaterThan(0, $result->count());
        $this->assertNotNull($result->getColumns());
        $this->assertContains('id', $result->getColumns());
        $this->assertContains('name', $result->getColumns());

        // Без __pr суфіксу
        $this->assertArrayNotHasKey('id__pr', $result->first());
    }

    public function testQueryWithFilter(): void
    {
        // Спочатку отримаємо всі фірми
        $all = $this->client->query('catalogs.firms')
            ->fields(['id' => 'id', 'code' => 'code'])
            ->withoutLinks()
            ->get();

        if ($all->isEmpty()) {
            $this->markTestSkipped('No firms in database');
        }

        $code = $all->first()['code'];

        // Фільтруємо за code
        $filtered = $this->client->query('catalogs.firms')
            ->fields(['id' => 'id', 'code' => 'code'])
            ->where('code', Operator::equal(), $code)
            ->withoutLinks()
            ->get();

        $this->assertGreaterThan(0, $filtered->count());
        $this->assertSame($code, $filtered->first()['code']);
    }

    public function testQueryFirst(): void
    {
        $first = $this->client->query('catalogs.firms')
            ->fields(['id' => 'id', 'name' => 'name'])
            ->withoutLinks()
            ->first();

        $this->assertNotNull($first);
        $this->assertArrayHasKey('id', $first);
    }

    public function testQueryEmptyResult(): void
    {
        $result = $this->client->query('catalogs.goods')
            ->fields(['id' => 'id', 'code' => 'code'])
            ->where('code', Operator::equal(), 'NONEXISTENT_SDK_TEST_CODE_99999')
            ->withoutLinks()
            ->get();

        $this->assertTrue($result->isEmpty());
    }

    public function testQueryPluck(): void
    {
        $result = $this->client->query('catalogs.firms')
            ->fields(['id' => 'id', 'code' => 'code'])
            ->withoutLinks()
            ->get();

        $codes = $result->pluck('code');
        $this->assertCount($result->count(), $codes);
    }

    // ── Error handling ───────────────────────────────────

    public function testGetObjectNotFoundThrowsApiException(): void
    {
        $this->expectException(ApiException::class);

        $this->client->getObject('0000000000000000');
    }

    public function testSaveObjectInvalidMetadataThrowsApiException(): void
    {
        $this->expectException(ApiException::class);

        $this->client->saveObject(['id' => 'nonexistent.object']);
    }

    // ── saleOrderCreate via call() ───────────────────────

    public function testSaleOrderCreateAndCleanup(): void
    {
        $response = $this->client->call('saleOrderCreate', [
            'header' => [],
            'goods' => [],
        ]);

        // Успішна відповідь call-метода
        $this->assertSame('success', $response->get('status'));
        $this->assertIsArray($response->get('data'));

        $data = $response->get('data');
        $orderId = $data['header']['id']['id'];
        $this->assertMatchesRegularExpression('/^\d{16}$/', $orderId);

        // Cleanup
        $this->client->setDelMark($orderId);
    }

    public function testSaleOrderCreateViaOrderBuilder(): void
    {
        $orderId = $this->client->createOrder()
            ->send();

        $this->assertMatchesRegularExpression('/^\d{16}$/', $orderId);

        // Verify via getObject
        $order = $this->client->getObject($orderId);
        $this->assertArrayHasKey('tableParts', $order->toArray());

        // Cleanup
        $this->client->setDelMark($orderId);
    }

    public function testCallErrorThrowsApiException(): void
    {
        $this->expectException(ApiException::class);

        $this->client->call('saleOrderCreate', [
            'header' => ['person' => '0000000000000000'],
            'goods' => [['good' => '0000000000000000', 'qty' => 1]],
        ]);
    }
}
