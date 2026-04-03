<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit;

use DateTimeImmutable;
use Maguto\Dilovod\Config;
use Maguto\Dilovod\DilovodClient;
use Maguto\Dilovod\Enum\Action;
use Maguto\Dilovod\Enum\SaveType;
use Maguto\Dilovod\Exception\InvalidArgumentException;
use Maguto\Dilovod\Request\Packet;
use Maguto\Dilovod\Response\ResponseData;
use Maguto\Dilovod\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;

final class DilovodClientTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config(
            apiKey: 'test-api-key',
            clientId: 'partner-app',
        );
    }

    // ── getObject ────────────────────────────────────────

    public function testGetObjectSendsCorrectPacket(): void
    {
        $captured = null;
        $client = $this->createClientCapturing($captured, $this->getObjectResponse());

        $client->getObject('1103600000001001');

        $this->assertSame(Action::GetObject, $captured->action);
        $this->assertSame(['id' => '1103600000001001'], $captured->params);
    }

    public function testGetObjectReturnsResponseData(): void
    {
        $client = $this->createClient($this->getObjectResponse());

        $result = $client->getObject('1103600000001001');

        $header = $result->get('header');
        $this->assertIsArray($header);
        $this->assertSame('SDKT', $header['code']);
    }

    // ── saveObject ───────────────────────────────────────

    public function testSaveObjectSendsCorrectPacketForCreate(): void
    {
        $captured = null;
        $client = $this->createClientCapturing($captured, $this->saveObjectResponse());

        $client->saveObject(
            header: ['id' => 'catalogs.units', 'code' => 'SDKT', 'name' => ['uk' => 'SDK тест']],
        );

        $this->assertSame(Action::SaveObject, $captured->action);
        $this->assertSame(0, $captured->params['saveType']);
        $this->assertSame('catalogs.units', $captured->params['header']['id']);
        $this->assertArrayNotHasKey('tableParts', $captured->params);
    }

    public function testSaveObjectSendsTablePartsWhenProvided(): void
    {
        $captured = null;
        $client = $this->createClientCapturing($captured, $this->saveObjectResponse());

        $client->saveObject(
            header: ['id' => 'catalogs.goods'],
            tableParts: ['tpBarcodes' => [['barcode' => '1234567890']]],
        );

        $this->assertSame(
            ['tpBarcodes' => [['barcode' => '1234567890']]],
            $captured->params['tableParts'],
        );
    }

    public function testSaveObjectWithRegisterSaveType(): void
    {
        $captured = null;
        $client = $this->createClientCapturing($captured, $this->saveObjectResponse());

        $client->saveObject(
            header: ['id' => '1109100000001038'],
            saveType: SaveType::Register,
        );

        $this->assertSame(1, $captured->params['saveType']);
    }

    public function testSaveObjectReturnsId(): void
    {
        $client = $this->createClient($this->saveObjectResponse());

        $id = $client->saveObject(header: ['id' => 'catalogs.units', 'code' => 'TEST']);

        $this->assertSame('1103600000001001', $id);
    }

    // ── setDelMark ───────────────────────────────────────

    public function testSetDelMarkSendsCorrectPacket(): void
    {
        $captured = null;
        $client = $this->createClientCapturing($captured, new ResponseData(['_result' => 'ok']));

        $client->setDelMark('1103600000001001');

        $this->assertSame(Action::SetDelMark, $captured->action);
        $this->assertSame(
            ['header' => ['id' => '1103600000001001']],
            $captured->params,
        );
    }

    // ── listMetadata ─────────────────────────────────────

    public function testListMetadataSendsLang(): void
    {
        $captured = null;
        $client = $this->createClientCapturing($captured, $this->listMetadataResponse());

        $client->listMetadata('ru');

        $this->assertSame(Action::ListMetadata, $captured->action);
        $this->assertSame(['lang' => 'ru'], $captured->params);
    }

    public function testListMetadataDefaultsToUk(): void
    {
        $captured = null;
        $client = $this->createClientCapturing($captured, $this->listMetadataResponse());

        $client->listMetadata();

        $this->assertSame('uk', $captured->params['lang']);
    }

    public function testListMetadataReturnsArray(): void
    {
        $client = $this->createClient($this->listMetadataResponse());

        $result = $client->listMetadata();

        $this->assertArrayHasKey('catalogs.persons', $result);
        $this->assertSame('11001', $result['catalogs.persons']['idPrefix']);
    }

    // ── getMetadata ──────────────────────────────────────

    public function testGetMetadataByObjectName(): void
    {
        $captured = null;
        $client = $this->createClientCapturing($captured, $this->getMetadataResponse());

        $client->getMetadata(objectName: 'catalogs.units');

        $this->assertSame(Action::GetMetadata, $captured->action);
        $this->assertSame('catalogs.units', $captured->params['objectName']);
        $this->assertArrayNotHasKey('objectId', $captured->params);
    }

    public function testGetMetadataByObjectId(): void
    {
        $captured = null;
        $client = $this->createClientCapturing($captured, $this->getMetadataResponse());

        $client->getMetadata(objectId: '1000000000001249');

        $this->assertSame('1000000000001249', $captured->params['objectId']);
        $this->assertArrayNotHasKey('objectName', $captured->params);
    }

    public function testGetMetadataThrowsWithoutArguments(): void
    {
        $client = $this->createClient(new ResponseData([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either objectName or objectId must be provided.');

        $client->getMetadata();
    }

    // ── getStatistic ─────────────────────────────────────

    public function testGetStatisticSendsCorrectParams(): void
    {
        $captured = null;
        $client = $this->createClientCapturing($captured, new ResponseData([]));

        $client->getStatistic(
            'partner-key-123',
            new DateTimeImmutable('2025-01-01 00:00:00'),
            new DateTimeImmutable('2025-06-30 23:59:59'),
        );

        $this->assertSame(Action::GetStatistic, $captured->action);
        $this->assertSame('partnersIntegrations', $captured->params['type']);
        $this->assertSame('partner-key-123', $captured->params['partnerAPIkey']);
        $this->assertSame('2025-01-01 00:00:00', $captured->params['dateFrom']);
        $this->assertSame('2025-06-30 23:59:59', $captured->params['dateTo']);
    }

    // ── call ──────────────────────────────────────────────

    public function testCallSendsCorrectPacket(): void
    {
        $captured = null;
        $client = $this->createClientCapturing($captured, new ResponseData([
            'status' => 'success',
            'data' => [],
        ]));

        $client->call('saleOrderCreate', [
            'header' => ['firm' => '1100400000001001'],
            'goods' => [['good' => '1100300000001001', 'qty' => 1]],
        ]);

        $this->assertSame(Action::Call, $captured->action);
        $this->assertSame('saleOrderCreate', $captured->params['method']);
        $this->assertSame('1100400000001001', $captured->params['arguments']['header']['firm']);
    }

    // ── execute (Packet assembly) ────────────────────────

    public function testExecutePassesConfigToPacket(): void
    {
        $captured = null;
        $client = $this->createClientCapturing($captured, new ResponseData([]));

        $client->getObject('1103600000001001');

        $this->assertSame('0.25', $captured->version);
        $this->assertSame('test-api-key', $captured->key);
        $this->assertSame('partner-app', $captured->clientId);
    }

    public function testExecuteWithoutClientId(): void
    {
        $config = new Config(apiKey: 'test-key');
        $captured = null;
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')
            ->willReturnCallback(function (Packet $packet) use (&$captured): ResponseData {
                $captured = $packet;

                return new ResponseData([]);
            });

        $client = new DilovodClient($config, $transport);
        $client->getObject('1103600000001001');

        $this->assertNull($captured->clientId);
    }

    // ── Response fixtures (matching real API formats) ────

    private function getObjectResponse(): ResponseData
    {
        return new ResponseData([
            'header' => [
                'id' => ['id' => '1103600000001001', 'pr' => 'sdkt'],
                'code' => 'SDKT',
                'delMark' => '0',
                'name' => ['ru' => 'SDK тест', 'uk' => 'SDK тест'],
            ],
            'misc' => false,
        ]);
    }

    private function saveObjectResponse(): ResponseData
    {
        return new ResponseData([
            'result' => 'ok',
            'id' => '1103600000001001',
        ]);
    }

    private function listMetadataResponse(): ResponseData
    {
        return new ResponseData([
            'catalogs.persons' => [
                'id' => '1000000000001249',
                'idPrefix' => '11001',
                'presentation' => 'Організації та фізичні особи',
            ],
            'catalogs.goods' => [
                'id' => '1000000000001258',
                'idPrefix' => '11003',
                'presentation' => 'Товари та послуги',
            ],
        ]);
    }

    private function getMetadataResponse(): ResponseData
    {
        return new ResponseData([
            'name' => 'catalogs.units',
            'presentation' => 'Одиниця виміру',
            'listPresentation' => 'Одиниці виміру',
            'idPrefix' => '11036',
            'hierarchyType' => 'none',
            'reqs' => [],
        ]);
    }

    // ── Helpers ──────────────────────────────────────────

    private function createClient(ResponseData $response): DilovodClient
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturn($response);

        return new DilovodClient($this->config, $transport);
    }

    private function createClientCapturing(?Packet &$captured, ResponseData $response): DilovodClient
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')
            ->willReturnCallback(function (Packet $packet) use (&$captured, $response): ResponseData {
                $captured = $packet;

                return $response;
            });

        return new DilovodClient($this->config, $transport);
    }
}
