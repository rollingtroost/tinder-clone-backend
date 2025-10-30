<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Swipe extends Model
{
    use HasFactory;

    protected $table = "swipes";

    protected $fillable = [
        'swiper_user_id',
        'target_person_id',
        'action',
    ];

    public function swiper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'swiper_user_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'target_person_id');
    }
}