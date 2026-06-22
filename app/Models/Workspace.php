<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkspaceStatus;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int                                 $id
 * @property int                                 $owner_id
 * @property string                              $name
 * @property string                              $slug
 * @property string                              $timezone
 * @property string                              $locale
 * @property \App\Enums\WorkspaceStatus          $status
 * @property array<string, mixed>|null           $settings
 * @property \Illuminate\Support\Carbon|null     $trial_ends_at
 * @property \Illuminate\Support\Carbon|null     $deleted_at
 */
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'timezone',
        'locale',
        'status',
        'settings',
        'trial_ends_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status'        => WorkspaceStatus::class,
            'settings'      => 'array',
            'trial_ends_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsToMany<User, $this, WorkspaceUser> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_users')
            ->using(WorkspaceUser::class)
            ->withPivot(['role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    /** @return HasMany<Invitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isActive(): bool
    {
        /** @var WorkspaceStatus $status */
        $status = $this->status;

        return $status === WorkspaceStatus::Active;
    }

    public function isSuspended(): bool
    {
        /** @var WorkspaceStatus $status */
        $status = $this->status;

        return $status === WorkspaceStatus::Suspended;
    }

    public function businessName(): string
    {
        /** @var array<string, mixed> $settings */
        $settings = $this->settings ?? [];

        return (string) ($settings['business_name'] ?? $this->name);
    }
}
