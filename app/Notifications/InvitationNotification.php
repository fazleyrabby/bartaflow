<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification
{
    public function __construct(
        private readonly Invitation $invitation,
        private readonly User $invitedBy,
        private readonly Workspace $workspace,
    ) {}

    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $acceptUrl = route('invitations.show', ['token' => $this->invitation->token]);

        return (new MailMessage)
            ->subject("You've been invited to join {$this->workspace->name} on BartaFlow")
            ->greeting('Hello!')
            ->line("{$this->invitedBy->name} has invited you to join **{$this->workspace->name}** as a **{$this->invitation->role->label()}**.")
            ->action('Accept Invitation', $acceptUrl)
            ->line('This invitation expires in 7 days.')
            ->line('If you did not expect this invitation, you can safely ignore this email.');
    }
}
