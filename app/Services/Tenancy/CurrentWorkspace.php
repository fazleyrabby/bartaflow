<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Workspace;

final class CurrentWorkspace
{
    private ?Workspace $workspace = null;

    public function set(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function get(): Workspace
    {
        if ($this->workspace === null) {
            throw new \RuntimeException('No active workspace has been resolved for this request.');
        }

        return $this->workspace;
    }

    public function id(): int
    {
        return $this->get()->id;
    }

    public function isSet(): bool
    {
        return $this->workspace !== null;
    }

    public function clear(): void
    {
        $this->workspace = null;
    }
}
