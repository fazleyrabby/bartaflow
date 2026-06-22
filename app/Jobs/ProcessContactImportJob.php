<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Workspace;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ProcessContactImportJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $timeout = 300;

    /**
     * @param  array<string, string>|null  $columnMap
     */
    public function __construct(
        public readonly int $workspaceId,
        public readonly string $path,
        public readonly ?array $columnMap = null,
    ) {
        $this->onQueue('imports');
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("import:{$this->workspaceId}"))->expireAfter(600)];
    }

    public function handle(): void
    {
        $workspace = Workspace::findOrFail($this->workspaceId);

        $rows = $this->parseCsv();

        $results = [
            'total' => count($rows),
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            try {
                $mapped = $this->mapColumns($row);
                $phone = (string) PhoneNumber::fromInput($mapped['phone']);

                DB::transaction(function () use ($workspace, $mapped, $phone): void {
                    Contact::withoutGlobalScopes()->updateOrCreate(
                        [
                            'workspace_id' => $workspace->id,
                            'phone' => $phone,
                        ],
                        [
                            'name' => $mapped['name'],
                            'email' => $mapped['email'] ?? null,
                            'notes' => $mapped['notes'] ?? null,
                            'source' => 'import',
                        ],
                    );
                });

                $results['imported']++;
            } catch (InvalidArgumentException $e) {
                $results['errors'][] = 'Row '.($index + 2).": {$e->getMessage()}";
                $results['skipped']++;
            } catch (\Exception $e) {
                Log::warning('Contact import row failed', [
                    'workspace_id' => $this->workspaceId,
                    'row' => $index + 2,
                    'error' => $e->getMessage(),
                ]);
                $results['errors'][] = 'Row '.($index + 2).": {$e->getMessage()}";
                $results['skipped']++;
            }
        }

        Storage::put(
            str_replace('.csv', '-results.json', $this->path),
            json_encode($results, JSON_PRETTY_PRINT),
        );
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseCsv(): array
    {
        $contents = Storage::get($this->path);

        if ($contents === null || $contents === '') {
            return [];
        }

        $lines = explode("\n", trim($contents));
        $header = str_getcsv(array_shift($lines));

        if ($header === ['']) {
            return [];
        }

        $header = array_map('trim', $header);
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $values = str_getcsv($line);
            $row = [];

            foreach ($header as $i => $col) {
                $row[$col] = $values[$i] ?? '';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string|null>
     */
    private function mapColumns(array $row): array
    {
        if ($this->columnMap !== null) {
            $mapped = [];
            foreach ($this->columnMap as $field => $column) {
                $mapped[$field] = $row[$column] ?? null;
            }

            return $mapped;
        }

        return [
            'name' => $row['name'] ?? $row['Name'] ?? $row['NAME'] ?? '',
            'phone' => $row['phone'] ?? $row['Phone'] ?? $row['PHONE'] ?? $row['mobile'] ?? '',
            'email' => $row['email'] ?? $row['Email'] ?? $row['EMAIL'] ?? null,
            'notes' => $row['notes'] ?? $row['Notes'] ?? $row['NOTES'] ?? null,
        ];
    }
}
