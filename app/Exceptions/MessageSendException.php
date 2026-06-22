<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a send is blocked before any message is queued
 * (disconnected account, suspended workspace, unverified user, no recipients).
 */
class MessageSendException extends RuntimeException
{
    public static function accountNotConnected(): self
    {
        return new self('The selected WhatsApp account is not connected.');
    }

    public static function workspaceSuspended(): self
    {
        return new self('This workspace is suspended and cannot send messages.');
    }

    public static function userUnverified(): self
    {
        return new self('Please verify your email address before sending messages.');
    }

    public static function noRecipients(): self
    {
        return new self('No valid recipients to send to. Opted-out contacts are skipped.');
    }
}
