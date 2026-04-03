<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Request;

use DateTimeInterface;
use Maguto\Dilovod\DilovodClient;
use Maguto\Dilovod\Enum\Action;

/**
 * Fluent-білдер для створення замовлення покупця (saleOrderCreate).
 */
final class OrderBuilder
{
    /** @var DilovodClient */
    private $client;

    /** @var array<string, mixed> */
    private $header = [];

    /** @var list<array<string, mixed>> */
    private $goods = [];

    /** @var bool */
    private $autoPlacement = false;

    /** @var array<string, mixed>|null */
    private $deliveryData;

    public function __construct(DilovodClient $client)
    {
        $this->client = $client;
    }

    // ── Шапка ────────────────────────────────────────────

    public function firm(string $id): self
    {
        return $this->headerField('firm', $id);
    }

    public function person(string $id): self
    {
        return $this->headerField('person', $id);
    }

    public function storage(string $id): self
    {
        return $this->headerField('storage', $id);
    }

    public function currency(string $id): self
    {
        return $this->headerField('currency', $id);
    }

    public function state(string $id): self
    {
        return $this->headerField('state', $id);
    }

    public function manager(string $id): self
    {
        return $this->headerField('manager', $id);
    }

    public function priceType(string $id): self
    {
        return $this->headerField('priceType', $id);
    }

    public function paymentForm(string $id): self
    {
        return $this->headerField('paymentForm', $id);
    }

    public function deliveryMethod(string $id): self
    {
        return $this->headerField('deliveryMethod', $id);
    }

    public function remark(string $text): self
    {
        return $this->headerField('remark', $text);
    }

    public function remarkFromPerson(string $text): self
    {
        return $this->headerField('remarkFromPerson', $text);
    }

    public function remarkForPerson(string $text): self
    {
        return $this->headerField('remarkForPerson', $text);
    }

    /**
     * @param DateTimeInterface|string $date
     */
    public function date($date): self
    {
        return $this->headerField('date', $this->formatDate($date));
    }

    public function number(string $number): self
    {
        return $this->headerField('number', $number);
    }

    public function contract(string $id): self
    {
        return $this->headerField('contract', $id);
    }

    public function contact(string $id): self
    {
        return $this->headerField('contact', $id);
    }

    public function discountPercent(float $percent): self
    {
        return $this->headerField('discountPercent', $percent);
    }

    /**
     * @param DateTimeInterface|string $date
     */
    public function supplyDate($date): self
    {
        return $this->headerField('supplyDate', $this->formatDate($date));
    }

    /**
     * Встановити будь-який реквізит шапки напряму.
     *
     * @param mixed $value
     */
    public function headerField(string $key, $value): self
    {
        $this->header[$key] = $value;

        return $this;
    }

    // ── Товари ───────────────────────────────────────────

    /**
     * Додати товар за ID.
     */
    public function addProduct(
        string $goodId,
        float $qty,
        ?float $price = null,
        ?string $unit = null,
        ?float $discount = null,
        ?string $remark = null
    ): self {
        $item = ['good' => $goodId, 'qty' => $qty];

        if ($price !== null) {
            $item['price'] = $price;
        }

        if ($unit !== null) {
            $item['unit'] = $unit;
        }

        if ($discount !== null) {
            $item['discount'] = $discount;
        }

        if ($remark !== null) {
            $item['remark'] = $remark;
        }

        $this->goods[] = $item;

        return $this;
    }

    /**
     * Додати товар за артикулом.
     */
    public function addProductByArticle(
        string $articleNumber,
        float $qty,
        ?float $price = null,
        ?string $unit = null
    ): self {
        $item = ['productNum' => $articleNumber, 'qty' => $qty];

        if ($price !== null) {
            $item['price'] = $price;
        }

        if ($unit !== null) {
            $item['unit'] = $unit;
        }

        $this->goods[] = $item;

        return $this;
    }

    // ── Розміщення та доставка ────────────────────────────

    /**
     * Увімкнути автоматичне розміщення замовлення.
     */
    public function withAutoPlacement(): self
    {
        $this->autoPlacement = true;

        return $this;
    }

    /**
     * Додати дані для створення / прив'язки ТТН Нової Пошти.
     *
     * @param array<string, mixed> $deliveryData
     */
    public function withNovaPoshtaTtn(array $deliveryData): self
    {
        $this->deliveryData = $deliveryData;

        return $this;
    }

    // ── Виконання ────────────────────────────────────────

    /**
     * Створити замовлення, повернути його ID.
     *
     * API відповідь: {"status": "success", "data": {"header": {"id": {"id": "...", "pr": "..."}, ...}}}
     */
    public function send(): string
    {
        $response = $this->client->execute(Action::call(), $this->toParams());

        // Реальний формат: data.header.id.id
        $data = $response->get('data');

        if (is_array($data)) {
            $header = $data['header'] ?? null;

            if (is_array($header)) {
                $idObj = $header['id'] ?? null;

                if (is_array($idObj) && isset($idObj['id']) && is_string($idObj['id'])) {
                    return $idObj['id'];
                }
            }
        }

        return '';
    }

    /**
     * Зібрати params без виконання.
     *
     * @return array<string, mixed>
     */
    public function toParams(): array
    {
        $arguments = [];

        if ($this->header !== []) {
            $arguments['header'] = $this->header;
        }

        if ($this->goods !== []) {
            $arguments['goods'] = $this->goods;
        }

        if ($this->autoPlacement) {
            $arguments['placement'] = ['autoPlacement' => true];
        }

        if ($this->deliveryData !== null) {
            $arguments['deliveryData'] = $this->deliveryData;
        }

        return [
            'method' => 'saleOrderCreate',
            'arguments' => $arguments,
        ];
    }

    /**
     * @param DateTimeInterface|string $date
     */
    private function formatDate($date): string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d H:i:s');
        }

        return $date;
    }
}
