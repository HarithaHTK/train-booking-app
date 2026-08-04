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
 *     schema="Reservation",
 *     type="object",
 *     required={"id", "user_id", "schedule_id", "start_station_id", "leave_station_id", "seat_id", "status"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="schedule_id", type="integer", example=1),
 *     @OA\Property(property="start_station_id", type="integer", example=2),
 *     @OA\Property(property="leave_station_id", type="integer", example=5),
 *     @OA\Property(property="seat_id", type="integer", example=10),
 *     @OA\Property(property="travel_date", type="string", format="date", nullable=true, example="2026-08-04"),
 *     @OA\Property(property="status", type="string", example="pending"),
 *     @OA\Property(property="checked_in_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="checked_out_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="created_by", type="integer", nullable=true, example=1),
 *     @OA\Property(property="updated_by", type="integer", nullable=true, example=1),
 *     @OA\Property(property="deleted_by", type="integer", nullable=true, example=null),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 */
#[Fillable([
    'user_id',
    'schedule_id',
    'start_station_id',
    'leave_station_id',
    'seat_id',
    'travel_date',
    'status',
    'checked_in_at',
    'checked_out_at',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'travel_date' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function startStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'start_station_id');
    }

    public function leaveStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'leave_station_id');
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'seat_id');
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
