<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TemplateCategory;
use App\Enums\TemplateStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property TemplateCategory $category
 * @property string $body
 * @property list<string>|null $variables
 * @property string $language
 * @property string|null $provider_template_name
 * @property TemplateStatus $status
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    protected $table = 'templates';

    protected $fillable = [
        'workspace_id',
        'name',
        'category',
        'body',
        'variables',
        'language',
        'provider_template_name',
        'status',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'category' => TemplateCategory::class,
            'status' => TemplateStatus::class,
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
