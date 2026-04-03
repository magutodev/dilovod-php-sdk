<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Tests\Unit;

use Maguto\Dilovod\Response\ResponseData;
use PHPUnit\Framework\TestCase;

final class ResponseDataTest extends TestCase
{
    public function testToArrayReturnsRawData(): void
    {
        $raw = ['result' => 'ok', 'id' => '1103600000001001'];
        $response = new ResponseData($raw);

        $this->assertSame($raw, $response->toArray());
    }

    public function testIsErrorReturnsTrueWhenErrorPresent(): void
    {
        $response = new ResponseData([
            'error' => 'bad key',
        ]);

        $this->assertTrue($response->isError());
    }

    public function testIsErrorReturnsTrueWithClientMessages(): void
    {
        $response = new ResponseData([
            'error' => 'Access to catalogs.goods denied, check role settings',
            'clientMessages' => [],
        ]);

        $this->assertTrue($response->isError());
        $this->assertSame('Access to catalogs.goods denied, check role settings', $response->getError());
    }

    public function testIsErrorReturnsFalseForSuccessfulResponse(): void
    {
        $response = new ResponseData([
            'header' => ['id' => ['id' => '1103600000001001', 'pr' => 'sdkt']],
            'misc' => false,
        ]);

        $this->assertFalse($response->isError());
    }

    public function testGetErrorReturnsNullWhenNoError(): void
    {
        $response = new ResponseData(['result' => 'ok', 'id' => '123']);

        $this->assertNull($response->getError());
    }

    public function testGetReturnsValueByKey(): void
    {
        $response = new ResponseData(['result' => 'ok', 'id' => '1103600000001001']);

        $this->assertSame('ok', $response->get('result'));
        $this->assertSame('1103600000001001', $response->get('id'));
    }

    public function testGetReturnsDefaultWhenKeyMissing(): void
    {
        $response = new ResponseData([]);

        $this->assertNull($response->get('missing'));
        $this->assertSame('fallback', $response->get('missing', 'fallback'));
    }

    public function testGetReturnsNestedData(): void
    {
        $response = new ResponseData([
            'header' => ['code' => 'SDKT', 'name' => ['uk' => 'SDK тест']],
        ]);

        $header = $response->get('header');
        $this->assertIsArray($header);
        $this->assertSame('SDKT', $header['code']);
    }

    public function testEmptyResponse(): void
    {
        $response = new ResponseData([]);

        $this->assertSame([], $response->toArray());
        $this->assertFalse($response->isError());
        $this->assertNull($response->getError());
    }

    public function testGetScalarResultReturnsString(): void
    {
        $response = new ResponseData(['_result' => 'ok']);

        $this->assertSame('ok', $response->getScalarResult());
    }

    public function testGetScalarResultReturnsInt(): void
    {
        $response = new ResponseData(['_result' => 1103600000001001]);

        $this->assertSame(1103600000001001, $response->getScalarResult());
    }

    public function testGetScalarResultReturnsNullForObjectResponse(): void
    {
        $response = new ResponseData(['header' => ['id' => '123']]);

        $this->assertNull($response->getScalarResult());
    }

    public function testArrayResponseWithNumericKeys(): void
    {
        $response = new ResponseData([
            ['id' => '1103600000001001', 'code' => 'SDKT'],
            ['id' => '1103600000001002', 'code' => 'TEST'],
        ]);

        $array = $response->toArray();
        $this->assertCount(2, $array);
        $this->assertSame('SDKT', $array[0]['code']);
    }

    public function testCallMethodErrorFormat(): void
    {
        $response = new ResponseData([
            'status' => 'error',
            'data' => [],
            'errorMessage' => 'Bad value type for column person',
        ]);

        $this->assertTrue($response->isError());
        $this->assertSame('Bad value type for column person', $response->getError());
    }

    public function testCallMethodSuccessIsNotError(): void
    {
        $response = new ResponseData([
            'status' => 'success',
            'data' => ['header' => ['id' => ['id' => '1109100000001001']]],
        ]);

        $this->assertFalse($response->isError());
    }
}
