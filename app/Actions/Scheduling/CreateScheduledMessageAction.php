<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Enums\ScheduleStatus;
use App\Models\ScheduledMessage;
use App\Models\Workspace;
use Illuminate\Support\Carbon;

final class CreateScheduledMessageAction
{
    /**
     * @param  array{
     *     account_id:int, template_id:int, name?:string|null, recipient_type:string,
     *     recipient_payload:array<string,mixed>, run_at:Carbon, timezone:string,
     *     variables_override?:array<string,string>|null
     * }  $data
     */
    public function execute(Workspace $workspace, int $userId, array $data): ScheduledMessage
    {
        return ScheduledMessage::create([
            'workspace_id' => $workspace->id,
            'whatsapp_account_id' => $data['account_id'],
            'template_id' => $data['template_id'],
            'created_by' => $userId,
            'name' => $data['name'] ?? null,
            'recipient_type' => $data['recipient_type'],
            'recipient_payload' => $data['recipient_payload'],
            'variables_override' => $data['variables_override'] ?? null,
            'run_at' => $data['run_at'],
            'timezone' => $data['timezone'],
            'recurrence' => 'none',
            'status' => ScheduleStatus::Pending,
        ]);
    }
}
