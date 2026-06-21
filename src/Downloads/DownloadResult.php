<?php

declare(strict_types = 1);

namespace MlSolutions\NovaDashboard\Downloads;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class DownloadResult
{
    public function __construct(
        private readonly array $columns,
        private readonly array $rows,
        private readonly ?string $filename = null,
    )
    {
    }

    public static function make(array $columns, iterable $rows, ?string $filename = null): self
    {
        return new self($columns, static::normalizeRows($rows), $filename);
    }

    public static function fromData(mixed $data, ?string $filename = null): self
    {
        if ($data instanceof self) {
            return $data;
        }

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        if ($data instanceof Collection) {
            $data = $data->all();
        }

        if ($data instanceof EloquentBuilder || $data instanceof QueryBuilder) {
            $data = $data->get()->map(function (mixed $row): mixed {
                return $row instanceof Arrayable ? $row->toArray() : $row;
            })->all();
        }

        if (is_array($data) && array_key_exists('rows', $data)) {
            return new self(
                $data['columns'] ?? static::inferColumns(static::normalizeRows($data['rows'])),
                static::normalizeRows($data['rows']),
                $data['filename'] ?? $filename,
            );
        }

        if (!is_iterable($data)) {
            throw new InvalidArgumentException('Download callback must return iterable data or a DownloadResult instance.');
        }

        $rows = static::normalizeRows($data);
        $columns = static::inferColumns($rows);

        return new self($columns, $rows, $filename);
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function rows(): array
    {
        return $this->rows;
    }

    public function filename(): ?string
    {
        return $this->filename;
    }

    private static function normalizeRows(iterable $rows): array
    {
        return collect($rows)
            ->map(function (mixed $row): array {
                if ($row instanceof Arrayable) {
                    return $row->toArray();
                }

                if (is_object($row)) {
                    return get_object_vars($row);
                }

                return (array) $row;
            })
            ->values()
            ->all();
    }

    private static function inferColumns(array $rows): array
    {
        return collect($rows)
            ->flatMap(fn (array $row): array => array_keys($row))
            ->unique()
            ->values()
            ->all();
    }
}
