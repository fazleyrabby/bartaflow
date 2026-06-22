<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Template;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function tplWorkspace(): array
{
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    WorkspaceUser::create([
        'workspace_id' => $ws->id,
        'user_id' => $owner->id,
        'role' => Role::Owner->value,
        'status' => 'active',
        'joined_at' => now(),
    ]);

    return [$owner, $ws];
}

// ── List & access ─────────────────────────────────────────────────────────────

it('shows the templates page to a member', function () {
    [$owner, $ws] = tplWorkspace();
    Template::factory()->create(['workspace_id' => $ws->id, 'name' => 'Welcome']);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('templates.index'))
        ->assertOk()
        ->assertSee('Welcome');
});

it('renders the create page', function () {
    [$owner, $ws] = tplWorkspace();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('templates.create'))
        ->assertOk()
        ->assertSee('Live preview');
});

it('renders the edit page', function () {
    [$owner, $ws] = tplWorkspace();
    $template = Template::factory()->create(['workspace_id' => $ws->id, 'name' => 'Greeting']);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('templates.edit', $template))
        ->assertOk()
        ->assertSee('Greeting');
});

// ── Create ────────────────────────────────────────────────────────────────────

it('creates a template and caches its variables', function () {
    [$owner, $ws] = tplWorkspace();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('templates.store'), [
            'name' => 'Order confirm',
            'category' => 'order',
            'body' => 'Hi {{ name }}, order {{order_id}} is ready.',
            'language' => 'en',
            'status' => 'active',
        ])
        ->assertRedirect(route('templates.index'));

    $template = Template::where('workspace_id', $ws->id)->first();
    expect($template)->not->toBeNull()
        ->and($template->variables)->toBe(['name', 'order_id'])
        ->and($template->created_by)->toBe($owner->id);
});

it('enforces name uniqueness per workspace', function () {
    [$owner, $ws] = tplWorkspace();
    Template::factory()->create(['workspace_id' => $ws->id, 'name' => 'Welcome']);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('templates.store'), [
            'name' => 'Welcome',
            'category' => 'general',
            'body' => 'Hello',
        ])
        ->assertSessionHasErrors('name');
});

it('allows the same name in a different workspace', function () {
    [$owner1, $ws1] = tplWorkspace();
    [$owner2, $ws2] = tplWorkspace();
    Template::factory()->create(['workspace_id' => $ws1->id, 'name' => 'Welcome']);

    $this->actingAs($owner2)
        ->withSession(['workspace_id' => $ws2->id])
        ->post(route('templates.store'), [
            'name' => 'Welcome',
            'category' => 'general',
            'body' => 'Hello',
        ])
        ->assertRedirect(route('templates.index'));

    expect(Template::where('workspace_id', $ws2->id)->where('name', 'Welcome')->exists())->toBeTrue();
});

it('rejects a body longer than 4096 characters', function () {
    [$owner, $ws] = tplWorkspace();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('templates.store'), [
            'name' => 'Too long',
            'category' => 'general',
            'body' => str_repeat('a', 4097),
        ])
        ->assertSessionHasErrors('body');
});

// ── Update ────────────────────────────────────────────────────────────────────

it('updates a template and refreshes the variable cache', function () {
    [$owner, $ws] = tplWorkspace();
    $template = Template::factory()->create([
        'workspace_id' => $ws->id,
        'name' => 'Old',
        'body' => 'Hi {{name}}',
    ]);
    expect($template->fresh()->variables)->toBe(['name']);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->put(route('templates.update', $template), [
            'name' => 'New',
            'category' => 'reminder',
            'body' => 'Hi {{name}}, due {{due_date}}',
        ])
        ->assertRedirect(route('templates.index'));

    $fresh = $template->fresh();
    expect($fresh->name)->toBe('New')
        ->and($fresh->variables)->toBe(['name', 'due_date']);
});

// ── Duplicate ─────────────────────────────────────────────────────────────────

it('duplicates a template with a (copy) suffix', function () {
    [$owner, $ws] = tplWorkspace();
    $template = Template::factory()->create(['workspace_id' => $ws->id, 'name' => 'Greeting']);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('templates.duplicate', $template))
        ->assertRedirect();

    expect(Template::where('workspace_id', $ws->id)->where('name', 'Greeting (copy)')->exists())->toBeTrue();
});

// ── Delete ────────────────────────────────────────────────────────────────────

it('soft-deletes a template', function () {
    [$owner, $ws] = tplWorkspace();
    $template = Template::factory()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->delete(route('templates.destroy', $template))
        ->assertRedirect(route('templates.index'));

    expect(Template::find($template->id))->toBeNull()
        ->and(Template::withTrashed()->find($template->id))->not->toBeNull();
});

// ── Tenant isolation ──────────────────────────────────────────────────────────

it('returns 404 when editing another workspace template', function () {
    [$owner1, $ws1] = tplWorkspace();
    [$owner2, $ws2] = tplWorkspace();
    $foreign = Template::factory()->create(['workspace_id' => $ws2->id]);

    $this->actingAs($owner1)
        ->withSession(['workspace_id' => $ws1->id])
        ->get(route('templates.edit', $foreign))
        ->assertNotFound();
});

// ── Live preview endpoint ─────────────────────────────────────────────────────

it('renders a live preview with missing variables reported', function () {
    [$owner, $ws] = tplWorkspace();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->postJson(route('templates.preview'), [
            'body' => 'Hi {{name}}, ref {{missing}} from {{business_name}}.',
        ])
        ->assertOk()
        ->assertJson(['missing' => ['name', 'missing']])
        ->assertJsonStructure(['text', 'missing', 'variables']);
});
