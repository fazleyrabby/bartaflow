<?php

declare(strict_types=1);

namespace App\Services\Messaging;

final class SendTemplatedMessageData
{
    /**
     * @param  'selected'|'tag'|'all'  $recipientMode
     * @param  list<int>  $contactIds
     * @param  array<string, scalar|null>  $overrides
     */
    public function __construct(
        public readonly int $accountId,
        public readonly int $templateId,
        public readonly string $recipientMode,
        public readonly array $contactIds = [],
        public readonly ?int $tagId = null,
        public readonly array $overrides = [],
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            accountId: (int) $input['account_id'],
            templateId: (int) $input['template_id'],
            recipientMode: $input['recipient_mode'],
            contactIds: array_map('intval', $input['contact_ids'] ?? []),
            tagId: isset($input['tag_id']) ? (int) $input['tag_id'] : null,
            overrides: $input['overrides'] ?? [],
        );
    }
}
