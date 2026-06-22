<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Jobs\ProcessContactImportJob;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function contactWorkspace(): array
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

function contactAddMember(Workspace $ws, User $user, Role $role): void
{
    WorkspaceUser::create([
        'workspace_id' => $ws->id,
        'user_id' => $user->id,
        'role' => $role->value,
        'status' => 'active',
        'joined_at' => now(),
    ]);
}

// ── List / View ──────────────────────────────────────────────────────────────

it('lists contacts for the workspace', function () {
    [$owner, $ws] = contactWorkspace();
    Contact::factory()->count(3)->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('contacts.index'))
        ->assertOk();
});

it('shows empty state when no contacts exist', function () {
    [$owner, $ws] = contactWorkspace();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('contacts.index'))
        ->assertOk()
        ->assertSee('No contacts yet');
});

// ── Create ───────────────────────────────────────────────────────────────────

it('owner can create a contact', function () {
    [$owner, $ws] = contactWorkspace();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('contacts.store'), [
            'name' => 'John Doe',
            'phone' => '01712345678',
            'email' => 'john@example.com',
            'notes' => 'Test contact',
        ])
        ->assertRedirect(route('contacts.index'));

    $contact = Contact::where('workspace_id', $ws->id)->first();
    expect($contact)->not->toBeNull()
        ->and($contact->name)->toBe('John Doe')
        ->and($contact->phone)->toBe('+8801712345678') // normalized
        ->and($contact->source->value)->toBe('manual');
});

it('staff can create a contact', function () {
    [$owner, $ws] = contactWorkspace();
    $staff = User::factory()->create();
    contactAddMember($ws, $staff, Role::Staff);

    $this->actingAs($staff)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('contacts.store'), [
            'name' => 'Staff Contact',
            'phone' => '01799999999',
        ])
        ->assertRedirect(route('contacts.index'));

    expect(Contact::where('workspace_id', $ws->id)->count())->toBe(1);
});

it('rejects duplicate phone per workspace', function () {
    [$owner, $ws] = contactWorkspace();
    Contact::factory()->create(['workspace_id' => $ws->id, 'phone' => '+8801712345678']);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('contacts.store'), [
            'name' => 'Duplicate',
            'phone' => '+8801712345678',
        ])
        ->assertSessionHasErrors('phone');
});

it('allows same phone in different workspace', function () {
    [$owner1, $ws1] = contactWorkspace();
    [$owner2, $ws2] = contactWorkspace();
    Contact::factory()->create(['workspace_id' => $ws1->id, 'phone' => '+8801712345678']);

    $this->actingAs($owner2)
        ->withSession(['workspace_id' => $ws2->id])
        ->post(route('contacts.store'), [
            'name' => 'Same Phone',
            'phone' => '+8801712345678',
        ])
        ->assertRedirect(route('contacts.index'));

    expect(Contact::where('workspace_id', $ws2->id)->count())->toBe(1);
});

// ── Update ───────────────────────────────────────────────────────────────────

it('owner can update a contact', function () {
    [$owner, $ws] = contactWorkspace();
    $contact = Contact::factory()->create(['workspace_id' => $ws->id, 'name' => 'Old Name']);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->patch(route('contacts.update', $contact), [
            'name' => 'New Name',
            'phone' => $contact->phone,
            'email' => 'updated@example.com',
        ])
        ->assertRedirect(route('contacts.index'));

    expect($contact->fresh()->name)->toBe('New Name');
});

// ── Delete ───────────────────────────────────────────────────────────────────

it('owner can delete a contact (soft)', function () {
    [$owner, $ws] = contactWorkspace();
    $contact = Contact::factory()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->delete(route('contacts.destroy', $contact))
        ->assertRedirect(route('contacts.index'));

    expect(Contact::where('workspace_id', $ws->id)->count())->toBe(0);
});

// ── Opt-out ──────────────────────────────────────────────────────────────────

it('can toggle opt-out status', function () {
    [$owner, $ws] = contactWorkspace();
    $contact = Contact::factory()->create(['workspace_id' => $ws->id, 'is_opted_out' => false]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('contacts.toggle-opt-out', $contact))
        ->assertRedirect(route('contacts.index'));

    expect($contact->fresh()->is_opted_out)->toBeTrue();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('contacts.toggle-opt-out', $contact));

    expect($contact->fresh()->is_opted_out)->toBeFalse();
});

// ── Tags ─────────────────────────────────────────────────────────────────────

it('can attach and filter by tags', function () {
    [$owner, $ws] = contactWorkspace();
    $tag = ContactTag::factory()->create(['workspace_id' => $ws->id, 'name' => 'VIP']);
    $tagged = Contact::factory()->create(['workspace_id' => $ws->id]);
    $tagged->tags()->attach($tag);
    Contact::factory()->create(['workspace_id' => $ws->id]);

    $response = $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('contacts.index', ['tag' => $tag->id]));

    $response->assertSee($tagged->name);
});

// ── Search ──────────────────────────────────────────────────────────────────

it('can search contacts by name and phone', function () {
    [$owner, $ws] = contactWorkspace();
    Contact::factory()->create(['workspace_id' => $ws->id, 'name' => 'Alice', 'phone' => '+8801711111111']);
    Contact::factory()->create(['workspace_id' => $ws->id, 'name' => 'Bob', 'phone' => '+8801722222222']);

    $response = $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('contacts.index', ['search' => 'Alice']));

    $response->assertSee('Alice')->assertDontSee('Bob');
});

// ── Tenant isolation ────────────────────────────────────────────────────────

it('cannot access another workspace contact', function () {
    [$owner1, $ws1] = contactWorkspace();
    [$owner2, $ws2] = contactWorkspace();
    $contactInWs2 = Contact::factory()->create(['workspace_id' => $ws2->id]);

    $this->actingAs($owner1)
        ->withSession(['workspace_id' => $ws1->id])
        ->patch(route('contacts.update', $contactInWs2), [
            'name' => 'Hack',
            'phone' => $contactInWs2->phone,
        ])
        ->assertForbidden();
});

// ── CSV Import ───────────────────────────────────────────────────────────────

it('queues a contact import job', function () {
    Queue::fake();

    [$owner, $ws] = contactWorkspace();

    $csv = "name,phone,email\nJohn,+8801712345678,john@test.com\nJane,+8801711111111,jane@test.com";
    $file = UploadedFile::fake()->createWithContent('contacts.csv', $csv);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('contacts.import'), ['file' => $file])
        ->assertRedirect(route('contacts.index'));

    Queue::assertPushed(ProcessContactImportJob::class);
});

// ── Phone normalization ─────────────────────────────────────────────────────

it('normalizes bangladeshi phone numbers to e164', function () {
    [$owner, $ws] = contactWorkspace();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('contacts.store'), [
            'name' => 'Test',
            'phone' => '01712345670',
        ]);

    $contact = Contact::where('workspace_id', $ws->id)->first();
    expect($contact->phone)->toBe('+8801712345670');
});
