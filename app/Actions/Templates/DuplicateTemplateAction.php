<?php

declare(strict_types=1);

namespace App\Actions\Templates;

use App\Models\Template;

final class DuplicateTemplateAction
{
    public function execute(Template $template, ?int $userId = null): Template
    {
        return Template::create([
            'workspace_id' => $template->workspace_id,
            'name' => $this->uniqueName($template),
            'category' => $template->category->value,
            'body' => $template->body,
            'language' => $template->language,
            'status' => $template->status->value,
            'created_by' => $userId,
        ]);
    }

    /**
     * Produce "<name> (copy)", incrementing the suffix until it's unique in the workspace.
     */
    private function uniqueName(Template $template): string
    {
        $base = $template->name.' (copy)';
        $candidate = $base;
        $n = 2;

        while (Template::where('workspace_id', $template->workspace_id)
            ->where('name', $candidate)
            ->withTrashed()
            ->exists()
        ) {
            $candidate = $base.' '.$n;
            $n++;
        }

        return mb_substr($candidate, 0, 80);
    }
}
