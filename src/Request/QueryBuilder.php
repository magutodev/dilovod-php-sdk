<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Request;

use DateTimeInterface;
use Maguto\Dilovod\DilovodClient;
use Maguto\Dilovod\Enum\Action;
use Maguto\Dilovod\Enum\Operator;
use Maguto\Dilovod\Enum\QueryType;
use Maguto\Dilovod\Response\ResultSet;

final class QueryBuilder
{
    /** @var array<string, mixed>|string|null */
    private string|array|null $from;

    /** @var array<string, string> */
    private array $fields = [];

    /** @var list<array{alias: string, operator: string, value: mixed}> */
    private array $filters = [];

    /** @var list<string> */
    private array $dimensions = [];

    private ?int $limitCount = null;

    private ?int $limitOffset = null;

    private bool $assembleLinks = true;

    private bool $multilang = false;

    public function __construct(
        private readonly DilovodClient $client,
        ?string $from = null,
    ) {
        $this->from = $from;
    }

    // ── Тип виборки ─────────────────────────────────────

    /**
     * Прямий запит до довідника/документа/табличної частини.
     */
    public function from(string $objectName): self
    {
        $this->from = $objectName;

        return $this;
    }

    /**
     * Залишки на дату.
     *
     * @param string[] $dimensions
     */
    public function balance(
        string $register,
        DateTimeInterface|string $date,
        array $dimensions = [],
    ): self {
        $source = [
            'type' => QueryType::Balance->value,
            'register' => $register,
            'date' => $this->formatDate($date),
        ];

        if ($dimensions !== []) {
            $source['dimensions'] = $dimensions;
        }

        $this->from = $source;

        return $this;
    }

    /**
     * Обороти за період.
     *
     * @param string[] $dimensions
     */
    public function turnover(
        string $register,
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate,
        array $dimensions = [],
    ): self {
        $source = [
            'type' => QueryType::Turnover->value,
            'register' => $register,
            'startDate' => $this->formatDate($startDate),
            'endDate' => $this->formatDate($endDate),
        ];

        if ($dimensions !== []) {
            $source['dimensions'] = $dimensions;
        }

        $this->from = $source;

        return $this;
    }

    /**
     * Залишки + обороти за період.
     *
     * @param string[] $dimensions
     */
    public function balanceAndTurnover(
        string $register,
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate,
        array $dimensions = [],
    ): self {
        $source = [
            'type' => QueryType::BalanceAndTurnover->value,
            'register' => $register,
            'startDate' => $this->formatDate($startDate),
            'endDate' => $this->formatDate($endDate),
        ];

        if ($dimensions !== []) {
            $source['dimensions'] = $dimensions;
        }

        $this->from = $source;

        return $this;
    }

    /**
     * Зріз актуальних значень інформаційного регістру.
     */
    public function sliceLast(
        string $register,
        DateTimeInterface|string $date,
    ): self {
        $this->from = [
            'type' => QueryType::SliceLast->value,
            'register' => $register,
            'date' => $this->formatDate($date),
        ];

        return $this;
    }

    // ── Конфігурація запиту ──────────────────────────────

    /**
     * Поля для вибірки.
     *
     * @param array<string, string> $fields ['dataPath' => 'alias', ...]
     */
    public function fields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Додати одне поле.
     */
    public function field(string $dataPath, string $alias): self
    {
        $this->fields[$dataPath] = $alias;

        return $this;
    }

    /**
     * Додати умову фільтрації.
     */
    public function where(string $alias, Operator $operator, mixed $value): self
    {
        $this->filters[] = [
            'alias' => $alias,
            'operator' => $operator->value,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Виміри для згортання (аналог GROUP BY).
     *
     * @param string[] $dimensions
     */
    public function dimensions(array $dimensions): self
    {
        $this->dimensions = array_values($dimensions);

        return $this;
    }

    /**
     * Ліміт записів.
     */
    public function limit(int $count, ?int $offset = null): self
    {
        $this->limitCount = $count;
        $this->limitOffset = $offset;

        return $this;
    }

    /**
     * Вимкнути збірку посилань (тільки ID, без представлень — швидше).
     */
    public function withoutLinks(): self
    {
        $this->assembleLinks = false;

        return $this;
    }

    /**
     * Увімкнути мультимовні текстові поля.
     */
    public function multilang(): self
    {
        $this->multilang = true;

        return $this;
    }

    // ── Виконання ────────────────────────────────────────

    /**
     * Виконати запит і повернути ResultSet.
     */
    public function get(): ResultSet
    {
        $response = $this->client->execute(Action::Request, $this->toParams());
        $data = $response->toArray();

        if (!$this->assembleLinks && isset($data['columns'], $data['data'])) {
            /** @var list<string> $columns */
            $columns = $data['columns'];

            /** @var list<list<mixed>> $rawRows */
            $rawRows = $data['data'];

            $rows = [];
            foreach ($rawRows as $rawRow) {
                /** @var array<string, mixed> $row */
                $row = array_combine($columns, $rawRow);
                $rows[] = $row;
            }

            return new ResultSet($rows, $columns);
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = array_values($data);

        return new ResultSet($rows);
    }

    /**
     * Виконати запит і повернути перший запис або null.
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $originalCount = $this->limitCount;
        $originalOffset = $this->limitOffset;
        $this->limitCount = 1;
        $this->limitOffset = null;

        try {
            return $this->get()->first();
        } finally {
            $this->limitCount = $originalCount;
            $this->limitOffset = $originalOffset;
        }
    }

    /**
     * Виконати і повернути кількість записів.
     */
    public function count(): int
    {
        return $this->get()->count();
    }

    /**
     * Зібрати params без виконання (для інспекції / тестування).
     *
     * @return array<string, mixed>
     */
    public function toParams(): array
    {
        $params = [];

        if ($this->from !== null) {
            $params['from'] = $this->from;
        }

        if ($this->fields !== []) {
            $params['fields'] = $this->fields;
        }

        if ($this->filters !== []) {
            $params['filters'] = $this->filters;
        }

        if ($this->dimensions !== []) {
            $params['dimensions'] = $this->dimensions;
        }

        if ($this->limitCount !== null) {
            if ($this->limitOffset !== null) {
                $params['limit'] = [
                    'offset' => $this->limitOffset,
                    'count' => $this->limitCount,
                ];
            } else {
                $params['limit'] = $this->limitCount;
            }
        }

        if (!$this->assembleLinks) {
            $params['assembleLinks'] = false;
        }

        if ($this->multilang) {
            $params['multilang'] = true;
        }

        return $params;
    }

    private function formatDate(DateTimeInterface|string $date): string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d H:i:s');
        }

        return $date;
    }
}
