<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScheduleStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\ScheduledMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int|null $whatsapp_account_id
 * @property int|null $template_id
 * @property int|null $created_by
 * @property string|null $name
 * @property string $recipient_type
 * @property array<string, mixed> $recipient_payload
 * @property array<string, string>|null $variables_override
 * @property Carbon $run_at
 * @property string $timezone
 * @property string $recurrence
 * @property array<string, mixed>|null $recurrence_meta
 * @property Carbon|null $next_run_at
 * @property ScheduleStatus $status
 * @property string|null $last_error
 * @property Carbon|null $processed_at
 * @property Carbon|null $deleted_at
 */
class ScheduledMessage extends Model
{
    /** @use HasFactory<ScheduledMessageFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    protected $table = 'scheduled_messages';

    protected $fillable = [
        'workspace_id',
        'whatsapp_account_id',
        'template_id',
        'created_by',
        'name',
        'recipient_type',
        'recipient_payload',
        'variables_override',
        'run_at',
        'timezone',
        'recurrence',
        'recurrence_meta',
        'next_run_at',
        'status',
        'last_error',
        'processed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'recipient_payload' => 'array',
            'variables_override' => 'array',
            'recurrence_meta' => 'array',
            'run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'processed_at' => 'datetime',
            'status' => ScheduleStatus::class,
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<WhatsAppAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
