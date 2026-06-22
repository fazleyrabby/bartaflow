<?php

declare(strict_types=1);

namespace App\Actions\Contacts;

use App\Models\Contact;

final class ToggleOptOutAction
{
    public function execute(Contact $contact): Contact
    {
        $contact->update([
            'is_opted_out' => ! $contact->is_opted_out,
            'opted_out_at' => $contact->is_opted_out ? null : now(),
        ]);

        return $contact->fresh();
    }
}
