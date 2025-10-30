<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    use HasFactory;

    protected $table = "persons";

    protected $fillable = [
        'user_id',
        'name',
        'age',
        'pictures',
        'city',
        'latitude',
        'longitude',
        'bio',
        'popular_notified_at',
    ];

    protected $casts = [
        'pictures' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'popular_notified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receivedLikes(): HasMany
    {
        return $this->hasMany(Swipe::class, 'target_person_id')->where('action', 'like');
    }
}