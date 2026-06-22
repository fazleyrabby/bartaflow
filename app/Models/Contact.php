<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactSource;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property array|null $custom_fields
 * @property string|null $notes
 * @property bool $is_opted_out
 * @property Carbon|null $opted_out_at
 * @property ContactSource $source
 * @property Carbon|null $deleted_at
 */
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    protected $table = 'contacts';

    protected $fillable = [
        'workspace_id',
        'name',
        'phone',
        'email',
        'custom_fields',
        'notes',
        'is_opted_out',
        'opted_out_at',
        'source',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'is_opted_out' => 'boolean',
            'opted_out_at' => 'datetime',
            'source' => ContactSource::class,
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsToMany<ContactTag, $this, Pivot> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ContactTag::class, 'contact_tag')
            ->withTimestamps();
    }
}
