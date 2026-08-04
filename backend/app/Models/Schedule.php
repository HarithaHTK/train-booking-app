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
 *     schema="Schedule",
 *     type="object",
 *     required={"id", "train_id", "route_id", "is_active"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="train_id", type="integer", example=1),
 *     @OA\Property(property="route_id", type="integer", example=1),
 *     @OA\Property(property="departure_time", type="string", format="time", nullable=true, example="20:00:00"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_by", type="integer", nullable=true, example=1),
 *     @OA\Property(property="updated_by", type="integer", nullable=true, example=1),
 *     @OA\Property(property="deleted_by", type="integer", nullable=true, example=null),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(
 *         property="station_schedules",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ScheduleStation")
 *     )
 * )
 */
#[Fillable([
    'train_id',
    'route_id',
    'departure_time',
    'is_active',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class Schedule extends Model
{
    /** @use HasFactory<\Database\Factories\ScheduleFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'departure_time' => 'datetime:H:i:s',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function train(): BelongsTo
    {
        return $this->belongsTo(Train::class, 'train_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TrainRoute::class, 'route_id');
    }

    public function stationSchedules(): HasMany
    {
        return $this->hasMany(ScheduleStation::class, 'schedule_id');
    }

    public function orderedStationSchedules(string $direction = 'asc'): HasMany
    {
        return $this->stationSchedules()->orderBy('sequence', $direction);
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
