<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvitationStatus;
use App\Enums\Role;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $invited_by
 * @property string $email
 * @property Role $role
 * @property string $token
 * @property InvitationStatus $status
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 */
class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $fillable = [
        'workspace_id',
        'invited_by',
        'email',
        'role',
        'token',
        'status',
        'expires_at',
        'accepted_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        /** @var Carbon $expiresAt */
        $expiresAt = $this->expires_at;

        return $expiresAt->isPast();
    }

    public function isPending(): bool
    {
        /** @var InvitationStatus $status */
        $status = $this->status;

        return $status === InvitationStatus::Pending && ! $this->isExpired();
    }
}
