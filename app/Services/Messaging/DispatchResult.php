<?php

declare(strict_types=1);

namespace App\Services\Messaging;

final class DispatchResult
{
    /**
     * @param  array<string, int>  $skippedReasons  reason => count
     */
    public function __construct(
        public readonly int $queued,
        public readonly int $skipped,
        public readonly array $skippedReasons = [],
    ) {}
}
