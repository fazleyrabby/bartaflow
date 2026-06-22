<?php

declare(strict_types=1);

namespace App\Actions\Contacts;

use App\Jobs\ProcessContactImportJob;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;

final class ImportContactsAction
{
    /**
     * @param  array<string, string>|null  $columnMap
     */
    public function execute(Workspace $workspace, UploadedFile $file, ?array $columnMap = null): string
    {
        $path = $file->store('imports');

        ProcessContactImportJob::dispatch($workspace->id, $path, $columnMap);

        return $path;
    }
}
