<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Writes append-only audit entries. Resolves the acting user, workspace, IP and
 * user-agent from the current request context unless explicitly provided.
 */
final class AuditLogger
{
    /** Metadata keys whose values must never be persisted in cleartext. */
    private const REDACT_KEYS = ['access_token', 'token', 'password', 'password_confirmation', 'secret', 'api_key'];

    public function __construct(private readonly CurrentWorkspace $current) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $metadata = [],
        ?int $workspaceId = null,
        ?int $userId = null,
    ): ActivityLog {
        $workspaceId ??= $this->current->isSet() ? $this->current->id() : null;
        $userId ??= Auth::id();

        return ActivityLog::create([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata === [] ? null : $this->redact($metadata),
            'ip_address' => Request::ip(),
            'user_agent' => mb_substr((string) Request::userAgent(), 0, 255),
        ]);
    }

    /**
     * Convenience for logging an action by a known actor (e.g. from a queued job
     * or console command where there is no authenticated request user).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function logAs(User $actor, int $workspaceId, string $action, ?Model $subject = null, ?string $description = null, array $metadata = []): ActivityLog
    {
        return $this->log($action, $subject, $description, $metadata, $workspaceId, (int) $actor->id);
    }

    /**
     * Recursively redact sensitive keys.
     *
     * @param  array<array-key, mixed>  $metadata
     * @return array<array-key, mixed>
     */
    private function redact(array $metadata): array
    {
        foreach ($metadata as $key => $value) {
            if (is_string($key) && in_array(mb_strtolower($key), self::REDACT_KEYS, true)) {
                $metadata[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $metadata[$key] = $this->redact($value);
            }
        }

        return $metadata;
    }
}
