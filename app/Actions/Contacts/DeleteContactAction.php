<?php

declare(strict_types=1);

namespace App\Actions\Contacts;

use App\Models\Contact;

final class DeleteContactAction
{
    public function execute(Contact $contact): void
    {
        $contact->delete();
    }
}
