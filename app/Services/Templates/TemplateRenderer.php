<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Models\Contact;
use App\Models\Workspace;
use Illuminate\Support\Carbon;

/**
 * Parses and renders `{{ variable }}` templates.
 *
 * Resolution order per variable:
 *   1. contact column (name, phone, email)
 *   2. contact.custom_fields[key]  (also reachable via `custom.key`)
 *   3. workspace globals (business_name, workspace_name, today, now)
 *   4. manual override passed in $context
 *   5. missing  → reported, placeholder left blank
 */
final class TemplateRenderer
{
    /**
     * Matches `{{ var }}`, `{{var}}`, `{{ custom.order_id }}` — whitespace tolerant.
     * Token allows letters, digits, underscore and a single dot for namespaced keys.
     */
    private const TOKEN_PATTERN = '/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+)*)\s*\}\}/';

    /**
     * Extract the distinct variable names referenced in a body, in order of first appearance.
     *
     * @return list<string>
     */
    public function parse(string $body): array
    {
        preg_match_all(self::TOKEN_PATTERN, $body, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Render a body against a context, reporting any variables that resolve to nothing.
     *
     * @param  array<string, scalar|null>  $overrides  Manual overrides keyed by variable name.
     */
    public function render(
        string $body,
        ?Contact $contact = null,
        ?Workspace $workspace = null,
        array $overrides = [],
    ): RenderResult {
        $missing = [];

        $text = preg_replace_callback(
            self::TOKEN_PATTERN,
            function (array $m) use ($contact, $workspace, $overrides, &$missing): string {
                /** @var string $name */
                $name = $m[1];
                $value = $this->resolve($name, $contact, $workspace, $overrides);

                if ($value === null || $value === '') {
                    $missing[] = $name;

                    return '';
                }

                return $this->sanitize($value);
            },
            $body,
        ) ?? $body;

        return new RenderResult(
            text: $text,
            missing: array_values(array_unique($missing)),
        );
    }

    /**
     * @param  array<string, scalar|null>  $overrides
     */
    private function resolve(string $name, ?Contact $contact, ?Workspace $workspace, array $overrides): ?string
    {
        // Normalize `custom.key` → `key` for custom_fields lookup.
        $isCustomNamespaced = str_starts_with($name, 'custom.');
        $customKey = $isCustomNamespaced ? substr($name, 7) : $name;

        // 1. contact columns
        if ($contact !== null && ! $isCustomNamespaced) {
            $column = match ($name) {
                'name' => $contact->name,
                'phone' => $contact->phone,
                'email' => $contact->email,
                default => null,
            };

            if ($column !== null && $column !== '') {
                return (string) $column;
            }
        }

        // 2. contact custom_fields
        if ($contact !== null) {
            $custom = $contact->custom_fields ?? [];
            if (isset($custom[$customKey]) && $custom[$customKey] !== '') {
                return (string) $custom[$customKey];
            }
        }

        // 3. workspace globals
        if ($workspace !== null && ! $isCustomNamespaced) {
            $global = $this->globals($workspace)[$name] ?? null;
            if ($global !== null && $global !== '') {
                return $global;
            }
        }

        // 4. manual override
        if (array_key_exists($name, $overrides) && $overrides[$name] !== null && $overrides[$name] !== '') {
            return (string) $overrides[$name];
        }

        return null;
    }

    /**
     * Reserved global variables resolved from the workspace.
     *
     * @return array<string, string>
     */
    private function globals(Workspace $workspace): array
    {
        $now = Carbon::now($workspace->timezone);

        return [
            'business_name' => $workspace->businessName(),
            'workspace_name' => $workspace->name,
            'today' => $now->format('F j, Y'),
            'now' => $now->format('F j, Y g:i A'),
        ];
    }

    /**
     * Strip control characters; keep message text safe and plain.
     */
    private function sanitize(string $value): string
    {
        return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? $value);
    }
}
