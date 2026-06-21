<?php

declare(strict_types = 1);

namespace MlSolutions\NovaDashboard\Downloads;

use Illuminate\Http\Response;
use InvalidArgumentException;

class DownloadWriter
{
    public function toResponse(DownloadResult $result, string $format, string $fallbackFilename): Response
    {
        $format = strtolower($format);
        $filename = $this->sanitizeFilename($result->filename() ?? $fallbackFilename);

        return match ($format) {
            'csv' => response(
                $this->csv($result),
                200,
                $this->headers("{$filename}.csv", 'text/csv; charset=UTF-8'),
            ),
            'excel' => response(
                $this->excel($result),
                200,
                $this->headers("{$filename}.xls", 'application/vnd.ms-excel; charset=UTF-8'),
            ),
            default => throw new InvalidArgumentException("Unsupported download format [{$format}]."),
        };
    }

    private function csv(DownloadResult $result): string
    {
        $stream = fopen('php://temp', 'r+');

        fputcsv($stream, $result->columns());

        foreach ($result->rows() as $row) {
            fputcsv($stream, $this->orderedRow($result, $row));
        }

        rewind($stream);

        return "\xEF\xBB\xBF" . stream_get_contents($stream);
    }

    private function excel(DownloadResult $result): string
    {
        $header = $this->excelRow($result->columns());
        $rows = collect($result->rows())
            ->map(fn (array $row): string => $this->excelRow($this->orderedRow($result, $row)))
            ->implode('');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
 <Worksheet ss:Name="Report">
  <Table>
   {$header}{$rows}
  </Table>
 </Worksheet>
</Workbook>
XML;
    }

    private function excelRow(array $values): string
    {
        $cells = collect($values)
            ->map(function (mixed $value): string {
                $escaped = htmlspecialchars((string) $this->stringify($value), ENT_XML1 | ENT_QUOTES, 'UTF-8');

                return "<Cell><Data ss:Type=\"String\">{$escaped}</Data></Cell>";
            })
            ->implode('');

        return "<Row>{$cells}</Row>";
    }

    private function orderedRow(DownloadResult $result, array $row): array
    {
        return collect($result->columns())
            ->map(fn (string $column): mixed => $row[$column] ?? null)
            ->all();
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return (string) $value;
    }

    private function headers(string $filename, string $contentType): array
    {
        return [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ];
    }

    private function sanitizeFilename(string $filename): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9\-_]+/', '-', trim($filename));

        return trim($sanitized ?: 'report', '-');
    }
}
