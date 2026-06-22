<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\WorkspaceScope;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Database\Eloquent\Model;

trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope(new WorkspaceScope);

        static::creating(static function (Model $model): void {
            if ($model->getAttribute('workspace_id') === null) {
                $current = app(CurrentWorkspace::class);
                if ($current->isSet()) {
                    $model->setAttribute('workspace_id', $current->id());
                }
            }
        });
    }
}
