<?php

declare(strict_types=1);

namespace App\Actions\Templates;

use App\Models\Template;
use App\Models\Workspace;

final class CreateTemplateAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Workspace $workspace, array $data, ?int $userId = null): Template
    {
        return Template::create([
            'workspace_id' => $workspace->id,
            'name' => $data['name'],
            'category' => $data['category'] ?? 'general',
            'body' => $data['body'],
            'language' => $data['language'] ?? 'en',
            'status' => $data['status'] ?? 'active',
            'created_by' => $userId,
        ]);
    }
}
