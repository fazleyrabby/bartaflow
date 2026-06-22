<?php

declare(strict_types=1);

namespace App\Actions\Contacts;

use App\Models\Contact;

final class UpdateContactAction
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>|null  $tagIds
     */
    public function execute(Contact $contact, array $data, ?array $tagIds = null): Contact
    {
        $contact->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'custom_fields' => $data['custom_fields'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if ($tagIds !== null) {
            $contact->tags()->sync($tagIds);
        }

        return $contact->fresh();
    }
}
