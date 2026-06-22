<?php

declare(strict_types=1);

namespace App\Actions\Templates;

use App\Models\Template;

final class UpdateTemplateAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Template $template, array $data): Template
    {
        $template->update([
            'name' => $data['name'],
            'category' => $data['category'] ?? $template->category->value,
            'body' => $data['body'],
            'language' => $data['language'] ?? $template->language,
            'status' => $data['status'] ?? $template->status->value,
        ]);

        return $template;
    }
}
