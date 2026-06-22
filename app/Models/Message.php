<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int|null $whatsapp_account_id
 * @property int|null $template_id
 * @property int|null $contact_id
 * @property int|null $scheduled_message_id
 * @property string $recipient_phone
 * @property string|null $recipient_name
 * @property string $body
 * @property array<string, string>|null $variables_used
 * @property string $direction
 * @property MessageStatus $status
 * @property string|null $provider_message_id
 * @property string|null $error_code
 * @property string|null $error_message
 * @property int $attempts
 * @property string|null $idempotency_key
 * @property Carbon|null $queued_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $read_at
 * @property Carbon|null $failed_at
 */
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $table = 'messages';

    protected $fillable = [
        'workspace_id',
        'whatsapp_account_id',
        'template_id',
        'contact_id',
        'scheduled_message_id',
        'recipient_phone',
        'recipient_name',
        'body',
        'variables_used',
        'direction',
        'status',
        'provider_message_id',
        'error_code',
        'error_message',
        'attempts',
        'idempotency_key',
        'queued_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'variables_used' => 'array',
            'status' => MessageStatus::class,
            'attempts' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
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

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
