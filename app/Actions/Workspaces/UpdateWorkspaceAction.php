<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Models\Workspace;
use Illuminate\Support\Str;

final class UpdateWorkspaceAction
{
    /** @param array<string, mixed> $data */
    public function execute(Workspace $workspace, array $data): Workspace
    {
        $settings = $workspace->settings ?? [];

        if (array_key_exists('business_name', $data)) {
            $settings['business_name'] = $data['business_name'];
        }

        $workspace->update([
            'name'     => $data['name'],
            'timezone' => $data['timezone'],
            'locale'   => $data['locale'] ?? $workspace->locale,
            'settings' => $settings,
        ]);

        return $workspace->fresh() ?? $workspace;
    }
}
