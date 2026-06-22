<?php

declare(strict_types=1);

namespace App\Services\Templates;

final class RenderResult
{
    /**
     * @param  list<string>  $missing  Variable names that could not be resolved.
     */
    public function __construct(
        public readonly string $text,
        public readonly array $missing = [],
    ) {}

    public function hasMissing(): bool
    {
        return $this->missing !== [];
    }
}
