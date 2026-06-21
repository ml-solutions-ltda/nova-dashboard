<?php

declare(strict_types = 1);

namespace MlSolutions\NovaDashboard\Downloads;

use Closure;
use InvalidArgumentException;

class Download
{
    public function __construct(
        private readonly Closure $resolver,
        private readonly string $label = 'Download',
        private readonly string $filename = 'report',
        private readonly array $formats = [ 'csv', 'excel' ],
    )
    {
    }

    public static function make(
        Closure $resolver,
        string $label = 'Download',
        string $filename = 'report',
        array $formats = [ 'csv', 'excel' ],
    ): self
    {
        return new self($resolver, $label, $filename, $formats);
    }

    public function resolver(): Closure
    {
        return $this->resolver;
    }

    public function meta(): array
    {
        return [
            'enabled' => true,
            'label' => $this->label,
            'filename' => $this->filename,
            'formats' => $this->normalizedFormats(),
        ];
    }

    private function normalizedFormats(): array
    {
        $formats = collect($this->formats)
            ->map(fn (string $format): string => strtolower(trim($format)))
            ->filter(fn (string $format): bool => in_array($format, [ 'csv', 'excel' ], true))
            ->values()
            ->all();

        if ($formats === []) {
            throw new InvalidArgumentException('At least one download format must be provided.');
        }

        return $formats;
    }
}
