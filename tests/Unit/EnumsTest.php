<?php

declare(strict_types=1);

use App\Enums\AccountStatus;
use App\Enums\MessageStatus;
use App\Enums\Role;
use App\Enums\ScheduleStatus;

it('exposes the documented roles and rank ordering', function () {
    expect(Role::Owner->value)->toBe('owner')
        ->and(Role::assignable())->toBe([Role::Admin, Role::Staff])
        ->and(Role::Owner->isAtLeast(Role::Admin))->toBeTrue()
        ->and(Role::Staff->isAtLeast(Role::Admin))->toBeFalse();
});

it('marks only failed messages as retryable and terminal states correctly', function () {
    expect(MessageStatus::Failed->canRetry())->toBeTrue()
        ->and(MessageStatus::Sent->canRetry())->toBeFalse()
        ->and(MessageStatus::Delivered->isTerminal())->toBeTrue()
        ->and(MessageStatus::Queued->isTerminal())->toBeFalse();
});

it('only allows sending from connected accounts', function () {
    expect(AccountStatus::Connected->canSend())->toBeTrue()
        ->and(AccountStatus::Pending->canSend())->toBeFalse()
        ->and(AccountStatus::Error->canSend())->toBeFalse();
});

it('treats pending and processing schedules as open', function () {
    expect(ScheduleStatus::Pending->isOpen())->toBeTrue()
        ->and(ScheduleStatus::Sent->isOpen())->toBeFalse();
});

it('maps every status to a colour token', function () {
    foreach (MessageStatus::cases() as $status) {
        expect($status->color())->toBeString()->not->toBeEmpty();
    }
    foreach (AccountStatus::cases() as $status) {
        expect($status->color())->toBeString()->not->toBeEmpty();
    }
});
