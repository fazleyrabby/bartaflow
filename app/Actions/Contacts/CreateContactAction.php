<?php

declare(strict_types=1);

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Models\Workspace;

final class CreateContactAction
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>|null  $tagIds
     */
    public function execute(Workspace $workspace, array $data, ?array $tagIds = null): Contact
    {
        $contact = Contact::create([
            'workspace_id' => $workspace->id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'custom_fields' => $data['custom_fields'] ?? null,
            'notes' => $data['notes'] ?? null,
            'source' => 'manual',
        ]);

        if ($tagIds !== null) {
            $contact->tags()->sync($tagIds);
        }

        return $contact;
    }
}
