<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WhatsAppAccount;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WhatsAppAccountErrorNotification extends Notification
{
    public function __construct(
        private readonly WhatsAppAccount $account,
        private readonly string $reason,
    ) {}

    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("WhatsApp Account Error — {$this->account->label}")
            ->greeting('Action Required')
            ->line("Your WhatsApp account **{$this->account->label}** ({$this->account->phone_number}) has encountered an error.")
            ->line("**Reason:** {$this->reason}")
            ->action('Review Account', route('settings.whatsapp.edit', $this->account))
            ->line('Please reconnect the account to resume sending messages.');
    }
}
