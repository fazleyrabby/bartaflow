<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Models\ScheduledMessage;
use Illuminate\Support\Carbon;

final class UpdateScheduledMessageAction
{
    /**
     * @param  array{
     *     account_id:int, template_id:int, name?:string|null, recipient_type:string,
     *     recipient_payload:array<string,mixed>, run_at:Carbon, timezone:string,
     *     variables_override?:array<string,string>|null
     * }  $data
     */
    public function execute(ScheduledMessage $schedule, array $data): ScheduledMessage
    {
        $schedule->update([
            'whatsapp_account_id' => $data['account_id'],
            'template_id' => $data['template_id'],
            'name' => $data['name'] ?? null,
            'recipient_type' => $data['recipient_type'],
            'recipient_payload' => $data['recipient_payload'],
            'variables_override' => $data['variables_override'] ?? null,
            'run_at' => $data['run_at'],
            'timezone' => $data['timezone'],
        ]);

        return $schedule;
    }
}
