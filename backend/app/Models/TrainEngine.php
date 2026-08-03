<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'train_id',
    'engine_id',
    'position',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class TrainEngine extends Model
{
    use SoftDeletes;

    public function train(): BelongsTo
    {
        return $this->belongsTo(Train::class, 'train_id');
    }

    public function engine(): BelongsTo
    {
        return $this->belongsTo(Engine::class, 'engine_id');
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
