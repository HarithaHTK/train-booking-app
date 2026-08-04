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
 *     schema="ScheduleStation",
 *     type="object",
 *     required={"id", "schedule_id", "station_id", "sequence"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="schedule_id", type="integer", example=1),
 *     @OA\Property(property="station_id", type="integer", example=2),
 *     @OA\Property(property="sequence", type="integer", example=1),
 *     @OA\Property(property="arrival_time", type="string", format="time", nullable=true, example="20:30:00"),
 *     @OA\Property(property="departure_time", type="string", format="time", nullable=true, example="20:35:00"),
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
    'schedule_id',
    'station_id',
    'sequence',
    'arrival_time',
    'departure_time',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class ScheduleStation extends Model
{
    /** @use HasFactory<\Database\Factories\ScheduleStationFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'arrival_time' => 'string',
            'departure_time' => 'string',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
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
