<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Train",
 *     type="object",
 *     required={"id", "train_number", "train_name", "route_id", "is_active"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="train_number", type="string", example="TR-001"),
 *     @OA\Property(property="train_name", type="string", example="Express North"),
 *     @OA\Property(property="route_id", type="integer", example=1),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_by", type="integer", nullable=true, example=1),
 *     @OA\Property(property="updated_by", type="integer", nullable=true, example=1),
 *     @OA\Property(property="deleted_by", type="integer", nullable=true, example=null),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 */
#[Fillable([
    'train_number',
    'train_name',
    'route_id',
    'is_active',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class Train extends Model
{
    /** @use HasFactory<\Database\Factories\TrainFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TrainRoute::class, 'route_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'train_id');
    }

    public function engines(): BelongsToMany
    {
        return $this->belongsToMany(Engine::class, 'train_engines')
            ->withPivot('position')
            ->orderBy('position');
    }

    public function coaches(): BelongsToMany
    {
        return $this->belongsToMany(Coach::class, 'train_coaches')
            ->withPivot('position')
            ->orderBy('position');
    }

    public function trainEngines(): HasMany
    {
        return $this->hasMany(TrainEngine::class, 'train_id');
    }

    public function trainCoaches(): HasMany
    {
        return $this->hasMany(TrainCoach::class, 'train_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
