<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RouteStation",
 *     type="object",
 *     required={"id", "route_id", "station_id", "sequence"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="route_id", type="integer", example=1),
 *     @OA\Property(property="station_id", type="integer", example=2),
 *     @OA\Property(property="sequence", type="integer", example=1),
 *     @OA\Property(property="station", ref="#/components/schemas/Station"),
 *     @OA\Property(property="created_by", type="integer", nullable=true, example=1),
 *     @OA\Property(property="updated_by", type="integer", nullable=true, example=1),
 *     @OA\Property(property="deleted_by", type="integer", nullable=true, example=null),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 */
#[Fillable([
    'route_id',
    'station_id',
    'sequence',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class RouteStation extends Model
{
    /** @use HasFactory<\Database\Factories\RouteStationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TrainRoute::class, 'route_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id');
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