<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class WorkspaceScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $current = app(CurrentWorkspace::class);

        if ($current->isSet()) {
            $builder->where($model->qualifyColumn('workspace_id'), $current->id());
        }
    }
}
