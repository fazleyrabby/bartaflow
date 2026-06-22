<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Template;
use App\Services\Templates\TemplateRenderer;

class TemplateObserver
{
    public function __construct(private readonly TemplateRenderer $renderer) {}

    /**
     * Cache the parsed variable list whenever the body is created or changed.
     */
    public function saving(Template $template): void
    {
        if ($template->isDirty('body') || $template->variables === null) {
            $template->variables = $this->renderer->parse($template->body);
        }
    }
}
