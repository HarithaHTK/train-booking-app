<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="TrainRoute",
 *     type="object",
 *     required={"id", "name", "is_active"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Main Line"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Primary railway route line"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_by", type="integer", nullable=true, example=1),
 *     @OA\Property(property="updated_by", type="integer", nullable=true, example=1),
 *     @OA\Property(property="deleted_by", type="integer", nullable=true, example=null),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(
 *         property="stations",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/RouteStation")
 *     )
 * )
 */
#[Fillable([
    'name',
    'description',
    'is_active',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class TrainRoute extends Model
{
    /** @use HasFactory<\Database\Factories\TrainRouteFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'routes';

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

    public function routeStations(): HasMany
    {
        return $this->hasMany(RouteStation::class, 'route_id');
    }

    public function orderedRouteStations(string $direction = 'asc'): HasMany
    {
        return $this->routeStations()->orderBy('sequence', $direction);
    }
}