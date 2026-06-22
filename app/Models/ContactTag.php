<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\ContactTagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $color
 */
class ContactTag extends Model
{
    /** @use HasFactory<ContactTagFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $table = 'contact_tags';

    protected $fillable = [
        'workspace_id',
        'name',
        'color',
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsToMany<Contact, $this, Pivot> */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_tag')
            ->withTimestamps();
    }
}
