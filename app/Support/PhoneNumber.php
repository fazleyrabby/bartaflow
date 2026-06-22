<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Lightweight E.164 phone value object with a Bangladesh-first default.
 *
 * Foundation-level normalisation only (no libphonenumber dependency yet).
 * Handles the common Bangladeshi input shapes:
 *   "01712345678"      -> +8801712345678
 *   "8801712345678"    -> +8801712345678
 *   "+8801712345678"   -> +8801712345678
 *   "01712-345678"     -> +8801712345678  (separators stripped)
 *
 * See docs/architecture.md §3 (Support) and docs/tasks/001-foundation.md.
 */
final readonly class PhoneNumber
{
    /** Default country dialing code when no "+" prefix is supplied. */
    private const DEFAULT_DIAL_CODE = '880';

    /** Local trunk prefix stripped for the default country (e.g. leading 0). */
    private const DEFAULT_TRUNK_PREFIX = '0';

    private function __construct(public string $e164) {}

    /**
     * Build from raw user input, throwing on invalid numbers.
     *
     * @param  string|null  $defaultDialCode  Override the default country dial code.
     */
    public static function fromInput(string $raw, ?string $defaultDialCode = null): self
    {
        $value = self::normalize($raw, $defaultDialCode);

        if ($value === null) {
            throw new InvalidArgumentException("Invalid phone number: {$raw}");
        }

        return new self($value);
    }

    /**
     * Attempt normalisation; returns null when the input cannot be parsed.
     */
    public static function tryFrom(string $raw, ?string $defaultDialCode = null): ?self
    {
        $value = self::normalize($raw, $defaultDialCode);

        return $value === null ? null : new self($value);
    }

    public static function isValid(string $raw, ?string $defaultDialCode = null): bool
    {
        return self::normalize($raw, $defaultDialCode) !== null;
    }

    /**
     * Core normalisation routine. Returns an E.164 string or null.
     */
    public static function normalize(string $raw, ?string $defaultDialCode = null): ?string
    {
        $dialCode = $defaultDialCode ?? self::DEFAULT_DIAL_CODE;

        $hadPlus = str_starts_with(trim($raw), '+');

        // Strip everything except digits.
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        if (! $hadPlus) {
            // No explicit "+": assume the default country.
            if (str_starts_with($digits, self::DEFAULT_TRUNK_PREFIX)) {
                // Local format e.g. 01712345678 -> drop trunk, prepend dial code.
                $digits = $dialCode.substr($digits, strlen(self::DEFAULT_TRUNK_PREFIX));
            } elseif (! str_starts_with($digits, $dialCode)) {
                // Bare subscriber number -> prepend dial code.
                $digits = $dialCode.$digits;
            }
        }

        // E.164: 8–15 digits total.
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return '+'.$digits;
    }

    public function equals(self $other): bool
    {
        return $this->e164 === $other->e164;
    }

    public function __toString(): string
    {
        return $this->e164;
    }
}
