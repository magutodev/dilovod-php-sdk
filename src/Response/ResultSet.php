<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Response;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * Результат виконання QueryBuilder::get().
 * Ітерований набір записів.
 *
 * @implements IteratorAggregate<int, array<string, mixed>>
 */
final class ResultSet implements IteratorAggregate, Countable
{
    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string>|null $columns Колонки (тільки для assembleLinks=false)
     */
    public function __construct(
        private readonly array $rows,
        private readonly ?array $columns = null,
    ) {}

    /**
     * @return ArrayIterator<int, array<string, mixed>>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->rows);
    }

    public function count(): int
    {
        return \count($this->rows);
    }

    public function isEmpty(): bool
    {
        return $this->rows === [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        return $this->rows[0] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->rows;
    }

    /**
     * Колонки (тільки для assembleLinks=false).
     *
     * @return list<string>|null
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    /**
     * Отримати значення одного поля з усіх записів.
     *
     * @return list<mixed>
     */
    public function pluck(string $key): array
    {
        $values = [];

        foreach ($this->rows as $row) {
            if (\array_key_exists($key, $row)) {
                $values[] = $row[$key];
            }
        }

        return $values;
    }
}
