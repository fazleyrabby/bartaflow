<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Templates\TemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

// ── Parser ───────────────────────────────────────────────────────────────────

it('extracts variables including spaced tokens and dedupes', function () {
    $vars = $this->renderer->parse('Hi {{name}}, order {{ order_id }} for {{name}}. {{custom.ref}}');

    expect($vars)->toBe(['name', 'order_id', 'custom.ref']);
});

it('returns an empty list when there are no variables', function () {
    expect($this->renderer->parse('Plain text, no tokens.'))->toBe([]);
});

it('ignores malformed tokens', function () {
    expect($this->renderer->parse('{{ 123bad }} {{ }} {{good_one}}'))->toBe(['good_one']);
});

// ── Renderer ─────────────────────────────────────────────────────────────────

it('fills from contact columns, custom fields, and globals', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id, 'name' => 'Acme Co']);
    $contact = Contact::factory()->create([
        'workspace_id' => $ws->id,
        'name' => 'Ada',
        'custom_fields' => ['order_id' => 'A-100'],
    ]);

    $result = $this->renderer->render(
        'Hi {{name}}, order {{order_id}} from {{business_name}}.',
        $contact,
        $ws,
    );

    expect($result->text)->toBe('Hi Ada, order A-100 from Acme Co.')
        ->and($result->missing)->toBe([]);
});

it('reports missing variables and leaves them blank', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $contact = Contact::factory()->create(['workspace_id' => $ws->id, 'name' => 'Bo', 'custom_fields' => null]);

    $result = $this->renderer->render('Hi {{name}}, ref {{missing_ref}}.', $contact, $ws);

    expect($result->missing)->toBe(['missing_ref'])
        ->and($result->hasMissing())->toBeTrue()
        ->and($result->text)->toBe('Hi Bo, ref .');
});

it('resolves custom.key namespaced access from custom_fields', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $contact = Contact::factory()->create([
        'workspace_id' => $ws->id,
        'custom_fields' => ['ref' => 'XYZ'],
    ]);

    $result = $this->renderer->render('Ref: {{custom.ref}}', $contact, $ws);

    expect($result->text)->toBe('Ref: XYZ');
});

it('resolves business_name and workspace_name globals', function () {
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id, 'name' => 'Globex']);

    $result = $this->renderer->render('{{workspace_name}} / {{business_name}}', null, $ws);

    expect($result->text)->toContain('Globex');
});
