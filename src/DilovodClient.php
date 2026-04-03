<?php

declare(strict_types=1);

namespace Maguto\Dilovod;

use DateTimeInterface;
use Maguto\Dilovod\Enum\Action;
use Maguto\Dilovod\Enum\SaveType;
use Maguto\Dilovod\Exception\InvalidArgumentException;
use Maguto\Dilovod\Request\OrderBuilder;
use Maguto\Dilovod\Request\Packet;
use Maguto\Dilovod\Request\QueryBuilder;
use Maguto\Dilovod\Response\ResponseData;
use Maguto\Dilovod\Transport\TransportInterface;

final class DilovodClient
{
    public function __construct(
        private readonly Config $config,
        private readonly TransportInterface $transport,
    ) {}

    /**
     * Отримати об'єкт за ID (документ, запис довідника, запис регістру).
     */
    public function getObject(string $id): ResponseData
    {
        return $this->execute(Action::GetObject, ['id' => $id]);
    }

    /**
     * Зберегти об'єкт (створення або оновлення).
     *
     * При створенні: $header['id'] = 'catalogs.goods' (ім'я метаданих).
     * При оновленні: $header['id'] = '1100300000022632' (числовий ID).
     *
     * @param array<string, mixed> $header Реквізити шапки
     * @param array<string, list<array<string, mixed>>> $tableParts Табличні частини
     * @return string ID збереженого об'єкта
     */
    public function saveObject(
        array $header,
        array $tableParts = [],
        SaveType $saveType = SaveType::Save,
    ): string {
        $params = [
            'saveType' => $saveType->value,
            'header' => $header,
        ];

        if ($tableParts !== []) {
            $params['tableParts'] = $tableParts;
        }

        $response = $this->execute(Action::SaveObject, $params);

        // API відповідь: {"result": "ok", "id": "1100300000001002"}
        $id = $response->get('id');

        if (\is_string($id) || \is_int($id)) {
            return (string) $id;
        }

        return '';
    }

    /**
     * Створити замовлення покупця через спецметод saleOrderCreate.
     */
    public function createOrder(): OrderBuilder
    {
        return new OrderBuilder($this);
    }

    /**
     * Виконати довільний спецметод (action: "call").
     *
     * @param string $method Назва методу (напр. "saleOrderCreate")
     * @param array<string, mixed> $arguments Аргументи методу
     */
    public function call(string $method, array $arguments = []): ResponseData
    {
        return $this->execute(Action::Call, [
            'method' => $method,
            'arguments' => $arguments,
        ]);
    }

    /**
     * Створити QueryBuilder для формування запитів (request).
     *
     * Прямий запит: $client->query('catalogs.goods')->...
     * Без аргументу: $client->query()->balance('goods', $date)->...
     */
    public function query(?string $from = null): QueryBuilder
    {
        return new QueryBuilder($this, $from);
    }

    /**
     * Позначити об'єкт на видалення.
     */
    public function setDelMark(string $id): void
    {
        $this->execute(Action::SetDelMark, [
            'header' => ['id' => $id],
        ]);
    }

    /**
     * Отримати список метаданих усіх об'єктів системи.
     *
     * @return array<array-key, mixed>
     */
    public function listMetadata(string $lang = 'uk'): array
    {
        return $this->execute(Action::ListMetadata, ['lang' => $lang])->toArray();
    }

    /**
     * Отримати детальний опис реквізитів об'єкта метаданих.
     *
     * @return array<array-key, mixed>
     */
    public function getMetadata(
        ?string $objectName = null,
        ?string $objectId = null,
        string $lang = 'uk',
    ): array {
        if ($objectName === null && $objectId === null) {
            throw new InvalidArgumentException(
                'Either objectName or objectId must be provided.',
            );
        }

        $params = ['lang' => $lang];

        if ($objectName !== null) {
            $params['objectName'] = $objectName;
        }

        if ($objectId !== null) {
            $params['objectId'] = $objectId;
        }

        return $this->execute(Action::GetMetadata, $params)->toArray();
    }

    /**
     * Отримати статистику партнерської інтеграції.
     *
     * @return array<array-key, mixed>
     */
    public function getStatistic(
        string $partnerApiKey,
        DateTimeInterface $dateFrom,
        DateTimeInterface $dateTo,
    ): array {
        return $this->execute(Action::GetStatistic, [
            'type' => 'partnersIntegrations',
            'partnerAPIkey' => $partnerApiKey,
            'dateFrom' => $dateFrom->format('Y-m-d H:i:s'),
            'dateTo' => $dateTo->format('Y-m-d H:i:s'),
        ])->toArray();
    }

    /**
     * @internal Створити та відправити Packet. Використовується білдерами.
     *
     * @param array<string, mixed> $params
     */
    public function execute(Action $action, array $params): ResponseData
    {
        $packet = new Packet(
            version: $this->config->version,
            key: $this->config->apiKey,
            action: $action,
            params: $params,
            clientId: $this->config->clientId,
        );

        return $this->transport->send($packet);
    }
}
