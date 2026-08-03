<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'coach_number',
    'coach_type',
    'total_seats',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class Coach extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = ['name', 'seat_count', 'type'];

    protected function casts(): array
    {
        return [
            'total_seats' => 'integer',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function trains(): BelongsToMany
    {
        return $this->belongsToMany(Train::class, 'train_coaches')
            ->withPivot('position')
            ->orderBy('position');
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'coach_id');
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

    public function getNameAttribute(): string
    {
        return (string) ($this->coach_number ?? '');
    }

    public function getSeatCountAttribute(): ?int
    {
        return $this->total_seats !== null ? (int) $this->total_seats : null;
    }

    public function getTypeAttribute(): ?string
    {
        return $this->coach_type ?? null;
    }
}
