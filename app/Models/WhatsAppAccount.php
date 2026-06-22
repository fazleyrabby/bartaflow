<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\WhatsAppAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                             $id
 * @property int                             $workspace_id
 * @property string                          $label
 * @property string                          $provider
 * @property string                          $phone_number
 * @property string|null                     $phone_number_id
 * @property string|null                     $business_account_id
 * @property string                          $access_token
 * @property string|null                     $webhook_verify_token
 * @property \App\Enums\AccountStatus        $status
 * @property string|null                     $status_reason
 * @property bool                            $is_default
 * @property \Illuminate\Support\Carbon|null $last_checked_at
 */
class WhatsAppAccount extends Model
{
    /** @use HasFactory<WhatsAppAccountFactory> */
    use HasFactory, BelongsToWorkspace;

    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'workspace_id',
        'label',
        'provider',
        'phone_number',
        'phone_number_id',
        'business_account_id',
        'access_token',
        'webhook_verify_token',
        'status',
        'status_reason',
        'is_default',
        'last_checked_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'access_token'   => 'encrypted',
            'status'         => AccountStatus::class,
            'is_default'     => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function maskedToken(): string
    {
        return '••••••••••••' . substr((string) $this->getRawOriginal('access_token'), -4);
    }

    public function isConnected(): bool
    {
        /** @var AccountStatus $status */
        $status = $this->status;

        return $status->isConnected();
    }
}
